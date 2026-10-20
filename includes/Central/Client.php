<?php
/**
 * HTTP client for Central's `/api/connect/v1`.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Central
 */

declare( strict_types = 1 );

namespace CinqCentral\Central;

use WP_Error;
use WP_User;
use const CinqCentral\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Talks to Central from this site's PHP, never from the browser.
 *
 * Every call carries two things: the site's secret (who is calling) and the
 * person logged in here (on whose behalf). Central bounds everything to the
 * client this site was paired with; it never takes a client from the request.
 */
final class Client {

	/**
	 * Calls Central.
	 *
	 * @param string                    $method HTTP method.
	 * @param string                    $path   Path under /api/connect/v1, with a leading slash.
	 * @param array<string, mixed>|null $body   JSON body, if any.
	 * @param WP_User|null              $user    Person on whose behalf; null for site-level calls.
	 * @param int                       $timeout Seconds; short for what every admin page shows.
	 * @return array<string, mixed>|WP_Error Decoded answer, or an error carrying an HTTP status.
	 */
	public static function request( string $method, string $path, ?array $body = null, ?WP_User $user = null, int $timeout = 15 ) {
		$connection = Connection::get();

		if ( null === $connection && '/appairer' !== $path ) {
			return new WP_Error( 'cinq_central_deconnecte', __( "Ce site n'est pas encore relié à Central.", 'cinq-central' ), array( 'status' => 409 ) );
		}

		$headers = self::headers( $connection, $user );
		$args    = array(
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => $headers,
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $body );
		}

		return self::send( $path, $args );
	}

	/**
	 * Sends one file to a request, as multipart/form-data.
	 *
	 * @param string  $path      Path under /api/connect/v1.
	 * @param string  $file_path Local path of the uploaded file.
	 * @param string  $file_name Name shown in Central.
	 * @param string  $mime      MIME type.
	 * @param WP_User $user      Person on whose behalf.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function upload( string $path, string $file_path, string $file_name, string $mime, WP_User $user ) {
		$connection = Connection::get();

		if ( null === $connection ) {
			return new WP_Error( 'cinq_central_deconnecte', __( "Ce site n'est pas encore relié à Central.", 'cinq-central' ), array( 'status' => 409 ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local upload, not a remote URL.
		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return new WP_Error( 'cinq_central_fichier', __( "Le fichier n'a pas pu être lu.", 'cinq-central' ), array( 'status' => 400 ) );
		}

		$boundary = 'cinq' . wp_generate_password( 24, false );
		$name     = str_replace( array( '"', "\r", "\n" ), '', $file_name );
		$payload  = '--' . $boundary . "\r\n"
			. 'Content-Disposition: form-data; name="file"; filename="' . $name . "\"\r\n"
			. 'Content-Type: ' . ( '' !== $mime ? $mime : 'application/octet-stream' ) . "\r\n\r\n"
			. $contents . "\r\n"
			. '--' . $boundary . "--\r\n";

		$headers                 = self::headers( $connection, $user );
		$headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;

		return self::send(
			$path,
			array(
				'method'  => 'POST',
				'timeout' => 60,
				'headers' => $headers,
				'body'    => $payload,
			)
		);
	}

	/**
	 * Exchanges a pairing code for the site's secret, and stores it.
	 *
	 * @param string $code Code issued from the client's page in Central.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function pair( string $code ) {
		$answer = self::request(
			'POST',
			'/appairer',
			array(
				'code'       => $code,
				'plateforme' => 'wordpress',
				'domaine'    => home_url(),
				'version'    => 'wp/' . VERSION,
			)
		);

		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		Connection::save( $answer );

		return $answer;
	}

	/**
	 * Tells Central this site leaves, then forgets the secret.
	 *
	 * Central is told first, but its answer does not matter: a site that
	 * cannot reach Central must still be able to disconnect.
	 *
	 * @return void
	 */
	public static function disconnect(): void {
		if ( Connection::is_connected() ) {
			self::request( 'DELETE', '/connexion' );
		}

		Connection::forget();
	}

	/**
	 * The value of the `X-Cinq-Acteur` header for a user.
	 *
	 * @param WP_User $user User.
	 * @return string base64url JSON.
	 */
	public static function actor( WP_User $user ): string {
		$json = (string) wp_json_encode(
			array(
				'email' => $user->user_email,
				'nom'   => '' !== trim( (string) $user->display_name ) ? $user->display_name : $user->user_login,
				'id'    => $user->ID,
			)
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- an HTTP header cannot carry accents.
		return rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	}

	/**
	 * Common headers.
	 *
	 * @param array<string, mixed>|null $connection Stored connection.
	 * @param WP_User|null              $user       Person on whose behalf.
	 * @return array<string, string>
	 */
	private static function headers( ?array $connection, ?WP_User $user ): array {
		$headers = array(
			'Accept'         => 'application/json',
			'X-Cinq-Version' => 'wp/' . VERSION,
		);

		if ( null !== $connection ) {
			$headers['Authorization'] = 'Bearer ' . $connection['secret'];
		}

		if ( null !== $user && $user->exists() ) {
			$headers['X-Cinq-Acteur'] = self::actor( $user );
		}

		return $headers;
	}

	/**
	 * Sends a request and reads the answer.
	 *
	 * Central's refusals are written to be read, in French: they travel
	 * unchanged to the screen. A 401 means the secret no longer opens
	 * anything (revoked from Central, or the site was paired again
	 * elsewhere); it is reported as 409 so the admin's own REST client does
	 * not take it for a WordPress login problem.
	 *
	 * @param string               $path Path under /api/connect/v1.
	 * @param array<string, mixed> $args wp_remote_request() arguments.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function send( string $path, array $args ) {
		$response = wp_remote_request( Connection::base_url() . '/api/connect/v1' . $path, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cinq_central_injoignable',
				__( 'Central ne répond pas. Réessayez dans un instant.', 'cinq-central' ),
				array(
					'status' => 502,
					'detail' => $response->get_error_message(),
				)
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data   = is_array( $data ) ? $data : array();

		if ( $status >= 200 && $status < 300 ) {
			return $data;
		}

		$message = isset( $data['erreur'] ) && is_string( $data['erreur'] )
			? $data['erreur']
			: __( "Central n'a pas pu traiter la demande.", 'cinq-central' );

		if ( 401 === $status ) {
			return new WP_Error( 'cinq_central_deconnecte', $message, array( 'status' => 409 ) );
		}

		return new WP_Error( 'cinq_central_refus', $message, array( 'status' => $status > 0 ? $status : 502 ) );
	}
}
