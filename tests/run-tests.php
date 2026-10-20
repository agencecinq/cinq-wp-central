<?php
/**
 * Standalone test harness.
 *
 * Every file in tests/suites/ is a suite; each starts from a clean world.
 *
 * Usage: php tests/run-tests.php [suite name filter]
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

require_once __DIR__ . '/bootstrap.php';

$filter = $argv[1] ?? '';
$suites = glob( __DIR__ . '/suites/*.php' );

sort( $suites );

foreach ( $suites as $suite ) {
	if ( '' !== $filter && ! str_contains( basename( $suite ), $filter ) ) {
		continue;
	}

	echo "\n=== " . basename( $suite, '.php' ) . " ===\n";
	World::reset();
	require $suite;
}

$failed = $GLOBALS['cinq_failed'];

echo "\n" . sprintf( "%d passed, %d failed\n", $GLOBALS['cinq_passed'], count( $failed ) );

if ( ! empty( $failed ) ) {
	echo "Failures:\n - " . implode( "\n - ", $failed ) . "\n";
	exit( 1 );
}

exit( 0 );
