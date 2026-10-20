import { useState } from '@wordpress/element';
import {
	Button,
	Modal,
	Notice,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { ecrire } from '../api';

/**
 * Gives a validation in one click — dated, named, and traced in Central as
 * "Validé en ligne depuis l'espace client sur <ce site> le … par …".
 */
export function BoutonValiderPorte( { porte, onValidee } ) {
	const [ ouvert, setOuvert ] = useState( false );
	const [ nom, setNom ] = useState( window.cinqCentral?.utilisateur ?? '' );
	const [ commentaire, setCommentaire ] = useState( '' );
	const [ envoi, setEnvoi ] = useState( false );
	const [ erreur, setErreur ] = useState( null );

	const valider = async () => {
		setEnvoi( true );
		setErreur( null );
		try {
			await ecrire( `/portes/${ porte.id }/valider`, {
				nom,
				commentaire: commentaire || null,
			} );
			setOuvert( false );
			onValidee?.();
		} catch ( e ) {
			setErreur( e.message );
		}
		setEnvoi( false );
	};

	return (
		<>
			<Button variant="primary" onClick={ () => setOuvert( true ) }>
				Valider
			</Button>
			{ ouvert && (
				<Modal
					title="Donner votre validation"
					onRequestClose={ () => setOuvert( false ) }
				>
					<p>
						<strong>{ porte.objet }</strong>
					</p>
					<p className="description">
						Tant qu’elle n’est pas donnée, l’équipe ne passe pas à
						l’étape suivante. Votre nom et la date sont conservés.
					</p>
					{ erreur && (
						<Notice status="error" isDismissible={ false }>
							{ erreur }
						</Notice>
					) }
					<TextControl
						label="Votre nom"
						value={ nom }
						onChange={ setNom }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextareaControl
						label="Un mot pour l’équipe (facultatif)"
						value={ commentaire }
						onChange={ setCommentaire }
						__nextHasNoMarginBottom
					/>
					<div className="cinq-actions">
						<Button
							variant="tertiary"
							onClick={ () => setOuvert( false ) }
						>
							Annuler
						</Button>
						<Button
							variant="primary"
							isBusy={ envoi }
							disabled={ envoi || ! nom.trim() }
							onClick={ valider }
						>
							Je valide
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
}
