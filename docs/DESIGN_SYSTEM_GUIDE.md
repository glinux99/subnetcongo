# Guide du Design System SUBNETCONGO

Ce document guide l'utilisation des composants standardisés du design system SUBNETCONGO pour l'application e-learning.

## Principes Fondamentaux

### 1. Tokens CSS
Tous les tokens sont définis dans `resources/css/app.css` dans la section `:root` et `.app-dark`. Utilisez toujours ces variables plutôt que des valeurs en dur.

```css
/* Couleurs */
--snc-primary: #2563EB;
--snc-primary-hover: #1D4ED8;
--snc-success: #059669;
--snc-warning: #D97706;
--snc-danger: #E11D48;

/* Espacements et Rayons */
--snc-radius: 1rem;
--snc-radius-sm: 0.75rem;

/* Ombres */
--snc-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
--snc-shadow-hover: 0 12px 24px -8px rgba(15, 23, 42, 0.12);
```

### 2. Composants Standardisés

#### SncButton
Bouton standardisé avec plusieurs variantes et tailles.

```vue
<!-- Variantes -->
<SncButton variant="primary">Action principale</SncButton>
<SncButton variant="secondary">Action secondaire</SncButton>
<SncButton variant="danger">Action destructive</SncButton>
<SncButton variant="success">Confirmation</SncButton>
<SncButton variant="ghost">Action discrète</SncButton>

<!-- Tailles -->
<SncButton size="sm">Petit</SncButton>
<SncButton size="md">Normal (défaut)</SncButton>
<SncButton size="lg">Grand</SncButton>

<!-- Avec icône -->
<SncButton icon="pi-check" icon-position="left">Valider</SncButton>
<SncButton icon="pi-arrow-right" icon-position="right">Continuer</SncButton>

<!-- États -->
<SncButton :loading="true">Chargement...</SncButton>
<SncButton :disabled="true">Désactivé</SncButton>
```

**Règles d'utilisation :**
- Utilisez `variant="primary"` pour l'action principale unique de l'écran
- Utilisez `variant="secondary"` pour les actions secondaires
- Utilisez `variant="danger"` pour les actions destructives (suppression, annulation)
- Utilisez `variant="ghost"` pour les actions discrètes ou de navigation

#### SncInput
Champ de saisie standardisé avec validation.

```vue
<!-- Usage basique -->
<SncInput v-model="email" label="Email" type="email" required />

<!-- Avec erreur -->
<SncInput v-model="password" label="Mot de passe" type="password" :error="errorMessage" />

<!-- Avec placeholder -->
<SncInput v-model="search" placeholder="Rechercher..." />
```

**Règles d'utilisation :**
- Toujours fournir un `label` pour l'accessibilité
- Utiliser `:error` pour afficher les messages de validation
- Le message d'erreur est automatiquement annoncé aux lecteurs d'écran

#### SncBadge
Badge pour les états et catégories.

```vue
<!-- Variantes -->
<SncBadge variant="primary">Nouveau</SncBadge>
<SncBadge variant="success">Terminé</SncBadge>
<SncBadge variant="warning">En attente</SncBadge>
<SncBadge variant="danger">Erreur</SncBadge>
<SncBadge variant="info">Info</SncBadge>

<!-- Avec point -->
<SncBadge variant="success" :dot="true">En ligne</SncBadge>

<!-- Tailles -->
<SncBadge size="sm">Petit</SncBadge>
<SncBadge size="md">Normal (défaut)</SncBadge>
<SncBadge size="lg">Grand</SncBadge>
```

#### SncCard
Carte conteneur standardisée.

```vue
<!-- Usage basique -->
<SncCard title="Titre" subtitle="Description">
  Contenu de la carte
</SncCard>

<!-- Avec header et footer -->
<SncCard title="Statistiques">
  <template #header>
    <SncButton variant="ghost" icon="pi-ellipsis-v" />
  </template>
  Contenu
  <template #footer>
    <SncButton>Voir plus</SncButton>
  </template>
</SncCard>

<!-- Sans padding -->
<SncCard :padded="false">
  <img src="..." />
</SncCard>

<!-- Effet hover -->
<SncCard :hoverable="true">
  Contenu avec effet au survol
</SncCard>
```

#### SncProgress
Barre de progression standardisée.

```vue
<!-- Usage basique -->
<SncProgress :value="75" />

<!-- Avec label -->
<SncProgress :value="75" label="Progression" show-label />

<!-- Variantes de couleur -->
<SncProgress :value="75" color="success" />
<SncProgress :value="75" color="warning" />
<SncProgress :value="75" color="danger" />

<!-- Animé -->
<SncProgress :value="75" striped animated />
```

#### SncAvatar
Avatar utilisateur avec initiales ou image.

```vue
<!-- Avec image -->
<SncAvatar src="/path/to/avatar.jpg" name="John Doe" />

<!-- Avec initiales (automatiques) -->
<SncAvatar name="John Doe" />

<!-- Tailles -->
<SncAvatar name="John" size="xs" />
<SncAvatar name="John" size="sm" />
<SncAvatar name="John" size="md" />
<SncAvatar name="John" size="lg" />
<SncAvatar name="John" size="xl" />

<!-- Avec statut -->
<SncAvatar name="John" status="online" />
<SncAvatar name="John" status="busy" />
<SncAvatar name="John" status="away" />
```

#### SncSkeleton
Skeleton loader pour les états de chargement.

```vue
<!-- Variantes -->
<SncSkeleton variant="text" class="w-32 h-4" />
<SncSkeleton variant="circular" class="w-10 h-10" />
<SncSkeleton variant="rectangular" class="w-full h-32" />
<SncSkeleton variant="avatar" class="w-12 h-12" />

<!-- Multiples -->
<SncSkeleton variant="text" :count="3" class="w-full h-4" />
```

#### SncLoader
Spinner de chargement global.

```vue
<!-- Usage basique -->
<SncLoader />

<!-- Avec texte -->
<SncLoader text="Chargement en cours..." />

<!-- Tailles -->
<SncLoader size="sm" />
<SncLoader size="md" />
<SncLoader size="lg" />
<SncLoader size="xl" />

<!-- Overlay (écran entier) -->
<SncLoader :overlay="true" text="Traitement..." />
```

#### SncAlert
Message d'alerte standardisé.

```vue
<!-- Variantes -->
<SncAlert variant="info" title="Information">
  Message d'information.
</SncAlert>

<SncAlert variant="success" title="Succès">
  Opération réussie.
</SncAlert>

<SncAlert variant="warning" title="Attention">
  Veuillez vérifier.
</SncAlert>

<SncAlert variant="error" title="Erreur">
  Une erreur est survenue.
</SncAlert>

<!-- Dismissible -->
<SncAlert variant="info" dismissible @dismiss="handleDismiss">
  Message avec bouton fermer.
</SncAlert>
```

**Règles d'utilisation :**
- Utilisez `variant="error"` avec `aria-live="assertive"` (automatique) pour les erreurs critiques
- Utilisez `variant="success"` pour les confirmations d'action
- Utilisez `dismissible` pour les alertes temporaires

### 3. Composants Existants

#### PageHeader
En-tête de page unifié.

```vue
<PageHeader
  title="Mes formations"
  subtitle="Reprenez là où vous vous êtes arrêté"
  icon="pi-book"
  badge="12 cours"
  accent="blue"
  :actions="[
    { label: 'Explorer', icon: 'pi-search', href: route('marketplace.index') }
  ]"
/>
```

#### PageCard
Carte de contenu standardisée.

```vue
<PageCard title="Statistiques" subtitle="Données en temps réel">
  Contenu
</PageCard>
```

#### PageKpi
Indicateur clé de performance.

```vue
<PageKpi
  label="Étudiants actifs"
  :value="1250"
  icon="pi-users"
  color="blue"
  :trend="12"
  subtext="+50 ce mois"
/>
```

#### PageSection
Section titrée.

```vue
<PageSection title="Formations récentes" subtitle="Ajoutées cette semaine">
  Contenu
</PageSection>
```

#### EmptyState
État vide standardisé.

```vue
<EmptyState
  icon="pi-folder-open"
  title="Aucune formation"
  description="Vous n'êtes inscrit à aucune formation."
  action-label="Explorer le catalogue"
  :action-href="route('marketplace.index')"
/>
```

### 4. Bonnes Pratiques

#### Hiérarchie Visuelle
- Un seul CTA principal par écran (variant="primary")
- Actions secondaires en style discret (variant="secondary" ou "ghost")
- Espace négatif généreux, éviter la surcharge

#### Accessibilité
- Toujours fournir des labels explicites pour les formulaires
- Utiliser les attributs ARIA automatiques des composants
- Tester la navigation au clavier
- Vérifier le contraste (4.5:1 minimum)

#### Responsive
- Mobile-first : concevez d'abord à 360px
- Cibles tactiles d'au moins 44×44px
- Pas de défilement horizontal
- Utiliser les breakpoints : sm (576px), md (768px), lg (992px), xl (1200px)

#### Performance
- Images optimisées avec `loading="lazy"`
- Polices limitées à 2 graisses maximum
- Code splitting pour les routes
- Utiliser les skeletons pour les états de chargement

#### Mode Sombre
- Tous les composants supportent le mode sombre via les tokens CSS
- Tester chaque composant en mode sombre
- Utiliser les variantes `dark:` de Tailwind pour les cas spécifiques

### 5. Migration Progressive

Pour migrer une page existante vers le design system :

1. Remplacer les boutons PrimeVue par `SncButton`
2. Remplacer les inputs PrimeVue par `SncInput`
3. Utiliser `PageHeader` pour les en-têtes
4. Utiliser `PageCard` pour les conteneurs
5. Ajouter des `SncSkeleton` pour les états de chargement
6. Utiliser `SncAlert` pour les messages d'erreur/succès
7. Optimiser le responsive mobile

### 6. Checklist de Validation

Avant de considérer une page comme terminée :

- [ ] Tous les boutons utilisent `SncButton` avec la bonne variante
- [ ] Tous les formulaires utilisent `SncInput` avec des labels
- [ ] Les états de chargement utilisent `SncSkeleton` ou `SncLoader`
- [ ] Les erreurs utilisent `SncAlert`
- [ ] Le responsive est testé à 360px, 768px et 1280px
- [ ] Le mode sombre fonctionne correctement
- [ ] La navigation au clavier est fonctionnelle
- [ ] Le contraste respecte WCAG 2.2 AA
- [ ] Un seul CTA principal par écran
- [ ] Les images ont des alt descriptifs

## Support

Pour toute question sur le design system, consultez le fichier `ELEARNING_IMPROVEMENTS_SUMMARY.md` ou contactez l'équipe de développement.
