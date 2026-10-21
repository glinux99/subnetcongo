# SUBNETCONGO E-Learning Platform

Plateforme d'apprentissage en ligne professionnelle construite avec Laravel 10, Inertia.js, Vue 3 et PrimeVue.

## Architecture du Projet

### Structure Organisée
Le projet suit une structure professionnelle avec séparation fonctionnelle:

**Contrôleurs (`app/Http/Controllers/`):**
- `Elearning/` - Gestion des cours, modules, leçons, examens, certificats
- `Community/` - Forums, chat, tickets de support
- `Billing/` - Paiements, commandes, marketplace, affiliés
- `Content/` - Pages, blog, menus, assets
- `UserManagement/` - Gestion des utilisateurs et profils
- `System/` - Configuration système, logs, API
- `Admin/` - Administration avancée
- `Auth/` - Authentification
- `Configuration/` - Configuration métier (engins, zones, régions)

**Modèles (`app/Models/`):**
- `Elearning/` - Course, Module, Lesson, Enrollment, Exam, Question, Certificate, etc.
- `Community/` - Forum, Chat, SupportTicket, Message
- `Billing/` - Order, Payment, Coupon, Affiliate, Subscription
- `Content/` - BlogPost, Page, Menu, Testimonial, Asset
- `UserManagement/` - User, Role, Permission, Profile
- `System/` - Setting, Notification, AuditLog

## Fonctionnalités

### E-Learning
- Création et gestion de cours avec modules et leçons
- Système d'examen avec différents types de questions
- Suivi de progression des apprenants
- Certificats automatiques
- Sessions en direct (LiveKit)
- Bibliothèque de médias

### Communauté
- Forums de discussion
- Chat en groupe
- Système de tickets de support

### Marketplace
- Catalogue de cours
- Panier et checkout
- Paiements intégrés
- Programme d'affiliation
- Coupons et promotions

### Administration
- Gestion des utilisateurs et rôles
- Configuration système
- Rapports et analytics
- Gestion des emails
- Maintenance serveur

## Stack Technique

- **Backend:** Laravel 10 (PHP 8.2+)
- **Frontend:** Vue 3 + Inertia.js
- **UI:** PrimeVue + PrimeFlex + TailwindCSS
- **Base de données:** MySQL / PostgreSQL
- **Authentification:** Laravel Sanctum + Socialite
- **Média:** Spatie Media Library
- **Permissions:** Spatie Laravel Permission
- **PDF:** Laravel DOMPDF
- **Live:** LiveKit

## Installation

```bash
# Clone le projet
git clone <repository-url>
cd elearning

# Installation des dépendances
composer install
npm install

# Configuration de l'environnement
cp .env.example .env
php artisan key:generate

# Configuration de la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subnetcongo_elearning
DB_USERNAME=root
DB_PASSWORD=

# Migration et seeding
php artisan migrate:fresh --seed

# Démarrage
npm run dev
php artisan serve
```

## Comptes de Test

### Superadmin
- Email: `superadmin@superadmin.com`
- Password: `superadmin`

### Admin
- Email: `admin@admin.com`
- Password: `admin`

### Instructeur
- Email: `instructor@instructor.com`
- Password: `instructor`

### Étudiant
- Email: `student@student.com`
- Password: `student`

## Documentation

Pour plus de détails sur les améliorations récentes, voir `ELEARNING_IMPROVEMENTS_SUMMARY.md`.

## Support

Pour toute question ou problème, contactez l'équipe de développement SUBNETCONGO.
