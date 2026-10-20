<?php
/**
 * The site's connection to Central.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Central
 */

declare( strict_types = 1 );

namespace CinqCentral\Central;

defined( 'ABSPATH' ) || exit;

/**
 * Stores what pairing returned: the site's secret and who it belongs to.
 *
 * The secret lives in a non-autoloaded option, so it is read only by the
 * requests that call Central and never sits in the object cache of every page.
 * It is never sent to a browser.
 */
final class Connection {

	/**
	 * Option holding the connection.
	 */
	public const OPTION = 'cinq_central_connexion';

	/**
	 * Default Central address.
	 */
	public const DEFAULT_URL = 'https://app.cinqteam.com';

	/**
	 * Central's base URL, without a trailing slash.
	 *
	 * The `CINQ_CENTRAL_URL` constant (wp-config.php) points a staging site or
	 * a local install at another Central; the filter does the same from code.
	 *
	 * @return string
	 */
	public static function base_url(): string {
		$url = defined( 'CINQ_CENTRAL_URL' ) ? (string) constant( 'CINQ_CENTRAL_URL' ) : self::DEFAULT_URL;

		/**
		 * Filters the address of Central.
		 *
		 * @param string $url Base URL, without a trailing slash.
		 */
		return untrailingslashit( (string) apply_filters( 'cinq_central_url', $url ) );
	}

	/**
	 * The stored connection, or null when the site is not paired.
	 *
	 * @return array{site_id: string, secret: string, client: string, domaine: string, connecte_le: int}|null
	 */
	public static function get(): ?array {
		$value = get_option( self::OPTION );

		if ( ! is_array( $value ) || empty( $value['secret'] ) || empty( $value['site_id'] ) ) {
			return null;
		}

		return array(
			'site_id'     => (string) $value['site_id'],
			'secret'      => (string) $value['secret'],
			'client'      => (string) ( $value['client'] ?? '' ),
			'domaine'     => (string) ( $value['domaine'] ?? '' ),
			'connecte_le' => (int) ( $value['connecte_le'] ?? 0 ),
		);
	}

	/**
	 * Whether the site is paired.
	 *
	 * @return bool
	 */
	public static function is_connected(): bool {
		return null !== self::get();
	}

	/**
	 * Stores a new connection.
	 *
	 * @param array<string, mixed> $pairing Pairing response from Central.
	 * @return void
	 */
	public static function save( array $pairing ): void {
		update_option(
			self::OPTION,
			array(
				'site_id'     => (string) ( $pairing['siteId'] ?? '' ),
				'secret'      => (string) ( $pairing['secret'] ?? '' ),
				'client'      => (string) ( $pairing['client'] ?? '' ),
				'domaine'     => (string) ( $pairing['domaine'] ?? '' ),
				'connecte_le' => time(),
			),
			false
		);
		Cache::flush();
	}

	/**
	 * Forgets the connection.
	 *
	 * @return void
	 */
	public static function forget(): void {
		delete_option( self::OPTION );
		Cache::flush();
	}

	/**
	 * This site's domain, as Central will label it.
	 *
	 * @return string
	 */
	public static function site_domain(): string {
		return (string) wp_parse_url( home_url(), PHP_URL_HOST );
	}
}
