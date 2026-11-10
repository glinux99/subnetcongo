# Plan — Fix prod /admin/emails (500 Class App\Models\SentEmail not found)

## Cause racine
Le déploiement précédent n’a uploadé que le working tree (modifiés/untracked vs HEAD). Le serveur est en `b05c2ee5`, le repo local a 5 commits d’avance (`a9feb7fb` → `ca1884bf`), contenant notamment `app/Models/SentEmail.php`. Le 500 existait déjà à 17:24. Décision utilisateur : déployer **uniquement l’utile**, le reste reste local ; `web/build` synchronisé sur le local (dernières versions) ; **commit local APRÈS validation** du site ; `inhemeter_diagnostic/` et tout artefact de dev restent en local ; `docs/` et `.md` jamais déployés.

## Exécution (sur build mode)

1. Régénérer les listes depuis la racine du repo local :
   - upload app : `git diff --name-only b05c2ee5..HEAD -- app routes database/migrations resources/views`
   - media : `git diff --name-only b05c2ee5..HEAD -- public/media`
   - suppressions : `git diff --name-status b05c2ee5..HEAD -- public` → champ 2, prefixe D ou R (236 fichiers, tous sous public/)
2. `tar -cf fix-upload.tar -T <upload list>` (app + media)
3. scp `fix-upload.tar` + listes vers `~/deploy-fix/` (home serveur)
4. Script distant `deploy-fix.sh` :
   - `B=web/elearning/backups/$TS` (backup pré-déploiement, dans web/ uniquement)
   - backup des fichiers écrasés dans `$B/app/…` et `$B/web/…`
   - suppression des 236 fichiers obsolètes (backup dans `$B/web/…` AVANT `rm -f`)
   - `tar -xf fix-upload.tar -C web/elearning app routes database resources`
   - `tar -xf fix-upload.tar -C web --strip-components=1 public`
5. Sur le serveur : `cd web/elearning && php8.3 artisan migrate:status | grep sent_emails` puis `php8.3 artisan config:clear route:clear view:clear`
6. Vérifier : `curl -s -o /dev/null -w '%{http_code}' https://subnetcongo.net/admin/emails` → attendu 302/200 (jamais 500) ; `tail -50 storage/logs/laravel.log` → aucune erreur « Class not found »
7. Appliquer nouvelles règles mémoire : mise à jour `docs/TODO.md` (cause racine : déployer le diff `état_serveur..HEAD` ∪ working tree ; jamais seulement `git status` ; liste d’exclusion ; item « Commit » reste ouvert jusqu’à validation user) et `AGENTS.md`.

## Après validation utilisateur
- `git add -A && git commit` local (tout le travail, pas de push sans demande explicite)
- Rapatrier `web/elearning/backups/<ts>/` → `docs/backups/<ts>/` en local, puis `rm -rf web/elearning/backups` sur le serveur
- Site 200 partout (`/`, `/login`, `/sessions-en-direct`, `/admin/emails`)

## Interdictions en vigueur
- Aucun travail hors `web/` sur le serveur ; aucune modif de config serveur ; `php8.3` pour artisan ; `.env` jamais écrasé ; pas de seed automatique ; pas de push ; pas de déploiement de `docs/`, `.md`, `vendor/pestphp/pest/.temp/…`, `inhemeter_diagnostic/`, `test_*.php`, `tailwind.config.js`, `resources/js` sources, `resources/css`, `tests/`.
