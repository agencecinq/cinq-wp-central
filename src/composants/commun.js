import { useCallback, useEffect, useState } from '@wordpress/element';
import { Notice, Spinner } from '@wordpress/components';
import { lire } from '../api';
import { TON } from '../format';

/**
 * Loads a Central route; `recharger` reads it again after a write.
 */
export function useLecture( chemin ) {
	const [ etat, setEtat ] = useState( {
		donnees: null,
		erreur: null,
		charge: true,
	} );
	const recharger = useCallback( () => {
		setEtat( ( e ) => ( { ...e, charge: true } ) );
		return lire( chemin ).then(
			( donnees ) => setEtat( { donnees, erreur: null, charge: false } ),
			( erreur ) => setEtat( { donnees: null, erreur, charge: false } )
		);
	}, [ chemin ] );
	useEffect( () => {
		recharger();
	}, [ recharger ] );
	return { ...etat, recharger };
}

export function Chargement() {
	return (
		<p className="cinq-chargement">
			<Spinner /> Chargement…
		</p>
	);
}

/**
 * Central's refusals are written to be read: they are shown as they come.
 */
export function Erreur( { erreur } ) {
	if ( ! erreur ) {
		return null;
	}
	const deconnecte = erreur.code === 'cinq_wp_central_deconnecte';
	const reglages = window.cinqWpCentral?.reglages;
	return (
		<Notice status="error" isDismissible={ false }>
			{ erreur.message ?? 'Une erreur est survenue.' }
			{ deconnecte && reglages && (
				<>
					{ ' ' }
					<a href={ reglages }>Reconnecter le site</a>
				</>
			) }
		</Notice>
	);
}

/**
 * Wraps a screen: loading, error, then content.
 */
export function Ecran( { lecture, children } ) {
	if ( lecture.charge && ! lecture.donnees ) {
		return <Chargement />;
	}
	if ( lecture.erreur ) {
		return <Erreur erreur={ lecture.erreur } />;
	}
	return children( lecture.donnees );
}

export function Pastille( { valeur, libelle } ) {
	return (
		<span className={ `cinq-pastille is-${ TON[ valeur ] ?? 'neutre' }` }>
			{ libelle }
		</span>
	);
}

export function Avancement( { ratio, libelle } ) {
	const pct = Math.round( ( Number( ratio ) || 0 ) * 100 );
	return (
		<span className="cinq-avancement" title={ libelle ?? `${ pct } %` }>
			<span className="cinq-avancement__barre">
				<span style={ { width: `${ pct }%` } } />
			</span>
			<span className="cinq-avancement__texte">
				{ libelle ?? `${ pct } %` }
			</span>
		</span>
	);
}

export function EnTete( { titre, action, retour } ) {
	return (
		<>
			{ retour && (
				<p className="cinq-retour">
					<a href={ retour.href }>← { retour.libelle }</a>
				</p>
			) }
			<h1 className="wp-heading-inline">{ titre }</h1>
			{ action }
			<hr className="wp-header-end" />
		</>
	);
}

export function Vide( { children } ) {
	return <p className="description">{ children }</p>;
}
