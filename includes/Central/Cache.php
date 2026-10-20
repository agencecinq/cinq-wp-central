<?php
/**
 * Short-lived cache of Central's answers.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Central
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Central;

defined( 'ABSPATH' ) || exit;

/**
 * A few minutes of cache for what is shown on every admin page (the menu
 * badge, the dashboard widget) and for the front-end widget settings.
 *
 * Keys carry a generation number, so disconnecting or writing anything drops
 * the whole cache at once without listing its keys. The screens of the space
 * itself are never cached: someone who just answered must see their answer.
 */
final class Cache {

	/**
	 * Option holding the generation number.
	 */
	private const GENERATION = 'cinq_wp_central_cache_gen';

	/**
	 * Reads a cached value.
	 *
	 * @param string $key Key.
	 * @return mixed|null
	 */
	public static function get( string $key ) {
		$value = get_transient( self::key( $key ) );

		return false === $value ? null : $value;
	}

	/**
	 * Stores a value.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value (never false).
	 * @param int    $ttl   Lifetime in seconds.
	 * @return void
	 */
	public static function set( string $key, $value, int $ttl = 300 ): void {
		set_transient( self::key( $key ), $value, $ttl );
	}

	/**
	 * Drops every cached value.
	 *
	 * @return void
	 */
	public static function flush(): void {
		update_option( self::GENERATION, (int) get_option( self::GENERATION, 0 ) + 1, true );
	}

	/**
	 * Builds a transient name.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private static function key( string $key ): string {
		return 'cinq_wp_central_' . (int) get_option( self::GENERATION, 0 ) . '_' . md5( $key );
	}
}
