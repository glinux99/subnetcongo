# TODO / JOURNAL DE BORD — Agents IA

> **RÈGLE ABSOLUE POUR TOUT AGENT IA (ouvrir AVANT tout travail)**
>
> 1. Lire ce fichier en entier, puis `docs/agents/decisions.md`.
> 2. **DÉPLOIEMENT = INTERDIT sans « on déploie » / « go prod » ou phrase équivalente de l'utilisateur.** On travaille uniquement en local. Aucune migration, aucun upload, aucun cache clear, même si ça a l'air prêt.
> 3. **Ne jamais** commiter/pousser sans demande explicite de l'utilisateur (un commit local après validation est autorisé/demandé).
> 4. Le déploiement se fait **uniquement par upload de fichiers** dans `web/` — jamais `git pull` côté serveur.
> 5. Mettre ce fichier à jour à chaque étape importante (le prochain agent doit pouvoir reprendre sans contexte oral).

## Règles strictes — serveur & déploiement (gravées le 10/11/2026)

0. **DÉPLOIEMENT = INTERDIT sans validation explicite de l'utilisateur** (« on déploie », « go prod », « déploie ça »). En cas de doute ou d'absence de phrase équivalente : on reste EN LOCAL, rien n'est envoyé au serveur, point final.
1. **Ne jamais travailler en dehors du dossier `web/` sur le serveur** (pas de création/modif dans le home, `/var/www`, ailleurs). Exception ponctuelle : fichiers temporaires d'upload créés par l'agent lui-même, nettoyés ensuite.
2. **Ne jamais modifier la configuration du serveur** (PHP, `.env`, Apache/nginx, Plesk, cron, droits…). **On est là uniquement pour déployer.**
3. **`docs/` et les fichiers `.md` (AGENTS.md, RAPPORT_*.md…) ne doivent JAMAIS être déployés sur le serveur.** `docs/` = notes internes locales uniquement, jamais visible en production.
4. Les **backups** de déploiement sont créés dans `web/elearning/backups/<timestamp>/`, servent uniquement le temps du déploiement, puis sont **supprimés après validation**. Avant de supprimer, rapatrier le backup en local dans `docs/backups/<timestamp>/` (jamais déployé, ignoré par git).
5. Après **validation** des changements, faire `git add` + `git commit` **localement**. **Jamais de push** sans demande explicite.
6. `.env` serveur **ne doit jamais être écrasé** : il contient la config de prod (IMAP, DB…).
7. **Déploiement build = miroir sur les DEUX dossiers** : `web/build/` ET `web/elearning/public/build/` (laisser vérifier : md5sum identical après extract).
8. En cas de doute sur une action serveur non-liée au déploiement : demander à l'utilisateur AVANT d'agir.

Dernière mise à jour : **11 novembre 2026** (session UI : sidebar rouge #E1251B unifiée, topbar sans texte doublon, fil d'Ariane dupliqué supprimé sur 19 pages, champs PrimeVue stylés via design tokens formField du preset app.js — build OK. Règle absolue inchangée : local uniquement.)

### UI admin (11/11/2026, session opencode)
- **Sidebar active = rouge de marque #E1251B partout** : unification des 3 sources qui se battaient (violet codé en dur `AppMenuItem.vue`/`AppMenu.vue` + bleu `--primary-color` dans `_menu.scss`). Constant après F5. Badges sidebar passés en rouge.
- **Topbar** : texte « SubNetCongo e-Learning » (doublon du logo) supprimé, logo seul.
- **Fils d'Ariane dupliqués supprimés** sur 19 pages (Modules, Courses, Exams, Certificates, Grading, LearningPaths, LiveSessions, Questions, Analytics, MediaLibrary, ContactMessages/Index, Emails/Index, Student SupportTickets/Index, Users/Cohorts+Instructors+Students, Web/Seo+Theme+Widgets) + imports/variables orphelines nettoyés. Le fil du layout Sakai (`AppBreadcrumb`) est la source unique.
- **Champs PrimeVue stylés via design tokens** : preset `definePreset(Aura)` dans `app.js` étendu (`semantic.formField` : radius 0.65rem, padding 0.65/0.9rem ; `colorScheme.light/dark.formField` : bordures, focus, invalide, placeholder). S'applique app-wide (InputText, Textarea, Select, DatePicker, InputNumber, Password…) y compris dialogs et site public. `app.css` allégé : seules la typo 0.9rem et l'éditeur riche restent en CSS.
- Build Vite OK, pages `/modules` et `/` vérifiées 200 en local.

### Langues & Médiathèque réelles (11/11/2026, session opencode)
- **`/system/languages` refaite à 100 % réel** (plus aucune donnée bidon) : `System\LanguageController` réécrit — langues depuis la table `languages` (auto-sync des fichiers `resources/js/locales/*.json`), complétion réelle = nb de clés traduites vs référence fr.json (fr 1762, en 1880, de 323, sw 1635, ln 1682 clés). Endpoints ajoutés : toggle, set-default, keys (pagination+recherche serveur), updateKey (écrit le JSON), import/export JSON. Vue refaite : KPI réels, DataTable (badge Défaut, switch visible, export, suppression), éditeur de clés avec colonne FR référence + clés manquantes en rouge, dialogue ajout langue (crée le JSON vide) et import. ⚠️ Les édits JSON nécessitent `npm run build` pour le front (indiqué dans l'UI).
- **`/web/assets` refaite = vraie médiathèque disques** : `Web\AssetController@index` scanne les disques **media** (public/media, 93 fichiers) et **public** (storage/app/public, 27 fichiers) — 119 fichiers, 19,2 Mo. Filtres serveur (recherche, disque, type image/vidéo/audio/document/archive), pagination 24, KPI réels par disque, grille avec aperçu images + lien « Ouvrir ». Layout Sakai (admin) au lieu du layout public.
- **Pattern `p-input-icon-left` cassé en PrimeVue v4 corrigé partout** : LiveSessions, Marketing/SalesReports, Student/SupportTickets/Index (+ Support.vue et ContactMessages/Index avant) → `IconField`+`InputIcon`.
- Vérifié en CLI : props Inertia réelles pour keys() et AssetController@index (pagination, recherche, URLs `http://127.0.0.1:8000/media/...`). Build OK.

---

## 1. Contexte

Application e-learning Laravel + Vue 3 + Inertia + PrimeVue, déployée sur `https://subnetcongo.net`.

- **Repo local** : `elearning/` (ce repo), branche `devin/1792524012-ui-ux-design-system`.
- **Serveur (SSH, clé `~\.ssh\id_ed25519`)** : `subnetcongo_dan_subnet@subnetcongo.net`, port 22.
  - Docroot public : `web/` (index.php Laravel à la racine).
  - App Laravel : `web/elearning/` (autoloadée par `web/index.php`).
  - Assets Vite buildés : **`web/build/` ET `web/elearning/public/build/` (miroir, obligatoire)**.
  - Médias publics : `web/media/`.
- Le dépôt git sur le serveur **n'est pas synchronisable** (checkout CRLF divergeant). Le déploiement se fait par **upload de fichiers**, pas par `git pull` côté serveur.
- Commits actifs : `ca1884bf` (amelioration gemail) + `f01893bd` (sidebar + lien tickets + fil d'Ariane) — jamais poussés sans demande explicite.

---

## 2. Travail réalisé

### Gmail (interface emails complète, cf RAPPORT_INTERFACE_GMAIL.md)
- Vues Gmail/Sent/SentShow/Compose/Templates, backend IMAP/SMTP, BCC sur templates.

### Notifications
- BaseAppNotification (database+broadcast), tous notifications refactorées dessus.

### Live sessions / visio
- VideoProviderConfig (.env → modifiable via admin), LiveSession refonte complète, pages publiques/student/RequestAccess, LiveKit/Zoom/GoogleMeet/Bbb/Jitsi.

### Support client
- Refonte SupportTicketController + Support.vue, SupportMessage model, ticket_number déjà lié dans emails.

### Dashboard / divers
- GreetingMessage (messages d'accueil), examens emails (ExamPassed/Failed), WelcomeEmail/Bbb refonte.

### Sidebar & fil d'Ariane (10/11/2026 soir)
- Sidebar : active link maintenant persistant (exact ou sous-chemin, F5 inclus), sections mémorisées en sessionStorage.
- Fil d'Ariane : libellés français, IDs/UUIDs masqués, dernier segment non cliquable.
- Composant `NavLink` (Breeze, border-b-2) remplacé par un `<Link>` sidebar propre.
- Liens tickets minuscules : `/social/support/ticket-xxxx` (backfill lowercase appliqué migration 203000).
- **Bindings app-wide propres** (slug partout) : route('….show', $model) génère `/menu/slug` (Course/Exam/Module/Lesson/Certificate/Coupon/Cohort/Region/Page/BlogPost/LearningPath/Resource/Support/ContactMessage…) — fallback int (rétro-compat).
- Lead d'outils : `.gitignore` IGNORE `/docs/backups/`.

### Déploiement du 10/11/2026 (~19h)
- 95 fichiers app → `web/elearning/` (backup avant écrasement).
- 264 fichiers build → **BOTH** `web/build/` ET `web/elearning/public/build/` (mirror — md5 identique confirmé).
- 14 fichiers media → `web/media/`.
- Migration `2026_11_10_203000_app_links_identifiers` appliquée en prod (CRGUuid sur live_sessions/exam_results + backfills + ticket lowercase).
- Caches Laravel vidés. Site vérifié : `/` 200, `/login` 200, `/sessions-en-direct` 200, `/admin/emails` 302.
- Backups serveur : `web/elearning/backups/20261110-{185610,191723,192450,202943}/` (à rapatrier puis supprimer après validation).

---

## 3. Ce qui reste à faire

- [x] Vérifier le déploiement prod (site 200 partout).
- [ ] **Vérifier en prod** : login admin → Emails (inbox, envoi, brouillon, renvoi), templates (BCC), live sessions (création, invitation, request-access), support tickets (vérifier `/social/support/ticket-…`), notifications realtime, dashboard greeting, examen → email.
- [ ] **Pusher** la branche sur origin (uniquement sur demande).
- [ ] Tests : `php artisan test` / pest, y compris `tests/Unit/LiveSessionAccessTest.php`.
- [ ] Rapatrier backups `docs/backups/20261110-*` puis supprimer `web/elearning/backups/` de la prod (après validation utilisateur).
- [ ] CRLF serveur → LF (reprise propre quand possible).
- [ ] Améliorations Gmail futures : cf RAPPORT_INTERFACE_GMAIL.md.

## 4. Procédure de déploiement (serveur)

```powershell
# 1) Construire la liste (git diff <etat-serveur>..HEAD, filtrer docs/.md/test_*.php/inhemeter)
# 2) Tar app/ + config/ + routes/ + resources/views + database/ -> extraction dans web/elearning/
# 3) Tar public/build/ -> extraction dans web/build/ ET web/elearning/public/build/ (miroir exact)
# 4) Tar public/media/ -> extraction dans web/media/
# 5) AVANT extraction : backup dans web/elearning/backups/<timestamp>/ (app écrasés + build supprimés)
# 6) NE PAS toucher .env ; ensuite : php8.3 artisan migrate --force + config/route/view clear
# 7) Après validation utilisateur : rapatrier backup -> docs/backups/<ts>/ local, supprimer web/elearning/backups/ prod
```

- Cible app : `web/elearning/` ; cible public (build) : **les deux** `web/build/` et `web/elearning/public/build/` (miroir) ; médias : `web/media/`.
- `web/` est un **symlink** vers `/var/www/clients/client2/web3/web` ; les chemins sont relatifs au home.
- ⚠️ Le CLI par défaut du serveur est **PHP 8.1** (composer exige >= 8.2) : toujours utiliser `php8.3 artisan …`.
- **Exclure absolument** : `docs/`, `.md` racine, `.git`, `vendor/`, `node_modules/`, `.env`, `inhemeter_diagnostic/`, `test_*.php`, `tailwind.config.js`, `resources/js` sources.
- Vérifier https://subnetcongo.net après déploiement (200 attendu).
