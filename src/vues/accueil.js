import { Card, CardBody, CardHeader, Notice } from '@wordpress/components';
import {
	useLecture,
	Ecran,
	EnTete,
	Pastille,
	Avancement,
	Vide,
} from '../composants/commun';
import { BoutonValiderPorte } from '../composants/valider-porte';
import {
	date,
	euros,
	ETAT_DEMANDE,
	IMPACT,
	STATUT_PROJET,
	ETAT_CARTE,
} from '../format';
import { urlVue } from '../routeur';

/**
 * The home screen, in the web space's order — it is not negotiable there and
 * is not here: what waits for the client, what happens this month, their
 * projects, what we owe them, one invoicing figure.
 */
export function Accueil() {
	const lecture = useLecture( '/accueil' );
	return (
		<Ecran lecture={ lecture }>
			{ ( a ) => (
				<>
					<EnTete
						titre={ a.situation.titre }
						action={
							<a
								className="page-title-action"
								href={ urlVue(
									'demandes',
									'/demandes/nouvelle'
								) }
							>
								Nouvelle demande
							</a>
						}
					/>
					<p className="cinq-lede">{ a.situation.lede }</p>

					{ a.attendus.length > 0 && (
						<Notice
							status="warning"
							isDismissible={ false }
							className="cinq-attendus"
						>
							<h2>Ce qu’on attend de vous</h2>
							<ul>
								{ a.attendus.map( ( p ) => (
									<li key={ p.porteId }>
										<div>
											<strong>{ p.objet }</strong>
											<br />
											<span className="description">
												{ p.projetTitre } · { p.etape }{ ' ' }
												· en attente depuis{ ' ' }
												{ p.joursEnAttente } jour
												{ p.joursEnAttente > 1
													? 's'
													: '' }
												{ p.documentUrl && (
													<>
														{ ' · ' }
														<a
															href={
																p.documentUrl
															}
															target="_blank"
															rel="noreferrer"
														>
															Voir le document
														</a>
													</>
												) }
											</span>
										</div>
										<BoutonValiderPorte
											porte={ {
												id: p.porteId,
												objet: p.objet,
											} }
											onValidee={ lecture.recharger }
										/>
									</li>
								) ) }
							</ul>
						</Notice>
					) }

					<div className="cinq-grille">
						<Card>
							<CardHeader>
								<h2>
									{ a.ceMois
										? `Ce mois-ci — ${ a.ceMois.libelle }`
										: 'Ce mois-ci' }
								</h2>
								<a href={ urlVue( 'roadmap' ) }>
									Toute la roadmap
								</a>
							</CardHeader>
							<CardBody>
								{ [ ...a.retard, ...( a.ceMois?.cartes ?? [] ) ]
									.length === 0 ? (
									<Vide>Rien de prévu ce mois-ci.</Vide>
								) : (
									<ul className="cinq-liste">
										{ a.retard.map( ( c ) => (
											<li key={ c.id }>
												<a
													href={ urlVue(
														'roadmap',
														`/roadmap/${ c.id }`
													) }
												>
													{ c.titre }
												</a>{ ' ' }
												<Pastille
													valeur="en_retard"
													libelle="En retard"
												/>
											</li>
										) ) }
										{ ( a.ceMois?.cartes ?? [] )
											.slice( 0, 5 )
											.map( ( c ) => (
												<li key={ c.id }>
													<a
														href={ urlVue(
															'roadmap',
															`/roadmap/${ c.id }`
														) }
													>
														{ c.titre }
													</a>{ ' ' }
													<Pastille
														valeur={ c.etat }
														libelle={
															ETAT_CARTE[ c.etat ]
														}
													/>
												</li>
											) ) }
									</ul>
								) }
							</CardBody>
						</Card>

						<Card>
							<CardHeader>
								<h2>Vos demandes en cours</h2>
								<a href={ urlVue( 'demandes' ) }>
									Toutes les demandes
								</a>
							</CardHeader>
							<CardBody>
								{ a.demandesOuvertes.length === 0 ? (
									<Vide>Aucune demande en cours.</Vide>
								) : (
									<ul className="cinq-liste">
										{ a.demandesOuvertes
											.slice( 0, 5 )
											.map( ( d ) => (
												<li key={ d.id }>
													<a
														href={ urlVue(
															'demandes',
															`/demandes/${ d.id }`
														) }
													>
														{ d.titre }
													</a>{ ' ' }
													<Pastille
														valeur={ d.etat }
														libelle={
															ETAT_DEMANDE[
																d.etat
															]
														}
													/>
													{ d.impact && (
														<span className="description">
															{ ' ' }
															·{ ' ' }
															{
																IMPACT[
																	d.impact
																]
															}
														</span>
													) }
												</li>
											) ) }
									</ul>
								) }
							</CardBody>
						</Card>
					</div>

					<h2 className="cinq-titre-section">Vos projets</h2>
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Projet</th>
								<th>Statut</th>
								<th>Avancement</th>
								<th>Échéance</th>
								<th>Demandes ouvertes</th>
							</tr>
						</thead>
						<tbody>
							{ a.projets.length === 0 && (
								<tr>
									<td colSpan="5">
										Aucun projet pour l’instant.
									</td>
								</tr>
							) }
							{ a.projets.map( ( p ) => (
								<tr key={ p.id }>
									<td>
										<strong>
											<a
												className="row-title"
												href={ `#/projets/${ p.id }` }
											>
												{ p.titre }
											</a>
										</strong>
									</td>
									<td>
										<Pastille
											valeur={ p.statut }
											libelle={
												STATUT_PROJET[ p.statut ] ??
												p.statut
											}
										/>
									</td>
									<td>
										{ p.etapesTotal > 0 ? (
											<Avancement
												ratio={ p.avancementPct }
												libelle={ `${ p.etapesFaites } / ${ p.etapesTotal } étapes` }
											/>
										) : (
											'—'
										) }
									</td>
									<td>{ date( p.deadline ) }</td>
									<td>{ p.demandesOuvertes }</td>
								</tr>
							) ) }
						</tbody>
					</table>

					{ a.resteAFacturer > 0 && (
						<p className="cinq-pied">
							Reste à facturer sur vos projets :{ ' ' }
							<strong>{ euros( a.resteAFacturer ) }</strong>.{ ' ' }
							<a href={ urlVue( 'factures' ) }>
								Voir l’échéancier
							</a>
						</p>
					) }
				</>
			) }
		</Ecran>
	);
}
