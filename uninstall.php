<?php
/**
 * Removes everything the plugin stored.
 *
 * Runs when the plugin is deleted from the Plugins screen, not when it is
 * deactivated: deactivating keeps the site connected. Central is told the site
 * leaves, so its secret stops opening anything there too.
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$cinq_central_connexion = get_option( 'cinq_central_connexion' );

if ( is_array( $cinq_central_connexion ) && ! empty( $cinq_central_connexion['secret'] ) ) {
	$cinq_central_base = defined( 'CINQ_CENTRAL_URL' ) ? (string) constant( 'CINQ_CENTRAL_URL' ) : 'https://app.cinqteam.com';
	wp_remote_request(
		untrailingslashit( $cinq_central_base ) . '/api/connect/v1/connexion',
		array(
			'method'   => 'DELETE',
			'timeout'  => 5,
			'blocking' => false,
			'headers'  => array( 'Authorization' => 'Bearer ' . $cinq_central_connexion['secret'] ),
		)
	);
}

delete_option( 'cinq_central_connexion' );
delete_option( 'cinq_central_roles' );
delete_option( 'cinq_central_cache_gen' );
delete_transient( 'cinq_central_latest_release' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own transients, whose names carry a generation number.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_cinq\\_central\\_%' OR option_name LIKE '\\_transient\\_timeout\\_cinq\\_central\\_%'" );
