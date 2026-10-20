<?php
/**
 * Talking to Central.
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

use CinqCentral\Central\Client;
use CinqCentral\Central\Connection;

$lea = new WP_User( 7, 'lea@boutique.fr', 'Léa Durand', 'lea', array( 'administrator' ) );

section( 'The person travels in a header Central can read' );

$header = Client::actor( $lea );
$json   = json_decode( base64_decode( strtr( $header, '-_', '+/' ) ), true );
check( 'base64url, without padding', ! str_contains( $header, '=' ) && ! str_contains( $header, '+' ) && ! str_contains( $header, '/' ) );
check( 'accents survive', 'Léa Durand' === ( $json['nom'] ?? null ), (string) ( $json['nom'] ?? '' ) );
check( 'e-mail and id are sent', 'lea@boutique.fr' === $json['email'] && 7 === $json['id'] );
$sans_nom = new WP_User( 8, 'x@boutique.fr', ' ', 'xavier', array( 'administrator' ) );
check( 'the login stands in for an empty display name', 'xavier' === json_decode( base64_decode( strtr( Client::actor( $sans_nom ), '-_', '+/' ) ), true )['nom'] );

section( 'Nothing is called before pairing' );

$r = Client::request( 'GET', '/moi', null, $lea );
check( 'a call without connection is refused locally', is_wp_error( $r ) && 'cinq_central_deconnecte' === $r->get_error_code() );
check( 'and Central was not called', array() === World::$requests );

section( 'Pairing' );

World::answer( array( 200, array( 'ok' => true, 'siteId' => 's1', 'secret' => 'cinq_site_abc', 'client' => 'Boutique', 'domaine' => 'boutique.fr' ) ) );
$r   = Client::pair( 'CINQ-ABCD-EFGH' );
$req = World::$requests[0];
$sent = json_decode( $req['body'], true );
check( 'the code goes to /appairer', str_ends_with( $req['url'], '/api/connect/v1/appairer' ), $req['url'] );
check( 'as a WordPress site, with its address', 'wordpress' === $sent['plateforme'] && 'https://boutique.fr' === $sent['domaine'] );
check( 'without a secret — there is none yet', ! isset( $req['headers']['Authorization'] ) );
check( 'the secret is stored', 'cinq_site_abc' === ( Connection::get()['secret'] ?? null ) );
check( 'Central defaults to app.cinqteam.com', 'https://app.cinqteam.com' === Connection::base_url() );

section( 'Every call carries the secret and the person' );

World::$requests = array();
World::answer( array( 200, array( 'acteur' => array( 'nom' => 'Léa' ) ) ) );
Client::request( 'GET', '/moi', null, $lea );
$h = World::$requests[0]['headers'];
check( 'Bearer secret', 'Bearer cinq_site_abc' === $h['Authorization'] );
check( 'X-Cinq-Acteur', $header === $h['X-Cinq-Acteur'] );
check( 'X-Cinq-Version', str_starts_with( $h['X-Cinq-Version'], 'wp/' ) );

section( 'Refusals reach the screen as written' );

World::answer( array( 422, array( 'erreur' => 'Projet introuvable.' ) ) );
$r = Client::request( 'GET', '/projets/x', null, $lea );
check( 'a refusal keeps its message', is_wp_error( $r ) && 'Projet introuvable.' === $r->get_error_message() );
check( 'and its status', 422 === ( $r->get_error_data()['status'] ?? 0 ) );

World::answer( array( 401, array( 'erreur' => 'Connexion à Central invalide.' ) ) );
$r = Client::request( 'GET', '/moi', null, $lea );
check( 'a revoked secret reads as "disconnected", not as a WordPress login problem', 'cinq_central_deconnecte' === $r->get_error_code() && 409 === $r->get_error_data()['status'] );

World::answer( new WP_Error( 'http_request_failed', 'cURL error 28' ) );
$r = Client::request( 'GET', '/moi', null, $lea );
check( 'an unreachable Central is a 502 with a readable message', 502 === $r->get_error_data()['status'] && str_contains( $r->get_error_message(), 'Central ne répond pas' ) );

section( 'Disconnecting' );

World::$requests = array();
World::answer( array( 200, array( 'ok' => true ) ) );
Client::disconnect();
check( 'Central is told', 'DELETE' === World::$requests[0]['method'] && str_ends_with( World::$requests[0]['url'], '/connexion' ) );
check( 'the secret is forgotten', null === Connection::get() );

World::answer( new WP_Error( 'http_request_failed', 'down' ) );
Connection::save( array( 'siteId' => 's2', 'secret' => 'cinq_site_def' ) );
Client::disconnect();
check( 'even when Central cannot be reached', null === Connection::get() );
