import { useState } from '@wordpress/element';
import {
	Button,
	Modal,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	useLecture,
	Ecran,
	EnTete,
	Pastille,
	Avancement,
	Chargement,
	Erreur,
	Vide,
} from '../composants/commun';
import { ecrire } from '../api';
import { dateHeure, mois, ETAT_CARTE } from '../format';
import { aller } from '../routeur';

/**
 * The roadmap: six months, what is late, what comes later, what is not dated
 * yet. The client moves a card from its window ("Déplacer"); undated cards are
 * shown but not moved — dating them would commit the agency in its place.
 */
export function Roadmap( { carteId } ) {
	const lecture = useLecture( '/roadmap' );
	return (
		<Ecran lecture={ lecture }>
			{ ( { roadmap: r } ) => (
				<>
					<EnTete titre="Roadmap" />
					<p className="description">
						Ce qui est prévu, mois par mois. Ouvrez une carte pour
						la déplacer ou en parler avec l’équipe.
					</p>
					<div className="cinq-roadmap">
						{ r.retard.length > 0 && (
							<Colonne
								titre="En retard"
								cartes={ r.retard }
								accent
							/>
						) }
						{ r.colonnes.map( ( c ) => (
							<Colonne
								key={ c.mois }
								titre={ c.libelle }
								cartes={ c.cartes }
								courant={ c.estMoisCourant }
							/>
						) ) }
						{ r.plusTard.length > 0 && (
							<Colonne titre="Plus tard" cartes={ r.plusTard } />
						) }
						{ r.backlog.length > 0 && (
							<Colonne
								titre="Pas encore daté"
								cartes={ r.backlog }
							/>
						) }
					</div>
					{ carteId && (
						<Carte
							id={ carteId }
							fenetre={ r.fenetre }
							onFermer={ () => aller( '/' ) }
							onChange={ lecture.recharger }
						/>
					) }
				</>
			) }
		</Ecran>
	);
}

function Colonne( { titre, cartes, courant, accent } ) {
	return (
		<section
			className={ `cinq-roadmap__colonne${
				courant ? ' is-courant' : ''
			}${ accent ? ' is-retard' : '' }` }
		>
			<h2>
				{ titre.charAt( 0 ).toUpperCase() + titre.slice( 1 ) }{ ' ' }
				<span className="count">({ cartes.length })</span>
			</h2>
			{ cartes.length === 0 && <p className="description">—</p> }
			{ cartes.map( ( c ) => (
				<a
					key={ c.id }
					className="cinq-roadmap__carte"
					href={ `#/roadmap/${ c.id }` }
				>
					<strong>{ c.titre }</strong>
					{ c.projet && (
						<span className="description">{ c.projet.titre }</span>
					) }
					<span>
						<Pastille
							valeur={ c.etat }
							libelle={ ETAT_CARTE[ c.etat ] }
						/>
					</span>
					{ c.avancement && (
						<Avancement
							ratio={ c.avancement.pourcent / 100 }
							libelle={ `${ c.avancement.faits } / ${ c.avancement.total }` }
						/>
					) }
				</a>
			) ) }
		</section>
	);
}

function Carte( { id, fenetre, onFermer, onChange } ) {
	const lecture = useLecture( `/roadmap/cartes/${ id }` );
	const [ vers, setVers ] = useState( '' );
	const [ motif, setMotif ] = useState( '' );
	const [ message, setMessage ] = useState( '' );
	const [ envoi, setEnvoi ] = useState( false );
	const [ erreur, setErreur ] = useState( null );

	const agir = async ( fn ) => {
		setEnvoi( true );
		setErreur( null );
		try {
			await fn();
			await lecture.recharger();
			onChange();
		} catch ( e ) {
			setErreur( e );
		}
		setEnvoi( false );
	};

	const { carte, fil = [], historique = [] } = lecture.donnees ?? {};

	return (
		<Modal
			title={ carte?.titre ?? 'Carte' }
			onRequestClose={ onFermer }
			size="medium"
		>
			{ lecture.charge && ! lecture.donnees && <Chargement /> }
			<Erreur erreur={ lecture.erreur ?? erreur } />
			{ lecture.donnees && (
				<>
					<p>
						<Pastille
							valeur={ carte.etat }
							libelle={ ETAT_CARTE[ carte.etat ] }
						/>{ ' ' }
						<span className="description">
							{ carte.mois
								? mois( carte.mois )
								: 'Pas encore daté' }
							{ carte.projet && ` · ${ carte.projet.titre }` }
						</span>
					</p>
					{ carte.detail && <p>{ carte.detail }</p> }

					{ carte.deplacable && (
						<div className="cinq-deplacer">
							<SelectControl
								label="Déplacer vers"
								value={ vers }
								onChange={ setVers }
								options={ [
									{ label: 'Choisir un mois', value: '' },
									...fenetre
										.filter( ( m ) => m !== carte.mois )
										.map( ( m ) => ( {
											label: mois( m ),
											value: m,
										} ) ),
								] }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
							<TextControl
								label="Pourquoi (facultatif)"
								value={ motif }
								onChange={ setMotif }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
							<Button
								variant="secondary"
								disabled={ ! vers || envoi }
								isBusy={ envoi }
								onClick={ () =>
									agir( async () => {
										await ecrire(
											`/roadmap/cartes/${ id }/deplacer`,
											{
												versMois: vers,
												motif: motif || null,
											}
										);
										setVers( '' );
										setMotif( '' );
									} )
								}
							>
								Déplacer
							</Button>
						</div>
					) }

					<h3>La conversation</h3>
					{ fil.length === 0 && (
						<Vide>Pas encore de message sur cette carte.</Vide>
					) }
					<ul className="cinq-fil">
						{ fil.map( ( m ) => (
							<li
								key={ m.id }
								className={
									m.cote === 'client' ? 'is-vous' : 'is-cinq'
								}
							>
								<span className="cinq-fil__auteur">
									{ m.signature } · { dateHeure( m.le ) }
								</span>
								<p>{ m.corps }</p>
							</li>
						) ) }
					</ul>
					<TextareaControl
						label="Votre message"
						value={ message }
						onChange={ setMessage }
						__nextHasNoMarginBottom
					/>
					<div className="cinq-actions">
						<Button
							variant="primary"
							disabled={ ! message.trim() || envoi }
							isBusy={ envoi }
							onClick={ () =>
								agir( async () => {
									await ecrire(
										`/roadmap/cartes/${ id }/commentaires`,
										{ corps: message }
									);
									setMessage( '' );
								} )
							}
						>
							Envoyer
						</Button>
					</div>

					{ historique.length > 0 && (
						<>
							<h3>Historique</h3>
							<ul className="cinq-liste">
								{ historique.map( ( h, i ) => (
									<li key={ i } className="description">
										{ dateHeure( h.le ) } — { h.parNom } :{ ' ' }
										{ h.de ? mois( h.de ) : 'non daté' } →{ ' ' }
										{ h.vers ? mois( h.vers ) : 'non daté' }
										{ h.motif && ` (« ${ h.motif } »)` }
									</li>
								) ) }
							</ul>
						</>
					) }
				</>
			) }
		</Modal>
	);
}
