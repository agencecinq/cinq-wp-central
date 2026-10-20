<?php
/**
 * "CINQ" block on the WordPress dashboard.
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

defined( 'ABSPATH' ) || exit;

/**
 * The space's headline where the client already is: the dashboard.
 *
 * The same order as the web space's home, cut to what fits in a dashboard
 * box: the situation in one sentence, what waits for the client, then the
 * requests we owe them. Read from a five-minute cache (`Space::home`).
 */
final class DashboardWidget implements Service {

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	/**
	 * Adds the widget, for the people allowed only.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! Gate::can() || ! Connection::is_connected() ) {
			return;
		}

		wp_add_dashboard_widget( 'cinq_central', __( 'CINQ — votre espace', 'cinq-central' ), array( $this, 'render' ) );
	}

	/**
	 * Renders the widget.
	 *
	 * @return void
	 */
	public function render(): void {
		$home = Space::home( wp_get_current_user() );

		if ( null === $home ) {
			echo '<p>' . esc_html__( 'Central ne répond pas pour l’instant.', 'cinq-central' ) . '</p>';
			$this->footer();
			return;
		}

		$situation = (array) ( $home['situation'] ?? array() );
		echo '<p><strong>' . esc_html( (string) ( $situation['titre'] ?? '' ) ) . '</strong></p>';

		$waiting = array_slice( (array) ( $home['attendus'] ?? array() ), 0, 3 );
		if ( ! empty( $waiting ) ) {
			echo '<h3>' . esc_html__( 'À valider', 'cinq-central' ) . '</h3><ul>';
			foreach ( $waiting as $item ) {
				printf(
					'<li><a href="%1$s">%2$s</a> <span class="description">— %3$s</span></li>',
					esc_url( AppPage::url( 'accueil', '/projets/' . rawurlencode( (string) ( $item['projetId'] ?? '' ) ) ) ),
					esc_html( (string) ( $item['objet'] ?? '' ) ),
					esc_html( (string) ( $item['projetTitre'] ?? '' ) )
				);
			}
			echo '</ul>';
		}

		$requests = array_slice( (array) ( $home['demandesOuvertes'] ?? array() ), 0, 3 );
		if ( ! empty( $requests ) ) {
			echo '<h3>' . esc_html__( 'Vos demandes en cours', 'cinq-central' ) . '</h3><ul>';
			foreach ( $requests as $item ) {
				printf(
					'<li><a href="%1$s">%2$s</a> <span class="description">— %3$s</span></li>',
					esc_url( AppPage::url( 'demandes', '/demandes/' . rawurlencode( (string) ( $item['id'] ?? '' ) ) ) ),
					esc_html( (string) ( $item['titre'] ?? '' ) ),
					esc_html( 'en_cours' === ( $item['etat'] ?? '' ) ? __( 'en cours', 'cinq-central' ) : __( 'reçue', 'cinq-central' ) )
				);
			}
			echo '</ul>';
		}

		$this->footer();
	}

	/**
	 * Links to the space.
	 *
	 * @return void
	 */
	private function footer(): void {
		printf(
			'<p class="community-events-footer"><a href="%1$s" class="button button-primary">%2$s</a> <a href="%3$s" class="button">%4$s</a></p>',
			esc_url( AppPage::url() ),
			esc_html__( 'Ouvrir l’espace CINQ', 'cinq-central' ),
			esc_url( AppPage::url( 'demandes', '/demandes/nouvelle' ) ),
			esc_html__( 'Nouvelle demande', 'cinq-central' )
		);
	}
}
