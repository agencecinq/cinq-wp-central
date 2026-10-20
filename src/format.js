/**
 * Formatting and labels. The labels are Central's own words
 * (`domain/*` in Central): the client reads the same thing here and there.
 */
const dateFr = new Intl.DateTimeFormat( 'fr-FR', {
	day: 'numeric',
	month: 'short',
	year: 'numeric',
} );
const dateHeureFr = new Intl.DateTimeFormat( 'fr-FR', {
	day: 'numeric',
	month: 'short',
	hour: '2-digit',
	minute: '2-digit',
} );
const eurosFr = new Intl.NumberFormat( 'fr-FR', {
	style: 'currency',
	currency: 'EUR',
	maximumFractionDigits: 0,
} );

export const date = ( iso ) => ( iso ? dateFr.format( new Date( iso ) ) : '—' );
export const dateHeure = ( iso ) =>
	iso ? dateHeureFr.format( new Date( iso ) ) : '—';
export const euros = ( n ) => eurosFr.format( Number( n ) || 0 );

export function mois( cle ) {
	if ( ! cle ) {
		return 'Sans date';
	}
	const [ a, m ] = cle.split( '-' ).map( Number );
	return new Intl.DateTimeFormat( 'fr-FR', {
		month: 'long',
		year: 'numeric',
	} ).format( new Date( Date.UTC( a, m - 1, 1 ) ) );
}

export const ETAT_DEMANDE = {
	ouverte: 'Reçue',
	en_cours: 'En cours',
	reglee: 'Réglée',
};
export const IMPACT = {
	bloquant: 'Bloquant',
	genant: 'Gênant',
	mineur: 'À corriger un jour',
};
export const STATUT_PROJET = {
	en_attente: 'En attente',
	en_cours: 'En cours',
	termine: 'Terminé',
	archive: 'Archivé',
};
export const STATUT_ETAPE = {
	a_venir: 'À venir',
	en_cours: 'En cours',
	terminee: 'Terminée',
	bloquee: 'Bloquée',
};
export const ETAT_CARTE = {
	a_venir: 'À venir',
	en_cours: 'En cours',
	livre: 'Livré',
};
export const ETAT_ECHEANCE = {
	reglee: 'Réglée',
	envoyee: 'Envoyée',
	en_retard: 'En retard',
	a_venir: 'À venir',
};
export const STATUT_ACCES = {
	a_demander: 'À demander',
	demande: 'Demandé',
	recu: 'Reçu',
	sans_objet: 'Sans objet',
};
export const FONCTION = {
	chef_projet: 'Chef de projet',
	dev_referent: 'Développeur référent',
};

/** Tone of a pill, mapped onto WordPress's own notice colours. */
export const TON = {
	reglee: 'succes',
	livre: 'succes',
	terminee: 'succes',
	validee: 'succes',
	recu: 'succes',
	en_cours: 'info',
	envoyee: 'info',
	en_retard: 'erreur',
	bloquant: 'erreur',
	bloquee: 'erreur',
	genant: 'alerte',
	en_attente: 'alerte',
	demande: 'alerte',
};
