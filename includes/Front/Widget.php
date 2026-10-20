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

defined( 'ABSPATH' ) || exit;

/**
 * Puts Central's feedback widget on the front end, for the people of the
 * client only, and adds "Signaler à CINQ" to the admin bar.
 *
 * Without the plugin, the widget appears only in browsers that opened an
 * activation link (`?cinq-feedback=KEY`). Here the site already knows who is
 * logged in and allowed: the key is written into the browser directly, and
 * visitors — logged out, or of another role — get nothing, not even the
 * script.
 */
final class Widget implements Service {

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'wp_footer', array( $this, 'render' ), 100 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 90 );
	}

	/**
	 * Prints the widget for an allowed, logged-in person.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( is_admin() || ! is_user_logged_in() || ! Gate::can() || ! Connection::is_connected() ) {
			return;
		}

		/**
		 * Filters whether the feedback widget is shown on the front end.
		 *
		 * @param bool $show Default true.
		 */
		if ( ! apply_filters( 'cinq_wp_central_front_widget', true ) ) {
			return;
		}

		$widget = Space::widget( wp_get_current_user() );

		if ( null === $widget || empty( $widget['actif'] ) || empty( $widget['script'] ) || empty( $widget['jeton'] ) ) {
			return;
		}

		$token = (string) $widget['jeton'];
		$key   = (string) ( $widget['cle'] ?? '' );

		// The key goes where widget.js reads it, before the script runs.
		if ( '' !== $key ) {
			wp_print_inline_script_tag(
				sprintf(
					'try{localStorage.setItem(%s,%s)}catch(e){}',
					wp_json_encode( 'cinq-feedback:' . $token ),
					wp_json_encode( $key )
				)
			);
		}

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- widget.js reads its own <script data-cinq> tag.
		printf( '<script src="%1$s" data-cinq="%2$s" defer></script>' . "\n", esc_url( (string) $widget['script'] ), esc_attr( $token ) );
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
