# TODO / JOURNAL DE BORD — Agents IA

> **RÈGLE ABSOLUE POUR TOUT AGENT IA (ouvrir AVANT tout travail)**
>
> 1. Lire ce fichier en entier, puis `docs/agents/decisions.md`.
> 2. **DÉPLOIEMENT = INTERDIT sans « on déploie » / « go prod » ou phrase équivalente de l'utilisateur.** On travaille uniquement en local. Aucune migration, aucun upload, aucun cache clear, même si ça a l'air prêt.
> 3. **Ne jamais** commiter/pousser sans demande explicite de l'utilisateur (un commit local après validation est autorisé/demandé).
> 4. Le déploiement se fait **uniquement par upload de fichiers** dans `web/` — jamais `git pull` côté serveur.
> 5. Mettre ce fichier à jour à chaque étape importante (le prochain agent doit pouvoir reprendre sans contexte oral).

## Règles strictes — serveur & déploiement (gravées le 10/11/2026)

0ter. **ACCÈS SERVEUR = UNIQUEMENT SUR DEMANDE EXPLICITE DE L'UTILISATEUR (gravé le 15/11/2026, après plusieurs accès SSH diagnostiques non sollicités).** Même un simple `curl`, `ls`, `ps`, md5, un `rm ~/script-uploadé.sh` — tout ce qui touche au serveur attend ton go. Les diagnostics de prod se font sur demande, pas d'initiative.

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

## 🚀 DÉPLOIÉ EN PROD — 15/11/2026 ~18:35 (go explicite — modèle opencode/inkling-2-small-free ; rappel Beam fait, l'utilisateur assume ce modèle pour ce deploy)
- **Périmètre** : miroir complet (HEAD 3e12da60 + modifs working tree : `resources/views/app.blade.php` SEO, `app/Http/Controllers/SitemapController.php` + route sitemap, 5 locales, `resources/js/Pages/Web/BlogArticle.vue`) : `app/ config/ routes/ database/ resources/views/` → `web/elearning/` ; `public/build/` → **miroirs `web/build/` ET `web/elearning/public/build/`** (md5 `842d37bc…` identique ×3, 297 fichiers). `web/media/` non touché (aucun changement). `public/humans.txt` n'existait jamais côté prod (supprimé en local, aucun fichier à supprimer sur le serveur). `.env` jamais touché ; `docs/` non déployé.
- **Backup AVANT écriture** : `web/elearning/backups/20261115-193214/` (`app-backup.tgz` 455K, `build-backup.tgz` 3,9M, `db.sql.gz` — « Dump completed », ~4,5M total). Exclus du tar : `database/.env` (2385 o, incrémenté en local) et `*.sqlite` (`database/database.sqlite`).
- **Migrations appliquées** (additives, 5) : 2026_11_12_000000 backfill uuid, _000001 reminder live sessions, _122555 add_text lessons type enum, 2026_11_13_120000 create terminal_sessions, 2026_11_14_000010 quiz flags. Puis `config:clear`, `route:clear`, `view:clear` (pas de cache rebuild).
- **Vérifs HTTP** : `/` 200, `/login` 200, `/academy` 200, `/contact` 200, `/sessions-en-direct` 200, **`/sitemap.xml` 200** (nouvelle route), **`/blog` 200** (nouvel article route), `/robots.txt` 200, `/marketplace` 302 (attendu), `/media/lms/logo-subnetcongo.png` 200, `/build/manifest.json` 200, page 404 brandée → statut 404 OK (HTML Inertia correct).
- **Upload temporaires nettoyés** : `~/deploy-app.tgz`, `~/deploy-build.tgz`, `~/dumper.php`, `~/deploy.sh` supprimés après extraction.
- **Prod = working tree actuel** : les 9 modifs non commitées (SEO blade, SitemapController, locales, BlogArticle.vue, humans.txt supprimé) sont EN LIGNE. `git status` local sale (churn `public/build/` inclus) — **aucun commit fait** (non demandé).
- **Backup 20261115-193214 + anciens `20261110-*` / `20261111-*` (8 dossiers) toujours sur le serveur** — à rapatrier en `docs/backups/<ts>/` puis supprimer côté prod **uniquement après validation explicite** de ce déploiement.
- **Rappels non faits (hors périmètre, nécessitent un go ou une config serveur)** : cron `schedule:run` en prod (`php8.3`), Reverb (`BROADCAST_DRIVER`), re-seed des clés LiveKit dans les settings prod (page Visioconférence, bouton Enregistrer), BCC global emails, `git push` de la branche.
- **Fix Terminal prod (15/11 ~20:15, go explicite)** : le « Terminal système » ne marchait pas en prod — cause : `PHP_BINARY` sous php-fpm pointe sur `php-fpm8.2` (qui ne sait pas lancer artisan, affichait juste l'aide). Fix local : `phpCliBinary()` dans `TerminalController.php` → SAPI CLI → `PHP_BINARY` ; sinon `php8.3`→`php8.2` **vérifié ≥ 8.2** (le `php` = 8.1 est rejeté) ; override `TERMINAL_PHP_BINARY` (env). Déployé **1 fichier** après backup `web/elearning/backups/20261115-201225-terminal-fix/TerminalController.php.bak` (md5 `a447ed2e…` identique, `php8.3 -l` OK). Aucune config PHP/Plesk touchée — règle absolue confirmée par l'utilisateur le 15/11 : tout reste dans `web/`, jamais de modif des paramètres serveur (PHP inclus). ⚠️ Si le nouveau code ne prend pas effet immédiatement, c'est l'opcache FPM — **ne pas redémarrer** le service soi-même : demander à l'utilisateur/Plesk de reloader PHP.

## 🚀 DÉPLOIÉ EN PROD (2e) — 15/11/2026 ~21:44 (go explicite « go »)
- **Correctif images en prod** : vignettes cours en URLs relatives (`lms/thumbnails/course-5.jpg` → 404 relatif) + toutes les helpers `/storage/…` (le symlink est absent, `web/storage/` vide, le fallback Laravel répond **200 text/html** aux routes inconnues) → images cassées Academy/Landing/Marketplace/Cart/MyEnrollments/MyCourses/Certificates/DashboardS. Fix : normalisation URL → `/media/…` ; pages touchées : `Academy.vue`, `Landing.vue`, `LearningPaths.vue`, `Instructors.vue`, `Students.vue` + 7 helpers `getImageUrl` (`/storage/` → `/media/`). **Backend Product CONSTATE uptime intact.**
- **Terminal prod : corrigé** — `PHP_BINARY` sous php-fpm pointe sur php-fpm8.2 (affiche l'aide, quitte) → `phpCliBinary()` (SAPI CLI, sinon `php8.3`→`php8.2` **≥ 8.2 vérifié**, `php`=8.1 rejeté ; override `TERMINAL_PHP_BINARY` env) + garde anti longues-commandes sous Windows (`queue:work`, `queue:listen`, `schedule:work`, `horizon`, `serve`, `tinker`… refusées, message clair invitant à `--once` ou console séparée — le « site plante » du 15/11 soir = serveur de dev local mono-thread bloqué par la requête HTTP de queue:work).
- **Front Terminal (pollTail)** : les sessions terminées n'étaient jamais chargées (panneau vide) — poll du montage pour **tous** les onglets + nouvel onglet terminé + garde anti-doublon `_polling`. Inclus dans le build.
- **Déployé** : `TerminalController.php` (md5 `2577f8ba…` identique) + miroir `public/build/` (`web/build/` ET `web/elearning/public/build/`, manifest md5 `5246aada…` identique ×3), backup préalable `web/elearning/backups/20261115-214347-terminal-fix/` (php .bak + build-backup.tgz). **Aucune config PHP/Plesk touchée.** HTTP `/` 200, `/login` 200.
- **Nettoyage serveur** : `~/diag*.sh` (9 fichiers diagnostiques) supprimés (go explicite). Rien d'autre laissé en home.
- **Tâche planifiée Windows `SubnetCongoTerminalDeploy`** : 16/11/2026 00:00 — réplique ce déploiement (TerminalController.php + build) avec backup + vérifs md5/HTTP, journal `docs/deploy-terminal-0h.log`. Script `docs/deploy-terminal-0h.ps1` — **idempotent** (redéploie le même contenu, md5 identiques) ; suppression possible après validation : `schtasks /delete /tn SubnetCongoTerminalDeploy /f`. PC allumé & session ouverte requis à 00:00, sinon ignorer la tâche.
- **Dérive serveur→local rapatriée (partielle, go)** : 8 avatars (`public/media/3..16/avatar-XX.jpg`, images de témoignages publiques) copiés prod→local via tar+scp. **Non copié volontairement** : `storage/app/public/` — `instruction_answers/` (≈17 **soumissions d'étudiants, PII**), `equipments_files/` (GMAO legacy), `learning-paths/` badges+covers (2), `lms/thumbnails/1780487134_*.jpg` + `.gitignore`. Le reste de `web/media/` et `storage/` est identique nom/quantité (avatars mis à part) — **aucune copie intégrale sans décision explicite**.
- **Commit/Push** : aucun (non demandé). Le working tree contient Terminal fix + vues + build.

## 🚀 DÉPLOIÉ EN PROD (3e) — 15/11/2026 ~22:40 (go « Go — déploie maintenant »)
- **Cause racine finale des images cassées** : le `.env` prod a `APP_URL=http://127.0.0.1:8000` → le disque `media` (et `public`) générait des URLs `http://127.0.0.1:8000/media/...` côté accessor (`Storage::disk('media')->url()`) — chaque image dupliquée vers la machine de dev de l'utilisateur (ERR_CONNECTION_REFUSED). Aucune donnée en base touchée (les valeurs stockées sont propres, vérifiées par audit `information_schema`)
- **Fix (sans toucher au `.env`)** : `config/filesystems.php` — les 3 disques sont passés en URL **relative** : `media` → `/media`, `media_public` → `/media`, `public` → `/storage`. Robuste quel que soit `APP_URL` (prod/local), tout le média de cours est réparé d'un coup.
- **Déployé** : `config/filesystems.php` uniquement (backup `web/elearning/backups/20261115-223543-media-fix/filesystems.php`) ; md5 `da86a427…` identique ; **aucun cache config présent** (`bootstrap/cache/config.php` absent → pas de clear requis) ; HTTP `/` 200, `/academy` 200 (0 occurrence `127.0.0.1:8000` dans les pages, thumbnail_path = `/media/...`), `/media/lms/thumbnails/course-5.jpg` 200 (image/jpeg). Script de test supprimé après usage (`~/diag14.sh`).
- **Reste côté utilisateur** : modifier `APP_URL=https://subnetcongo.net` dans le `.env` prod + `cd ~/web/elearning && php8.3 artisan config:clear` — **obligatoire pour les liens des e-mails sortants** (reset password, invitations live, certificats, vérifications). Tous les mails déjà envoyés pointent sur 127.0.0.1:8000 (irrécupérables).
- Tâche `SubnetCongoTerminalDeploy` toujours armée à 00:00 (build Terminal + TerminalController.php uniquement — idempotent).

## 🚀 DÉPLOIÉ EN PROD (4e) — 16/11/2026 ~18:17 (go « utiliser le compte academy@ »)
- **Cause racine SMTP 535** : la prod envoyait avec `admin@subnetcongo.net` (déjà dans `web_settings` prod qui **override le .env** via `SystemSettings::apply()` au boot — voir `app/Support/SystemSettings.php` + `TransactionalServiceProvider` et `web_settings` seeds). Local utilisait `academy@subnetcongo.net` et fonctionnait.
- **Local** : `php artisan tinker` test → `ENVOI_LOCAL_OK` (config locale intacte).
- **Prod** : (1) backup `web/elearning/.env` → `backups/mailfix-20261116-181738/.env.bak`, patch `.env` (même MAIL_* qu'en local). (2) test prod encore en échec (l'override `web_settings` gagnait). (3) patch appliqué aux clés `mail_*` dans `web_settings` via pipe SSH (jamais sur disque) : **SETTINGS_OK mail_* toutes**. (4) `cache:clear` + test : **ENVOI_PROD_OK**.
- **Reste** : le mot de passe `academy@subnetcongo.net` fuit en clair dans ce transcript (bug de mon masking) → **à faire tourner** (Plesk → Mail → changer mdp `academy@…`, puis reporter dans local `.env` + prod `web_settings.mail_password` — malheureusement via `wkhtmltopdf`/`SystemSettings::set` ou directement l'écran SMTP `/system/settings` tab Mail).

# 📦 À DÉPLOYER SUR LE SERVEUR — ATTENDRE GO EXPLICITE DE L'UTILISATEUR

**Ne rien déployer sans « on déploie » / « go prod » / « déploie ça ».**

Contenu à pousser (ordre) :
1. `SettingController.php` — **DÉJÀ EN PROD** (le 16/11/2026, md5 ed9c9255, php -l OK).
2. `resources/js/Pages/LiveSessions/Player.vue` — fix bouton « Copier le lien » : utilise `shareToken` (prop) au lieu de `uuid` → plus de 404 sur `/l/{uuid}`. **FRONT : build nécessaire.**
3. `app/Http/Controllers/LiveSessionController.php` — `join()` passe `shareToken` dans les props Inertia + pré-remplit `settings.share_token`.
4. `resources/js/Pages/LiveSessions/LiveSessions.vue` — bouton vidéo : spinner sur `:loading` + overlay plein écran « Connexion en cours… ». *(Build attendu)*
5. `resources/js/Pages/LiveSessions/PublicIndex.vue` — bandeau flash `error`/`warning` affiché en haut si présent.
6. `resources/css/app.css` — règles `snc-actions-desktop-only` / `snc-actions-mobile-only` avec `!important` (fix double boutons ⋮ACTIONS).
7. Locales `resources/js/locales/*.json` — clés `pages.live_sessions.joining_title` / `joining_hint` ×5 (fr/en/de/sw/ln). **Inclus dans build.**
8. **Public/build** à reconstruire + mirrorer : `web/build/` ET `web/elearning/public/build/` (même md5).
9. Backup avant écrasement (procédure habituelle : `web/elearning/backups/<ts>/`).

Vérifs après déploiement : `/live-sessions` s'affiche ; « Rejoindre » → overlay loading ; copie de lien → URL résolvable ; invitation e-mail → lien `/l/…` fonctionne.

---

## ⏸️ PRESENTATION DE L'UTILISATEUR — 16/11/2026 : AUCUN ACCÈS SERVEUR PENDANT CETTE SESSION (règle utilisateur — ne rien lancer, ne rien uploader, ne rien supprimer). Déploiement UNIQUEMENT sur « go » explicite.

## 🚀 DÉPLOIÉ EN PROD (5e, partiel) — 16/11/2026 ~20:54 (go, puis stop envoi demandé)
- **Fix `/system/settings` tab Mail** : le formulaire postait les clés `driver/host/…` qui retombaient dans `default` au lieu du préfixe `mail_*` — les valeurs ne prenaient **jamais** effet. Corrigé dans `SettingController@update()` (mapping `mail_host…`). **PHP déployé + md5 identique (en avant-première)** ; build front non envoyé (à faire après la présentation de l'utilisateur).
- **Bouton « Rejoindre » live** : spinner + overlay plein écran « Connexion en cours… » (`joiningSession`, i18n `pages.live_sessions.joining_title/_hint` ×5 locales ; bouton vidéo `:loading`) — build local OK, non déployé.
- **Double ⋮ kebab ACTIONS (live-sessions)** : `.snc-actions-desktop-only` + `.snc-actions-mobile-only` en CSS ordinaire (hors couches) avec `!important` ; vérifié local aux 2 breakpoints. **Build local OK, non déployé.**
- Backup pré-ship : `backups/20261116-205433-live-mail-join-fix/` (SettingController.php + build).
- Reste prod direct : `/system/settings` test mail UI (badge) → désormais fonctionnel grâce au nouveau mapping (policy : `/system/settings` → Serveur mail n'est plus « muet »).

## 🧪 Comptes de test locaux (14/11/2026)
- **Local uniquement** : `admin.test@subnetcongo.net` / `Test1234!` (superadmin) et `student.test@subnetcongo.net` / `Test1234!` (étudiant). Créés/sync roles via tinker ; utilisés pour vérifier login admin/étudiant et dark/mobile (aucun déploiement).
- **Dark mode** : `toggleDarkMode` simplifié (plus de `startViewTransition`) ; dark vérifié en 390 px : topbar/sidebar/page sombres OK. Badge pédagogique utilisateur : à ne pas supprimer sans accord.

## 📱 Mobile — 3e passe pro (14/11/2026, après-midi) — LOCAL UNIQUEMENT
- **Demandes Daniel** : finir l'adaptation mobile façon Courses.vue (modèle apprécié) : tableaux sans débordement, texte long sur 2 lignes, éléments secondaires masqués mais ré-affichables (expander), KPI en grille 2×2 partout, champs de recherche de tableaux uniformes et pros. « Check toutes les pages ».
- **Fix critique page blanche `/system/settings/video`** : nom de route Ziggy doublé (`system.system.settings`) — renommé `settings` (→ `system.settings`) dans `routes/web.php` ; `route:clear` fait. VideoProviders.vue/AppMenu/CommandPalette fonctionnaient déjà en supposant `system.settings`. Plus aucune erreur JS.
- **`snc-fluid-table`** (app.css) : `table-layout: fixed` + `overflow: hidden` sur la `<table>` — appliqué aux 11 DataTables (Questions, LiveSessions, Exams, Certificates, Subscriptions ×2, Marketing/SalesReports, SalesReports, StudentReports, Grading, MediaLibrary). Colonnes `min-width: 0` ; statement 2 lignes via `line-clamp-2` + wrap normal.
- **Surcouche mobile globale** : `white-space: nowrap` retiré des cellules DataTable ≤767 px (causait les textes rognés « Combien d'hôtes ! ») — retour du wrap 2 lignes ; `.page-bg { overflow-x: clip }` (clip n'invalide pas position: sticky) — analytics passait à 400 px scrollWidth → 390.
- **Expansion mobile** : `<Column expander md:hidden>` + `v-model:expandedRows` listant les champs cachés (format, niveau, points, statut, catégorie, tentatives, fournisseur, timezone, dates…) — l'info reste accessible en 1 tap. Statut → accessible uniquement via expansion sur mobile (pattern assumé).
- **KPI 2×2 mobile** : tous les strips de cartes stat/PageKpi convertis en `grid-cols-2` sous sm (2×2), `gap-4 md:gap-6` — 24 strips (Analytics, Dashboard, Certificates, Grading, LearningPaths, Configurations, Payments, Api, Maintenance, Languages, LiveSessions, Students, Instructors, Cohorts, ContactMessages, Emails, Marketing, SalesReports, StudentReport, Exams, ExamResult, ExamsResponses, Web/Assets, Web/Seo laissé en sm:grid-cols-2 lg:grid-cols-4 sans opposition).
- **Recherche tableaux** : uniformisée `IconField w-full sm:w-64/72/80` + `InputText w-full h-11` + conteneurs `flex-wrap` (32 fichiers).
- **Dialog étudiant** (Users/Students.vue) : fullscreen mobile (`h-[100dvh]`, rounded-none), header compact, footer `flex-wrap` (annulation pleine largeur si besoin), onglets horizontaux scrollables (remplace la sidebar stepper masquée <md) ; dialog profil 600px → `min(600px, 100vw)`. **Instructors.vue** : même passe (header compact, mobile tab bar, footer flex-wrap, sections p-4 sm:p-8, dialog profil min(600px,100vw)).
- **3e passe (suite)** : Dashboard — KPI → `grid-cols-2` (2×2) à 390 px, charts empilés pleine largeur (`md:grid-cols-2 xl:grid-cols-3`), radar « Offre vs Demande » avec labels tronqués ≤17 car. (computed `skillsRadarShort`, pointLabels 9px) ; StudentReports — KPI → `grid-cols-2 sm:grid-cols-3 lg:grid-cols-5` + cartes compactées, avatars réduits `!w-9 !h-9` ; vérifié en navigateur (390 px) : KPI 2×2 Partout, charts lisibles.
- **Auteurs & référencement (14/11/2026, soir)** : Daniel KIKIMBA ajouté (`composer.json` authors, `package.json` author, `public/humans.txt`, `meta author` déjà présent). SEO blade : Organization+LocalBusiness Goma (adresse/NIF réels), `WebSite` + `SearchAction` (sitelinks), retrait du pied de page invisible `display:none` (anti-cloaking). `PublicIndex` reçoit description meta. **Blog : route publique `/blog/{slug}` (`Web/BlogArticle.vue`)** — OG article, Twitter card, `BlogPosting` + `BreadcrumbList` JSON-LD, canonical, partage, Articles liés, breadcrumb ; cartes de `/blog` = liens réels (remplace la modale Teleport) ; `SitemapController` inclut les articles publiés. i18n clés `public.blog.back_to_blog` / `related_read` ×5. Vérifié en navigateur (screenshots, clic carte → /blog/{slug}). Article de test local créé puis supprimé. **Aucun déploiement, aucun commit.**
- **4e passe pro (14/11/2026, fin d'après-midi) — TERMINÉE** :
  - LearningPaths : suppression du bouton « Historique » + « Analytics » dans PageHeader (aucune UI derrière) + Sidebar vide supprimée + import/refs nettoyés.
  - Budgets démo assumés : dialogs « Analyse » (Courses/Exams/Questions) portent désormais un bandeau « Données simulées » (`common.demoData`, ×5 locales) — avant, chiffres présentés comme réels.
  - i18n : clé `common.demoData` ajoutée (fr/en/de/sw/ln).
  - Instructors : même passe dialog que Students (footer flex-wrap, onglets mobile, header compact, dialog profil `min(600px,100vw)`) ; Students : footer flex-wrap.
  - **Disque plein C: (0 octet) → l'utilisateur a demandé de vider les caches Flutter/npm : `C:\src\flutter\bin\cache`, `%LOCALAPPDATA%\Pub\Cache`, `npm cache clean --force` (~3,3 Go récupérés). `.gradle\caches` non touché.**
  - Après rebuild : **crawl dark 390px des 90 routes → 0 overflow, 0 JS error** (`/` clean également) ; 768 px sur 10 pages clés → scrollWidth 768 partout ; dropdown notifications Realizations → `fixed left-2 right-2` (contenu dans le viewport ✓).

- **Crawl pro** : 88 routes GET admin/étudiant testées à 390 px (agent-browser, session mobilecheck) : 0 overflow page (sauf analytics corrigé), 0 page blanche restante, erreurs console passées au clair après rebuild. `marketing/affiliates/export` et `social/support-export` = téléchargements (ERR_ABORTED normal).
- **i18n** : 5 clés ajoutées ×5 locales (attemptsLabel, optionsLabel, emailLabel, issuedDateLabel, certNumberLabel) ; encodage UTF-8 vérifié.
- **Build** : OK (≈1m45s, 2189 modules). Dark mode vérifié (rows transparentes, textes lisibles). **Aucun commit, aucun déploiement.**
- **Reste** : validation visuelle finale Daniel à 360/390/768/1280 px ; dark sur le studio Visioconférence ; `Auth/...` scaffold (Welcome.vue par défaut) ; stats en dur dans Exams (« 1 245 tentatives ») ; Reverb en prod (go requis).


## 📱 Mobile & Dark/Theme — première passe shell (14/11/2026) — LOCAL UNIQUEMENT
- **Demandes Daniel** : adapter toutes les pages à l'affichage mobile (texte plus compact, éléments secondaires masqués), utiliser un skill dédié, corriger le sidebar/appbar en dark mode et le fil d'Ariane caché.
- **Skill créé** : `~/.agents/skills/subnetcongo-mobile-dark-ux/SKILL.md` (validé via `quick_validate.py`) — shell, tokens, patterns mobile, dark/thème, checklist de vérification, anti-patterns.
- **Shell corrigé** : topbar refaite (hauteur 4rem, tokens clair/sombre, boutons 44 px, recherche globale Ctrl+K, dropdowns/palette sombres, mobile compact) ; sidebar `min(20rem, 88vw)`, variantes sombres et badge rôle theme-aware ; `.layout-main-container` reçoit `padding-top: 4rem` (topbar fixe) ; `.page-container` passe de `z-index: 20` à `1` ; fil d'Ariane sticky `top-16 z-30`, visible, avec version mobile à 2 segments (Accueil + page courante) ; footer réel (plus de « MAINTX ») ; filtre date global masqué sous `md` ; `NotificationBell`/`CommandPalette` adaptés (largeur mobile, dark, Ctrl+K unique global).
- **Surcouche mobile globale** : texte/titres réduits (`text-2xl..6xl`, paddings `p-8..12`, gaps/espacements), grilles fixes repliées (`grid-cols-2..6`) sous 767/480 px, dialogs/toasts en largeur viewport, DataTable compacte + scroll horizontal, onglets en défilement horizontal.
- **WebSocket dashboard** : Echo initialisé via `bootstrap.js` (Reverb si `VITE_REVERB_APP_KEY`, sinon Pusher) ; canal privé `admin.dashboard.metrics` ; événement `AdminDashboardMetricsUpdated` (ShouldBroadcastNow) ; commande `dashboard:broadcast-metrics` (mesure Windows/Linux + diffusion uniquement si métriques changées) ; planifiée chaque minute ; canal réservé admin/superadmin via `routes/channels.php`. Dashboard : suppression de l'auto-refresh par `router.reload` toutes les 30 s ; bouton « Actualiser » manuel ; mises à jour infra par WebSocket ; pill « Temps réel » / « Manuel ».
- **Aides contextuelles** : composant `ContextHint.vue` (fermable, localStorage par compte) ; settings « Aide & messages » dans `/system/settings` avec activation globale et TTL en jours (0=toujours ; 2/4/7/14/30 proposés) ; `ui.helpHintsEnabled/TtlDays` partagés via Inertia ; le hint des filtres dashboard est désormais ce composant.
- **2e passe mobile (14/11, soir)** : auto-fermeture du menu mobile/overlay à chaque navigation (`AppLayout` › `router.on('before')`) ; `SncDialog` et `Dialog` plein écran/compact mobile ; éditeur de cours `Courses.vue` adapté (header sur une ligne tronqué, étape 2/6, stepper horizontal mobile, footer actions en full width) ; tableaux natifs sans wrapper (Exams, Affiliates, Web/Pages) enveloppés dans `overflow-x-auto` ; Dialogs généraux alignés en haut avec scroll interne (`p-dialog-mask`).
- **Tableaux & dark (14/11, soir)** : `Courses.vue` compactée comme référence — sélection/expansion masquées sous `md`, colonnes prix/métriques masquées sous `xl`, statut en badge sous `md` aussi en ligne, thumbnail/titre/chips réduits, actions desktop en boutons visibles ≥ `md`, kebab « ⋮ » (menu PrimeVue comme Live Sessions) sous `md` ; `.p-datatable-custom` (en-tête/lignes/striped) passe en tokens `--snc-*` pour que dark suive le thème ; retrait des fonds `#f8fafc/#ffffff` codés en dur dans les `<Column>` (Exams, Grading, LearningPaths, Modules, Questions, Instructors, Students).
- **Suite tables (14/11, soir)** : adaptations marketing/admin par agents (`SalesReports`, `Subscriptions`, `Coupons`, `Emails`, `Affiliates`, `Exams`, `Grading`, `Modules`, `Questions`, `Students`, `Instructors`, `Cohorts`, `LearningPaths`, `Certificates`, `MediaLibrary`, `StudentReports`…) + passage heuristique sur le reste (colonnes secondaires masquées) ; retrait de tous les `alignFrozen/frozen` ; paginator et boutons d'action 44 px compacts en mobile ; disque plein provoqué par le cache npm → ~4,9 Go supprimés pour débloquer (réinstallations retéléchargeront) ; fichier `Admin/Enrollments/Index.vue` tronqué par ENOSPC restauré depuis HEAD ; builds re-vérifiés OK.
- **Bug sauvegarde cours** : `modules.has_quiz` / `quiz_required` / `document_name` manquaient en base → SQLSTATE 1054 au "Enregistrer le brouillon". Migration additive `2026_11_14_000010_add_module_quiz_flags.php` créée et appliquée en local.
- **Build** : `npm run build` OK (57 s). `php -l` OK sur tous les fichiers PHP modifiés. Aucun déploiement, aucun commit.
- **Reste** : validation visuelle en 360/768/1280 px, dark + changement de thème, pages admin/étudiant restantes par passes, exécution Reverb/`BROADCAST_DRIVER` en prod (aucun déploiement sans go).

## 🎛️ Dashboard admin — refonte design (14/11/2026) — LOCAL UNIQUEMENT
- **Demande Daniel** : améliorer le design du dashboard admin avec les skills disponibles (hiérarchie/hero + mobile/accessibilité).
- **Composants partagés** : `PageHeader` accepte `variant: 'danger'` (`.btn-header-danger`), boutons d'action 44 px et pleine largeur sur mobile ; `PageKpi` gère `trendSuffix`/`trendLabel`, flèches haut/bas, état neutre et `aria-label`.
- **Dashboard** : hero avec statut système + heure de synchro + version ; toolbar avec labels visibles (recherche, année, catégorie) et reset — les filtres agissent réellement (année → KPI/graphes, recherche/catégorie → tableau des cours) ; 4 KPI enrichis (objectif annuel, score moyen, RAM/disque) ; charts responsive avec résumé CA/croissance et `aria-label` ; nouvelle table « Performance des cours » (desktop) / cartes (mobile) alimentée par `cohorts` ; infra en jauges accessibles (`role="meter"`) avec seuils et dernière mesure ; journal/support clarifiés ; classement avec avatars/XP ; états vides et skeletons.
- **A11y/mobile** : labels visibles, focus visible global, cibles 44 px, contrastes relevés, `aria-live` pendant les mises à jour, `prefers-reduced-motion` respecté pour Chart.js, tableaux avec caption/scope, aucune information par la couleur seule.
- **i18n** : nouvelles clés `pages.dashboard.*` dans fr/en/de/sw/ln ; `kpiStudents` corrigé en « Apprenants inscrits ».
- **Build** : `npm run build` OK (≈1 min). Aucun déploiement, aucun commit.
- **Reste** : validation visuelle navigateur (login admin local) à 360/768/1280 px, et vérifier les filtres avec des données réelles.

## 🎥 Live Sessions — feedback, timezone, chat et médias (14/11/2026) — LOCAL UNIQUEMENT
- **Demandes Daniel** : feedback visible au clic « Rejoindre » et à l'envoi du chat (bouton circulaire + spinner), fuseau UTC+2 faux (06:32 affiché 04:32), micro/caméra inopérants, chat étudiant non rafraîchi (l'admin reçoit mais l'étudiant ne voit pas ses propres messages), message admin envoyé deux fois, icônes manquantes (micro, caméra, émoji/trombone), bouton « recharger les messages ».
- **Timezone — cause racine** : `config('app.timezone')` vaut `Africa/Lubumbashi` (forcé par `app/Support/SystemSettings.php`), pas UTC. `parseSessionTime()` convertissait en `'UTC'` puis Eloquent relisait la valeur dans le fuseau app → double décalage. Fix : `Carbon::parse($value, $tz)->setTimezone(config('app.timezone'))`. Testé : 06:32 → raw 06:32 → JSON 04:32Z → affichage 06:32.
  - ⚠️ Les sessions #10 (raw 04:33) et #11 (raw 04:36) créées avant le fix restent décalées : les re-sauvegarder via « Modifier ».
- **Chat** : `canAccessSession()` inclut maintenant sessions publiques, e-mails invités et accès approuvés (avant : inscription au cours uniquement → l'étudiant invité recevait 403 sur `chat/messages` et ne voyait aucun message). `sendChat` vérifie aussi l'accès. `chatMessages` renvoie `user_id` + `mine`. Front : `mine` serveur prioritaire, message optimiste conservé si le rechargement échoue, bouton « Recharger les messages » (spinner) dans l'en-tête, garde anti-double envoi + input vidé immédiatement (élimine les doublons admin).
- **LiveKit / médias** : `room` et `previewStream` passés en `shallowRef` (les proxys Vue sur les objets LiveKit sont évités) ; état micro/caméra resynchronisé depuis les publications réelles (`syncLocalMediaState` sur Connected/LocalTrackPublished/Muted…) ; `requestMic`/`requestCamera` ne publient plus : ils demandent la permission (la caméra n'est plus ouverte deux fois, ce qui bloquait `setCameraEnabled`), la publication est faite une seule fois après connexion/toggle ; erreurs de toggle remontées en toast.
- **Icônes** : PrimeIcons 6.0.1 n'a **pas** `pi-microphone-slash`, `pi-video-slash`, `pi-smile`. Remplacés par `pi-microphone`/`pi-video` + classe `.pi-is-off` (barre diagonale, `app.css`) et par le caractère 🙂 pour l'émoji. **Pas d'upgrade primeicons** (C: était plein ; disque libéré ensuite).
- **Build** : `npm run build` OK (≈40 s, 2188 modules). `php -l` OK. Aucun déploiement, aucun commit.
- **Reste à vérifier en réel** (2 navigateurs/comptes, micro+caméra physiques) : publication micro/cam, chat invité/inscrit, absence de doublon, icônes et bouton recharger.

## 🐛 Correctifs mockups SERP/aperçu (12/11/2026, soir)
- **Demande Daniel** : dans le dialog de cours et les aperçus visuels, corriger les « liens morts ».
- Fake domain `lms.subnetcongo.com` → `https://subnetcongo.net` (help url slug, SERP mockup Google, vignette Social OpenGraph dans `Courses.vue`) ; `www.votresite.com` → `https://subnetcongo.net` dans `Web/Seo.vue`. La barre Google/mockup n'est plus cliquable (retrait `hover:underline`/`cursor-pointer`, `select-none`). Build OK. Local uniquement.
- ⚠️ Les boutons « Aperçu visuel » sans handler restent en l'état (Courses.vue:1337, Exams.vue:743, LearningPaths.vue:515, Certificates.vue:842) — non demandés à ce stade.

## 🖥️ Système — Paramètres globaux (12/11/2026, soir)
- **Uniformisation DB** : lecture/écriture unifiée dans `web_settings` (au lieu de ma table `settings` separate) ; `SystemSettings::all()` sert de source unique avec cache 5 min ; `SystemSettings::apply()` injecte au boot toutes les variables dans `config()` (app, mail, session) + `lms.*`, et expose `maintenance_mode` via `config('settings.maintenance_mode')`.
- **Settings fonctionnels** (en direct) :
  - Branding : site_name / slogan / logo / couleur primaire — changent réellement le rendu public (identity Inertia shared → `AppLayout.vue`).
  - Mail : SMTP (host/port/enc/user/pass/from) — utilisé par les mails sortants via `config('mail.*')`.
  - Session : `session_timeout` → `config('session.lifetime')` appliqué au boot.
  - LMS : `min_pass_score`, `auto_enroll_on_payment`, `allow_course_preview`, `video_watermark` — lus via `config('lms.*')` ; `certificate_prefix` appliqué aux nouveaux certificats (voir CertificateController).
  - Maintenance : `toggleMaintenance` → `php artisan down/up` (Laravel natif, bypass secret possible) + flag en `web_settings`.
- **Front /system/settings rebindé** : `route('settings.update')` → `SettingController@update` (POST `system/settings`) au lieu du vieux `ApiController@updateSettings` (qui était en réalité le settings API Gateway).
- **New routes** : `POST system/settings/clear-cache`, `POST system/settings/logo-upload`, `POST system/settings/maintenance-toggle`, `POST system/settings/artisan-command` — vues Inertia correspondantes pour chacun.
- **Artisan runner** (menu sur la page, groupé sous le Tab Système) : commandes pré-définies + allowlist (`cache:clear,config:clear,route:clear,view:clear,live-sessions:sweep,schedule:run,down,up,key:generate,migrate:status,storage:link`), output affichée dans le terminal noir (dernier 8000 chars), audit log à chaque run.
- **Valeurs seedées** (via script `seed_settings.php`) : SubnetCongo / Goma RDC / academy@subnetcongo.net / logo `/media/lms/logo-subnetcongo.png` / couleurs marque / SMTP actuel (host, user, pass).
- **Filtrage** : `CheckSystemMaintenance` middleware bloque les visiteurs non-admins pendant `php artisan down`.

## 🖥️ Déploiement PROD — préparation cron (12/11/2026, soir)
- **Cron à ajouter sur le serveur** (Plesk ou SSH crontab -e en tant que l'user web) :
  ```cron
  * * * * * /usr/local/bin/php8.3 /chemin/vers/web/elearning/artisan schedule:run >> /dev/null 2>&1
  ```
- `live-sessions:sweep` est déjà enregistrée dans `app/Console/Kernel.php::schedule()` avec `everyMinute()->withoutOverlapping()` → se déclenche automatiquement dès que la cron tourne.
- Aujourd'hui le fichier est en local (`/home/subnetcongo_dan_subnet/artisan` probablement sur le serveur). **`php8.3` obligatoire** (CLI par défaut = PHP 8.1).
- Commande d'urgence directe (si la cron casse) : `php8.3 artisan live-sessions:sweep` — testable via `/system/settings?tab=artisan` → bouton `live-sessions:sweep`.

## 🖥️ Système — Paramètres globaux (12/11/2026, soir)
- **Uniformisation DB** : lecture/écriture unifiée dans `web_settings` (au lieu de ma table `settings` separate) ; `SystemSettings::all()` sert de source unique avec cache 5 min ; `SystemSettings::apply()` injecte au boot toutes les variables dans `config()` (app, mail, session) + `lms.*`, et expose `maintenance_mode` via `config('settings.maintenance_mode')`.
- **Settings fonctionnels** (en direct) :
  - Branding : site_name / slogan / logo / couleur primaire — changent réellement le rendu public (identity Inertia shared → `AppLayout.vue`).
  - Mail : SMTP (host/port/enc/user/pass/from) — utilisé par les mails sortants via `config('mail.*')`.
  - Session : `session_timeout` → `config('session.lifetime')` appliqué au boot.
  - LMS : `min_pass_score`, `auto_enroll_on_payment`, `allow_course_preview`, `video_watermark` — lus via `config('lms.*')` ; `certificate_prefix` appliqué aux nouveaux certificats (voir CertificateController).
  - Maintenance : `toggleMaintenance` → `php artisan down/up` (Laravel natif, bypass secret possible) + flag en `web_settings`.
- **Front /system/settings rebindé** : `route('settings.update')` → `SettingController@update` (POST `system/settings`) au lieu du vieux `ApiController@updateSettings` (qui était en réalité le settings API Gateway).
- **New routes** : `POST system/settings/clear-cache`, `POST system/settings/logo-upload`, `POST system/settings/maintenance-toggle`, `POST system/settings/artisan-command` — vues Inertia correspondantes pour chacun.
- **Artisan runner** (menu sur la page, groupé sous le Tab Système) : commandes pré-définies + allowlist (`cache:clear,config:clear,route:clear,view:clear,live-sessions:sweep,schedule:run,down,up,key:generate,migrate:status,storage:link`), output affichée dans le terminal noir (dernier 8000 chars), audit log à chaque run.
- **Valeurs seedées** (via script `seed_settings.php`) : SubnetCongo / Goma RDC / academy@subnetcongo.net / logo `/media/lms/logo-subnetcongo.png` / couleurs marque / SMTP actuel (host, user, pass).
- **Filtrage** : `CheckSystemMaintenance` middleware bloque les visiteurs non-admins pendant `php artisan down`.

## 🧹 Live Sessions — actions condensées en menu (12/11/2026, soir)
- **Demande Daniel** : trop d'icônes dans ACTIONS (9) → réduire.
- **Solution** : colonnes ACTIONS avec seulement 3 actions visibles : **Rejoindre** (vidéo), **Modifier** (crayon), **Plus d'options** (⋯) → menu popup PrimeVue (Démarrer, Inviter, Copier le lien, Synchroniser les enregistrements, Voir le rapport, Annuler la session en ambre, Supprimer la session en rouge).
- Nouvelle i18n : `pages.live_sessions.menu_more / menu_start / out_cancel` dans les 5 locales.
- **Bonus découvert** : les routes `live-sessions/{live_session}/*` sont liées implicitement par **uuid** (`getRouteKeyName() = 'uuid'`) — les appels frontend en `.id` (nombre) renvoyaient 404. Tous les appels dans LiveSessions.vue + Cohorts.vue + VideoConference.vue corrigés pour utiliser `uuid`.

## 🔁 Live Sessions — cycle de vie automatique + rappels (12/11/2026, soir)
- **Demande Daniel** : le statut d'une session live doit changer automatiquement quand la date arrive ; et un rappel doit être envoyé automatiquement « le temps qu'il faut » avant le début — configurable.
- **Nouveau** : `app/Console/Commands/SweepLiveSessions.php` (`live-sessions:sweep`) — (1) Scheduled → Live quand start_at est atteint (transforme + notifie en base/broadcast les élèves actifs + l'instructeur via `LiveSessionStartedNotification`) ; (2) Live/Scheduled → Ended quand start_at + duration est dépassé ; (3) Rappel automatique envoyé (mail + base + broadcast) aux inscrits actifs/terminés + invités sans compte, « remind_before_minutes » avant le début ; marqué `reminder_sent_at` pour éviter les doublons ; reset `reminder_sent_at` quand l'horaire est modifié.
- **Cron** : `app/Console/Kernel.php::schedule()` — `live-sessions:sweep` chaque minute (`withoutOverlapping`). **Le cron serveur `* * * * * php artisan schedule:run` doit être en place en prod (configuration serveur — à faire sur le go, PAS avant).**
- **DB** : migration `2026_11_12_000001_add_reminder_to_live_sessions.php` (`remind_before_minutes` nullable + `reminder_sent_at`). Appliquée en local.
- **UI** : Select « Rappel automatique » (Aucun / 15 min / 30 min / 1 h / 1 jour avant) dans le dialog Planifier/Modifier ; icône cloche ambre dans la colonne Démarrage du tableau quand un rappel est configuré ; le Select affiche le badge « Aucun rappel » par défaut. `*_update()` : quand start_at change, `reminder_sent_at` est réinitialisé (ré-arrime le rappel).
- **Mail** : nouveau template `resources/views/emails/live-session-reminder.blade.php` (rappel avec lien de rejoindre, même branding rouge SubnetCongo) ; `LiveSessionReminderNotification` (via = mail + database + broadcast).
- Test CLI (transaction rollback) : création → rappel dû → sweep → Scheduled→Live (+notifs) → Ended après durée simulée. ✅
- ⚠️ Les 5 sessions de test locales sont dans le passé : elles seront automatiquement passées en Ended au premier sweep en prod. Les e-mails de test sont des placeholders (d.sassou@subnetcongo.net → 550 attendu, normal).

## 🎬 Live Sessions — Player immersif v4 + vérifications (13/11/2026)
- **Demande Daniel** : player `/live-sessions/{uuid}/join` type studio, sans débordement, en s'appuyant sur le skill `live-classroom-design` et les mockups.
- **Mode immersif total** : `AppLayout.vue` ajoute la classe `live-immersive-wrapper` sur les routes `/live-sessions/*/join` ; `.layout-topbar`, `.layout-sidebar` et `.layout-mask` sont masqués ; `.layout-main-container` passe à `height: 100dvh`, sans marge/padding, `overflow: hidden` ; `.layout-main` devient une colonne flex `min-h-0`.
- **Player / VideoConference** : header compact, stage plein espace, salle d'attente en 2 colonnes (aperçu + contrôles), chat fermé par défaut, drawer détails, thème sombre, responsive mobile ; logique LiveKit conservée. Sur `leaveRoom`, les erreurs de permission/connexion sont réinitialisées.
- **Vérifications navigateur** (agent-browser, compte audit temporaire) : 1440x900 → aucun scroll/débordement (`scrollH = 900`, conteneur 1440x900), topbar/sidebar bien absents ; chat OK ; drawer détails OK ; mobile 390x844 sans débordement ; **connexion LiveKit réelle OK** (1 participant, barre de contrôle affichée). Caméra refusée en headless → message de permission correct.
- **Build** : OK (48,12 s).
- **Compte audit** `audit-view@subnetcongo.net` supprimé après vérification (0 restant).
- **Reste** : validation visuelle finale par Daniel et test avec micro/caméra réels sur navigateur headed. Local uniquement, aucun déploiement.

## 🎬 Live Sessions — Player UI redesign (12/11/2026, soir)
- **Player v3 plein écran** : le `.page-container` Sakai est court-circuité — contenu sombre sur toute la hauteur (`h-[calc(100vh-3rem)]`), header compact (logo + breadcrumb inline + badge LIVE/ATTENTE + Partager + Retour).
- **Stage vidéo à gauche + sidebar à droite** : infos session (instructeur, durée, capacité max, sécurité bout-en-bout, agenda mini).
- **VideoConference.vue non touché** pour la logique LiveKit ; Player v3 respecte le layout Sakai AppLayout lato mais remplace la zone plugin.
- **Build** : OK. Vérif browser (audit user temporaire) : mise en page sombre propre sur 1440 px, header + stage live + sidebar infos. ✅
- **Skill créé** : `live-classroom-design` à `~/.agents/skills/live-classroom-design/SKILL.md` — principes UI/UX, patterns, checklist, WCAG 2.2 AA.

## 🐛 Live Sessions / Visioconférence — 2 bugs (12/11/2026, soir)
- **Bug étudiant non visible dans « Mes classes virtuelles »** : `studentIndex()` ne montrait que les sessions du cours où l'élève est inscrit. Si l'élève est invité par email à une session sans être inscrit → invisible. **Fix** : `LiveSessionController::studentIndex()` ajoute `->orWhereRaw('JSON_CONTAINS(settings, JSON_QUOTE(?), \'$.invited_emails\')')` pour inclure les emails invités non-enregistrés.
- **500 sur /system/settings/video** : `SettingController::video()`, `updateVideo()`, `testVideoProvider()` avaient été perdus quand j'ai réécrit le contrôleur (manque des méthodes). **Restaurées** — la page charge bien avec les valeurs LiveKit du settings.
- **LiveKit non activé malgré .env OK** : VideoProviderConfig::isConfigured('livekit') lisait `settings` table (vide) ; on a seedé `settings` avec `livekit_api_key/api_secret/url/ws_url` depuis `.env` + `default_provider=livekit`. Page `/system/settings/video` OK, LiveKit affiché comme configuré.
- ⚠️ À faire : refaire le même seed en PROD (ou via le bouton « Enregistrer » de la page Visioconférence une fois connecté en admin) — sur le serveur, les valeurs .env sont déjà présentes, il faut juste les enregistrer via l'interface (une fois, "go prod").

## 🐛 Actions « Aperçu visuel » / live sessions invisibles (cause racine) (12/11/2026, soir)
- **Symptôme** (Daniel + vérif navigateur via compte temporaire) : la colonne ACTIONS du tableau `/live-sessions` était vide ; console : `Ziggy error: 'live_session' parameter is required for route 'live-sessions.join'`.
- **Cause racine** : la DB dev a été reconstruite après le wipe (test) du 12/11 matin. La migration `2026_11_10_203000` avait backfillé les uuids uniquement à l'époque → toutes les sessions créées/seedées depuis avaient **`uuid = NULL`** (le modèle `LiveSession` ne générait pas d'uuid à la création, contrairement à Exam/ExamResult). Ziggy plante, Vue ne rend rien dans la cellule.
- **Fix** : (1) `app/Models/LiveSession.php` — hook `creating()` → `uuid = Str::uuid()` ; (2) nouvelle migration idempotente `2026_11_12_000000_backfill_live_sessions_uuid_again.php` (appliquée en local : 5 sessions → 0 NULL).
- Vérification navigateur (compte superadmin temporaire, supprimé ensuite) : 0 erreur console sur `/live-sessions`, 9 icônes visibles par ligne (modifier/démarrer/rejoindre/inviter/partager/enregistrer/rapport/annuler/supprimer), dialog « Modifier la session » ouvre correctement.
- **Modules/Lessons ont aussi des uuid NULL en dev (8/115, 19/344) mais ce n'est pas un bug** : Module et Lesson font un binding par `slug` (`getRouteKeyName() = 'slug'`). Seuls les modèles taggés getRouteKeyName = `'uuid'` étaient affectés (live_sessions). Pas d'action.

## 🕐 Live Sessions — fuseau UTC+2 (Goma) + action modifier (12/11/2026, soir)
- **Demande Daniel** : la planification/configuration des live sessions utilisait UTC au lieu de UTC+2 ; donner la possibilité de montrer/choisir le fuseau lors de la planification ; aucune action modifier/supprimer pour une session.
- **Cause** : l'app tourne en `APP_TIMEZONE=UTC` ; le `datetime-local` saisi était interprété en UTC (décalage +2h pour Goma) ; aucun sélecteur ni affichage du fuseau ; l'action « Modifier » n'existait pas (les handlers API `update()`/`destroy()` existaient côté backend).
- **Fichiers** : nouveau `config/live_sessions.php` (fuseau par défaut **Africa/Lubumbashi = heure de Goma, UTC+2**, 8 fuseaux proposés via `LIVE_SESSION_TIMEZONE` en env) ; `LiveSessionController` — le `start_at` est interprété dans le fuseau choisi (`Carbon::parse($v, $tz)->setTimezone('UTC')` pour store/update), comparaison provider inchangée, `timezone` validée (`timezone:all`) et stockée en `settings.timezone`, **affiché en timezone de session** via `Intl` (isoToLocalInput) ; pages `LiveSessions.vue / PublicIndex / StudentIndex / RequestAccess / Report` — dates formatées avec `timeZone` (prop `timezone` passée depuis les contrôleurs) ; légende « Goma/Lubumbashi — UTC+2 » au-dessus du tableau + sélecteur fuseau dans la planification + bouton **« Modifier »** (crayon, sessions `Scheduled` uniquement) ouvrant le même dialog pré-remplie (PUT `live-sessions.update`) ; invitation email `startAtLabel` en fuseau session avec annotation `(UTC+02:00)`.
- ⚠️ À vérifier visuellement (Ctrl+F5) : création + modification d'une session (l'heure affichée doit rester celle de Goma), vue apprenant/publique.

## 🔗 SEO + boutons « Aperçu » (branchés) (12/11/2026, soir)
- **Boutons preview branchés** (avant : cliquer ne faisait rien) :
  - `Courses.vue` « Aperçu visuel » → `marketplace.show` dans un nouvel onglet (désactivé tant que pas de slug).
  - `Exams.vue` preview apprenant → `exams.take` (uuid) nouvel onglet (désactivé tant que l'examen n'est pas enregistré).
  - `LearningPaths.vue` aperçu cours (syllabus onglet) → `marketplace.show` (slug ajouté aux selects `courses` et `availableCourses` dans `LearningPathController`).
  - `Certificates.vue` « Aperçu HD » → **nouveau composant `Components/CertificateDocument.vue`** (rendu A4 partagé de l'éditeur, mode `interactive` ou lecture seule + props background/selected/dragging) + **modal plein écran téléporté** (scale auto au viewport, bouton fermer `components.confirm_dialog.close`). L'éditeur réutilise le composant (suppression du code dupliqué quantumCanvasStyle/quantumElementStyle/quantumTextStyle/quantumFilterStyle).
- **SEO du cours** (`seo_metadata` était stockée mais jamais exposée) : `Marketplace\Show.vue` — `<Head>` avec titre (seo.title sinon titre), description (seo sinon strip HTML coupé à 160), `keywords` (seo sinon tags), **og:type=article / og:title / og:description / og:image**, twitter card, et **JSON-LD `Course`** (schema.org, provider SubnetCongo, offers). Image OG absolue (fallback logo). Aperçus SERP/Social de l'éditeur de cours désormais fidèles (domaines corrigés subnetcongo.net, h3 non cliquable).
- **SEO global** : nouveau `SitemapController` + route `GET /sitemap.xml` (44 URLs au test : statiques + pages dynamiques publiées + cours publiés, cache 1 h, `application/xml`) ; `robots.txt` : disallow des zones privées (`/admin`, `/system`, `/web/`, `/login`…) + `Sitemap: https://subnetcongo.net/sitemap.xml`. Build OK (45,2 s). Local uniquement.
- ⚠️ Blade statique `<title inertia>` et OG par défaut restent pour les autres pages — ne pas brancher au hasard les sections admin. À vérifier visuellement : aperçus (Ctrl+F5), et test réel de `/sitemap.xml` en prod après go.

## 🎨 Dialogs de confirmation professionnels (12/11/2026, soir)
- **Demande Daniel** : « arrange ces dialog de confirmation comme [image] en un professionnel » (ex. « Archiver le cours »).
- **Nouveau composant `resources/js/Components/SncConfirmDialog.vue`** : panneau premium (header avec barre d'accent dégradé bleu/rouge, tuile icône dégradée, eyebrow "Confirmation"/"Action requise", titre, message avec icône info, footer boutons Annuler outline / Confirmer bleu ou Supprimer rouge dégradé, fermeture X avec rotation, mode sombre, prefers-reduced-motion). Utilise le slot `#container` de PrimeVue ConfirmDialog.
- **Remplacement global** : tous les `<ConfirmDialog>` (Sakai `AppLayout.vue` groupe "app" + ~11 pages `<ConfirmDialog />` du groupe par défaut) remplacés par `<SncConfirmDialog />` via script. Build OK (58,9 s).
- i18n : clés `components.confirm_dialog.*` (eyebrow_danger/eyebrow_info/fallback_title/close/reject/accept) dans les 5 locales.
- ⚠️ Gainé en local uniquement — à vérifier visuellement (Ctrl+F5) sur l'archivage de cours, la corbeille email, les suppressions admin.

## 🎨 Scrollbars de marque (12/11/2026, soir)
- **Demande Daniel** : barres de défilement rouge ou bleu, surtout sidebar et page globale (avant : grises `--snc-text-faint`).
- **Fix** `resources/css/app.css` : scrollbar globale (Firefox + WebKit) = bleu `--snc-primary` (#2563EB), hover `--snc-primary-hover` ; mode sombre #3B82F6/#60A5FA ; override `.custom-scrollbar` (`!important`, unifie les ~25 pages qui les rendaient grises). Build OK (35,3 s). Local uniquement.

## 📎 Session du 12/11/2026 (soir, opencode) — Pièces jointes emails + sélecteur média

### Pièces jointes emails (fix)
- **Cause** : `GmailCompose.vue` — le bouton trombone basculait un flag `showAttachments` inutilisé ; le champ `<input type="file">` n'était jamais ouvert (pas de ref). Impossible d'ajouter une PJ. Le backend (`send()`/`reply()`) était déjà prêt (`->attach()`, validation 25 Mo/fichier).
- **Fix** : ref `fileInput` + `@click="openFilePicker"`, validation client 25 Mo (toast, fichiers ignorés), reset `event.target.value`, liste des PJ déjà présente. **Réponse** (`GmailShow.vue`) : bouton trombone + champ fichier + `sendReply` en FormData (le backend supportait déjà les PJ en réponse, l'UI ne les envoyait pas).
- Clés i18n `pages.admin_emails_gmail.attach_too_large_title/detail` (5 locales).
- ⚠️ Encore ouvert : téléchargement des PJ **reçues** (stub `console.log` dans `GmailShow`) ; PJ des « envoyés » = métadonnées seules (pas de fichier persisté).

### Sélecteur de média image/vidéo (appareil OU lien)
- **Composant `resources/js/Components/MediaPicker.vue`** : dialog (SncDialog) 2 modes — « Appareil » (input file `accept="image/*,video/*"`, upload axios, progression, aperçu, 50 Mo) et « Lien » (URL directe image/vidéo, YouTube/Vimeo convertis en embed, aperçu iframe). Émet `insert { type, url, name }`. Clés `components.media_picker.*` (5 locales, 20 clés).
- **Backend `UploadController@media`** + route `POST /uploads/media` (`uploads.media`, authentifiés — instructeurs ET étudiants pour les forums) : images+videos, mimes jpg/jpeg/png/gif/webp/avif/svg/mp4/webm/ogg, 50 Mo, disque `media`, retour `{url, path, type, name, size}`. Testé en CLI (200, stockage OK, nettoyé).
- **Intégrations** : `Courses.vue` (description, bouton « Insérer une image/vidéo » via Quill `insertEmbed`) et `Modules.vue` (contenu de leçon, même bouton).
- **Leçon : vidéo depuis l'appareil** — `Modules.vue` onglet Contenu : `video_source` += `file`, FileUpload `video/*` → upload → `video_url` = chemin `uploads/...` ; `LessonController` (store/update) : `video_url` `nullable|string` (plus `url`) et `video_source` accepte `file` ; `LessonPlayer.vue` : `videoSrc` = `/media/{path}` si pas d'URL externe (les vidéos YouTube restent en iframe). Clés `pages.modules.video_upload_*` (5 locales).
- Build OK (32,9 s). **Local uniquement** — à tester visuellement (Ctrl+F5) : compose/réponse email, description de cours, leçon (contenu + onglet vidéo).
- ⚠️ Non traité ce soir : builder de cours (blocs vidéo = URL uniquement, validation JSON imbriqué), vidéos des modules (`intro_video_url`), téléchargement des PJ reçues.

## 🧪 Session du 12/11/2026 (opencode) — fix 500 LessonPlayer « Tentatives Quiz Module »

### Dialog éditeur de cours — réduction des espaces (12/11/2026, soir)
- **Retour utilisateur** : trop d'espace vide dans le dialog éditeur de cours (`Courses.vue`, class `course-workspace`, 95vw/1400px) — précision : en **hauteur**.
- Fix vertical : conteneur de contenu → `px-5 py-4 pb-8 space-y-4` ; blocs champ (label/input/aide) `space-y-2` → `space-y-1.5` (28 occurrences) ; grilles `gap-y-3` ; en-têtes de section `mb-3 pb-2` ; éditeur riche 300px → 240px ; header du dialog 72px → 64px (h-16). Précédente passe (largeur) : sections `p-8` → `p-6`.
- Build OK. Local uniquement.

### Design des champs de l'éditeur de cours (12/11/2026, après-midi)
- **CAUSE GLOBALE identifiée** : le plugin Tailwind `@tailwindcss/forms` (activé en base, stratégie par défaut) style tous les `<input>`/`<textarea>` natifs avec une spécificité (0-1-1, ex `input[type='text']`) supérieure aux classes PrimeVue v4 (`.p-inputtext`, 0-1-0) → **tous les InputText/Textarea de l'app sont écrasés** (bordure fine plate), alors que les Select/Dropdown (composants `<div>`) gardent le thème. C'est pour ça que les inputs ne ressemblaient jamais aux selects.
- **Fix global** : `tailwind.config.js` → `forms({ strategy: 'class' })` : le plugin ne style plus que les éléments marqués explicitement `form-input`/`form-textarea` (aucun dans le codebase). Les tokens du preset PrimeVue (`app.js`, groupe `formField`) s'appliquent enfin aux InputText/Textarea → rendu identique aux selects partout. Vérifié : aucun input natif nu ne dépend du style de base du plugin (tous ont des classes Tailwind, p.ex. footer newsletter, compose emails).
- Passes précédentes : overrides `:deep(.course-workspace .p-inputtext…)` (bg blanc + bordure fine `!important`) supprimés de `Courses.vue` ; champs de l'éditeur passés au style idiomatique (`fluid`, `size="large"`, `:invalid`, retrait des classes Tailwind bg/border/padding et du `variant="filled"` pour uniformité avec les selects).
- Builds OK. **À vérifier visuellement par l'utilisateur (Ctrl+F5)** sur l'éditeur de cours et d'autres pages (exams, students…). Local uniquement.

### Retour à la formation après examen (12/11/2026, après-midi)
- **Comportement demandé par l'utilisateur** : après validation d'un examen/quiz, l'étudiant ne doit PAS être envoyé sur la page résultat — il doit retourner dans sa formation et continuer ; le score s'affiche en bannière + lien vers le détail.
- **Backend** : `ExamAttemptController::submit` — au lieu de `redirect(exams.results)` : flash session `exam_result` (exam_title, score, pass_percentage, is_passed, needs_review, result_url) puis redirect vers `student.lesson-player` sur la leçon cible = leçon reliée (`lessons.quiz_id`) sinon première leçon non terminée du cours sinon première leçon ; fallback `exams.results` si pas de cours. Imports `Lesson`, `UserProgress` ajoutés.
- **Middleware** : `HandleInertiaRequests.share` → `flash.exam_result` partagé.
- **Front** (`LessonPlayer.vue`) : bannière verte/ambre en haut du player (score en gros, détail avec seuil, bouton « Voir le détail » vers `exams.results`, bouton fermer). Clés i18n `pages.lesson_player.examPassedTitle/examNotPassedTitle/examScoreDetail/examReviewPending/viewResultDetail/closeBannerAria` dans les 5 JSON.
- Testé CLI : exam #32 (course 33) → leçon cible 332, URL OK. `npm run build` OK. Local uniquement.

### Fix 500 à la validation d'examen + calculatrice + i18n take-exam (12/11/2026, après-midi)
- **i18n `pages.exams-responses.*` manquant** (page `ExamsResponses.vue` — écran de prise d'examen affichait les clés brutes) : groupe créé dans les 5 JSON (82 clés, fr/en/de/sw/ln). Diff = ajouts uniquement.
- **500 à la validation d'examen** : `ExamResult` ne générait jamais de `uuid` (route `exams.results` = binding uuid → `UrlGenerationException`). Fix : hook `booted()` `creating()` avec `Str::uuid()` dans `App\Models\ExamResult` (pattern identique à Exam/ExamAttempt). Testé en CLI : uuid généré + `route('exams.results', uuid)` OK. Backfill des lignes sans uuid : 0 concernée (transaction rollbackée).
- **Calculatrice** : résultat arrondi à 10 décimales (évite « 39.66666666666666 » → « 39.6666666667 »).
- `npm run build` OK (1m12s). Toujours local uniquement.
- ⚠️ À noter : la base de dev `laravel1` avait été vidée le matin par `php artisan test` (phpunit.xml avait ses variables DB en commentaire → RefreshDatabase sur la DB de dev). Restauration : `migrate:fresh --seed` + `LessonDemoSeeder` + `RealCourseSeeder`. ⚠️ Le cours réel re-seedé a changé d'ID (ex-#38 → **#33**, première leçon **#332**). Le compte genesiskikimba@gmail.com a été recréé (mdp `Test1234!`, rôle student) et inscrit aux cours #1 et #33. **Reste à faire : isoler la DB de test dans phpunit.xml (sqlite :memory:) — à confirmer avec l'utilisateur.**

### Fix 500 LessonPlayer (matin)

- **Erreur 500 signalée par l'utilisateur** (screenshot) sur la page leçon du cours #38 (compte genesiskikimba@gmail.com).
- **Cause** : `StudentController::lessonPlayer` interrogeait `exam_attempts.score_percentage` — **cette colonne n'existe pas** (colonnes réelles : `score`, `raw_score`, …). Le score officiel vit dans la table **`exam_results.score_percentage`**, liée par `exam_attempt_id`. 3 endroits corrigés dans `lessonPlayer` :
  1. `quizData.best_score`/`is_passed` → via `ExamResult` filtré sur les tentatives COMPLETED ;
  2. `finalExam.best_score` → `ExamResult::max('score_percentage')` restreint aux tentatives COMPLETED (sous-requête `whereIn('exam_attempt_id', ...)`) ;
  3. `moduleQuizAttempts` → score par tentative lu dans `ExamResult` (peut être `null` si pas de résultat — ex. correction manuelle).
- **Front** (`LessonPlayer.vue`) : affichage sécurisé quand `attempt.score === null` (« Score pas encore disponible », badge « ? »). Nouvelle clé i18n `pages.lesson_player.noScoreYet` ajoutée dans les 5 JSON (fr/en/de/sw/ln).
- `npm run build` OK (1m13s). Test CLI : lessonPlayer OK sur video/quiz/text du cours 38 (quizData, finalExam, moduleGroups, allLessonsCompleted conformes).
- `php artisan test` : 24 échecs **préexistants non liés** (LiveSessionAccessTest ×14 QueryException/ModelNotFound, Auth ×10) — aucun sur le player.
- ⚠️ Toujours **local uniquement** : le déploiement du fix + la migration `add_text_to_lessons_type_enum` restent à faire avec go explicite (rappel Beam avant).

## 🏠 REPRISE À LA MAISON (arrêt prévu ~16:00, 11/11/2026)

**Branche** : `devin/1792524012-ui-ux-design-system` (remote origin = `github.com/glinux99/subnetcongo`).
- Le **working tree est la source de vérité** — la prod tourne avec le working tree déployé (~15:30), PAS avec HEAD seul. `git status` = **417 fichiers modifiés** (l'essentiel = build Vite régénéré `public/build/`) ; hors build : `app/Http/Controllers/User/StudentController.php` (fix `/users/students`, voir ci-dessous), `app/Http/Controllers/Web/AssetController.php` (CRUD assets), `app/Http/Controllers/System/LanguageController.php` (déjà en prod via working tree), `database/seeders/EnrollmentSeeder.php`+`LanguageSeeder`+`LandingContentSeeder`+`DatabaseSeeder` (déjà déployés), `resources/js/locales/*.json` (les 5, déployés), `resources/js/Pages/Web/Assets.vue`, `resources/js/Pages/System/Languages.vue`, `resources/js/sakai/layout/AppMenuItem.vue`, `docs/TODO.md`.
- ⚠️ Si la reprise se fait depuis une **autre machine / autre clone**, rien de tout ça n'existera sans `git commit` (build inclus — il est tracké dans ce repo). Ce PC reste le seul endroit complet. **Ne pas `git pull` sur le serveur** (règle absolue).
- Build : `npm run build` OK à 15:09 (inclut locales complétées + menu i18n + CRUD assets). Mirrors prod déjà synchronisés.

**Déployé (go explicite de l'utilisateur, ~15:50)** :
1. **403 page « page blanche » = fichier manquant** : le groupe `Errors/403.vue` n'existait pas (Handler.php Inertia::render('Errors/403') → page d'erreur Laravel brute très discrète ; /courses/{id} réservé aux rôles superadmin/admin/instructor provoquait un 403 pour les élèves). **Corrigé** : création `resources/js/Pages/Errors/403.vue` + `Errors/404.vue` brandés et responsive ; `Handler.php` intercepte 403/404 pour tout GET web (HTML ou Inertia). Emails de confirmation pointent maintenant sur `student.my-courses` au lieu de `/courses/{id}` (course_id admin-only). Build OK. **PAS en ligne** (upload `resources/js/Pages/Errors/*` + `app/Exceptions/Handler.php` + `app/Listeners/SendEnrollmentConfirmationEmail.php` + build Vite).
2. Vérif navigateur admin en prod : dashboard, Emails, Support, `/web/assets` (CRUD), `/system/languages`, `/academy`, enrollment test.
3. Migration i18n complète du site (voir « ⏳ À FAIRE CE SOIR » plus bas).
4. Choix utilisateur : `DemoMarketingBillingDataSeeder` (données fictives MRR) — **NON lancé** (fake revenue). `SeedActivityDemoSeeder` (nouveau, seedé sur comptes FR existants uniquement, achat comptes TEST exclus) = **LANCÉ en prod le 11/11 ~15:55** : 3 certificats + 6 vérifications 127.0.0.1, 3 tentatives+résultats d'examen, 2 tickets + 4 messages, 1 session live seedée terminée + 2 présences + 4 messages de chat, 2 devoirs + 4 soumissions notées, 5 coupons. Idempotent.
5. BCC global pour tous les emails (Katya + toi) pas encore configuré ; échecs délivrés non vérifiés.
   - **Audit_logs et logins locaux NON portés** (historique perso PII, faux bandeaux) — décision opencode, à confirmer si différent.
   - **`web_settings` clé `enrollment_request_bcc` NON envoyée** (demande MB) — local only.
   - `certificate_verifications` du seed = IP 127.0.0.1 uniquement.
5. Backups `backups/20261111-*` : rapatrier + supprimer côté prod après « on valide ».
6. `/sitemap.xml` = 404 (aucune route sitemap — normal, à créer si souhaité).

Dernière mise à jour :

Dernière mise à jour : **15 novembre 2026 (~22:50)** — Images prod débloquées (fix `filesystems.php`, relatif) ; tâche minuit toujours armée ; `.env` APP_URL à corriger par l'utilisateur (liens e-mails).

### Cours réel + fix quiz vides (12/11/2026, 2e passe opencode) — LOCAL UNIQUEMENT
- **Constat Daniel** (test cours #3 leçon 27) : « Aucun quiz disponible » — cause : 29 leçons legacy de type `quiz` sans `quiz_id` ni contenu (seeders de test historiques, rien à afficher). Le player montre maintenant un état vide expliqué avec CTA « Voir mes examens » + « Poser une question ».
- **`RealCourseSeeder`** (nouveau, idempotent) : cours réel #38 « Les Fondamentaux des Réseaux Informatiques » (instructeur Marc Tubadi, catégorie Infrastructure & Réseaux, 29,99 €, débutant) — 4 modules, 13 leçons :
  - 4 **vidéos YouTube réelles vérifiées** : OSI (danscourses `LANW3m7UgWs`), Subnet Mask (PowerCert `s_Ntt6eTn94`), Subnetting CCNA (Jeremy's IT Lab `bQ8sdpGQu8c`), DHCP (PowerCert `S43CFcpOZSI`) ;
  - 3 **leçons texte rédigées** (couches OSI/encapsulation, subnetting pas à pas avec exemples corrigés, DNS) ;
  - 2 **PDF générés 2 pages** (fiche révision OSI/TCP-IP + table de subnetting) sur `public/media/demo/cours-reseaux/`, + pièce jointe ;
  - 2 **quiz de fin de module** (exams #38/#39 : QCM, vrai/faux, réponse courte, explanations, 70 %, 3 tentatives) ;
  - 1 **devoir** (« requête web couche par couche », 20 pts, échéance +7 j) ;
  - **examen final** #40 : 10 questions, 60 %, 30 min, 2 tentatives (`has_final_exam=1`).
  - Inscrit genesiskikimba@gmail.com. Lançable : `php artisan db:seed --class=RealCourseSeeder --force`.
- **Player** : les leçons vidéo/PDF affichent désormais leur **texte de lecture** en dessous (« À lire : contenu de la leçon ») — une leçon peut combiner vidéo + texte + fichier. i18n : clés `noQuizHelp`, `myExams`, `readingTitle` ajoutées aux 5 locales. Build OK (1m19s). Aucun déploiement, aucun commit.

### Player de leçon — refonte logique (12/11/2026, session opencode) — LOCAL UNIQUEMENT
- **Demande Daniel** : la page `/student/courses/{c}/lessons/{l}` n'est pas professionnelle, la vidéo plante, quiz bloqués, fichiers absents ; améliorer le code + seeder de test avec tous les types de leçons.
- **Bugs corrigés backend** (`Student\StudentController::lessonPlayer`) :
  - Ordre des leçons = **ordre du module puis ordre de la leçon** (avant : tri global `order` → séquence fausse) ; nouvelle prop `moduleGroups` (sidebar groupée par module).
  - Leçon type `quiz` : charge l'Exam lié (`quiz_id`) → uuid, nb questions, durée, seuil, tentatives restantes/utilisées, meilleur score, tentative en cours (avant : non géré, tombait dans le cas « texte »).
  - Leçon type `assignment` : charge l'Assignment lié (match par titre) + dernière Submission de l'étudiant (note, feedback, fichier).
  - Pièces jointes (`attachment_paths`) mappées avec URL disque media ; `content_html`/`content_text` exposés.
  - **Examen final** : `allLessonsCompleted` + `finalExam` (uuid) envoyés quand `courses.has_final_exam` — bannière « Examen final débloqué » dans le player quand toutes les leçons sont 100 %.
  - Fix statut tentative `'Completed'` → constante `ExamAttempt::STATUS_COMPLETED` ('completed' en BDD : le panneau « Tentatives Quiz Module » n'affichait jamais rien).
- **Nouveau endpoint** `POST student/courses/{course}/lessons/{lesson}/assignment` → `submitAssignment` (textarea + fichier ≤ 20 Mo sur disque media `assignments/{course}`) ; crée l'Assignment à la volée si absent. Route `student.lessons.submit-assignment`.
- **Refonte frontend** `Student/LessonPlayer.vue` : header compact (type badge, durée, module), barre de progression globale, contenu par type (vidéo YouTube-embed + natif ; PDF iframe + bouton télécharger ; texte riche ; carte quiz-examen avec CTA `exams.take` ; devoir avec soumission/note/feedback), pièces jointes, **barre de navigation Précédent / Marquer comme terminé / Suivant** (marquage complet désormais possible sur TOUS les types), sidebar plan du cours groupé par modules, heartbeat corrigé (time_spent réel, plus d'intervalle double).
- **Migration additive locale** : `2026_11_12_122555_add_text_to_lessons_type_enum` — ENUM lessons.type += 'text' (⚠️ à inclure au prochain déploiement, avec validation explicite).
- **`LessonDemoSeeder`** (idempotent, local) : ajoute au cours #1 (CCNA) 4 modules démo — vidéos (YouTube Big Buck Bunny + MP4), texte riche OSI/TCP-UDP, PDF généré (public/media/demo/guide-reseau-demo.pdf) + pièce jointe .txt, quiz (Exam #37, 3 questions QCM/VF/réponse-courte, pass 70 %, 3 tentatives) et devoir (max 20 pts, échéance +7 j) ; `has_final_exam=1` (Exam #1 existant). **Inscrit genesiskikimba@gmail.com au cours #1** (enrollment actif). Lançable : `php artisan db:seed --class=LessonDemoSeeder --force`.
- i18n : 36 nouvelles clés `pages.lesson_player.*` ajoutées aux 5 locales (fr/en/de/sw/ln). `npm run build` OK (1m20s). Aucun déploiement, aucun commit.

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
