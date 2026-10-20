# CINQ Central — plugin WordPress

L'espace client CINQ **dans l'administration WordPress du client** : projets et
validations, roadmap, demandes, factures, ressources. Les données viennent de Central
(app.cinqteam.com) par son API `/api/connect/v1` — pas d'iframe — et s'affichent avec les
composants de WordPress, sans les couleurs de CINQ : l'espace prend l'allure de chaque site.

## Ce que le plugin ajoute

| Où | Quoi |
|---|---|
| Menu **CINQ** (sous Tableau de bord) | Accueil, Roadmap, Demandes, Factures, Ressources. Pastille = validations attendues. |
| **Tableau de bord** | Bloc « CINQ — votre espace » : la situation, ce qui attend le client, ses demandes en cours. |
| **Front du site** | Le widget de retour de Central, chargé en dernier. Le bouton n'apparaît que dans les navigateurs qui ont ouvert le lien d'activation (`?cinq-feedback=…`) : **Réglages › CINQ Central › Afficher le widget**, ou le lien à envoyer à qui doit signaler (même sans accès à l'admin). |
| **Barre d'admin** (front) | « Signaler à CINQ » : ouvre une nouvelle demande avec la page en cours. |
| **Réglages › CINQ Central** | Relier le site, choisir les rôles qui voient l'espace, déconnecter. |

## Qui voit quoi

Règle convenue : **qui a accès à l'admin du site a accès à l'espace de ce client.**

- Les rôles cochés dans les réglages (administrateurs par défaut) voient l'espace. Un client
  WooCommerce ou un abonné, qui ont aussi un compte WordPress, ne le voient jamais.
- Chaque personne apparaît **sous son nom** dans Central. Une adresse inconnue y devient un
  compte client (sans mot de passe : il n'entre que par le site). Une adresse
  `@agencecinq.com` est reconnue comme l'équipe CINQ : ses messages partent signés CINQ, et
  elle ne peut pas valider une étape à la place du client.
- Une adresse déjà rattachée à un autre client dans Central est refusée, jamais déplacée.

Le filtre `cinq_wp_central_user_can( bool $allowed, WP_User $user )` a le dernier mot.

## Installer chez un client

1. Installer le zip de la dernière release (ou le plugin depuis ce dépôt) et l'activer.
2. Dans Central : fiche client › **Sites** › choisir « WordPress » et le projet dont le widget
   doit apparaître › **Émettre un code de connexion**. Le code vaut 24 h et sert une fois.
3. Dans WordPress : **Réglages › CINQ Central** › coller le code › **Relier le site**.

Le site garde un secret (option non chargée automatiquement, jamais envoyé au navigateur).
Central ne garde que son empreinte. Supprimer le plugin prévient Central et efface tout.

## Fonctionnement

```
navigateur ──(cookie + nonce REST)──▶ /wp-json/cinq-wp-central/v1/*   (PHP du plugin)
                                         │  Bearer <secret du site>
                                         │  X-Cinq-Acteur: <personne connectée>
                                         ▼
                                    Central /api/connect/v1/*
```

- `includes/Rest/Proxy.php` : une route WordPress par route Central (liste, pas de
  passe-plat), identifiants vérifiés comme UUID.
- `includes/Central/Client.php` : appels à Central ; un secret révoqué remonte en
  « site déconnecté », un refus de Central s'affiche tel qu'il est écrit.
- `includes/Central/Space.php` : ce qui s'affiche sur toutes les pages (pastille, bloc du
  tableau de bord) est mis en cache 5 minutes par personne, échecs compris ; le widget,
  une heure pour le site.
- `includes/Front/Widget.php` : le front n'appelle jamais Central. La partie publique du
  widget (script, jeton — jamais la clé) est gardée en option, rafraîchie quand une
  personne autorisée passe dans l'admin.
- `src/` : l'app React (`@wordpress/components`), construite dans `build/`.

## Configuration

| Réglage | Où |
|---|---|
| Adresse de Central | constante `CINQ_CENTRAL_URL` dans `wp-config.php`, ou filtre `cinq_wp_central_url` (défaut `https://app.cinqteam.com`) |
| Widget sur le front | filtre `cinq_wp_central_front_widget` (`false` pour le retirer) |
| Mises à jour automatiques | filtre `cinq_wp_central_auto_update` (activées par défaut) |

## Développer

```bash
composer install && composer test && composer lint
npm install && npm run build      # ou npm start
npm run lint:js && npm run lint:css
```

Tester contre un Central local (base de test, jamais la production) :

```bash
# Central (worktree feat/connect), sur une base Postgres locale
DATABASE_URL=postgresql://…/test pnpm dev -p 3100

# WordPress jetable, plugin monté, un seul worker (sinon la base n'est pas partagée)
npx @wp-playground/cli server --port 9400 --workers=1 --login \
  --mount=$(pwd):/wordpress/wp-content/plugins/cinq-wp-central \
  --define CINQ_CENTRAL_URL http://127.0.0.1:3100
```

`.wp-env.json` permet la même chose avec Docker (`npm run env start`).

## Publier une version

Monter la version dans l'en-tête **et** la constante `VERSION` de `cinq-wp-central.php`, puis
pousser un tag `vX.Y.Z`. Le workflow lance les tests puis `deploy.sh`, qui installe et
construit l'app d'administration et retire tout ce qui ne sert pas en production (sources,
tests, dépendances de dev, configs). Ce qui reste est zippé dans `cinq-wp-central.zip` (dossier
`cinq-wp-central/`) et attaché à la release avec `update.json` ; les sites l'installent d'eux-mêmes
dans les 12 heures. Aucune mise à jour n'est proposée quand le dossier du plugin est une copie de
travail Git, puisque la mise à jour remplace tout le dossier.

```bash
./deploy.sh /tmp/deploy.log   # à lancer sur une copie : le script supprime des fichiers
```

Le dépôt doit être public pour que les sites puissent télécharger le zip (il ne contient
aucun secret).
