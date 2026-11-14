# Journal des décisions — agents & design

Ce fichier consigne les décisions prises lors des chantiers menés par les agents (Devin) sur SubnetCongo.
Chaque entrée : contexte, rôles consultés, options débattues, décision, conséquences.

---

## 2026-10-20 — Refonte UI/UX : fondations du design system + espace étudiant

**Demande** (Daniel) : « améliorer le UI et UIX avec un bon design », utiliser les skills du projet
(`agents-collaboration`, `elearning-ui-ux-subnetcongo`, `elearning-pedagogie-ux`, `qualite-tests-securite`,
`microcopy-i18n`), faire débattre les rôles, consigner les décisions, implémenter avec le design system,
revue croisée, tests et checklists.

**Note sur les skills** : aucun de ces skills n'existe dans le dépôt ni dans le pool de skills accessible à
l'agent (recherche par nom et par mots-clés). Les rôles ci-dessous ont donc été tenus par l'agent en
s'appuyant sur `PROMPT_IA_REFONTE.md` (section T9) et l'audit du code. Si ces skills existent ailleurs,
merci de les ajouter au dépôt (ex. `.devin/skills/<nom>/SKILL.md`) pour qu'ils soient appliqués à la lettre.

### Rôles consultés

| Rôle | Préoccupation principale |
|------|--------------------------|
| Designer UI (design system) | Cohérence visuelle, tokens, composants réutilisables |
| Expert UX pédagogique (e-learning) | Motivation, « prochaine action » claire, états vides qui guident |
| Accessibilité (a11y) | Contraste, focus clavier, lecteurs d'écran, mouvement réduit |
| Microcopy / i18n | Ton, casse, vocabulaire cohérent en français |
| Qualité / sécurité | Risque de régression, tests, pas de changement backend |

### Constats d'audit (avant)

- 175 vues `.vue` (~46 500 lignes), 2 layouts (`@/Layouts/AppLayout` public sur 13 pages, `@/sakai/layout/AppLayout` sur 76).
- Couleur incohérente : preset PrimeVue bleu, mais pages en rouge (58×), indigo (68×), bleu, violet. Un ancien
  script de remplacement massif (`resources/js/Pages/refactor.py`) a converti emerald/amber/rose → rouge et
  indigo/violet → bleu : des états « succès » s'affichent en rouge.
- Composants `PageHeader`/`PageCard`/`PageKpi`/`PageSection` présents mais utilisés par aucune page ;
  `PageHeader` rendait les actions avec lien en `<button>` (lien mort) et inversement.
- `app.css` : règles `:deep()` hors composant (ignorées par le navigateur), helpers qui redéfinissent des
  classes Tailwind, directives `@tailwind` en double.
- 116 pages sans aucune variante sombre ; mode sombre non mémorisé (perdu à chaque rechargement).
- 11 pages avec `confirm()` / `alert()` natifs.
- 43 pages montaient leur propre `<Toast />` alors que le layout Sakai en monte déjà un : chaque notification
  s'affichait **deux fois**.
- `DashboardS.vue` ne compilait plus (balise `</div>` manquante).
- Faux contenus sur « Mes certificats » : KPI codés en dur (« À jour », « Actif ») et deux boutons sans action.

### D1 — Palette

- **Options** : (a) violet `#8B5CF6` + CTA emerald (T9 de `PROMPT_IA_REFONTE.md`) ; (b) bleu `#2563EB`
  (preset PrimeVue actuel) + rouge de marque.
- **Débat** : le designer préférait (a), plus distinctif. Qualité/sécurité : (a) recolore toutes les vues
  PrimeVue d'un coup sans relecture page par page, donc risque plus élevé. UX : ce qui compte avant tout
  est de rendre la sémantique cohérente (succès = vert, attention = ambre, danger = rose/rouge).
- **Décision (provisoire, à valider par Daniel)** : (b) bleu primaire + rouge réservé à la marque et à
  l'urgence ; sémantique stricte emerald / amber / rose / sky. Les couleurs passent par les variables
  `--snc-*` de `resources/css/app.css` et le preset de `resources/js/app.js` : passer au violet ne
  touchera que ces deux endroits.

### D2 — Design system

- Les tokens CSS `--snc-*` (surface, bordure, texte, primaire, rayon, ombre, durée/easing) sont définis dans
  `:root` et redéfinis sous `.app-dark`.
- On garde les classes existantes (`page-card`, `page-kpi`, `page-header`, `btn-*`, `page-input`…) mais elles
  utilisent maintenant les tokens. La casse normale remplace l'italique + majuscules partout, pour la lisibilité.
- Composants : `PageHeader` (corrigé : `href` → `<Link>` Inertia, sinon `<button>`), `PageCard`, `PageKpi`
  (+ état `loading`), `PageSection`, **nouveau** `EmptyState` (icône + titre + explication + CTA).
- **Nouveau** composable `useConfirmAction()` pour un `ConfirmDialog` global (groupe `app`) monté dans le
  layout Sakai.

### D3 — Mode sombre

- Tailwind `darkMode: ['selector', '.app-dark']`, aligné sur PrimeVue (`darkModeSelector: '.app-dark'`).
- Choix mémorisé dans `localStorage` (`snc-dark-mode`) et appliqué dès le `<head>` pour éviter le flash blanc.
- **Couche de compatibilité** dans `app.css` : dans `.layout-main`, les utilitaires clairs les plus courants
  (`bg-white`, `bg-slate-50`, `text-slate-800`, `border-slate-200`, pastels `-50`…) sont remappés en sombre,
  **sauf** si l'élément déclare déjà une variante `dark:`. Les 116 pages restent ainsi lisibles en sombre
  sans réécriture. Débat a11y : solution pragmatique, à remplacer par de vraies variantes `dark:` au fil
  des refontes de pages.

### D4 — Feedback utilisateur

- Plus aucun `alert()` / `confirm()` : `useConfirmAction()` (bouton rouge pour les actions destructives,
  libellés explicites comme « Supprimer » ou « Annuler la demande » au lieu d'« OK ») et `useToast()`.
- Un seul `<Toast />` global ; les `<Toast />` locaux sans `group` sont supprimés. Ceux avec `group="tc"`
  sont conservés car ils écoutent un canal distinct.

### D5 — UX pédagogique (espace étudiant)

- Chaque carte de cours propose une seule action principale contextuelle : « Commencer », « Reprendre » ou
  « Revoir le cours ». Les boutons sans action ont été retirés.
- Filtres avec compteurs et `role="tablist"`. Un état vide distingue « aucune inscription » de « filtre vide ».
- « Ma progression » : KPI via `PageKpi`, graphique adapté au mode sombre, état vide encourageant quand il
  n'y a pas d'activité.
- « Mes certificats » : KPI réels (obtenus, score moyen, meilleur score, dernier obtenu) à la place des
  valeurs inventées ; copie du lien de vérification avec un repli si le presse-papiers est refusé.

### D6 — Accessibilité

- `:focus-visible` global (anneau 2 px primaire), `prefers-reduced-motion` respecté.
- Icônes décoratives en `aria-hidden`, boutons icône avec `aria-label`, `ProgressBar` avec `aria-label`,
  erreurs de formulaire reliées par `aria-describedby` + `role="alert"`.
- Contrastes : sous-titres du header en `slate-300` sur fond `#0F172A` (≈ 12:1) ; textes pastels assombris
  en mode clair et éclaircis en sombre.

### D7 — Microcopy

- Casse de phrase (« Mes formations », pas « MES FORMATIONS »), vouvoiement, verbes d'action
  (« Télécharger le PDF », « Fermer le ticket »), espaces avant `%`, pas d'anglais (« Verified Credentials »
  → « Certificats vérifiables »).

### D8 — Périmètre et limites

- Aucun changement backend, route ou modèle.
- Le layout public `@/Layouts/AppLayout` (site vitrine) n'est pas touché dans ce lot.
- Les grosses pages admin (`Courses.vue` 2 575 lignes, `Certificates.vue`, `LearningPaths.vue`…) ne profitent
  que des fondations (tokens, sombre, toasts, confirmations) ; leur refonte se fera dans des lots séparés.
- `public/build` est versionné : les assets compilés ne sont **pas** inclus dans la PR. Lancer `npm run build`
  au déploiement.

### Revue croisée et vérifications

Voir la section « Checklist » de la PR (build Vite, `php -l`, Pest comparé à `main`, recherche
`alert(`/`confirm(`, vérification des routes Ziggy utilisées).

## 2026-11-14 (soir) — Mobile : dialogues et navigation

**Contexte** : Daniel montre que les dialogs débordent sur mobile, que le menu latéral reste ouvert après un clic, et demande l'adaptation de toutes les pages + tableaux au mobile.

**Décisions**
- **Fermeture automatique du menu** dans `AppLayout` (`router.on('before')`) plutôt que de compter sur chaque `MenuItem` : toute navigation, y compris les filtres `?start_date=...`, referme l'overlay/mobile. C'est le comportement attendu d'un drawer.
- **Dialogues alignés en haut et en plein écran utile** : `.p-dialog-mask` avec padding 0.375rem + `.p-dialog` en `max-height: 100dvh - 0.75rem`, flex column, contenu scrollable interne. `SncDialog` : largeur `min(width, 100vw - 0.75rem)`, header/body compacts, footer wrappable. `Courses.vue` : header tronqué sur une ligne, stepper horizontal sous le header sur mobile, footer actions en `flex-1`.
- **Tableaux adaptés** : les 8 tableaux HTML natifs sans wrapper reçoivent `overflow-x-auto` et un `min-width` (Exams, Affiliates, Web/Pages) ; DataTable compacte via la surcouche CSS (`font-size .8125rem`, cellules nowrap, wrapper scrollable). Le desktop n'est pas touché.
- **Tableaux = expérience mobile d'abord** : colonne principale + statut/kebab uniquement sous `md` ; prix/métriques repoussés sous `xl` ; sélection/expansion masquables sous `md` ; actions regroupées en menu ⋮ (même pattern que Live Sessions) ; fonds `#f8fafc/#ffffff` supprimés des `<Column>` au profit des tokens `--snc-*` pour rendre le mode sombre cohérent.
- **Colonnes `modules` manquantes** : `has_quiz`, `quiz_required`, `document_name` attendues par le modèle mais absentes en base (SQLSTATE 1054 à la sauvegarde). Migration additive locale appliquée ; aucun déploiement sans go.

## 2026-11-14 — Shell mobile/dark : fil visible, tokens et WebSocket dashboard

**Contexte** : Daniel demande l'adaptation mobile de toutes les pages, la réduction des textes, le masquage d'éléments secondaires, la création d'un skill dédié, la correction du sidebar/appbar en dark mode, et l'abandon du rechargement automatique du dashboard au profit du WebSocket.

**Décisions**
- **Skill spécifique** `subnetcongo-mobile-dark-ux` plutôt qu'un simple « responsive pass » : il capture le shell réel (topbar fixe, sidebar, fil d'Ariane, `.page-container`), les anti-patterns déjà constatés et la checklist de vérification mobile/dark.
- **Shell unique** : topbar 4rem en tokens (`--surface-card`) au lieu de `bg-white/90` qui écrasait le thème ; `.layout-main-container` reçoit `padding-top: 4rem` ; fil d'Ariane sticky sous la topbar (`top-16 z-30`) ; contenu `.page-container` ramené à `z-index: 1` (avant 20 → il passait devant le fil). Le fil est réduit à 2 segments sur mobile et masqué le filtre date global sous `md`.
- **Tokens/dark partout où l'app ne couvre pas `.layout-main`** : topbar, sidebar, fil, footer, palette, notifications, dropdowns. La couche de compatibilité `.layout-main` de `app.css` ne suffit pas à ces overlays.
- **Réduire avant de masquer** : surcouche mobile après Tailwind (typo/padding/grid/dialogs/datatables). Les pages conservent le desktop ; seuls les éléments secondaires sont masqués (métadonnées, colonnes, filtre date global).
- **Dashboard sans auto-reload** : `router.reload` toutes les 30 s supprimé. Les métriques infra sont poussées par Echo (`admin.dashboard.metrics`, ShouldBroadcastNow) via la commande `dashboard:broadcast-metrics` planifiée chaque minute ; diffusion seulement si le hash des métriques change. Le bouton « Actualiser » reste manuel et l'utilisateur déclenche lui-même une mise à jour complète.
- **Aides contextuelles pilotées** : `ContextHint` remplace le simple hint des filtres ; `/system/settings` › Aide & messages permet d'activer/désactiver globalement et de fixer un TTL en jours (0=illimité, 2/4/7/14/30 proposés). Le choix utilisateur est aussi enregistré par compte (localStorage).

**Conséquences** : le fil d'Ariane reste lisible, le shell suit dark/thème, le mobile devient la base de la surcouche, le dashboard ne se recharge plus tout seul. Reste à valider visuellement sur 360/768/1280 px et à exécuter Reverb (ou Pusher) en prod — uniquement sur go explicite.

## 2026-11-14 — Dashboard admin : hiérarchie, filtres utiles et accessibilité

**Contexte** : demande de refonte design du dashboard administrateur (`Dashboard.vue`) en s'appuyant sur les skills UI/UX e-learning, avec priorité hiérarchie/hero/KPI + mobile/accessibilité.

**Décisions**
- **Composants partagés plutôt que styles locaux** : `PageHeader` gagne une variante `danger` (purge) et des actions 44 px pleine largeur sur mobile ; `PageKpi` gagne `trendSuffix`, `trendLabel`, flèches directionnelles et un état neutre. Les autres pages héritent des gains d'accessibilité sans changement de markup.
- **Filtres honnêtes** : on remplace les tags année/mois décoratifs et la recherche sans effet par une toolbar avec labels visibles. L'année pilote KPI + graphiques ; recherche/catégorie pilotent le tableau des cours (`cohorts`, jusqu'ici passé mais jamais affiché). Le mois est retiré car le contrôleur ne l'utilise pas.
- **Nouvelle table « Performance des cours »** : exploite `cohorts` (déjà fourni par `DashboardController@adminDashboard`) en tableau desktop et cartes mobile ; aucun changement backend.
- **Infrastructure accessible** : les Knob sont remplacés par des jauges `role="meter"` avec seuils (75 %/90 %), statut textuel et heure de dernière mesure ; polling 30 s conservé mais sans barre de progression Inertia.
- **Charts** : options Chart.js theme-agnostiques, tooltip en devise, `aria-label` sur les conteneurs, animation désactivée si `prefers-reduced-motion`.
- **Chargement réel** : suppression du faux skeleton de 500 ms ; le skeleton ne s'affiche que pendant les requêtes de filtres, avec `aria-live` discret.

**Conséquences** : dashboard plus dense mais hiérarchisé, filtres qui font ce qu'ils annoncent, meilleure lisibilité mobile et clavier. Aucun changement de données ni de routes. Reste à valider visuellement avec un compte admin local.

## 2026-11-14 — Live : timezone, accès chat et icônes PrimeIcons

**Contexte** : heures de session décalées de −2 h, chat invisible pour les étudiants invités, micro/caméra qui ne publient pas, double envoi de messages, icônes micro/caméra/émoji vides.

**Décisions**
- **Timezone** : les colonnes `datetime` sont lues/écrites par Eloquent dans `config('app.timezone')` (forcé à `Africa/Lubumbashi` par `SystemSettings`), pas en UTC. On convertit donc l'heure saisie dans le fuseau de l'app, et plus en UTC. Conséquence : les anciennes lignes enregistrées avec l'ancien calcul doivent être re-sauvegardées (pas de migration de données automatique).
- **Accès chat** : on aligne `canAccessSession()` sur les règles d'entrée de `join()` — inscription active/terminée **ou** e-mail invité **ou** accès approuvé **ou** session publique. `sendChat` est protégé par la même règle (avant : envoi possible sans lecture → chat à sens unique pour les invités).
- **Identité des messages** : `chatMessages` renvoie `user_id` et `mine` (calcul serveur) ; le front ne se base plus uniquement sur l'égalité des noms.
- **Médias LiveKit** : objets LiveKit stockés en `shallowRef` (pas de proxy Vue), état micro/caméra dérivé des publications, permission demandée sans ouvrir la caméra une seconde fois avant publication (évite le conflit de périphérique).
- **Icônes** : PrimeIcons 6.0.1 ne fournit pas les variantes `-slash` ni `smile`. On ne change pas de version pour l'instant : variantes « off » via `.pi-is-off` (barre CSS) et émoji natif. Un upgrade PrimeIcons pourra remplacer ces fallbacks plus tard.

**Conséquences** : comportement identique pour les inscrits ; les invités/approuvés/publics ont enfin un chat bidirectionnel ; les sessions déjà enregistrées avec le mauvais fuseau doivent être éditées.

## 2026-11-11 � V�rification email obligatoire + BCC global + Acc�s examens conditionnels

**Demandes** (Daniel) : ? confirmation par email lors de l''inscription ; ? tout email envoy� par la plateforme copie genesiskikimba@gmail.com ET joackimmuholu@gmail.com (Katya) ; ? un inscrit qui n''a pas suivi le cours ne peut pas prendre l''examen (sinon � 28 examens en attente � pour un nouveau compte).

**D�cisions**
- **V�rif email = MustVerifyEmail Laravel standard** (URI sign�e, throttle 6/min, `verified` middleware). Les 24 comptes prod existants sont backfill�s via migration unique (`email_verified_at = now()`). Nouveaux comptes : aucun acc�s app tant que le lien n''est pas cliqu�. Override de `User::sendEmailVerificationNotification()` ? envoi via EmailTemplateService + template `email_verification` (d�j� seed�) � charte SubnetCongo.
- **BCC global = listener `MessageSending`** (event Laravel 10) qui ajoute `config('mail.default_bcc')` (2 adresses cod�es en clair dans config/mail.php � pas de secret, d�j� publi�es comme emails de contact publiquement). Toute voie d''envoi (Mailable direct, EmailTemplateService, Verifyable) passe par l� ; les BCC explicites par template (EmailTemplate::bcc_emails) sont pr�serv�s.
- **Acc�s examens conditionn� par progression cours = 100 %** : scope `Exam::eligibleFor($user)` ; utilis� pour : page Mes examens (learner.index), show/start/submit, badge `exams_pending` du menu sidebar. Pas d''acc�s avant la fin du cours = seuls les cours termin�s (progress_percent = 100) d�bloquent leurs examens.

**Cons�quences**
- Les 24 comptes prod gardent l''acc�s (backfill), les nouveaux doivent confirmer.
- Suppression logique de la liste � 28 examens en attente � fictive ; un compte sans cours termin� voit 0 examen + message d''aide.
