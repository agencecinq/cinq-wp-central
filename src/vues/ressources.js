import { Card, CardBody, CardHeader } from '@wordpress/components';
import { useLecture, Ecran, EnTete, Vide } from '../composants/commun';

export function Ressources() {
	const lecture = useLecture( '/ressources' );
	return (
		<Ecran lecture={ lecture }>
			{ ( { sections } ) => (
				<>
					<EnTete titre="Ressources" />
					{ sections.length === 0 && (
						<Vide>Aucune ressource pour l’instant.</Vide>
					) }
					<div className="cinq-grille">
						{ sections.map( ( s ) => (
							<Card key={ s.categorie }>
								<CardHeader>
									<h2>{ s.libelle }</h2>
								</CardHeader>
								<CardBody>
									<ul className="cinq-liste">
										{ s.ressources.map( ( r ) => (
											<li key={ r.id }>
												<a
													href={ r.url }
													target="_blank"
													rel="noreferrer"
												>
													{ r.titre }
												</a>
												{ r.description && (
													<>
														<br />
														<span className="description">
															{ r.description }
														</span>
													</>
												) }
											</li>
										) ) }
									</ul>
								</CardBody>
							</Card>
						) ) }
					</div>
				</>
			) }
		</Ecran>
	);
}
