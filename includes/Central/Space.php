<?php
/**
 * Cached reads shown outside the space itself.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Central
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Central;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * The few reads that every admin page shows: the menu badge, the dashboard
 * widget, the front-end feedback widget.
 *
 * Cached five minutes per person, failures included: a Central outage must
 * not slow every admin page down. Calls are short (4 seconds) for the same
 * reason. Any write through the plugin drops the cache (`Cache::flush`).
 */
final class Space {

	/**
	 * `/moi` for a user: who they are for Central, and the badge counts.
	 *
	 * @param WP_User $user User.
	 * @return array<string, mixed>|null Null when Central cannot answer.
	 */
	public static function me( WP_User $user ): ?array {
		return self::cached( 'moi', $user, 300 );
	}

	/**
	 * `/accueil` for a user, for the dashboard widget.
	 *
	 * @param WP_User $user User.
	 * @return array<string, mixed>|null
	 */
	public static function home( WP_User $user ): ?array {
		return self::cached( 'accueil', $user, 300 );
	}

	/**
	 * The feedback widget of the project linked to this site.
	 *
	 * The same for every person, and read on front-end pages: cached an hour
	 * for the whole site, so a page waits for Central once an hour at most.
	 *
	 * @param WP_User $user  User.
	 * @param bool    $fresh Ask Central again, whatever the cache holds.
	 * @return array<string, mixed>|null
	 */
	public static function widget( WP_User $user, bool $fresh = false ): ?array {
		if ( $fresh ) {
			Cache::forget( 'widget:site' );
		}

		return self::cached( 'widget', $user, HOUR_IN_SECONDS, 'widget:site' );
	}

	/**
	 * Number of things waiting for the client (validations).
	 *
	 * @param WP_User $user User.
	 * @return int
	 */
	public static function waiting( WP_User $user ): int {
		$me = self::me( $user );

		return (int) ( $me['comptes']['attendus'] ?? 0 );
	}

	/**
	 * Reads one route through the cache.
	 *
	 * @param string  $route Route name under /api/connect/v1.
	 * @param WP_User $user  User.
	 * @param int     $ttl   Lifetime in seconds.
	 * @param ?string $key   Cache key, per person by default.
	 * @return array<string, mixed>|null
	 */
	private static function cached( string $route, WP_User $user, int $ttl, ?string $key = null ): ?array {
		if ( ! Connection::is_connected() ) {
			return null;
		}

		$key  ??= $route . ':' . $user->ID;
		$cached = Cache::get( $key );

		if ( is_array( $cached ) ) {
			return empty( $cached['__echec'] ) ? $cached : null;
		}

		$answer = Client::request( 'GET', '/' . $route, null, $user, 4 );

		if ( is_wp_error( $answer ) ) {
			Cache::set( $key, array( '__echec' => true ), 120 );
			return null;
		}

		Cache::set( $key, $answer, $ttl );

		return $answer;
	}
}
