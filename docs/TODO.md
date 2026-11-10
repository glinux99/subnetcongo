# TODO / JOURNAL DE BORD — Agents IA

> **RÈGLE ABSOLUE POUR TOUT AGENT IA (ouvrir AVANT tout travail)**
>
> 1. Lire ce fichier en entier, puis `docs/agents/decisions.md`.
> 2. Lire l'état serveur dans la section « Déploiement » ci-dessous avant toute action sur le serveur.
> 3. Ne jamais uploader `.env`, `vendor/`, `node_modules/`, ni supprimer de fichiers sur le serveur.
> 4. Les changements sont sur la branche `devin/1792524012-ui-ux-design-system`.
> 5. Mettre ce fichier à jour à chaque étape importante (le prochain agent doit pouvoir reprendre sans contexte oral).

Dernière mise à jour : **10 novembre 2026, 18h05** (déploiement terminé — site vérifié 200).

---

## 1. Contexte

Application e-learning Laravel + Vue 3 + Inertia + PrimeVue, déployée sur `https://subnetcongo.net`.

- **Repo local** : `elearning/` (ce repo), branche `devin/1792524012-ui-ux-design-system`.
- **Serveur (SSH, clé `~\.ssh\id_ed25519`)** : `subnetcongo_dan_subnet@subnetcongo.net`, port 22.
  - Docroot public : `web/` (index.php Laravel à la racine).
  - App Laravel : `web/elearning/` (autoloadée par `web/index.php`).
  - Assets Vite buildés : `web/build/` (manifest.json + assets), upload manuel des `public/build/*`.
  - Médias publics : `web/media/`.
- Le dépôt git sur le serveur **n'est pas synchronisable** (checkout CRLF divergeant, modifications locales, 4389 entrées dans `git status`). Le déploiement se fait par **upload de fichiers**, pas par `git pull` côté serveur.
- `.env` serveur **ne doit jamais être écrasé** : il contient la config de prod (IMAP, DB…).

---

## 2. Travail réalisé (en cours, commit du 10/11/2026 « amelioration de ce qu on fait - gemail »)

### Emails (interface type Gmail)
- Vues : `resources/js/Pages/Admin/Emails/Gmail.vue` (inbox + dossiers + recherche + sélection multiple + KPIs), `GmailShow.vue` (lecture/réponse/transfert), `GmailCompose.vue` (composition, pièces jointes, brouillons), `Sent.vue` (envoyés + statuts + renvoi), `SentShow.vue` (nouveau), `SentEmail` (déjà en base via migration `2026_11_10_090257_create_sent_emails_table`).
- Backend : `AdminEmailController` (IMAP/SMTP, actions masse, archivage, corbeille, suppression), `EmailService`, `EmailTemplateService`, `EmailTemplateController`.
- **Templates email** : ajout du champ **BCC** (`database/migrations/2026_11_11_110000_add_bcc_emails_to_email_templates_table.php`).
- Template welcome refondu : `resources/views/emails/welcome.blade.php` + `app/Mail/WelcomeEmail.php`.
- Rapport détaillé : `RAPPORT_INTERFACE_GMAIL.md` (racine du repo).

### Notifications
- Nouveau `app/Notifications/BaseAppNotification.php` (**database + broadcast**, `ShouldBroadcastNow`, title/message/icon/severity/url).
- Toutes les notifications existantes refactorisées dessus (Enrollment*, CourseEnrolled, CertificateIssued, NewQuestion, QuestionAnswered…).
- Nouvelles : `EmailSentNotification`, `LiveSessionInvitationNotification`.
- Page notifications : `resources/js/Pages/Notifications/Index.vue`.

### Live sessions / visioconférence
- **Nouveau** : `app/Services/Video/VideoProviderConfig.php` — config visio stockée en table `settings` (groupe `video`), prioritaire sur `.env`, modifiable depuis l'admin sans redéploiement.
- Nouvelle page admin : `resources/js/Pages/System/VideoProviders.vue` (+ route).
- `app/Http/Controllers/LiveSessionController.php` refonte, `resources/js/Pages/LiveSessions.vue`, `Player.vue`, nouvelles pages `LiveSessions/PublicIndex.vue`, `StudentIndex.vue`, `RequestAccess.vue` (+ routes), invitation par notification, `LiveAttendance`, `LiveSessionChatMessage`.
- Providers vidéo refondus : `LiveKitService`, `ZoomService`, `GoogleMeetService`, `BbbService`, `JitsiService`.
- Test : `tests/Unit/LiveSessionAccessTest.php` (nouveau, à exécuter/maintenir).

### Support client
- Refonte `SupportTicketController`, `resources/js/Pages/Social/Support.vue` (réécriture complète), nouveau `app/Models/SupportMessage.php` + `database/migrations/2026_11_11_120000_create_support_ticket_messages_table.php`, `SupportTicket` enrichi.

### Dashboard / divers
- `GreetingMessage` (migration `2026_11_11_100000_create_greeting_messages_table.php`, `GreetingService`, seeder, messages d'accueil par rôle/période sur le dashboard).
- Examens : events `ExamPassed` / `ExamFailed` + listener `SendExamResultEmail`; `SendPasswordChangedEmail`.
- `DashboardController`, `Marketplace/Show.vue`, `AppLayout.vue`, `AppMenu.vue`, layout sakai.
- Assets buildés mis à jour dans `public/build/` (uploadés sur le serveur, MD5 vérifiés le 10/11/2026 17:39).

---

## 3. Ce qui reste à faire

- [x] **Déployer le code applicatif** sur le serveur (upload du 10/11/2026 18h04, backup `web/deploy-backups/20261110-180450/`, 55 fichiers app + 217 public écrasés, site vérifié 200).
- [x] **Lancer les migrations** sur le serveur (✅ 2026_11_11_100000 greeting_messages, 110000 bcc_emails, 120000 support_ticket_messages).
- [x] **Vider/rebuilder les caches** Laravel avec `php8.3 artisan config:clear route:clear view:clear` (⚠️ le CLI par défaut est PHP 8.1, incompatible — utiliser `php8.3`).
- [x] Seeders idempotents appliqués : `GreetingMessageSeeder`, `TicketCategorySeeder`.
- [ ] **Vérifier en prod** : login admin → Emails (inbox, envoi, brouillon, envoi échoué → renvoyer), templates (BCC), live sessions (création, invitation, lien public/request-access), support tickets, notifications realtime (broadcast), dashboard (greeting), examen → email de résultat.
- [ ] **Commiter** le travail en cours (branche en avance d'1 commit sur origin, ~240 fichiers untracked dont build + app, ~54 modifiés) et **pusher** la branche.
- [ ] Exécuter la suite de tests (`php artisan test` ou pest) y compris `tests/Unit/LiveSessionAccessTest.php`.
- [ ] Le serveur garde les anciens assets build hashés (inoffensifs, optionnel de les nettoyer).
- [ ] Améliorations Gmail déjà proposées : étiquettes, filtres avancés, règles automatiques, intégration calendrier, multi-comptes, mode hors ligne (voir `RAPPORT_INTERFACE_GMAIL.md`).
- [ ] Remplacer les CRLF du serveur par LF lors d'une prochaine reprise propre du repo serveur (actuellement `.gitattributes` force LF ; le serveur est en CRLF → faux positifs dans `git status`).
- [ ] NE PAS `git pull` sur le serveur (divergence). Toujours uploader via tar/scp avec sauvegarde préalable.

## 4. Procédure de déploiement (serveur)

```powershell
# 1) Tar du code app (sans public/, vendor/, node_modules/, .env, .git)
# 2) Tar de public/ (build + media) -> extraction dans web/
# 3) scp vers ~/deploy-<date>/ ; sauvegarde des fichiers écrasés dans
#    web/deploy-backups/<timestamp>/ puis extraction
# 4) NE PAS toucher .env ; ensuite : php8.3 artisan migrate --force + config/route/view clear
```

- Cible app : `web/elearning/` ; cible public : `web/` (le contenu de `public/` est la racine web).
- `web/` est un **symlink** vers `/var/www/clients/client2/web3/web` ; les chemins sont relatifs au home.
- ⚠️ Le CLI par défaut du serveur est **PHP 8.1** (composer exige >= 8.2) : toujours utiliser `php8.3 artisan …`. Le site tourne via une autre version PHP côté web.
- Backups : `web/deploy-backups/20261110-180450/` (dernière sauvegarde, arborescence conservée).
- Tars/manifests d'upload : `~/deploy-2026-11-10/` (hors racine web).
- Ce fichier est aussi présent sur le serveur : `web/elearning/docs/TODO.md`.
- Vérifier https://subnetcongo.net après déploiement.
