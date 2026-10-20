import { Card, CardBody, CardHeader } from '@wordpress/components';
import { useLecture, Ecran, EnTete, Vide } from '../composants/commun';
import { euros } from '../format';
import { Echeances } from './projet';

/**
 * Invoicing, project by project. A carried project says where its schedule
 * lives; a retainer says its monthly amount — "no schedule" would read as
 * "nothing owed", which is false.
 */
export function Factures() {
	const lecture = useLecture( '/factures' );
	return (
		<Ecran lecture={ lecture }>
			{ ( { factures } ) => (
				<>
					<EnTete titre="Factures" />
					{ factures.length === 0 && (
						<Vide>Aucune facturation pour l’instant.</Vide>
					) }
					{ factures.map( ( f ) => (
						<Card key={ f.projetId } className="cinq-carte-bloc">
							<CardHeader>
								<h2>{ f.projetTitre }</h2>
								<span className="description">
									Facturé { euros( f.facture ) } · reste{ ' ' }
									{ euros( f.reste ) }
									{ f.enRetard > 0 && (
										<>
											{ ' ' }
											·{ ' ' }
											<strong>
												{ euros( f.enRetard ) } en
												retard
											</strong>
										</>
									) }
								</span>
							</CardHeader>
							<CardBody>
								{ f.regime === 'porte' && f.porteur && (
									<p>
										Facturé avec le projet «{ ' ' }
										{ f.porteur.titre } ».
									</p>
								) }
								{ f.regime === 'recurrent' && f.recurrent && (
									<p>
										Accompagnement :{ ' ' }
										{ euros( f.recurrent.montantMensuel ) }{ ' ' }
										HT par mois
										{ f.recurrent.jourFacturation
											? `, facturé le ${ f.recurrent.jourFacturation } du mois`
											: '' }
										.
									</p>
								) }
								{ f.couvreAussi && (
									<p className="description">
										{ f.couvreAussi }
									</p>
								) }
								{ f.regime !== 'porte' && (
									<Echeances echeances={ f.echeances } />
								) }
							</CardBody>
						</Card>
					) ) }
				</>
			) }
		</Ecran>
	);
}
