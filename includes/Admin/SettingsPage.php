<?php
/**
 * Settings › CINQ Central.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Admin
 */

declare( strict_types = 1 );

namespace CinqCentral\Admin;

use CinqCentral\Access\Gate;
use CinqCentral\Central\Client;
use CinqCentral\Central\Connection;
use CinqCentral\Contracts\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Connects the site to Central, chooses who sees the space, disconnects.
 *
 * Built with WordPress's own settings markup (form-table, notices), so it
 * looks like every other settings screen of the site. Forms post to
 * `admin-post.php` with a nonce and a capability check.
 */
final class SettingsPage implements Service {

	/**
	 * Page slug.
	 */
	public const SLUG = 'cinq-central-reglages';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_cinq_central_appairer', array( $this, 'pair' ) );
		add_action( 'admin_post_cinq_central_roles', array( $this, 'save_roles' ) );
		add_action( 'admin_post_cinq_central_deconnecter', array( $this, 'disconnect' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( \CinqCentral\PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * URL of the page.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'options-general.php?page=' . self::SLUG );
	}

	/**
	 * Adds the page under Settings.
	 *
	 * @return void
	 */
	public function menu(): void {
		add_options_page(
			__( 'CINQ Central', 'cinq-central' ),
			__( 'CINQ Central', 'cinq-central' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * "Réglages" link on the Plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Réglages', 'cinq-central' ) ) );

		return $links;
	}

	/**
	 * Exchanges the pairing code for the site's secret.
	 *
	 * @return void
	 */
	public function pair(): void {
		$this->guard( 'cinq_central_appairer' );

		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		$answer = Client::pair( $code );

		if ( is_wp_error( $answer ) ) {
			$this->back( 'error', $answer->get_error_message() );
		}

		/* translators: %s: client name in Central. */
		$this->back( 'success', sprintf( __( 'Site relié à l’espace de %s.', 'cinq-central' ), (string) ( $answer['client'] ?? '' ) ) );
	}

	/**
	 * Saves the roles that see the space.
	 *
	 * @return void
	 */
	public function save_roles(): void {
		$this->guard( 'cinq_central_roles' );

		$known = array_keys( wp_roles()->get_names() );
		$roles = isset( $_POST['roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['roles'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		$roles = array_values( array_intersect( $roles, $known ) );

		// Nobody at all would hide the space from the people who configure it.
		if ( empty( $roles ) ) {
			$roles = Gate::DEFAULT_ROLES;
		}

		update_option( Gate::OPTION, $roles, true );
		$this->back( 'success', __( 'Accès enregistrés.', 'cinq-central' ) );
	}

	/**
	 * Disconnects the site.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->guard( 'cinq_central_deconnecter' );
		Client::disconnect();
		$this->back( 'success', __( 'Site déconnecté de Central.', 'cinq-central' ) );
	}

	/**
	 * Renders the page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Gate::can_configure() ) {
			return;
		}

		$connection = Connection::get();
		$notice     = get_transient( $this->notice_key() );
		delete_transient( $this->notice_key() );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'CINQ Central', 'cinq-central' ) . '</h1>';
		echo '<p>' . esc_html__( 'Votre espace client CINQ dans l’administration de ce site : projets, validations, roadmap, demandes et factures.', 'cinq-central' ) . '</p>';

		if ( is_array( $notice ) ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( 'error' === $notice['type'] ? 'error' : 'success' ),
				esc_html( (string) $notice['message'] )
			);
		}

		if ( null === $connection ) {
			$this->render_pairing();
		} else {
			$this->render_connected( $connection );
		}

		echo '</div>';
	}

	/**
	 * Pairing form.
	 *
	 * @return void
	 */
	private function render_pairing(): void {
		echo '<h2>' . esc_html__( 'Relier ce site à Central', 'cinq-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Votre chef de projet CINQ vous transmet un code de connexion (il est valable 24 heures et ne sert qu’une fois).', 'cinq-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_central_appairer' );
		echo '<input type="hidden" name="action" value="cinq_central_appairer" />';
		echo '<table class="form-table" role="presentation"><tr>';
		echo '<th scope="row"><label for="cinq-central-code">' . esc_html__( 'Code de connexion', 'cinq-central' ) . '</label></th>';
		echo '<td><input type="text" id="cinq-central-code" name="code" class="regular-text code" placeholder="CINQ-XXXX-XXXX" autocomplete="off" required /></td>';
		echo '</tr></table>';
		submit_button( __( 'Relier le site', 'cinq-central' ) );
		echo '</form>';
	}

	/**
	 * Connection state, access, diagnostics, disconnection.
	 *
	 * @param array<string, mixed> $connection Stored connection.
	 * @return void
	 */
	private function render_connected( array $connection ): void {
		$me = Client::request( 'GET', '/moi', null, wp_get_current_user(), 8 );

		echo '<h2>' . esc_html__( 'Connexion', 'cinq-central' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		$this->row( __( 'Espace', 'cinq-central' ), esc_html( (string) $connection['client'] ) );
		$this->row(
			__( 'Relié le', 'cinq-central' ),
			esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $connection['connecte_le'] ) )
		);
		$this->row( __( 'Central', 'cinq-central' ), '<code>' . esc_html( Connection::base_url() ) . '</code>' );

		if ( is_wp_error( $me ) ) {
			$this->row(
				__( 'État', 'cinq-central' ),
				'<span style="color:#b32d2e">' . esc_html( $me->get_error_message() ) . '</span>'
			);
		} else {
			$actor = (array) ( $me['acteur'] ?? array() );
			$label = 'agence' === ( $actor['cote'] ?? '' )
				? __( 'compte CINQ', 'cinq-central' )
				: __( 'compte client', 'cinq-central' );
			$this->row(
				__( 'État', 'cinq-central' ),
				'<span style="color:#008a20">' . esc_html__( 'Connecté', 'cinq-central' ) . '</span> — '
				/* translators: 1: person name, 2: account kind. */
				. esc_html( sprintf( __( 'vous apparaissez comme %1$s (%2$s)', 'cinq-central' ), (string) ( $actor['nom'] ?? '' ), $label ) )
			);
		}
		echo '</table>';

		echo '<h2>' . esc_html__( 'Qui voit l’espace CINQ', 'cinq-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Les comptes de ces rôles voient le menu CINQ, le bloc du tableau de bord et le bouton de retour sur le site. Chaque personne y apparaît sous son nom ; une adresse @agencecinq.com est reconnue comme l’équipe CINQ.', 'cinq-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_central_roles' );
		echo '<input type="hidden" name="action" value="cinq_central_roles" />';
		echo '<fieldset>';
		$allowed = Gate::roles();
		foreach ( wp_roles()->get_names() as $slug => $name ) {
			printf(
				'<label style="display:block;margin:4px 0"><input type="checkbox" name="roles[]" value="%1$s" %2$s /> %3$s</label>',
				esc_attr( $slug ),
				checked( in_array( $slug, $allowed, true ), true, false ),
				esc_html( translate_user_role( $name ) )
			);
		}
		echo '</fieldset>';
		submit_button( __( 'Enregistrer les accès', 'cinq-central' ), 'secondary' );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Déconnecter', 'cinq-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Le site cesse d’ouvrir l’espace. Rien n’est effacé dans Central ; un nouveau code permet de le relier à nouveau.', 'cinq-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_central_deconnecter' );
		echo '<input type="hidden" name="action" value="cinq_central_deconnecter" />';
		submit_button( __( 'Déconnecter ce site', 'cinq-central' ), 'delete' );
		echo '</form>';
	}

	/**
	 * One row of a form table. `$html` is already escaped.
	 *
	 * @param string $label Label.
	 * @param string $html  Escaped value.
	 * @return void
	 */
	private function row( string $label, string $html ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $html is escaped by the caller.
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $html . '</td></tr>';
	}

	/**
	 * Checks the nonce and the capability of a form post.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private function guard( string $action ): void {
		if ( ! Gate::can_configure() ) {
			wp_die( esc_html__( 'Vous n’avez pas les droits nécessaires.', 'cinq-central' ), 403 );
		}

		check_admin_referer( $action );
	}

	/**
	 * Returns to the page with a notice.
	 *
	 * @param string $type    `success` or `error`.
	 * @param string $message Message.
	 * @return never
	 */
	private function back( string $type, string $message ) {
		set_transient(
			$this->notice_key(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			60
		);
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Transient carrying the notice to the next page load.
	 *
	 * @return string
	 */
	private function notice_key(): string {
		return 'cinq_central_notice_' . get_current_user_id();
	}
}
