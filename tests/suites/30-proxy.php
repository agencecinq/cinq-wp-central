<?php
/**
 * The REST routes relayed to Central.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

use CinqWpCentral\Rest\Proxy;

( new Proxy() )->register();

section( 'A list, not a pass-through' );

$routes = Proxy::routes();
check( 'no route forwards an arbitrary path', array() === array_filter( $routes, static fn ( $r ) => str_contains( $r[1], '.*' ) || str_contains( $r[1], '(?P<path>' ) ) );
check( 'every {id} in a Central path comes from a checked route parameter', array() === array_filter( $routes, static fn ( $r ) => str_contains( $r[2], '{id}' ) !== str_contains( $r[1], '(?P<id>' ) ) );
check( 'the widget key is not exposed to the browser', array() === array_filter( $routes, static fn ( $r ) => str_contains( $r[2], 'widget' ) ) );
check( 'no route can disconnect the site from the browser', array() === array_filter( $routes, static fn ( $r ) => str_contains( $r[2], 'connexion' ) || str_contains( $r[2], 'appairer' ) ) );
check( 'every registered route has a permission check', array() === array_filter( World::$routes, static fn ( $r ) => empty( $r['permission_callback'] ) ) );

section( 'Identifiers are UUIDs' );

$pattern = '#^' . str_replace( '(?P<id>', '(?<id>', $routes[3][1] ) . '$#';
check( 'a UUID matches', 1 === preg_match( $pattern, '/projets/5c66c168-ff06-4bef-9439-558a99e51e70' ) );
check( 'a path traversal does not', 0 === preg_match( $pattern, '/projets/../../appairer' ) );
check( 'a free string does not', 0 === preg_match( $pattern, '/projets/abc' ) );

section( 'Only allowed people' );

World::$user = new WP_User( 3, 'abonne@x.fr', 'A', 'a', array( 'subscriber' ) );
$r = Proxy::permission();
check( 'a subscriber is refused, with a reason', is_wp_error( $r ) && 403 === $r->get_error_data()['status'] );
World::$user = new WP_User( 1, 'lea@boutique.fr', 'Léa', 'lea', array( 'administrator' ) );
check( 'an administrator passes', true === Proxy::permission() );
