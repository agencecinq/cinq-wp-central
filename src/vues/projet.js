import { Card, CardBody, CardHeader, TabPanel } from '@wordpress/components';
import {
	useLecture,
	Ecran,
	EnTete,
	Pastille,
	Vide,
} from '../composants/commun';
import { BoutonValiderPorte } from '../composants/valider-porte';
import {
	date,
	euros,
	ETAT_DEMANDE,
	ETAT_ECHEANCE,
	FONCTION,
	IMPACT,
	STATUT_ACCES,
	STATUT_ETAPE,
	STATUT_PROJET,
} from '../format';
import { urlVue } from '../routeur';

/**
 * A project, in the web space's tabs: where it stands, its documents, what we
 * expect from the client, its requests, its invoicing. The people in charge
 * are named: a space without faces sends the client back to whatever e-mail
 * was at hand.
 */
export function Projet( { id } ) {
	const lecture = useLecture( `/projets/${ id }` );
	return (
		<Ecran lecture={ lecture }>
			{ ( { projet: p } ) => {
				const portesAttendues = p.etapes
					.map( ( e ) => e.gate )
					.filter(
						( g ) => g && g.statut !== 'validee' && g.soumisLe
					);
				const onglets = [
					{ name: 'avancement', title: 'Où en est mon projet' },
					{
						name: 'attendus',
						title: `Ce qu’on attend de moi${
							portesAttendues.length
								? ` (${ portesAttendues.length })`
								: ''
						}`,
					},
					{ name: 'documents', title: 'Documents' },
					{ name: 'demandes', title: 'Demandes' },
					{ name: 'factures', title: 'Factures' },
				];
				return (
					<>
						<EnTete
							titre={ p.titre }
							retour={ {
								href: urlVue( 'accueil' ),
								libelle: 'Accueil',
							} }
							action={
								<a
									className="page-title-action"
									href={ urlVue(
										'demandes',
										`/demandes/nouvelle?projet=${ p.id }`
									) }
								>
									Nouvelle demande
								</a>
							}
						/>
						<p>
							<Pastille
								valeur={ p.statut }
								libelle={
									STATUT_PROJET[ p.statut ] ?? p.statut
								}
							/>
							{ p.deadline && (
								<span className="description">
									{ ' ' }
									· échéance { date( p.deadline ) }
								</span>
							) }
						</p>
						{ p.description && <p>{ p.description }</p> }

						<div className="cinq-colonnes">
							<div className="cinq-colonnes__principale">
								<TabPanel tabs={ onglets }>
									{ ( onglet ) => (
										<div className="cinq-onglet">
											{ onglet.name === 'avancement' && (
												<Etapes
													etapes={ p.etapes }
													onValidee={
														lecture.recharger
													}
												/>
											) }
											{ onglet.name === 'attendus' && (
												<Attendus
													portes={ portesAttendues }
													acces={ p.acces }
													onValidee={
														lecture.recharger
													}
												/>
											) }
											{ onglet.name === 'documents' && (
												<Documents
													livrables={ p.livrables }
												/>
											) }
											{ onglet.name === 'demandes' && (
												<Demandes
													demandes={ p.demandes }
												/>
											) }
											{ onglet.name === 'factures' && (
												<Echeances
													echeances={ p.echeances }
												/>
											) }
										</div>
									) }
								</TabPanel>
							</div>
							<div className="cinq-colonnes__cote">
								<Card>
									<CardHeader>
										<h2>Vos interlocuteurs chez CINQ</h2>
									</CardHeader>
									<CardBody>
										{ p.interlocuteurs.length === 0 ? (
											<Vide>
												L’équipe vous sera présentée au
												lancement.
											</Vide>
										) : (
											<ul className="cinq-liste">
												{ p.interlocuteurs.map(
													( i ) => (
														<li key={ i.email }>
															<strong>
																{ i.nom }
															</strong>
															<br />
															<span className="description">
																{ FONCTION[
																	i.fonction
																] ??
																	i.fonction }
															</span>
															<br />
															<a
																href={ `mailto:${ i.email }` }
															>
																{ i.email }
															</a>
														</li>
													)
												) }
											</ul>
										) }
									</CardBody>
								</Card>
							</div>
						</div>
					</>
				);
			} }
		</Ecran>
	);
}

function Etapes( { etapes, onValidee } ) {
	if ( etapes.length === 0 ) {
		return <Vide>Les étapes du projet seront posées au lancement.</Vide>;
	}
	return (
		<ol className="cinq-etapes">
			{ etapes.map( ( e ) => (
				<li key={ e.id } className={ `is-${ e.statut }` }>
					<div className="cinq-etapes__tete">
						<span className="cinq-etapes__numero">
							{ e.numero }
						</span>
						<strong>{ e.libelle }</strong>
						<Pastille
							valeur={ e.statut }
							libelle={ STATUT_ETAPE[ e.statut ] ?? e.statut }
						/>
						{ e.dateFin && (
							<span className="description">
								{ date( e.dateFin ) }
							</span>
						) }
					</div>
					{ e.gate && (
						<Porte porte={ e.gate } onValidee={ onValidee } />
					) }
				</li>
			) ) }
		</ol>
	);
}

function Porte( { porte, onValidee } ) {
	if ( porte.statut === 'validee' ) {
		return (
			<p className="cinq-porte">
				<Pastille valeur="validee" libelle="Validée" /> { porte.objet }{ ' ' }
				— par { porte.valideeParNom }, { date( porte.valideeLe ) }
			</p>
		);
	}
	return (
		<div className="cinq-porte">
			<span>
				<Pastille
					valeur={ porte.soumisLe ? 'en_attente' : 'a_venir' }
					libelle={
						porte.soumisLe ? 'À valider' : 'Validation à venir'
					}
				/>{ ' ' }
				{ porte.objet }
				{ porte.documentUrl && (
					<>
						{ ' · ' }
						<a
							href={ porte.documentUrl }
							target="_blank"
							rel="noreferrer"
						>
							Voir le document
						</a>
					</>
				) }
			</span>
			{ porte.soumisLe && (
				<BoutonValiderPorte porte={ porte } onValidee={ onValidee } />
			) }
		</div>
	);
}

function Attendus( { portes, acces, onValidee } ) {
	const attendus = acces.filter(
		( a ) => a.statut === 'demande' || a.statut === 'a_demander'
	);
	if ( portes.length === 0 && attendus.length === 0 ) {
		return <Vide>Rien ne vous attend sur ce projet.</Vide>;
	}
	return (
		<>
			{ portes.map( ( g ) => (
				<Porte key={ g.id } porte={ g } onValidee={ onValidee } />
			) ) }
			{ attendus.length > 0 && (
				<>
					<h3>Les accès dont nous avons besoin</h3>
					<ul className="cinq-liste">
						{ attendus.map( ( a ) => (
							<li key={ a.id }>
								<strong>{ a.libelle }</strong>{ ' ' }
								<Pastille
									valeur={ a.statut }
									libelle={ STATUT_ACCES[ a.statut ] }
								/>
								{ a.note && (
									<span className="description">
										{ ' ' }
										— { a.note }
									</span>
								) }
							</li>
						) ) }
					</ul>
				</>
			) }
		</>
	);
}

function Documents( { livrables } ) {
	if ( livrables.length === 0 ) {
		return <Vide>Aucun document partagé pour l’instant.</Vide>;
	}
	return (
		<ul className="cinq-liste">
			{ livrables.map( ( l ) => (
				<li key={ l.id }>
					<span
						className="dashicons dashicons-media-default"
						aria-hidden="true"
					/>{ ' ' }
					{ l.href ? (
						<a href={ l.href } target="_blank" rel="noreferrer">
							{ l.nom }
						</a>
					) : (
						l.nom
					) }
				</li>
			) ) }
		</ul>
	);
}

function Demandes( { demandes } ) {
	if ( demandes.length === 0 ) {
		return <Vide>Aucune demande sur ce projet.</Vide>;
	}
	return (
		<table className="wp-list-table widefat striped">
			<tbody>
				{ demandes.map( ( d ) => (
					<tr key={ d.id }>
						<td>
							<a
								href={ urlVue(
									'demandes',
									`/demandes/${ d.id }`
								) }
							>
								{ d.titre }
							</a>
						</td>
						<td>{ d.impact ? IMPACT[ d.impact ] : '—' }</td>
						<td>
							<Pastille
								valeur={ d.etat }
								libelle={ ETAT_DEMANDE[ d.etat ] }
							/>
						</td>
						<td>{ date( d.ouverteLe ) }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}

export function Echeances( { echeances } ) {
	if ( echeances.length === 0 ) {
		return <Vide>Aucune échéance pour l’instant.</Vide>;
	}
	return (
		<table className="wp-list-table widefat striped">
			<thead>
				<tr>
					<th>Échéance</th>
					<th>Montant HT</th>
					<th>Date</th>
					<th>État</th>
					<th>Facture</th>
				</tr>
			</thead>
			<tbody>
				{ echeances.map( ( e ) => (
					<tr key={ e.id }>
						<td>{ e.libelle }</td>
						<td>{ euros( e.montant ) }</td>
						<td>{ date( e.dateEcheance ) }</td>
						<td>
							<Pastille
								valeur={ e.etat }
								libelle={ ETAT_ECHEANCE[ e.etat ] }
							/>
						</td>
						<td>
							{ e.factureUrl ? (
								<a
									href={ e.factureUrl }
									target="_blank"
									rel="noreferrer"
								>
									{ e.numeroFacture ?? 'PDF' }
								</a>
							) : (
								'—'
							) }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
