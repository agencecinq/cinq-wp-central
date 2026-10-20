/**
 * A hash router: details (a project, a request, a card) open inside the
 * screen the submenu loaded, and the browser's back button works.
 */
import { useEffect, useState } from '@wordpress/element';

function lireHash() {
	const brut = window.location.hash.replace( /^#/, '' ) || '/';
	const [ chemin, requete = '' ] = brut.split( '?' );
	return { chemin, params: new URLSearchParams( requete ) };
}

export function useRoute() {
	const [ route, setRoute ] = useState( lireHash );
	useEffect( () => {
		const ecoute = () => {
			setRoute( lireHash() );
			window.scrollTo( 0, 0 );
		};
		window.addEventListener( 'hashchange', ecoute );
		return () => window.removeEventListener( 'hashchange', ecoute );
	}, [] );
	return route;
}

export function aller( chemin ) {
	window.location.hash = chemin;
}

/**
 * Link to another screen of the space (another submenu).
 */
export function urlVue( vue, chemin = '' ) {
	const base = window.cinqCentral?.urls?.[ vue ] ?? '';
	return chemin ? `${ base }#${ chemin }` : base;
}
