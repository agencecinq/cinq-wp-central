import { useState } from '@wordpress/element';
import {
	Button,
	FormFileUpload,
	Notice,
	RadioControl,
	SelectControl,
	TabPanel,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	useLecture,
	Ecran,
	EnTete,
	Pastille,
	Vide,
} from '../composants/commun';
import { ecrire, envoyerFichier } from '../api';
import { date, dateHeure, ETAT_DEMANDE, IMPACT } from '../format';
import { aller, urlVue } from '../routeur';

const ONGLETS = [
	{ name: 'ouverte', title: 'Reçues' },
	{ name: 'en_cours', title: 'En cours' },
	{ name: 'reglee', title: 'Réglées' },
];

/** All requests, in the three columns of the web space: received, in progress, settled. */
export function Demandes() {
	const lecture = useLecture( '/demandes' );
	return (
		<Ecran lecture={ lecture }>
			{ ( { demandes } ) => (
				<>
					<EnTete
						titre="Demandes"
						action={
							<a
								className="page-title-action"
								href="#/demandes/nouvelle"
							>
								Nouvelle demande
							</a>
						}
					/>
					<TabPanel
						tabs={ ONGLETS.map( ( o ) => ( {
							...o,
							title: `${ o.title } (${
								demandes.filter( ( d ) => d.etat === o.name )
									.length
							})`,
						} ) ) }
					>
						{ ( onglet ) => {
							const liste = demandes.filter(
								( d ) => d.etat === onglet.name
							);
							return (
								<table className="wp-list-table widefat fixed striped cinq-onglet">
									<thead>
										<tr>
											<th>Demande</th>
											<th>Projet</th>
											<th>Gêne</th>
											<th>Ouverte le</th>
										</tr>
									</thead>
									<tbody>
										{ liste.length === 0 && (
											<tr>
												<td colSpan="4">
													Aucune demande.
												</td>
											</tr>
										) }
										{ liste.map( ( d ) => (
											<tr key={ d.id }>
												<td>
													<strong>
														<a
															className="row-title"
															href={ `#/demandes/${ d.id }` }
														>
															{ d.titre }
														</a>
													</strong>
												</td>
												<td>{ d.projetTitre }</td>
												<td>
													{ d.impact ? (
														<Pastille
															valeur={ d.impact }
															libelle={
																IMPACT[
																	d.impact
																]
															}
														/>
													) : (
														'—'
													) }
												</td>
												<td>{ date( d.ouverteLe ) }</td>
											</tr>
										) ) }
									</tbody>
								</table>
							);
						} }
					</TabPanel>
				</>
			) }
		</Ecran>
	);
}

/**
 * One request: what was said, the files, the thread "vous / CINQ".
 */
export function Demande( { id } ) {
	const lecture = useLecture( `/demandes/${ id }` );
	const [ message, setMessage ] = useState( '' );
	const [ envoi, setEnvoi ] = useState( false );
	const [ erreur, setErreur ] = useState( null );

	const agir = async ( fn ) => {
		setEnvoi( true );
		setErreur( null );
		try {
			await fn();
			await lecture.recharger();
		} catch ( e ) {
			setErreur( e.message );
		}
		setEnvoi( false );
	};

	return (
		<Ecran lecture={ lecture }>
			{ ( { demande: d } ) => (
				<>
					<EnTete
						titre={ d.titre }
						retour={ { href: '#/', libelle: 'Demandes' } }
					/>
					<p>
						<Pastille
							valeur={ d.etat }
							libelle={ ETAT_DEMANDE[ d.etat ] }
						/>
						{ d.impact && (
							<>
								{ ' ' }
								<Pastille
									valeur={ d.impact }
									libelle={ IMPACT[ d.impact ] }
								/>
							</>
						) }
						<span className="description">
							{ ' ' }
							· { d.projetTitre } · ouverte le{ ' ' }
							{ date( d.ouverteLe ) }
						</span>
					</p>
					{ d.description && (
						<p className="cinq-description">{ d.description }</p>
					) }
					{ d.page && (
						<p>
							Page concernée :{ ' ' }
							<a href={ d.page } target="_blank" rel="noreferrer">
								{ d.page }
							</a>
						</p>
					) }

					<h2>Pièces jointes</h2>
					{ d.piecesJointes.length === 0 ? (
						<Vide>Aucune pièce jointe.</Vide>
					) : (
						<ul className="cinq-liste">
							{ d.piecesJointes.map( ( p ) => (
								<li key={ p.id }>
									<span
										className="dashicons dashicons-paperclip"
										aria-hidden="true"
									/>{ ' ' }
									<a
										href={ p.href }
										target="_blank"
										rel="noreferrer"
									>
										{ p.nom }
									</a>
								</li>
							) ) }
						</ul>
					) }
					<FormFileUpload
						render={ ( { openFileDialog } ) => (
							<Button
								variant="secondary"
								onClick={ openFileDialog }
								disabled={ envoi }
							>
								Joindre un fichier
							</Button>
						) }
						onChange={ ( e ) => {
							const fichier = e.target.files?.[ 0 ];
							if ( fichier ) {
								agir( () => envoyerFichier( d.id, fichier ) );
							}
						} }
					/>

					<h2>La conversation</h2>
					{ erreur && (
						<Notice status="error" isDismissible={ false }>
							{ erreur }
						</Notice>
					) }
					{ d.messages.length === 0 && (
						<Vide>Pas encore de message.</Vide>
					) }
					<ul className="cinq-fil">
						{ d.messages.map( ( m ) => (
							<li key={ m.id } className={ `is-${ m.cote }` }>
								<span className="cinq-fil__auteur">
									{ m.cote === 'cinq'
										? `${ m.auteurNom } · CINQ`
										: m.auteurNom }{ ' ' }
									· { dateHeure( m.ecritLe ) }
								</span>
								<p>{ m.contenu }</p>
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
										`/demandes/${ d.id }/commentaires`,
										{ contenu: message }
									);
									setMessage( '' );
								} )
							}
						>
							Envoyer
						</Button>
					</div>
				</>
			) }
		</Ecran>
	);
}

/**
 * A new request, with the same requirements as the web journey: a title,
 * what happens, and how much it bothers you. The browser and screen size are
 * read, never asked — and shown before sending.
 */
export function NouvelleDemande( { params } ) {
	const lecture = useLecture( '/demandes' );
	const [ projet, setProjet ] = useState( params.get( 'projet' ) ?? '' );
	const [ titre, setTitre ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ impact, setImpact ] = useState( '' );
	const [ page, setPage ] = useState( params.get( 'page' ) ?? '' );
	const [ fichiers, setFichiers ] = useState( [] );
	const [ envoi, setEnvoi ] = useState( false );
	const [ erreur, setErreur ] = useState( null );

	const navigateur = window.navigator.userAgent;
	const tailleEcran = `${ window.screen.width }×${ window.screen.height }`;

	const envoyer = async ( projets ) => {
		setEnvoi( true );
		setErreur( null );
		try {
			const { id } = await ecrire( '/demandes', {
				projectId: projet || projets[ 0 ]?.id,
				titre,
				description,
				impactClient: impact || null,
				url: page || null,
				navigateur,
				tailleEcran,
			} );
			for ( const f of fichiers ) {
				await envoyerFichier( id, f );
			}
			aller( `/demandes/${ id }` );
		} catch ( e ) {
			setErreur( e.message );
			setEnvoi( false );
		}
	};

	return (
		<Ecran lecture={ lecture }>
			{ ( { projets } ) => (
				<>
					<EnTete
						titre="Nouvelle demande"
						retour={ {
							href: urlVue( 'demandes' ),
							libelle: 'Demandes',
						} }
					/>
					<div className="cinq-formulaire">
						{ erreur && (
							<Notice status="error" isDismissible={ false }>
								{ erreur }
							</Notice>
						) }
						{ projets.length > 1 && (
							<SelectControl
								label="Projet"
								value={ projet || projets[ 0 ].id }
								options={ projets.map( ( p ) => ( {
									label: p.titre,
									value: p.id,
								} ) ) }
								onChange={ setProjet }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
						<TextControl
							label="En une phrase"
							help="Par exemple : « Le menu ne s’ouvre pas sur mobile »."
							value={ titre }
							onChange={ setTitre }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextareaControl
							label="Ce qui se passe"
							help="Ce que vous avez fait, ce que vous attendiez, ce qui s’est produit."
							value={ description }
							onChange={ setDescription }
							rows={ 6 }
							__nextHasNoMarginBottom
						/>
						<RadioControl
							label="Ce que ça vous coûte"
							selected={ impact }
							options={ [
								{
									label: 'Bloquant — je ne peux pas travailler / vendre',
									value: 'bloquant',
								},
								{
									label: 'Gênant — je contourne, mais ça me ralentit',
									value: 'genant',
								},
								{
									label: 'À corriger un jour',
									value: 'mineur',
								},
							] }
							onChange={ setImpact }
						/>
						<TextControl
							label="Page concernée (facultatif)"
							type="url"
							value={ page }
							onChange={ setPage }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<div>
							<FormFileUpload
								multiple
								render={ ( { openFileDialog } ) => (
									<Button
										variant="secondary"
										onClick={ openFileDialog }
									>
										Joindre des fichiers
									</Button>
								) }
								onChange={ ( e ) =>
									setFichiers(
										Array.from( e.target.files ?? [] )
									)
								}
							/>
							{ fichiers.length > 0 && (
								<span className="description">
									{ ' ' }
									{ fichiers
										.map( ( f ) => f.name )
										.join( ', ' ) }
								</span>
							) }
						</div>
						<p className="description">
							Envoyé avec votre demande : { navigateur } — écran{ ' ' }
							{ tailleEcran }.
						</p>
						<div className="cinq-actions">
							<Button
								variant="primary"
								isBusy={ envoi }
								disabled={
									envoi || ! titre.trim() || ! projets.length
								}
								onClick={ () => envoyer( projets ) }
							>
								Envoyer à CINQ
							</Button>
						</div>
					</div>
				</>
			) }
		</Ecran>
	);
}
