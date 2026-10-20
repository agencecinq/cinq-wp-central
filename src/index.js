/**
 * The CINQ space inside wp-admin.
 *
 * One app, five screens — one per submenu, like the web space's navigation.
 * Details open through the URL hash inside the screen that loaded them.
 */
import { createRoot } from '@wordpress/element';
import { useRoute } from './routeur';
import { Accueil } from './vues/accueil';
import { Projet } from './vues/projet';
import { Roadmap } from './vues/roadmap';
import { Demandes, Demande, NouvelleDemande } from './vues/demandes';
import { Factures } from './vues/factures';
import { Ressources } from './vues/ressources';
import './style.scss';

const UUID = '([0-9a-fA-F-]{36})';

function App() {
	const { chemin, params } = useRoute();
	const vue = window.cinqWpCentral?.vue ?? 'accueil';
	let m;

	if ( ( m = chemin.match( new RegExp( `^/projets/${ UUID }$` ) ) ) ) {
		return <Projet id={ m[ 1 ] } />;
	}
	if ( chemin === '/demandes/nouvelle' ) {
		return <NouvelleDemande params={ params } />;
	}
	if ( ( m = chemin.match( new RegExp( `^/demandes/${ UUID }$` ) ) ) ) {
		return <Demande id={ m[ 1 ] } />;
	}

	switch ( vue ) {
		case 'roadmap': {
			const carte = chemin.match( new RegExp( `^/roadmap/${ UUID }$` ) );
			return <Roadmap carteId={ carte?.[ 1 ] } />;
		}
		case 'demandes':
			return <Demandes />;
		case 'factures':
			return <Factures />;
		case 'ressources':
			return <Ressources />;
		default:
			return <Accueil />;
	}
}

const racine = document.getElementById( 'cinq-wp-central-app' );
if ( racine ) {
	createRoot( racine ).render( <App /> );
}
