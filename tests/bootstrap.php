<?php
/**
 * Test harness bootstrap: WordPress stubs and assertions.
 *
 * No WordPress, no database: the plugin's logic runs against an in-memory
 * world (options, transients, users, HTTP) that each suite resets.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'CINQ_WP_CENTRAL_PATH', dirname( __DIR__ ) . '/' );

require_once __DIR__ . '/stubs.php';

$GLOBALS['cinq_passed'] = 0;
$GLOBALS['cinq_failed'] = array();

/**
 * Asserts a condition.
 *
 * @param string $label     What is being checked.
 * @param bool   $condition Result.
 * @param string $detail    Extra context shown on failure.
 * @return void
 */
function check( string $label, bool $condition, string $detail = '' ): void {
	if ( $condition ) {
		++$GLOBALS['cinq_passed'];
		echo "  ok    {$label}\n";
		return;
	}

	$GLOBALS['cinq_failed'][] = $label;
	echo "  FAIL  {$label}" . ( '' !== $detail ? " — {$detail}" : '' ) . "\n";
}

/**
 * Prints a section title.
 *
 * @param string $title Title.
 * @return void
 */
function section( string $title ): void {
	echo "\n{$title}\n";
}

require_once CINQ_WP_CENTRAL_PATH . 'includes/autoload.php';

// The constants the main plugin file defines.
eval( 'namespace CinqWpCentral; const VERSION = "0.0.0-test"; const PLUGIN_FILE = "' . addslashes( CINQ_WP_CENTRAL_PATH . 'cinq-wp-central.php' ) . '";' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test bootstrap only.
