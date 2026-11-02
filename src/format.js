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

const jourMoisFr = new Intl.DateTimeFormat( 'fr-FR', {
	day: 'numeric',
	month: 'long',
} );
const jourMoisAnneeFr = new Intl.DateTimeFormat( 'fr-FR', {
	day: 'numeric',
	month: 'long',
	year: 'numeric',
} );
const jourHeureFr = new Intl.DateTimeFormat( 'fr-FR', {
	day: 'numeric',
	month: 'long',
	hour: '2-digit',
	minute: '2-digit',
} );
const centiemesFr = new Intl.NumberFormat( 'fr-FR', {
	maximumFractionDigits: 2,
} );

export const date = ( iso ) => ( iso ? dateFr.format( new Date( iso ) ) : '—' );
export const dateHeure = ( iso ) =>
	iso ? dateHeureFr.format( new Date( iso ) ) : '—';
export const euros = ( n ) => eurosFr.format( Number( n ) || 0 );

/** « 1 novembre » — an instant, in a sentence. */
export const jourMois = ( iso ) =>
	iso ? jourMoisFr.format( new Date( iso ) ) : '—';

/** « 20 octobre à 15:05 » — when an interruption started. */
export const jourHeure = ( iso ) =>
	iso ? jourHeureFr.format( new Date( iso ) ) : '—';

/**
 * « 14 janvier 2027 » from a calendar day (`YYYY-MM-DD`), read at noon UTC
 * so no time zone moves it to the day before.
 */
export const jourMoisAnnee = ( jour ) =>
	jour ? jourMoisAnneeFr.format( new Date( `${ jour }T12:00:00Z` ) ) : '—';

/**
 * An availability rate: « 99,96 % ». Truncated, never rounded up — a site
 * down twelve minutes in the month would otherwise read « 100 % », and the
 * figure would contradict the interruptions listed under it (Central's
 * `pourcentDispo`). The epsilon only absorbs floating-point noise
 * (0.57 × 10 000 = 5 699,999…).
 */
export function pourcentDispo( taux ) {
	const centiemes = Math.floor( Number( taux ) * 10000 + 1e-6 ) / 100;
	return `${ centiemesFr.format( centiemes ) } %`;
}

/**
 * A duration, from minutes: « 12 min », « 1 h 05 », « 2 j 3 h » (Central's
 * `formatDuree`).
 */
export function duree( minutesBrutes ) {
	const minutes = Math.max( 1, Math.ceil( Number( minutesBrutes ) || 0 ) );
	if ( minutes < 60 ) {
		return `${ minutes } min`;
	}
	const heures = Math.floor( minutes / 60 );
	const reste = minutes % 60;
	if ( heures < 24 ) {
		return reste
			? `${ heures } h ${ String( reste ).padStart( 2, '0' ) }`
			: `${ heures } h`;
	}
	const jours = Math.floor( heures / 24 );
	const h = heures % 24;
	return h ? `${ jours } j ${ h } h` : `${ jours } j`;
}

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
	valide: 'succes',
	attention: 'alerte',
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
