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
