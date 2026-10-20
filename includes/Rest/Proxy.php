<?php
/**
 * REST routes of the admin app, relayed to Central.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Rest
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Rest;

use CinqWpCentral\Access\Gate;
use CinqWpCentral\Central\Cache;
use CinqWpCentral\Central\Client;
use CinqWpCentral\Contracts\Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * `/wp-json/cinq-wp-central/v1/*`: one WordPress route per Central route.
 *
 * A list, not a pass-through: a generic proxy would forward whatever path the
 * browser asked for, with this site's secret attached. Each route here names
 * the Central path it reaches, and identifiers are checked as UUIDs before
 * they are put into one.
 *
 * The browser authenticates with WordPress (cookie + REST nonce); the PHP
 * authenticates with Central (site secret) and names the person.
 */
final class Proxy implements Service {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'cinq-wp-central/v1';

	/**
	 * UUID pattern for route parameters.
	 */
	private const ID = '(?P<id>[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})';

	/**
	 * Relayed routes: [method, WordPress route, Central path].
	 *
	 * `{id}` in the Central path is replaced by the checked route parameter.
	 *
	 * @return array<int, array{0: string, 1: string, 2: string}>
	 */
	public static function routes(): array {
		$id = self::ID;

		return array(
			array( 'GET', '/moi', '/moi' ),
			array( 'GET', '/accueil', '/accueil' ),
			array( 'GET', '/projets', '/projets' ),
			array( 'GET', "/projets/{$id}", '/projets/{id}' ),
			array( 'GET', '/roadmap', '/roadmap' ),
			array( 'GET', "/roadmap/cartes/{$id}", '/roadmap/cartes/{id}' ),
			array( 'POST', "/roadmap/cartes/{$id}/deplacer", '/roadmap/cartes/{id}/deplacer' ),
			array( 'POST', "/roadmap/cartes/{$id}/commentaires", '/roadmap/cartes/{id}/commentaires' ),
			array( 'GET', '/demandes', '/demandes' ),
			array( 'POST', '/demandes', '/demandes' ),
			array( 'GET', "/demandes/{$id}", '/demandes/{id}' ),
			array( 'POST', "/demandes/{$id}/commentaires", '/demandes/{id}/commentaires' ),
			array( 'POST', "/portes/{$id}/valider", '/portes/{id}/valider' ),
			array( 'GET', '/factures', '/factures' ),
			array( 'GET', '/ressources', '/ressources' ),
		);
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Registers the routes.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( self::routes() as list( $method, $route, $central ) ) {
			register_rest_route(
				self::NAMESPACE,
				$route,
				array(
					'methods'             => $method,
					'permission_callback' => array( self::class, 'permission' ),
					'callback'            => static function ( WP_REST_Request $request ) use ( $method, $central ) {
						return self::relay( $request, $method, $central );
					},
				)
			);
		}

		register_rest_route(
			self::NAMESPACE,
			'/demandes/' . self::ID . '/pieces',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( self::class, 'permission' ),
				'callback'            => array( self::class, 'upload' ),
			)
		);
	}

	/**
	 * Only the people the settings allow.
	 *
	 * @return bool|WP_Error
	 */
	public static function permission() {
		if ( Gate::can() ) {
			return true;
		}

		return new WP_Error( 'cinq_wp_central_interdit', __( "Votre compte n'a pas accès à l'espace CINQ.", 'cinq-wp-central' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Relays one call to Central.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $method  HTTP method.
	 * @param string          $central Central path template.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function relay( WP_REST_Request $request, string $method, string $central ) {
		$path = str_replace( '{id}', strtolower( (string) $request->get_param( 'id' ) ), $central );
		$body = 'GET' === $method ? null : (array) $request->get_json_params();

		$answer = Client::request( $method, $path, $body, wp_get_current_user() );

		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		// A write changes the badge and the dashboard widget.
		if ( 'GET' !== $method ) {
			Cache::flush();
		}

		return rest_ensure_response( $answer );
	}

	/**
	 * Relays a file attached to a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function upload( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		$file  = $files['file'] ?? null;

		if ( ! is_array( $file ) || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			return new WP_Error( 'cinq_wp_central_fichier', __( "Aucun fichier n'a été reçu.", 'cinq-wp-central' ), array( 'status' => 400 ) );
		}

		$answer = Client::upload(
			'/demandes/' . strtolower( (string) $request->get_param( 'id' ) ) . '/pieces',
			(string) $file['tmp_name'],
			sanitize_file_name( (string) $file['name'] ),
			(string) ( $file['type'] ?? '' ),
			wp_get_current_user()
		);

		return is_wp_error( $answer ) ? $answer : rest_ensure_response( $answer );
	}
}
