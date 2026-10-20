<?php
/**
 * Settings › CINQ Central.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Admin
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Admin;

use CinqWpCentral\Access\Gate;
use CinqWpCentral\Central\Client;
use CinqWpCentral\Central\Connection;
use CinqWpCentral\Contracts\Service;
use CinqWpCentral\Front\Widget;

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
	public const SLUG = 'cinq-wp-central-reglages';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_cinq_wp_central_appairer', array( $this, 'pair' ) );
		add_action( 'admin_post_cinq_wp_central_roles', array( $this, 'save_roles' ) );
		add_action( 'admin_post_cinq_wp_central_deconnecter', array( $this, 'disconnect' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( \CinqWpCentral\PLUGIN_FILE ), array( $this, 'action_links' ) );
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
			__( 'CINQ Central', 'cinq-wp-central' ),
			__( 'CINQ Central', 'cinq-wp-central' ),
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
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Réglages', 'cinq-wp-central' ) ) );

		return $links;
	}

	/**
	 * Exchanges the pairing code for the site's secret.
	 *
	 * @return void
	 */
	public function pair(): void {
		$this->guard( 'cinq_wp_central_appairer' );

		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		$answer = Client::pair( $code );

		if ( is_wp_error( $answer ) ) {
			$this->back( 'error', $answer->get_error_message() );
		}

		// The front end gets its widget right away, not at the next admin visit.
		Widget::refresh( wp_get_current_user() );

		/* translators: %s: client name in Central. */
		$this->back( 'success', sprintf( __( 'Site relié à l’espace de %s.', 'cinq-wp-central' ), (string) ( $answer['client'] ?? '' ) ) );
	}

	/**
	 * Saves the roles that see the space.
	 *
	 * @return void
	 */
	public function save_roles(): void {
		$this->guard( 'cinq_wp_central_roles' );

		$known = array_keys( wp_roles()->get_names() );
		$roles = isset( $_POST['roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['roles'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		$roles = array_values( array_intersect( $roles, $known ) );

		// Nobody at all would hide the space from the people who configure it.
		if ( empty( $roles ) ) {
			$roles = Gate::DEFAULT_ROLES;
		}

		update_option( Gate::OPTION, $roles, true );
		$this->back( 'success', __( 'Accès enregistrés.', 'cinq-wp-central' ) );
	}

	/**
	 * Disconnects the site.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->guard( 'cinq_wp_central_deconnecter' );
		Client::disconnect();
		$this->back( 'success', __( 'Site déconnecté de Central.', 'cinq-wp-central' ) );
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
		echo '<h1>' . esc_html__( 'CINQ Central', 'cinq-wp-central' ) . '</h1>';
		echo '<p>' . esc_html__( 'Votre espace client CINQ dans l’administration de ce site : projets, validations, roadmap, demandes et factures.', 'cinq-wp-central' ) . '</p>';

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
		echo '<h2>' . esc_html__( 'Relier ce site à Central', 'cinq-wp-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Votre chef de projet CINQ vous transmet un code de connexion (il est valable 24 heures et ne sert qu’une fois).', 'cinq-wp-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_wp_central_appairer' );
		echo '<input type="hidden" name="action" value="cinq_wp_central_appairer" />';
		echo '<table class="form-table" role="presentation"><tr>';
		echo '<th scope="row"><label for="cinq-wp-central-code">' . esc_html__( 'Code de connexion', 'cinq-wp-central' ) . '</label></th>';
		echo '<td><input type="text" id="cinq-wp-central-code" name="code" class="regular-text code" placeholder="CINQ-XXXX-XXXX" autocomplete="off" required /></td>';
		echo '</tr></table>';
		submit_button( __( 'Relier le site', 'cinq-wp-central' ) );
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

		echo '<h2>' . esc_html__( 'Connexion', 'cinq-wp-central' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		$this->row( __( 'Espace', 'cinq-wp-central' ), esc_html( (string) $connection['client'] ) );
		$this->row(
			__( 'Relié le', 'cinq-wp-central' ),
			esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $connection['connecte_le'] ) )
		);
		$this->row( __( 'Central', 'cinq-wp-central' ), '<code>' . esc_html( Connection::base_url() ) . '</code>' );

		if ( is_wp_error( $me ) ) {
			$this->row(
				__( 'État', 'cinq-wp-central' ),
				'<span style="color:#b32d2e">' . esc_html( $me->get_error_message() ) . '</span>'
			);
		} else {
			$actor = (array) ( $me['acteur'] ?? array() );
			$label = 'agence' === ( $actor['cote'] ?? '' )
				? __( 'compte CINQ', 'cinq-wp-central' )
				: __( 'compte client', 'cinq-wp-central' );
			$this->row(
				__( 'État', 'cinq-wp-central' ),
				'<span style="color:#008a20">' . esc_html__( 'Connecté', 'cinq-wp-central' ) . '</span> — '
				/* translators: 1: person name, 2: account kind. */
				. esc_html( sprintf( __( 'vous apparaissez comme %1$s (%2$s)', 'cinq-wp-central' ), (string) ( $actor['nom'] ?? '' ), $label ) )
			);
		}
		echo '</table>';

		$this->render_widget();

		echo '<h2>' . esc_html__( 'Qui voit l’espace CINQ', 'cinq-wp-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Les comptes de ces rôles voient le menu CINQ et le bloc du tableau de bord. Chaque personne y apparaît sous son nom ; une adresse @agencecinq.com est reconnue comme l’équipe CINQ.', 'cinq-wp-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_wp_central_roles' );
		echo '<input type="hidden" name="action" value="cinq_wp_central_roles" />';
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
		submit_button( __( 'Enregistrer les accès', 'cinq-wp-central' ), 'secondary' );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Déconnecter', 'cinq-wp-central' ) . '</h2>';
		echo '<p>' . esc_html__( 'Le site cesse d’ouvrir l’espace. Rien n’est effacé dans Central ; un nouveau code permet de le relier à nouveau.', 'cinq-wp-central' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cinq_wp_central_deconnecter' );
		echo '<input type="hidden" name="action" value="cinq_wp_central_deconnecter" />';
		submit_button( __( 'Déconnecter ce site', 'cinq-wp-central' ), 'delete' );
		echo '</form>';
	}

	/**
	 * The feedback button: its activation link, to open here or to send.
	 *
	 * @return void
	 */
	private function render_widget(): void {
		echo '<h2>' . esc_html__( 'Bouton de retour sur le site', 'cinq-wp-central' ) . '</h2>';

		$widget = Widget::refresh( wp_get_current_user() );
		$link   = Widget::activation_url( $widget );

		if ( null === $widget ) {
			echo '<p>' . esc_html__( 'Central ne répond pas pour l’instant : le lien s’affichera au prochain passage.', 'cinq-wp-central' ) . '</p>';
			return;
		}

		if ( '' === $link ) {
			echo '<p>' . esc_html__( 'Aucun bouton de retour n’est actif pour ce site. L’équipe CINQ le règle depuis Central (fiche client › Sites, projet du widget).', 'cinq-wp-central' ) . '</p>';
			return;
		}

		echo '<p>' . esc_html__( 'Le bouton « Signaler » apparaît sur le site dans les navigateurs qui ont ouvert ce lien une fois ; les visiteurs ne le voient jamais. Il peut être envoyé à toute personne qui doit signaler quelque chose, même sans accès à cette administration.', 'cinq-wp-central' ) . '</p>';
		echo '<p>';
		printf(
			'<a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s</a> ',
			esc_url( $link ),
			esc_html__( 'Afficher le widget', 'cinq-wp-central' )
		);
		echo '</p>';
		echo '<table class="form-table" role="presentation"><tr>';
		echo '<th scope="row"><label for="cinq-wp-central-lien">' . esc_html__( 'Lien à envoyer', 'cinq-wp-central' ) . '</label></th>';
		printf(
			'<td><input type="text" id="cinq-wp-central-lien" class="large-text code" value="%1$s" readonly onclick="this.select()" /> <button type="button" class="button" onclick="%2$s">%3$s</button></td>',
			esc_attr( $link ),
			esc_attr( "var c=document.getElementById('cinq-wp-central-lien');c.select();navigator.clipboard&&navigator.clipboard.writeText(c.value);this.textContent='" . esc_js( __( 'Copié', 'cinq-wp-central' ) ) . "'" ),
			esc_html__( 'Copier', 'cinq-wp-central' )
		);
		echo '</tr></table>';
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
			wp_die( esc_html__( 'Vous n’avez pas les droits nécessaires.', 'cinq-wp-central' ), 403 );
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
		return 'cinq_wp_central_notice_' . get_current_user_id();
	}
}
