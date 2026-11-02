<?php
/**
 * La Veille: the monitoring of the client's sites.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

use CinqWpCentral\Admin\AppPage;
use CinqWpCentral\Central\Cache;
use CinqWpCentral\Central\Connection;
use CinqWpCentral\Central\Space;
use CinqWpCentral\Rest\Proxy;

// The example of Central's contract (docs/api-connect.md, « La Veille »).
$veille = json_decode( (string) file_get_contents( __DIR__ . '/../fixtures/veille.json' ), true );
$lea    = new WP_User( 7, 'lea@boutique.fr', 'Léa Durand', 'lea', array( 'administrator' ) );
$entry  = AppPage::SLUG . '>' . AppPage::views()['veille'][0];

section( 'The browser reads /veille through the plugin' );

check( 'GET /veille is relayed to Central’s /veille', in_array( array( 'GET', '/veille', '/veille' ), Proxy::routes(), true ) );
check( 'and nothing else is written there', array() === array_filter( Proxy::routes(), static fn ( $r ) => str_contains( $r[2], 'veille' ) && 'GET' !== $r[0] ) );

section( 'Nothing is asked before pairing' );

check( 'not monitored while the site is not paired', ! Space::monitored( $lea ) );
check( 'and Central was not called', array() === World::$requests );

Connection::save( array( 'siteId' => 's1', 'secret' => 'cinq_site_abc', 'client' => 'Lomi', 'domaine' => 'lomi.fr' ) );

section( 'A monitored site' );

World::answer( array( 200, $veille ) );
check( 'the fixture is read as monitored', Space::monitored( $lea ) );
$req = World::$requests[0] ?? array();
check( 'from Central’s /veille', str_ends_with( (string) ( $req['url'] ?? '' ), '/api/connect/v1/veille' ), (string) ( $req['url'] ?? '' ) );
check( 'with the secret and the person', 'Bearer cinq_site_abc' === ( $req['headers']['Authorization'] ?? '' ) && isset( $req['headers']['X-Cinq-Acteur'] ) );
check( 'within the short timeout of what every admin page shows', 4 === ( $req['timeout'] ?? 0 ) );
check( 'the answer is kept as Central wrote it', 'lomi.fr' === ( Space::veille( $lea )['sites'][0]['domaine'] ?? null ) );
check( 'and read from the cache afterwards, for everyone on the site', 1 === count( World::$requests ) && Space::monitored( new WP_User( 9, 'max@lomi.fr', 'Max', 'max', array( 'administrator' ) ) ) && 1 === count( World::$requests ) );

section( 'No monitored site, or a Central that does not serve /veille yet' );

Cache::flush();
World::answer( array( 200, array( 'sites' => array() ) ) );
check( 'an empty list is not monitored', ! Space::monitored( $lea ) );

Cache::flush();
World::$requests = array();
World::answer( array( 404, array() ) );
check( 'a 404 is not monitored', ! Space::monitored( $lea ) );
check( 'and the failure is cached: one call, not one per page', ! Space::monitored( $lea ) && 1 === count( World::$requests ) );

Cache::flush();
World::answer( array( 200, array( 'sites' => 'lomi.fr' ) ) );
check( 'an unexpected shape is not monitored', ! Space::monitored( $lea ) );

section( 'The « Surveillance » entry follows the monitoring' );

World::$user = $lea;

Cache::flush();
World::answer( array( 200, array( 'comptes' => array( 'attendus' => 0 ) ) ) );
World::answer( array( 200, $veille ) );
( new AppPage() )->menu();
check( 'shown when a site is monitored', 'Surveillance' === ( World::$menu[ $entry ] ?? null ) );
check( 'next to the other screens', isset( World::$menu[ AppPage::SLUG . '>' . AppPage::SLUG . '-ressources' ] ) );

Cache::flush();
World::$menu = array();
World::answer( array( 200, array( 'comptes' => array( 'attendus' => 0 ) ) ) );
World::answer( array( 200, array( 'sites' => array() ) ) );
( new AppPage() )->menu();
check( 'left out of the menu when none is', ! isset( World::$menu[ $entry ] ) );
check( 'the other screens stay', isset( World::$menu[ AppPage::SLUG . '>' . AppPage::SLUG . '-ressources' ] ) );
check( 'the page keeps its URL, for the home’s link', str_ends_with( AppPage::url( 'veille' ), 'admin.php?page=cinq-wp-central-surveillance' ) );
