# PROMPT IA — REFONTE COMPLÈTE DU E-LEARNING SUBNETCONGO

> Copier-coller ce prompt dans une session IA (Claude Opus / GPT-4 / Gemini 1.5 Pro) pour orchestrer une refonte intégrale du LMS.
> Le contexte en tête est obligatoire ; les sections numérotées sont les livrables exigés.

---

## CONTEXTE OBLIGATOIRE (à coller en début)

```
Tu travailles sur le projet SUBNETCONGO e-learning :
- Stack : Laravel 11.x (PHP 8.2+) + Inertia.js + Vue 3 (Composition API) + PrimeVue 4 (Aura preset) + Tailwind 3 + Vite
- Base de données : MySQL/MariaDB, Eloquent ORM
- Auth & rôles : Laravel Sanctum + Spatie Permission (rôles : superadmin, admin, instructor, cohort-assistant, student, finance-manager, support-agent, moderator)
- i18n : vue-i18n (FR, EN, LN, SW existants — ajouter DE)
- Build : npm run dev / npm run build via Vite
- Tests : PHPUnit + Pest (à mettre en place)
- Le projet est sur Windows (PowerShell 5.1). Toujours utiliser `;` entre commandes PowerShell et `if ($?)` pour les chaînes dépendantes. Préférer `cd` via le paramètre workdir des outils bash.
- Tu as le droit de modifier n'importe quel fichier sauf la config git/CI. Pas besoin de demander confirmation pour des changements de structure.
- Tu ne dois JAMAIS inventer de route, model ou package. Toujours vérifier avec grep / read avant d'agir.

Modules déjà en place : Cours, Modules, Leçons, Examens, Questions, ExamAttempt, ExamResult, Certificats, Enrollments, UserProgress, Learning Paths, Cohortes, Forums, Support tickets, LiveSessions, Recordings, Orders, Payments, Subscriptions, Coupons, EmailCampaigns, Affiliates, Roles, Permissions, WebSetting, Blog, Pages, Menus, Testimonials, MediaLibrary, Resources (polymorphique), Categories, AuditLog, ChatGroup.

Manques identifiés (par audit du code) :
1. Aucun système de notifications persistantes en BDD (ni temps réel Echo)
2. Aucun marketplace de cours côté public (page catalogue + checkout)
3. Aucune intégration paiement réelle (Stripe/PayPal/Mobile Money) — seulement structure Order/Payment
4. Pas d'allemand dans les locales
5. Pas de thèmes persistés en BDD
6. Pas de tests automatisés
7. Dashboard Analytics basique (peu de widgets)
8. Player vidéo simple, pas de sessions live intégrées Zoom/Jitsi/BBB
```

---

## TÂCHES À RÉALISER (par ordre de priorité)

### 🟥 P0 — CRITIQUE (bloque la prod)

#### T1. Système de notifications complet
**Backend** :
- Migration `create_notifications_table.php` (id, user_id, type, title, message, data JSON, read_at, created_at) avec index sur user_id+read_at
- Model `App\Models\Notification` + traits Notifiable + interface
- Service `App\Services\NotificationService` : `send(user, type, title, message, data)`, `markAsRead(id)`, `markAllAsRead(userId)`, `unreadCount(userId)`
- Channel broadcasting `App\Events\NotificationSent` (broadcast on `private-notifications.{userId}`)
- Controller `App\Http\Controllers\NotificationController` : index, markRead, markAllRead, unreadCount
- Routes : `GET notifications`, `POST notifications/{id}/read`, `POST notifications/read-all`
- Trigger notifications sur : nouvelle inscription, nouveau certificat, rappel examen, réponse forum, mention, live imminente

**Frontend** :
- Composant `NotificationBell.vue` (cloche + badge dans TopBar) — composant PrimeVue OverlayPanel
- Dropdown liste avec notifications temps réel via Laravel Echo
- Marquer comme lu au clic / "tout marquer"
- Lien vers la ressource concernée
- Son optionnel

**Tests** :
- Feature test : envoi notification + récupération + marquage

#### T2. Marketplace public de cours
**Backend** :
- Page `GET /marketplace` (accessible sans auth mais enrichie si loggué)
- Filtres : catégorie, niveau, prix, langue, durée, rating
- Detail `GET /marketplace/{course}` avec preview vidéo, programme, instructeur, avis
- Panier (session-based) : `POST /cart/add`, `DELETE /cart/{course}`, `GET /cart`
- Checkout mock : `GET /checkout`, `POST /checkout/process`
- Webhook paiement : `POST /webhooks/payment/{provider}` (signature vérifiée)
- Refactor Order/Payment pour supporter les nouvelles sources

**Frontend** :
- Pages `Marketplace.vue`, `MarketplaceCourse.vue`, `Cart.vue`, `Checkout.vue`
- Hero avec recherche + filtres avancés (PrimeVue Slider, MultiSelect, Rating)
- Cards uniformes (cohérence avec DashboardS)
- Étapes de checkout (panier → adresse → paiement → confirmation)
- Page de confirmation `OrderConfirmation.vue`

**Routes à ajouter** :
- `marketplace.index`, `marketplace.show`, `cart.*`, `checkout.*`, `webhooks.payment`

#### T3. Paiement multi-provider
**Backend** :
- Interface `App\Contracts\PaymentGateway` (méthodes : createIntent, capture, refund, verifyWebhook)
- 3 implémentations squelette : `StripeGateway`, `PayPalGateway`, `MobileMoneyGateway` (Orange Money / M-Pesa / Airtel)
- Choix du provider via `PaymentService` selon le pays + préférence utilisateur
- Migration `add_payment_metadata_to_payments_table.php`
- Settings `payment_gateways` (JSON : config par provider, activé/désactivé)
- Webhook générique + signature par provider

**Frontend** :
- Page `System/Payments.vue` enrichie : config par provider (clé API, secret, mode test/live)
- Stepper checkout : choix provider → formulaire provider → confirmation

---

### 🟧 P1 — IMPORTANT (différenciation produit)

#### T4. Internationalisation 5 langues
- Ajouter `resources/js/locales/de.json` (clé complète, traductions cohérentes avec FR/EN)
- Compléter les éventuelles clés manquantes dans FR/EN/LN/SW
- Helper de traduction côté backend pour emails : `__('email.welcome', [], $user->locale)`
- Détection auto langue depuis `Accept-Language` header à l'inscription
- Sélecteur de langue persisté en BDD (table `user_locale_settings` ou colonne sur `users`)

#### T5. Thèmes persistants multi-preset
- Migration `create_user_theme_settings.php` : `user_id`, `preset` (aura|lara|bootstrap|nora), `primary_color`, `dark_mode`
- API `GET/PUT /api/user/theme`
- Composant `ThemePicker.vue` enrichi : preview live, persistance serveur, partage entre appareils
- 3 nouveaux presets en plus de Aura : **Nora** (élégant), **Bootstrap-like** (familier), **Dark Academia** (premium)
- Thème "Haute Contrast" pour accessibilité

#### T6. Player vidéo enrichi
- Composant `VideoPlayer.vue` réutilisable : HLS / MP4 / YouTube / Vimeo
- Contrôles : vitesse (0.5x à 2x), qualité (auto/1080/720/480), sous-titres (.vtt), plein écran, PiP
- Reprise automatique (`localStorage` + sync backend)
- Tracking temps de visionnage → met à jour `UserProgress.time_spent_seconds`
- Marque-pages / notes horodatées
- Mini-map chapitres cliquables

#### T7. Sessions live intégrées
- Migration `add_meeting_to_live_sessions_table.php` : `meeting_provider` (zoom|jitsi|bbb|internal), `meeting_id`, `join_url`, `host_url`, `passcode`, `recording_url`
- Service `LiveSessionService` : génère lien Zoom/Jitsi, planifie rappel
- Page `Student/LiveRoom.vue` : iframe Jitsi Meet intégré + chat latéral + participants
- Tableau de bord instructeur : démarrer/arrêter live, capturer participants, lancer enregistrement
- Notification automatique 15 min avant

#### T8. Analytics avancés
- Page `Analytics/Advanced.vue` (admin) : heatmap d'activité 24h×7j, funnel conversion, rétention cohorte
- Export CSV/PDF des rapports clés
- Widgets live (mise à jour toutes les 30s via Echo)
- Page instructeur `Instructor/Analytics.vue` : mes cours, mes étudiants, revenus
- Graphiques Chart.js supplémentaires : Sankey (parcours apprenant), Radar (skills)

---

### 🟨 P2 — POLISH (qualité de vie)

#### T9. UI/UX moderne — DIRECTIVES STRICTES
- **Cohérence** : tous les boutons primary en violet (#8B5CF6), CTA en emerald pour les actions positives
- **Tokens** : utiliser exclusivement les classes Tailwind + variables CSS PrimeVue
- **Micro-interactions** : hover scale 1.02, transitions 200ms ease-out, focus ring 2px violet
- **Empty states** : illustration + texte + CTA pour chaque liste vide
- **Loading states** : skeletons (pas juste des spinners)
- **Toasts** : utiliser le service Toast uniformément (pas d'alert())
- **Responsive** : tester mobile (sidebar collapsible, cards empilées)
- **Accessibilité** : aria-labels, focus visible, navigation clavier complète
- **Dark mode** : couverture totale (formulaires, charts, modals)

#### T10. Tests
- Configuration Pest (`tests/Pest.php`)
- Test feature : enrollment flow complet
- Test feature : exam attempt + grading
- Test unit : NotificationService
- Test feature : checkout + webhook payment
- Test browser (optionnel) : parcours étudiant

#### T11. Sécurité
- Vérifier toutes les policies (`CoursePolicy`, `EnrollmentPolicy`, `ExamPolicy`, `CertificatePolicy`)
- Rate limiting sur tentatives d'examen (`throttle:10,1`)
- CSRF sur tous les POST
- Headers sécurité (CSP, X-Frame-Options, etc.) via middleware
- Audit log sur toutes les actions sensibles (déjà en place, vérifier exhaustivité)

#### T12. Performance
- Cache Redis pour : leaderboard, stats dashboard, top cours
- Eager loading systématique (vérifier absence de N+1)
- Pagination Cursor sur les grandes listes
- Image optimization (Spatie Media Library + thumbnails)
- Lazy loading composants Vue (`defineAsyncComponent`)
- Route caching activé en prod

#### T13. Documentation
- `README.md` complet : setup, env vars, commandes, déploiement
- `docs/ARCHITECTURE.md` : schéma de l'app, flux clés
- OpenAPI spec pour les endpoints `/api/*`

---

## RÈGLES D'OR

1. **Toujours vérifier avant d'agir** : `grep` pour les noms de routes, `glob` pour les fichiers existants, `read` pour le contexte
2. **Compatibilité ascendante** : ne pas casser les routes/vues existantes
3. **Tous les PHP doivent passer `php -l`**, tous les builds Vite doivent passer
4. **Une migration par modification de schéma**, jamais d'altérations inline
5. **Tous les nouveaux modèles** : fillable + casts + relations + scopes + accessors utiles
6. **Tous les nouveaux contrôleurs** : FormRequest pour validation, Resource pour les réponses API
7. **i18n systématique** : aucune string hardcodée en français dans le JS, toujours via `$t()`
8. **Permissions explicites** : toute route sensible derrière `has_role:` ou `permission:`
9. **Tests pour toute logique métier** non triviale
10. **Commit message clair** : `feat(notifications): add realtime bell + db persistence`

---

## MÉTHODOLOGIE DE TRAVAIL

1. Commencer par lire 3 fichiers : `routes/web.php`, `app/Http/Kernel.php`, `resources/js/app.js`
2. Identifier les noms de routes existants pour ne pas en inventer
3. Pour chaque tâche P0 : migration → model → controller → routes → vue → test
4. Vérifier après chaque sous-tâche : `php -l` + `npx vite build`
5. Documenter dans le code (commentaires PHPDoc + JSDoc)
6. En fin de session : récapitulatif des fichiers créés/modifiés

---

## RÉSULTAT ATTENDU

Une application e-learning opérationnelle, sécurisée, performante, accessible, multilingue (5 langues), avec :
- ✅ Notifications temps réel
- ✅ Marketplace public + checkout multi-provider
- ✅ Player vidéo pro
- ✅ Sessions live intégrées
- ✅ Analytics avancés
- ✅ UI/UX moderne et cohérente
- ✅ Couverture de tests >70% sur la logique métier
- ✅ Documentation complète

---

## NOTES TECHNIQUES SPÉCIFIQUES À CE PROJET

- Le projet est sur Windows. Pour les commandes bash : utiliser `;` entre commandes, `if ($?)` pour les enchaînements conditionnels
- PrimeVue 4 : la config du thème se fait via `definePreset` dans `app.js`, modifier `MyCustomPreset`
- Pour Ziggy (route helper côté JS) : `route('courses.index')` — ne JAMAIS utiliser de string URL brute
- Le pattern Inertia pour les props partagées : `HandleInertiaRequests::share()`
- Le pattern Spatie : `User::hasRole('admin')` ou middleware `'role:admin'`
- Les composants Vue sont dans `resources/js/Components/` (réutilisables) ou `resources/js/Pages/` (vues Inertia)
- Les CSS globaux : `resources/css/app.css` + `resources/js/sakai/assets/tailwind.css`

---

**FIN DU PROMPT — Bonne refonte !**