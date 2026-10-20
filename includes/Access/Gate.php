<?php
/**
 * Who opens the client space from this site.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Access
 */

declare( strict_types = 1 );

namespace CinqCentral\Access;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Decides who, among this site's users, sees the CINQ space.
 *
 * The rule agreed with the agency: whoever has access to the site's admin has
 * access to the client's space. "Admin" is read as the roles ticked in the
 * settings (administrators by default), so a WooCommerce customer or a
 * newsletter subscriber, who also have a WordPress account, never do.
 *
 * Central then decides who that person is on its side: an unknown address
 * becomes an account of this site's client, an @agencecinq.com address is
 * matched to the agency member. That decision is not made here.
 */
final class Gate {

	/**
	 * Option holding the allowed roles.
	 */
	public const OPTION = 'cinq_central_roles';

	/**
	 * Roles allowed until the settings say otherwise.
	 */
	public const DEFAULT_ROLES = array( 'administrator' );

	/**
	 * Allowed roles.
	 *
	 * @return string[]
	 */
	public static function roles(): array {
		$roles = get_option( self::OPTION, self::DEFAULT_ROLES );

		return is_array( $roles ) && ! empty( $roles ) ? array_values( array_map( 'strval', $roles ) ) : self::DEFAULT_ROLES;
	}

	/**
	 * Whether a user opens the space.
	 *
	 * @param WP_User|null $user User; the current one when null.
	 * @return bool
	 */
	public static function can( ?WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();

		if ( ! $user instanceof WP_User || ! $user->exists() || '' === (string) $user->user_email ) {
			return false;
		}

		$allowed = ( is_multisite() && is_super_admin( $user->ID ) )
			|| ! empty( array_intersect( (array) $user->roles, self::roles() ) );

		/**
		 * Filters whether a user opens the CINQ space.
		 *
		 * @param bool    $allowed Decision from the allowed roles.
		 * @param WP_User $user    User.
		 */
		return (bool) apply_filters( 'cinq_central_user_can', $allowed, $user );
	}

	/**
	 * Whether the current user may change the connection and the roles.
	 *
	 * @return bool
	 */
	public static function can_configure(): bool {
		return current_user_can( 'manage_options' );
	}
}
