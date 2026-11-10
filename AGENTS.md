# AGENTS.md

## Règle absolue — à lire AVANT tout travail

1. **Lire `docs/TODO.md` en entier** avant toute modification, puis `docs/agents/decisions.md`.
2. **Ne jamais** commiter/pousser sans demande explicite de l'utilisateur, ni uploader `.env`, `vendor/` ou `node_modules/` vers le serveur.
3. Le déploiement serveur se fait **par upload de fichiers** (voir section « Déploiement » de `docs/TODO.md`) : le repo git distant (`web/elearning`) est divergeant et ne doit pas être `git pull`.
4. Avant chaque fin de session, mettre à jour `docs/TODO.md` (décisions, état, reste à faire).
