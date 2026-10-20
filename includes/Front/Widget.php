<?php
/**
 * The feedback widget on the site's front end.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Front
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Front;

use CinqWpCentral\Access\Gate;
use CinqWpCentral\Admin\AppPage;
use CinqWpCentral\Central\Connection;
use CinqWpCentral\Central\Space;
use CinqWpCentral\Contracts\Service;
use WP_Admin_Bar;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Puts Central's feedback widget on the front end, and adds "Signaler à
 * CINQ" to the admin bar.
 *
 * The widget keeps Central's own rule: the script is on every page, and the
 * button appears only in browsers that opened the activation link
 * (`?cinq-feedback=KEY`). Visitors see nothing; the link can be sent to
 * anyone who should report something — a director who never opens the WP
 * admin included. The settings page gives the link.
 *
 * Front-end pages never call Central: the public part of the widget (script
 * address and token, never the key) is kept in an option, refreshed when an
 * allowed person goes through the admin.
 */
final class Widget implements Service {

	/**
	 * Option holding the public part of the widget: `{script, jeton}`.
	 */
	public const OPTION = 'cinq_wp_central_widget';

	/**
	 * The query parameter widget.js reads the activation key from.
	 */
	public const PARAM = 'cinq-feedback';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		// Last of the footer, after wp_print_footer_scripts (20) and whatever
		// other plugins print late: the widget runs once the page is done.
		add_action( 'wp_footer', array( $this, 'render' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'refresh_for_current_user' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 90 );
	}

	/**
	 * Reads the widget from Central for a person and keeps its public part
	 * for the front end. Cached an hour for the site (`Space::widget`).
	 *
	 * @param WP_User $user  Allowed person.
	 * @param bool    $fresh Ask Central again, whatever the cache holds.
	 * @return array<string, mixed>|null The full answer, key included, or null.
	 */
	public static function refresh( WP_User $user, bool $fresh = false ): ?array {
		$widget = Space::widget( $user, $fresh );

		// Central did not answer: keep what the front end already has.
		if ( null === $widget ) {
			return null;
		}

		if ( empty( $widget['actif'] ) || empty( $widget['script'] ) || empty( $widget['jeton'] ) ) {
			delete_option( self::OPTION );
			return $widget;
		}

		update_option(
			self::OPTION,
			array(
				'script' => (string) $widget['script'],
				'jeton'  => (string) $widget['jeton'],
			),
			true
		);

		return $widget;
	}

	/**
	 * The activation link: the site's home page with the widget's key.
	 *
	 * @param array<string, mixed>|null $widget Answer of `refresh()`.
	 * @return string Empty when the widget is off.
	 */
	public static function activation_url( ?array $widget ): string {
		if ( empty( $widget['actif'] ) || empty( $widget['cle'] ) ) {
			return '';
		}

		return add_query_arg( self::PARAM, rawurlencode( (string) $widget['cle'] ), home_url( '/' ) );
	}

	/**
	 * Keeps the front end's copy fresh as allowed people use the admin.
	 *
	 * @return void
	 */
	public function refresh_for_current_user(): void {
		if ( wp_doing_ajax() || ! Gate::can() || ! Connection::is_connected() ) {
			return;
		}

		self::refresh( wp_get_current_user() );
	}

	/**
	 * Prints the widget's script, for every visitor.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Connection::is_connected() ) {
			return;
		}

		// Not inside the Customizer preview: pointing at an element there
		// would report the editor, not the site.
		if ( is_customize_preview() ) {
			return;
		}

		/**
		 * Filters whether the feedback widget is put on the front end.
		 *
		 * @param bool $show Default true.
		 */
		if ( ! apply_filters( 'cinq_wp_central_front_widget', true ) ) {
			return;
		}

		$widget = get_option( self::OPTION );

		if ( ! is_array( $widget ) || empty( $widget['script'] ) || empty( $widget['jeton'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- widget.js reads its own <script data-cinq> tag.
		printf( '<script src="%1$s" data-cinq="%2$s" defer></script>' . "\n", esc_url( (string) $widget['script'] ), esc_attr( (string) $widget['jeton'] ) );
	}

	/**
	 * "Signaler à CINQ" in the admin bar, on the front end: opens a new
	 * request with the current page already filled in.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public function admin_bar( WP_Admin_Bar $bar ): void {
		if ( is_admin() || ! Gate::can() || ! Connection::is_connected() ) {
			return;
		}

		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$page   = '' !== $host ? $scheme . $host . $uri : home_url( '/' );

		$bar->add_node(
			array(
				'id'    => 'cinq-wp-central-signaler',
				'title' => '<span class="ab-icon dashicons dashicons-flag" style="margin-top:2px"></span>' . esc_html__( 'Signaler à CINQ', 'cinq-wp-central' ),
				'href'  => AppPage::url( 'demandes', '/demandes/nouvelle?page=' . rawurlencode( $page ) ),
			)
		);
	}
}
