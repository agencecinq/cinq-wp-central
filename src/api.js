/**
 * Calls the plugin's REST routes, which relay to Central from the site's PHP.
 *
 * The browser never sees Central's address nor the site's secret: it talks to
 * WordPress (cookie + REST nonce, set up by core's api-fetch), WordPress talks
 * to Central.
 */
import apiFetch from '@wordpress/api-fetch';

const ns = () => window.cinqWpCentral?.namespace ?? 'cinq-wp-central/v1';

export function lire( chemin ) {
	return apiFetch( { path: `/${ ns() }${ chemin }` } );
}

export function ecrire( chemin, data ) {
	return apiFetch( { path: `/${ ns() }${ chemin }`, method: 'POST', data } );
}

export function envoyerFichier( demandeId, fichier ) {
	const body = new window.FormData();
	body.append( 'file', fichier );
	return apiFetch( {
		path: `/${ ns() }/demandes/${ demandeId }/pieces`,
		method: 'POST',
		body,
	} );
}
