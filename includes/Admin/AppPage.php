<?php
/**
 * The "CINQ" admin menu and the space's screens.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Admin
 */

declare( strict_types = 1 );

namespace CinqCentral\Admin;

use CinqCentral\Access\Gate;
use CinqCentral\Central\Connection;
use CinqCentral\Central\Space;
use CinqCentral\Contracts\Service;
use CinqCentral\Rest\Proxy;
use const CinqCentral\PLUGIN_FILE;
use const CinqCentral\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * A top-level "CINQ" menu, one submenu per screen of the space, the same
 * entries as the web space: Accueil, Roadmap, Demandes, Factures, Ressources.
 *
 * Each screen is the same small React app (WordPress's own components, no
 * CINQ colours), told which view to open. Details (a project, a request, a
 * card) open inside the screen, through the URL hash.
 *
 * The menu badge counts what waits for the client — the validations — like
 * the "Accueil" counter of the web space. A count of zero is not shown.
 */
final class AppPage implements Service {

	/**
	 * Slug of the top-level page (Accueil).
	 */
	public const SLUG = 'cinq-central';

	/**
	 * Screens: view => [submenu slug, title].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function views(): array {
		return array(
			'accueil'    => array( self::SLUG, __( 'Accueil', 'cinq-central' ) ),
			'roadmap'    => array( self::SLUG . '-roadmap', __( 'Roadmap', 'cinq-central' ) ),
			'demandes'   => array( self::SLUG . '-demandes', __( 'Demandes', 'cinq-central' ) ),
			'factures'   => array( self::SLUG . '-factures', __( 'Factures', 'cinq-central' ) ),
			'ressources' => array( self::SLUG . '-ressources', __( 'Ressources', 'cinq-central' ) ),
		);
	}

	/**
	 * URL of a view, with an optional hash route.
	 *
	 * @param string $view  View.
	 * @param string $route Hash route, e.g. `/projets/<id>`.
	 * @return string
	 */
	public static function url( string $view = 'accueil', string $route = '' ): string {
		$slug = self::views()[ $view ][0] ?? self::SLUG;

		return admin_url( 'admin.php?page=' . $slug ) . ( '' !== $route ? '#' . $route : '' );
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Adds the menu, for the people allowed only.
	 *
	 * @return void
	 */
	public function menu(): void {
		if ( ! Gate::can() ) {
			return;
		}

		$waiting = Connection::is_connected() ? Space::waiting( wp_get_current_user() ) : 0;
		$badge   = $waiting > 0
			? sprintf( ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $waiting )
			: '';

		add_menu_page(
			__( 'Espace CINQ', 'cinq-central' ),
			__( 'CINQ', 'cinq-central' ) . $badge,
			'read',
			self::SLUG,
			array( $this, 'render' ),
			self::icon(),
			3
		);

		foreach ( self::views() as $view => list( $slug, $title ) ) {
			add_submenu_page(
				self::SLUG,
				$title . ' — ' . __( 'Espace CINQ', 'cinq-central' ),
				'accueil' === $view ? $title . $badge : $title,
				'read',
				$slug,
				array( $this, 'render' )
			);
		}
	}

	/**
	 * Renders the mount point; the app does the rest.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Gate::can() ) {
			wp_die( esc_html__( 'Votre compte n’a pas accès à l’espace CINQ.', 'cinq-central' ), 403 );
		}

		echo '<div class="wrap cinq-central">';

		if ( ! Connection::is_connected() ) {
			echo '<h1>' . esc_html__( 'Espace CINQ', 'cinq-central' ) . '</h1>';
			echo '<div class="notice notice-info inline"><p>';
			if ( Gate::can_configure() ) {
				printf(
					/* translators: %s: settings page link. */
					esc_html__( 'Ce site n’est pas encore relié à Central. Saisissez le code transmis par CINQ dans %s.', 'cinq-central' ),
					'<a href="' . esc_url( SettingsPage::url() ) . '">' . esc_html__( 'Réglages › CINQ Central', 'cinq-central' ) . '</a>'
				);
			} else {
				esc_html_e( 'Ce site n’est pas encore relié à Central. Un administrateur doit le relier.', 'cinq-central' );
			}
			echo '</p></div></div>';
			return;
		}

		echo '<div id="cinq-central-app"><p>' . esc_html__( 'Chargement de votre espace…', 'cinq-central' ) . '</p></div>';
		echo '<noscript><p>' . esc_html__( 'L’espace CINQ a besoin de JavaScript.', 'cinq-central' ) . '</p></noscript>';
		echo '</div>';
	}

	/**
	 * Loads the app on the space's screens only.
	 *
	 * The screen is recognised from `?page=` rather than from the hook
	 * suffix, whose value depends on the translated menu title.
	 *
	 * @return void
	 */
	public function assets(): void {
		$view = self::current_view();

		if ( null === $view || ! Connection::is_connected() || ! Gate::can() ) {
			return;
		}

		$dir   = plugin_dir_path( PLUGIN_FILE ) . 'build/';
		$url   = plugin_dir_url( PLUGIN_FILE ) . 'build/';
		$asset = is_readable( $dir . 'index.asset.php' )
			? require $dir . 'index.asset.php'
			: array(
				'dependencies' => array(),
				'version'      => VERSION,
			);

		wp_enqueue_script( 'cinq-central-app', $url . 'index.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'wp-components' );

		if ( is_readable( $dir . 'style-index.css' ) ) {
			wp_enqueue_style( 'cinq-central-app', $url . 'style-index.css', array( 'wp-components' ), $asset['version'] );
		}

		$urls = array();
		foreach ( array_keys( self::views() ) as $name ) {
			$urls[ $name ] = self::url( $name );
		}

		wp_add_inline_script(
			'cinq-central-app',
			'window.cinqCentral = ' . wp_json_encode(
				array(
					'vue'         => $view,
					'espace'      => (string) ( Connection::get()['client'] ?? '' ),
					'namespace'   => Proxy::NAMESPACE,
					'urls'        => $urls,
					'reglages'    => Gate::can_configure() ? SettingsPage::url() : null,
					'utilisateur' => wp_get_current_user()->display_name,
					'siteUrl'     => home_url( '/' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * The view of the current screen, or null outside the space.
	 *
	 * @return string|null
	 */
	public static function current_view(): ?string {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.

		foreach ( self::views() as $view => list( $slug ) ) {
			if ( $slug === $page ) {
				return $view;
			}
		}

		return null;
	}

	/**
	 * Menu icon: the CINQ asterisk, as an SVG data URI WordPress recolours.
	 *
	 * @return string
	 */
	private static function icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M9 1h2v6.6l4.7-4.7 1.4 1.4L12.4 9H19v2h-6.6l4.7 4.7-1.4 1.4-4.7-4.7V19H9v-6.6l-4.7 4.7-1.4-1.4L7.6 11H1V9h6.6L2.9 4.3l1.4-1.4L9 7.6z"/></svg>';

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- the data URI WordPress expects for menu icons.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}
