<?php
/**
 * PSR-4 style autoloader for the CinqCentral namespace.
 *
 * The plugin ships without a Composer vendor directory so it can be zipped and
 * installed like any other WordPress plugin.
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

namespace CinqCentral;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = CINQ_CENTRAL_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
