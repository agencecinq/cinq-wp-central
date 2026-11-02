import { Card, CardBody, CardHeader, Notice } from '@wordpress/components';
import {
	useLecture,
	Ecran,
	EnTete,
	Pastille,
	Vide,
} from '../composants/commun';
import {
	duree,
	jourHeure,
	jourMois,
	jourMoisAnnee,
	pourcentDispo,
} from '../format';
import { urlVue } from '../routeur';

/**
 * The monitoring of the client's sites: availability, deadlines, copies —
 * the background work they never see (Central's « La surveillance de votre
 * site », `espace-client/_components/disponibilite.tsx`, same words).
 *
 * It shows even when all is well: « vérifié en continu, aucune
 * interruption » is exactly what they should read. An interruption in
 * progress says the team knows: the client sees the site down anyway, and
 * it is the one sentence that spares them writing to tell us.
 */
export function Veille() {
	const lecture = useLecture( '/veille' );
	return (
		<Ecran lecture={ lecture }>
			{ ( { sites } ) => (
				<>
					<EnTete titre="Surveillance" />
					{ sites.length === 0 ? (
						<Vide>Aucun site n’est surveillé pour l’instant.</Vide>
					) : (
						<p className="cinq-lede">
							Ce que nous vérifions en continu : la disponibilité,
							les échéances et les copies de{ ' ' }
							{ sites.length > 1 ? 'vos sites' : 'votre site' }.
						</p>
					) }
					{ sites.map( ( s ) => (
						<Site key={ s.domaine } site={ s } />
					) ) }
				</>
			) }
		</Ecran>
	);
}

function Site( { site } ) {
	const d = site.disponibilite;
	const enCours = enPanne( site );
	return (
		<Card className="cinq-carte-bloc cinq-veille">
			<CardHeader>
				<h2>{ site.domaine }</h2>
				<span className="description">{ periode( d ) }</span>
			</CardHeader>
			<CardBody>
				{ enCours ? (
					<Notice status="error" isDismissible={ false }>
						{ site.domaine } rencontre une interruption en ce
						moment. Notre équipe est prévenue et s’en occupe.
					</Notice>
				) : (
					<p>
						{ site.domaine } est vérifié en continu.
						{ d.incidents.length === 0 &&
							' Aucune interruption sur la période.' }
					</p>
				) }
				{ d.reponseMoyenneMs !== null && (
					<p className="description">
						Temps de réponse moyen :{ ' ' }
						{ ( d.reponseMoyenneMs / 1000 ).toLocaleString(
							'fr-FR',
							{ maximumFractionDigits: 2 }
						) }{ ' ' }
						s.
					</p>
				) }

				{ d.incidents.length > 0 && (
					<>
						<h3>Interruptions</h3>
						<table className="wp-list-table widefat striped">
							<thead>
								<tr>
									<th>Début</th>
									<th>Durée</th>
									<th>Cause</th>
								</tr>
							</thead>
							<tbody>
								{ d.incidents.map( ( i ) => (
									<tr key={ i.debut }>
										<td>{ jourHeure( i.debut ) }</td>
										<td>
											{ i.fin ? (
												duree( i.dureeMinutes )
											) : (
												<Pastille
													valeur="attention"
													libelle="en cours"
												/>
											) }
										</td>
										<td>{ i.cause }</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</>
				) }

				{ site.echeances.length > 0 && (
					<>
						<h3>Échéances</h3>
						<table className="wp-list-table widefat striped">
							<thead>
								<tr>
									<th>Échéance</th>
									<th>Expire le</th>
									<th>Délai</th>
								</tr>
							</thead>
							<tbody>
								{ site.echeances.map( ( e ) => (
									<tr key={ e.libelle }>
										<td>{ e.libelle }</td>
										<td>{ jourMoisAnnee( e.expireLe ) }</td>
										<td>
											<Pastille
												valeur={ e.ton }
												libelle={ delai( e ) }
											/>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</>
				) }

				{ site.copies.actif && <Copies copies={ site.copies } /> }
			</CardBody>
		</Card>
	);
}

function Copies( { copies } ) {
	return (
		<>
			<h3>Copies de votre boutique</h3>
			<p>
				{ copies.variantesSurveillees > 0
					? `${ copies.variantesSurveillees.toLocaleString(
							'fr-FR'
					  ) } variantes de votre adresse sont surveillées`
					: 'La recherche de copies de votre boutique démarre' }
				{ copies.dernierScanLe &&
					`, dernière recherche le ${ jourMois(
						copies.dernierScanLe
					) }` }
				{ copies.suspects.length === 0
					? '. Aucune copie repérée.'
					: '.' }
			</p>
			{ copies.suspects.length > 0 && (
				<table className="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Adresse</th>
							<th>Nature</th>
							<th>Repérée le</th>
							<th>État</th>
						</tr>
					</thead>
					<tbody>
						{ copies.suspects.map( ( c ) => (
							<tr key={ c.domaine }>
								<td>{ c.domaine }</td>
								<td>{ c.nature }</td>
								<td>{ jourMois( c.detecteLe ) }</td>
								<td>
									<Pastille
										valeur={
											c.ferme ? 'valide' : 'attention'
										}
										libelle={ c.etat }
									/>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</>
	);
}

/**
 * The home's summary: one line per site, and only what needs a look
 * flagged. Silent while loading, on error and with no site — a Central that
 * does not serve `/veille` yet must not put an error on the home.
 */
export function ResumeVeille() {
	const { donnees } = useLecture( '/veille' );
	const sites = donnees?.sites ?? [];
	if ( sites.length === 0 ) {
		return null;
	}
	return (
		<Card className="cinq-carte-bloc">
			<CardHeader>
				<h2>
					{ sites.length > 1
						? 'La surveillance de vos sites'
						: 'La surveillance de votre site' }
				</h2>
				<a href={ urlVue( 'veille' ) }>Voir le détail</a>
			</CardHeader>
			<CardBody>
				<ul className="cinq-liste">
					{ sites.map( ( s ) => (
						<li key={ s.domaine }>
							<strong>{ s.domaine }</strong>{ ' ' }
							<span className="description">
								· { periode( s.disponibilite ) }
							</span>
							{ signaux( s ).map( ( [ ton, libelle ] ) => (
								<span key={ libelle }>
									{ ' ' }
									<Pastille
										valeur={ ton }
										libelle={ libelle }
									/>
								</span>
							) ) }
						</li>
					) ) }
				</ul>
			</CardBody>
		</Card>
	);
}

/** What needs a look, as [tone, label] pairs; nothing when all is well. */
function signaux( site ) {
	const liste = [];
	if ( enPanne( site ) ) {
		liste.push( [ 'bloquant', 'Interruption en cours' ] );
	}
	for ( const e of site.echeances ) {
		if ( e.ton !== 'valide' ) {
			liste.push( [ e.ton, `${ e.libelle } : ${ delai( e ) }` ] );
		}
	}
	const ouvertes = site.copies.actif
		? site.copies.suspects.filter( ( c ) => ! c.ferme ).length
		: 0;
	if ( ouvertes > 0 ) {
		liste.push( [
			'attention',
			`${ ouvertes } copie${ ouvertes > 1 ? 's' : '' } repérée${
				ouvertes > 1 ? 's' : ''
			}`,
		] );
	}
	return liste;
}

function enPanne( site ) {
	return (
		site.disponibilite.enPanne ||
		site.disponibilite.incidents.some( ( i ) => ! i.fin )
	);
}

function delai( echeance ) {
	return echeance.joursRestants < 0
		? 'expiré'
		: `dans ${ echeance.joursRestants } j`;
}

/**
 * « disponible 99,96 % sur 30 jours » — or fewer days when the monitoring
 * started less than thirty days ago, since the rate covers no more.
 */
function periode( d ) {
	if ( d.taux === null ) {
		return 'surveillance démarrée';
	}
	const jours = Math.round(
		( Date.now() - new Date( d.surveilleDepuis ).getTime() ) / 86400000
	);
	const sur =
		jours >= 30
			? '30 jours'
			: `${ Math.max( 1, jours ) } jour${ jours > 1 ? 's' : '' }`;
	return `disponible ${ pourcentDispo( d.taux ) } sur ${ sur }`;
}
