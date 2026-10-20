<?php
/**
 * Updates from GitHub releases.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

use CinqWpCentral\Update\GitHubUpdater;

section( 'Reading a release' );

$release = GitHubUpdater::parse(
	(string) json_encode(
		array(
			'tag_name' => 'v0.2.0',
			'html_url' => 'https://github.com/agencecinq/cinq-wp-central/releases/tag/v0.2.0',
			'body'     => 'Changes.',
			'assets'   => array(
				array( 'name' => 'cinq-wp-central.zip', 'browser_download_url' => 'https://github.com/agencecinq/cinq-wp-central/releases/download/v0.2.0/cinq-wp-central.zip' ),
			),
		)
	)
);

check( 'the version drops the v', '0.2.0' === ( $release['version'] ?? null ) );
check( 'the package is the attached zip', str_ends_with( (string) ( $release['package'] ?? '' ), '/cinq-wp-central.zip' ) );
check( 'a release without the zip is ignored', null === GitHubUpdater::parse( (string) json_encode( array( 'tag_name' => 'v0.2.0', 'assets' => array() ) ) ) );
check( 'a prerelease is ignored', null === GitHubUpdater::parse( (string) json_encode( array( 'tag_name' => 'v1.0.0-beta', 'prerelease' => true, 'assets' => array( array( 'name' => 'cinq-wp-central.zip', 'browser_download_url' => 'x' ) ) ) ) ) );
check( 'garbage is ignored', null === GitHubUpdater::parse( 'rate limited' ) );

section( 'Reading the release manifest' );

$manifest = static fn( array $data ): ?array => GitHubUpdater::parse_manifest( (string) json_encode( $data ) );
$package  = 'https://github.com/agencecinq/cinq-wp-central/releases/download/v0.2.0/cinq-wp-central.zip';
$release  = $manifest( array( 'version' => 'v0.2.0', 'package' => $package, 'requires' => '6.6', 'requires_php' => '8.1' ) );

check( 'the manifest gives the version', '0.2.0' === ( $release['version'] ?? null ) );
check( 'and the zip of that release', $package === ( $release['package'] ?? null ) );
check( 'and the requirements', '6.6' === ( $release['requires'] ?? null ) && '8.1' === ( $release['requires_php'] ?? null ) );
check( 'a zip from anywhere else is refused', null === $manifest( array( 'version' => '9.9.9', 'package' => 'https://evil.example/cinq-wp-central.zip' ) ) );
check( 'a zip from another repository is refused', null === $manifest( array( 'version' => '9.9.9', 'package' => 'https://github.com/someone/else/releases/download/v9/cinq-wp-central.zip' ) ) );
check( 'a manifest without a version is ignored', null === $manifest( array( 'package' => $package ) ) );
check( 'a missing manifest is ignored', null === GitHubUpdater::parse_manifest( '' ) );
check( 'garbage is refused', null === GitHubUpdater::parse_manifest( 'not json' ) );
