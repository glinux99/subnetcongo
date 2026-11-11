# AGENT_QA_AVEUGLE.md — Checklist obligatoire avant toute livraison

> Ce document est destiné à un agent qui **n’a pas de yeux** : il ne peut pas « voir » le résultat. Il ne peut donc pas se faire confiance sur un simple rendu visuel ou sur ses propres impressions. Il doit appliquer une **checklist mécanique** et **vérifier avec des commandes** (rendu, compilation, routes, tests HTTP).
>
> La règle d’or : **si tu ne peux pas prouver qu’une chose est correcte, elle est fausse.**

---

## 1. Avant de rendre la main — 3 niveaux de vérification

### Niveau 1 — Compilation/syntaxe (Obligatoire)
| Langage | Commande |
|---|---|
| PHP | `php -l <fichier-modifié>` pour chacun des fichiers PHP touchés |
| Vue SFC | Parser + compiler script et template avec `@vue/compiler-sfc` (pas de build complet, juste le parse) |
| JS/TS | Vérifier qu’aucun souci de syntaxe visible (pas de parenthèse oubliée, pas de virgule en trop dans le JSX) |

### Niveau 2 — Routing (Obligatoire)
- `php artisan route:list --path=<chemin>` pour chaque groupe de routes ajouté/modifié.
- Vérifier l’absence de doublons dans `php artisan route:list` (lignes >= 1 écran).
- Pour Inertia : vérifier que la route rend bien un composant existant (nom correct).

### Niveau 3 — HTTP local (Obligatoire si serveur local dispo)
- `curl -s -o NUL -w "%{http_code}\n" http://127.0.0.1:8000/<route>` pour chaque route GET ajoutée/modifiée — attendu : 200 (ou 302→login si auth, ce qui est OK).
- Si cookies/auth nécessaires : récupérer d’abord `/login`, extraire `_token` + XSRF, poser le cookie, puis faire la requête.
- Vérifier que la page contient bien `data-page="component":"NomComposant"`.

---

## 2. Règles de cohérence visuelle — checklist à vérifier AVANT de dire « c’est bon »

### 2.1 Layout
- [ ] La page utilise-t-elle **LE** layout canonique de son contexte ?
  - Pages **publiques** : `resources/js/Layouts/AppLayout.vue`
  - Pages **admin sakai** : `resources/js/sakai/layout/AppLayout.vue`
- [ ] Le titre de page (`<Head title="…"/>`) est défini dans chaque page.
- [ ] L’en-tête/slot, les marges supérieures/inférieures proviennent du layout, pas de la page.

### 2.2 Composants PRIMEVUE vs HTML brut
**NE PAS utiliser de HTML/CSS custom** si un composant PrimeVue existe équivalent :
| Cas | Composant attendu | Mauvais (prohibé) |
|---|---|---|
| Bouton | `<Button …/>` | `<button class="bg-red-600 …">` custom |
| Champ texte | `<InputText />` / `QuantityControl` | `<input>` avec classes maison |
| Champ password | `<Password/>` | `<input type="password">` |
| Sélection | `<Select />` ou `<Dropdown/>` | `<select>` natif non stylé |
| Textarea | `<InputTextArea/>` | `<textarea class="border …">` |
| Checkbox/radio | `<Checkbox />` / `<RadioButton />` | `<input type="checkbox">` |
| Tooltip | `<Tooltip />` directive `v-tooltip` | attribut `title="…"` |
| Selecteur date | `<DatePicker/>` | `<input type="date">` natif |
| Dialog popup | `<Dialog/>` | `<div class="fixed inset-0 …">` |
| Confirm dialog | `useConfirmAction` + `<ConfirmDialog group="app"/>` | `confirm('…')` natif |
| Toast | `<Toast/>` (déjà dans AppLayout) | `alert(…)` |
| DataTable | `<DataTable />` | `<table>` HTML brut |
| Tag/badge | `<Tag/>` / `<Badge/>` | `<span class="rounded-full bg-…">` |
| Skeleton | `<Skeleton/>` | spinners CSS custom |
| Texte icon-fiable | `<IconField><InputIcon/><InputText/></IconField>` | icône en position absolue sur input |

### 2.3 Recherche / filtres — règles absolues
- Une barre de recherche = `<IconField iconPosition="left">` + `<InputIcon class="pi pi-search"/>` + `<InputText …/>`.
  - **JAMAIS** de loupe flottante au-dessus/en-dehors du champ.
- Les rangées de filtres (recherche + selects) doivent :
  - Être sur **la même ligne** en desktop (`flex items-center` + `gap-3` + `h-11` uniforme).
  - Wrapt proprement en mobile (`flex-col md:flex-row` / `md:items-center`).
  - Avoir largeur cohérente — jamais d’éléments coupés par `overflow-hidden`.
- Tous les boutons d’une même barre ont la même hauteur (ex : `h-11`), même alignement vertical.

### 2.4 Attribut `icons-inside`
- IconField/InputIcon : la classe `!flex !items-center` sur l’InputText pile le libellé verticalement.
- Placeholder lisible : `placeholder:text-slate-400` sur fond sombre (pas `placeholder:text-slate-600`).

### 2.5 Couleurs — cohérence
- Palette large : rouge `#E11D48` (signature), bleu `#003399`/`#2563EB`, slate 50→900.
- Fond sombre (noir/#0A0F1C) : texte blanc/slate-300 ; **jamais** `slate-600` ni `slate-500` comme pseudo-placeholder.
- Texte app : `#0B1221` (titres), `slate-700` (libellés), `slate-500` (aide). **Jamais** `#000` pur sur fond clair.

### 2.6 Espacement
- Grilles/portfolios : padding `p-4 sm:p-6 lg:p-8` ; gap `gap-x-6 gap-y-4`.
- Cartes : `rounded-2xl border border-slate-200 shadow-sm`.
- Pas de padding 0 sur un texte destiné à un bouton (`px-4 py-2` minimum).

### 2.7 Accessibilité — minimum obligatoire
- Bouton icône-only : `aria-label="Action"`.
- Images informatives : `alt="Description"` ; décorative : `alt=""` + `aria-hidden="true"`.
- `htmlFor`/`id` liés sur chaque champ de formulaire.
- Champ requis : `required` + `aria-required="true"`.
- Focus visible : `focus:ring-2 focus:ring-{color}-500 focus:ring-offset-2` (déjà PrimeVue).

---

## 3. Procédure de livraison par type de tâche

### Ajouter un écran Admin (CRUD)
1. Route CRUD (`index/store/update/destroy/toggle`) -> `php artisan route:list --path=<path>` — sortie visible.
2. Contrôleur : `index` renvoie bien `Inertia::render('X/Index', [...])`, `store/update` utilisent `redirect()->route()->with('success', …)`.
3. Vue : `<AppLayout>…<AppLayout>` ; `PageHeader`/titre + `DataTable` + `Dialog`.
4. Vérifier avec `curl` que la page renvoie 200/302 attendu.

### Réaliser un bandeau/newsletter
1. Mobile-first (`flex-col sm:flex-row`, tailles de texte `text-xs sm:text-base`).
2. Aucun padding horizontal supérieur à `px-4` en mobile.
3. Input email : full-width sur mobile (`w-full sm:w-64`).

### Harmoniser des pages publiques
1. Le navbar/footer vit dans `Layouts/AppLayout.vue` — ne pas dupliquer.
2. Titre ici = sous-titre ; `<Head title="…"/>` se met dans chaque page racine.

---

## 4. Pièges connus dans ce projet (à revérifier)

| # | Piège | Symptôme | Fix |
|---|---|---|---|
| 1 | `p-input-icon-left` avec InputIcon (v3 archaïque) | loupe **au-dessus** du champ | Utiliser `<IconField iconPosition="left">` (v4). |
| 2 | SFC modifié mais pas recobuild ; les imports manquent (ex. IconField) | `<ib` renders en snippet | En ajouter l’import explicite ou pointer `primevue/*` — contrôler via compiler-sfc. |
| 3 | Variables de chemin Windows où Linux (`" is not defined "`) | Route ne répond plus | Vérifier `route('…')` exactement dans `php artisan route:list`. |
| 4 | État Sakura `bell=[]` confond | Bell silencieux | Badge ne s’affiche que si `unread_count > 0` (conditionnel). |
| 5 | JS hydraté en local mais pas en prod (config `APP_URL=127.0.0.1:8000` en prod) | Lien cassé `href='http://localhost/…'` | Toujours utiliser `route()` ou des chemins relatifs. |
| 6 | Texte coupé en mobile (`whitespace-nowrap`) | Débordement horizontal | `whitespace-nowrap` uniquement sur ≥ sm. |
| 7 | `template` multiple root dans SFC | Vue n’hydrate pas | `template` Exactement un root. |
| 8 | `inputClass` Select non supporté par le preset | Texte non centré | Tester visuellement ; sinon fallback `h-11` + `pt-2`. |

---

## 5. Test de non-régression mobile

- Largeur viewports à vérifier (au moins en mental) : 375px / 768px / 1280px.
- Aucun texte < 11px pour un titre, < 10px pour une aide.
- Aucun élément plus large que `max-w-screen` → regardez la largeur cumulée (padding + gap + columns).
- `overflow-hidden` autorisé uniquement pour les zones de contenu masquées intentionnellement.

---

## 6. Modèle de rapport de livraison

```
TRAVAIL FAIT
- Fichiers modifiés : <liste>
- Changements principaux : <liste brève>

VERIFICATIONS
- php -l : <n fichiers OK / erreurs éventuelles>
- route:list : <sortie visible/ref>
- SFC compile : <OK / erreurs>
- curl : <codes HTTP pour chaque route>

LIMITES
- <ce qui n’a pas pu être testé / reste à confirmer>
```

---

## 7. Règles d’or

1. **Flux complet avant l’opinion.** Si tu ne l’as pas exécutée, tu ne la connais pas.
2. **Économie du clic et de la faute.** Chaque composant PrimeVue est déjà testé pour l’accessibilité et le design ; le HTML brut vous éloignera de cette promesse.
3. **Cohérence > utilité** : un bouton moins performant mais cohérent avec le design gagne face à un bouton custom différent.
4. **Les 3 niveaux de vérification (section 1) ne sont pas optionnels.**
5. Conclusion : si quelque chose te paraît "probablement correct", vérifie. Si tu échoues, dis-le clairement ; ne dis jamais livré sans preuve.
