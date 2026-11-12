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
0bis. **AVANT TOUT DÉPLOIEMENT : rappeler à l'utilisateur de basculer sur le modèle « Beam »** (il ne veut pas que ses données ni son serveur servent à entraîner des modèles de test). Ce rappel est OBLIGATOIRE à chaque session qui touche au serveur.
1. **Ne jamais travailler en dehors du dossier `web/` sur le serveur** (pas de création/modif dans le home, `/var/www`, ailleurs). Exception ponctuelle : fichiers temporaires d'upload créés par l'agent lui-même, nettoyés ensuite.
2. **Ne jamais modifier la configuration du serveur** (PHP, `.env`, Apache/nginx, Plesk, cron, droits…). **On est là uniquement pour déployer.**
3. **`docs/` et les fichiers `.md` (AGENTS.md, RAPPORT_*.md…) ne doivent JAMAIS être déployés sur le serveur.** `docs/` = notes internes locales uniquement, jamais visible en production.
4. Les **backups** de déploiement sont créés dans `web/elearning/backups/<timestamp>/`, servent uniquement le temps du déploiement, puis sont **supprimés après validation**. Avant de supprimer, rapatrier le backup en local dans `docs/backups/<timestamp>/` (jamais déployé, ignoré par git).
5. Après **validation** des changements, faire `git add` + `git commit` **localement**. **Jamais de push** sans demande explicite.
6. `.env` serveur **ne doit jamais être écrasé** : il contient la config de prod (IMAP, DB…).
7. **Déploiement build = miroir sur les DEUX dossiers** : `web/build/` ET `web/elearning/public/build/` (laisser vérifier : md5sum identical après extract).
8. En cas de doute sur une action serveur non-liée au déploiement : demander à l'utilisateur AVANT d'agir.

## 🏠 REPRISE À LA MAISON (arrêt prévu ~16:00, 11/11/2026)

**Branche** : `devin/1792524012-ui-ux-design-system` (remote origin = `github.com/glinux99/subnetcongo`).
- Le **working tree est la source de vérité** — la prod tourne avec le working tree déployé (~15:30), PAS avec HEAD seul. `git status` = **417 fichiers modifiés** (l'essentiel = build Vite régénéré `public/build/`) ; hors build : `app/Http/Controllers/User/StudentController.php` (fix `/users/students`, voir ci-dessous), `app/Http/Controllers/Web/AssetController.php` (CRUD assets), `app/Http/Controllers/System/LanguageController.php` (déjà en prod via working tree), `database/seeders/EnrollmentSeeder.php`+`LanguageSeeder`+`LandingContentSeeder`+`DatabaseSeeder` (déjà déployés), `resources/js/locales/*.json` (les 5, déployés), `resources/js/Pages/Web/Assets.vue`, `resources/js/Pages/System/Languages.vue`, `resources/js/sakai/layout/AppMenuItem.vue`, `docs/TODO.md`.
- ⚠️ Si la reprise se fait depuis une **autre machine / autre clone**, rien de tout ça n'existera sans `git commit` (build inclus — il est tracké dans ce repo). Ce PC reste le seul endroit complet. **Ne pas `git pull` sur le serveur** (règle absolue).
- Build : `npm run build` OK à 15:09 (inclut locales complétées + menu i18n + CRUD assets). Mirrors prod déjà synchronisés.

**Déployé (go explicite de l'utilisateur, ~15:50)** :
1. **Fix `/users/students` en prod** : `app/Http/Controllers/User/StudentController.php` uploadé (md5 local=distant `4e04ed418349fb7bf094749a68aacfc2`), backup de l'ancien fichier dans `backups/20261111-153008/StudentController.php.bak`, `php8.3 -l` OK. ⚠️ **À vérifier côté navigateur** : `/users/students?page=2` ne doit plus afficher l'erreur « Attempt to read property created_at on null ». Si un autre log apparaît, l'auteur du site doit vérifier les écrans utilisateurs. Aucun build nécessaire (PHP seul).
2. Vérif navigateur admin en prod : dashboard, Emails, Support, `/web/assets` (CRUD), `/system/languages`, `/academy`, enrollment test.
3. Migration i18n complète du site (voir « ⏳ À FAIRE CE SOIR » plus bas).
4. Choix utilisateur : `DemoMarketingBillingDataSeeder` (données fictives MRR) — **NON lancé** (fake revenue). `SeedActivityDemoSeeder` (nouveau, seedé sur comptes FR existants uniquement, achat comptes TEST exclus) = **LANCÉ en prod le 11/11 ~15:55** : 3 certificats + 6 vérifications 127.0.0.1, 3 tentatives+résultats d'examen, 2 tickets + 4 messages, 1 session live seedée terminée + 2 présences + 4 messages de chat, 2 devoirs + 4 soumissions notées, 5 coupons. Idempotent.
   - **Audit_logs et logins locaux NON portés** (historique perso PII, faux bandeaux) — décision opencode, à confirmer si différent.
   - **`web_settings` clé `enrollment_request_bcc` NON envoyée** (demande MB) — local only.
   - `certificate_verifications` du seed = IP 127.0.0.1 uniquement.
5. Backups `backups/20261111-*` : rapatrier + supprimer côté prod après « on valide ».
6. `/sitemap.xml` = 404 (aucune route sitemap — normal, à créer si souhaité).

Dernière mise à jour :

## ⏳ À FAIRE CE SOIR (rappel explicite de l'utilisateur, 11/11/2026)

- **Migration i18n complète du site** — état au 11/11/2026 soir : **site public + layout + 403 + pages admin critiques (Courses, Cohorts, Students, Instructors, Exams, ExamsResponses, Grading, System/*) convertis et build OK.** Restant ~486 littéraux FR dans ~86 fichiers (dont Certificates.vue 30, LearningPaths.vue 33, Modules.vue 26). `defineProps()` ne peut pas référencer t() (hoist module-scope). Plan faire après :
  1. Finir Certificates/LearningPaths/Modules (agent ciblé 1 fichier à la fois, `scan_lit.cjs` sort 0).
  2. ~85 fichiers mineurs par lots (vérifier aucun littéral FR).
  3. Relecture SW/LN par locuteur natif avant switch officiel.
  4. Vérification visuelle EN/DE en prod après déploiement go explicite.
- Base déjà en place : 5 JSON reformatés indent 4 + complets (0 clé manquante vs fr, clés `sidebar.*` pour le menu). Méthode : ajouter les clés dans les 5 JSON (groupes existants), puis `t()` dans les composants. Attention au front compilé : `npm run build` après chaque lot de JSON.
- Ordre suggéré : (1) site public (Welcome, Contact, header/footer), (2) composants transverses (PageHeader, états vides, toasts), (3) pages admin les plus utilisées, (4) reste. Les clés legacy ERP sont à ne pas supprimer sans audit ; le groupe `menu` (reliquat ERP) est à ignorer ou nettoyer.

## ✅ DÉPLOIÉ EN PROD le 11/11/2026 ~15:30 (go explicite de l'utilisateur)

Périmètre déployé (working tree complet) : `app/` (556 fichiers), `config/`, `routes/`, `database/` (seeders + 4 migrations), `resources/views/`, `public/build/` → **miroirs `web/build/` ET `web/elearning/public/build/`** (286 fichiers, `diff -r` identique), `public/media/` (110 fichiers) → `web/media/`, storage public → `web/elearning/storage/app/public/`. `.env` jamais touché.

**Sécurité données réelles (24 comptes préexistants)** :
- Migrations = 4, additives uniquement (creates / colonnes gardées) ; aucun DROP en `up()`. Appliquées : 2026_11_10_230000 / _231500 / _232000 / 2026_11_11_081804. ✅ Aucune donnée personnelle altérée.
- `EnrollmentSeeder` **patché** pour n'inscrire que les comptes de test `%@subnetcongo.net` (172 inscriptions générées) — les vrais inscrits n'ont reçu ni enrollment ni progression fictive.
- `LandingContentSeeder` : suppression de la `delete()` qui effaçait les slides non listées (au profit de updateOrCreate).
- `LegalInfoSeeder` : update if exists / create sinon ✅. `UserSeeder`/`CourseSeeder`/`ModuleLessonSeeder`/`ExamSeeder`/`EnrollmentSeeder`/`AffiliateSeeder` : tous gardés (firstOrCreate/existence checks) — aucune duplication.
- Seeders lancés : DatabaseSeeder complet + `SubscriptionPlanSeeder` (3 plans) + `GreetingMessageSeeder`. **`DemoMarketingBillingDataSeeder` NON lancé** (stats marketing fictives — à ne faire qu'avec accord explicite). `EmailTestSeeder` ignoré.
- Backup AVANT écriture dans `web/elearning/backups/20261111-153008/` : `file-backup.tar.gz` (16 Mo, app+build+media) + `db.sql.gz` (dump MariaDB complet, 113 tables, 29 INSERTs, « Dump completed » vérifié).
- Résultat base prod : users 24→49 (test ajoutés), courses 10→42, modules 39→146, lessons→379, exams 0→28 (questions 338), enrollments 0→172 (tests uniquement), realizations 0→17, email_templates 0→23, subscription_plans 0→3, languages 2→5 (fr/en/de/sw/ln), web_settings inchangé (52→52, contact placeholders remplacés par les vraies coordonnées Goma).
- Vérifs HTTP : `/` 200, `/login` 200, `/academy` 200, `/contact` 200, `/forgot-password` 200, `/sessions-en-direct` 200, `/marketplace` 302→/login (intentionnel, groupe `auth`), média 200, manifest build 200.
- Reste : valider côté navigateur admin (login admin → pages Emails, /web/assets CRUD, /system/languages 5 langues, dashboard, /academy) + rapatrier `backups/20261111-*` en local puis supprimer côté prod **après validation explicite** (procédure habituelle).

Dernière mise à jour : **11 novembre 2026 (soir)** — Session i18n + CRUD assets : les 4 locales (en/de/sw/ln) sont **complétées à 100 % vs fr.json** (0 clé manquante, fr 1839 feuilles) et le menu Sakai est désormais **traduisible** (groupe `sidebar.*` 77 libellés dans les 5 JSON, `AppMenuItem` utilise `t('sidebar.*', fallback FR)`). `/web/assets` : **CRUD complet + détection « utilisé » dans la BDD** (scan table/colonnes texte candidates, map cache 1 h, ~75 colonnes/34 tables ; blocage suppression d'un fichier référencé sauf force, 409 avec liste des références). Build Vite OK. Règle absolue inchangée : local uniquement.

### i18n + menus + CRUD assets (11/11/2026, soir — opencode)
### Sidebar — bug deux liens actifs + tonique (11/11/2026, session opencode)
- **Bug « Paramètres Généraux » + « Visioconférence » tous deux en rouge sur `/system/settings/video`** : racine = `matchesPath()` en préfixe, donc sœur `/system/settings/video` sortait `active` sur deux entrées. Fix : **longest-prefix-wins** via provide/inject `MENU_DEPTH_KEY` (Symbole partagé entre `AppMenu.vue` et `AppMenuItem.vue`) : seul le lien le plus spécifique (le plus long matching) reçoit `active-route`. Parent highlight = `matchesChildPath()` distinct (n'importe quel préfixe pour le parent). `hasActiveDescendant` utilise maintenant `matchesChildPath` (n'importe quelle profondeur). Build OK.

### Sidebar — menus i18n (11/11/2026, session opencode)
- **Menus de développement = i18n complet** ; groupe `sidebar` = 77 libellés dans les 5 JSON.
- **Admin menu nettoyé** (11/11/2026) : suppression des doublons/morts (`Réalisations` publique sortie d'admin, `Articles de Blog` dédupliqué en faveur de `Blog`, `Bibliothèque Médias` commentée supprimée, violet footer → rouge de marque). Rouge de marque #E1251B actif confirmé unique.
- **`defineProps()` n'accepte pas t()** (hoisting module-scope). Build OK ~1m30s.
- **Les 5 locales sont complètes vs fr.json** (fr reformaté indent 4, 1839 clés feuilles) : en 2042 feuilles (162 ajoutées), de 2089 (1776 ajoutées en 2 passes), sw 1831→1839 (199 ajoutées), ln (232 ajoutées). Validation : 0 clé fr manquante partout, placeholders intacts, UTF-8 sans BOM. Scripts node temporaires dans `AppData\Local\Temp\opencode` (hors repo).
- **Menu Sakai traduisible** : groupe `sidebar` = 77 libellés de `AppMenu.vue` (les 3 menus : student/instructor/admin), clés = libellés FR exacts (vue-i18n fallback FR via `t('sidebar.X', 'X')` dans `AppMenuItem.vue`), valeurs traduites dans les 5 JSON (fr = identité ; ln = 53 traductions lingala + termes tech en français). ⚠️ Les labels de menu restent en français dans le code (clé = libellé) — changer un libellé FR = mettre à jour les 5 JSON + le code.
- ⚠️ Le groupe `menu` existant dans les JSON = reliquat d'un **autre projet (ERP MaintenX)** — ne pas l'utiliser, candidat à suppression.
- **`/web/assets` : CRUD complet** (`Web\AssetController`) : `upload` (10 fichiers max, 50 Mo, mimes whitelist, nom unique auto `-1`…), `rename` (renomme + déplace dossier, même disque), `delete` (bulk, **bloqué si référencé sauf force=true** → 409 + liste des références `table.colonne`), `usage` (détection BDD : `SHOW COLUMNS` filtrés sur pattern path/image/photo/content… → `Cache::remember('assets_usage_columns', 1h)` ; scan d'un fichier ≈ 0,1–0,25 s). UI : upload (Dialog + FileUpload customUpload), renommer, copier URL, sélection multiple + suppression groupée, badge « Utilisé (n) / Non utilisé » par fichier (tooltip = références), **route `web.assets`** (l'ancienne page utilisait un nom `web.assets.index` inexistant → filtres KO avant). Export/import JSON côté langues inchangé.
- Build Vite OK (1m9s). ⚠️ Aucun déploiement, aucun commit.
- À noter pour la suite : le front reste majoritairement en français en dur hors menu/topbar (les JSON sont prêts) ; une migration « tout en t() » est un chantier à part. Les anciennes clés ERP legacy dans les JSON (en/de/sw/ln) sont conservées intactes exprès (utilisées ou non) — ne pas les supprimer sans audit.

### i18n — complétion sw.json (11/11/2026, session opencode)
- **`resources/js/locales/sw.json` complété** depuis fr.json (référence) : **199 clés manquantes traduites** en swahili pro (122 module stock/labels/meters/maintenances/myActivities/columnSelector/common.status + 77 sidebar), via scripts node temporaires (aplatissement, fusion dans l'ordre fr, écriture UTF-8 sans BOM, indent 4). Groupe `sidebar` remplacé : 77 clés = libellés FR exacts.
- Validation : 0 clé fr manquante, total **1831 clés feuilles** (1830 fr + 1 sw-only `dashboard.mainChart.exportPdf` conservée), placeholders `{n}`/`{count}`/`:param`/`%s` intacts, pas de BOM, clés sidebar identiques à fr.
- ⚠️ Anomalies : (1) conflit `common.status` — feuille FR "Statut" vs groupe SW legacy `approved/pending/rejected` (aucun usage dans le code, vérifié par grep) → remplacé par la feuille "Hali", les 3 clés legacy inutilisées ont disparu ; (2) `stockMovements.movementTypes.movableTypeOptions` = array FR traduit (labels), fausse alerte placeholder possible dans les outils de comparaison ; (3) valeur préexistante bizarre conservée telle quelle : `labels.messages.confirmDelete` = "Uko hakika unapenda kutosha label \"{name}\"?" (à revoir dans une future passe qualité).
- Aucun autre fichier modifié par cette session (modifs présentes sur fr/en/de.json, AppMenuItem.vue, TODO.md = sessions précédentes).

### i18n — complétion en.json (11/11/2026, session opencode)
- **`resources/js/locales/en.json` complété** : 162 clés feuilles manquantes traduites et fusionnées (méthode : script node d'aplatissement dot-notation → traductions → merge en conservant l'ordre des groupes existants + nouvelles clés dans l'ordre fr). Groupe `sidebar` : clés = libellés français EXACTS de fr.json (77), valeurs traduites EN.
- Anciennes clés legacy ERP d'en.json **conservées intactes** (1880 → 2033 clés feuilles). Format de sortie : JSON.stringify(obj, null, 4) + '\n', UTF-8 sans BOM. Validation : 0 clé fr manquante, sidebar 77/77 identique à fr, 0 valeur vide.
- ⚠️ Le `npm run build` reste nécessaire pour que le front prenne en compte les edits JSON (cf. section Langues plus bas).

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

### i18n — 7 pages System (11/11/2026, session opencode)
- `resources/js/Pages/System/{Api,Settings,Languages,Payments,VideoProviders,Logs,Maintenance}.vue` entièrement passées en `t()` vue-i18n (aucune chaîne française visible : toasts, confirmations, titres, tooltips, placeholders, en-têtes de colonnes, états vides, aria/title). Groupes de clés `pages.*` : pages.api.* (132), pages.settings.* (93), pages.languages.* (72), pages.payments.* (50), pages.logs.* (35), pages.video_providers.* (30), pages.maintenance.* (22) — 434 clés ajoutées aux 5 locales (fr/en/de/sw/ln), placeholders {n}/{name}/{date}/{used}/{total}/{action}/{state}/{connection}/{event}/{id}/{p}/{php}/{laravel}/{flag}/{provider} intacts. fr.json = texte FR original.
- `scan_lit.cjs` : 0 littéral français dans les 7 fichiers ; SFC parse + compileTemplate + compileScript OK. NE PAS build (build centralisé à la fin). Aucun déploiement, aucun commit.
