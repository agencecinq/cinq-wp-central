<?php
/**
 * Updates from GitHub.
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

use CinqCentral\Update\GitHubUpdater;

section( 'Only this repository’s releases are installed' );

$ok = GitHubUpdater::parse_manifest( json_encode( array( 'version' => 'v0.2.0', 'package' => 'https://github.com/agencecinq/wp-central/releases/download/v0.2.0/cinq-central.zip' ) ) );
check( 'its own manifest is read', '0.2.0' === ( $ok['version'] ?? null ) );
$bad = GitHubUpdater::parse_manifest( json_encode( array( 'version' => '9.9.9', 'package' => 'https://evil.example/cinq-central.zip' ) ) );
check( 'a package from elsewhere is refused', null === $bad );
check( 'garbage is refused', null === GitHubUpdater::parse_manifest( 'not json' ) );
