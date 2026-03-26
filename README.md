# 🎮 GGManager - Plateforme de Tournois E-Sport

## 📋 Description

GGManager est une plateforme backend de gestion de tournois e-sport permettant aux organisateurs de créer et gérer des tournois, aux joueurs de s'inscrire, et aux spectateurs de suivre l'évolution des matchs en temps réel.

## ✨ Fonctionnalités

### 👤 Authentification et Rôles
- Inscription / Connexion via Laravel Sanctum (tokens)
- Trois rôles : **Organisateur**, **Joueur**, **Spectateur**
- Gestion des permissions par rôle

### 🏆 Gestion des Tournois (Organisateur)
- Créer un tournoi (nom, jeu, saison, nombre max de participants)
- Modifier / Supprimer un tournoi (si aucun match n'a commencé)
- Voir la liste des tournois avec filtres (par jeu, statut)

### 📝 Inscriptions (Joueur)
- Un joueur peut s'inscrire à un tournoi ouvert
- L'organisateur peut voir la liste des inscrits
- Clôture des inscriptions avec génération automatique du bracket

### 🎲 Génération du Bracket (Single Elimination)
- Algorithme de bracket en single elimination
- Gestion automatique des BYE (nombre de participants non puissance de 2)
- Génération asynchrone via Job Laravel
- Structure arborescente exposée via API

### ⚽ Gestion des Matchs (Organisateur)
- Saisie des scores (ex: 3-1)
- Validation automatique (scores cohérents, pas d'égalité)
- Qualification automatique du vainqueur au tour suivant
- Événement broadcasté en temps réel

### 📡 Temps Réel (WebSockets)
- Mises à jour en direct via Pusher
- Canal public par tournoi (ex: tournament.1)
- Affichage automatique des scores pour tous les clients connectés

### 📊 Dashboard
- **Joueur** : Mes matchs, mes participations, statistiques
- **Organisateur** : Mes tournois, statistiques, matchs en attente

## 🛠️ Technologies Utilisées

| Technologie | Version | Utilisation |
|-------------|---------|-------------|
| Laravel | 13.x | Framework PHP |
| PHP | 8.3 | Langage backend |
| MySQL | 8.0 | Base de données |
| Laravel Sanctum | - | Authentification API |
| Pusher | - | WebSockets temps réel |
| Postman | - | Tests API |

## 📁 Structure du Projet
GGManager/
├── app/
│ ├── Http/
│ │ ├── Controllers/
│ │ │ ├── AuthController.php
│ │ │ ├── TournamentController.php
│ │ │ ├── MatchController.php
│ │ │ ├── BracketController.php
│ │ │ └── ScoreController.php
│ │ └── Middleware/
│ │ └── CheckRole.php
│ ├── Models/
│ │ ├── User.php
│ │ ├── Tournament.php
│ │ └── MatchGame.php
│ ├── Services/
│ │ └── BracketGenerator.php
│ └── Jobs/
│ └── GenerateBracketJob.php
├── database/
│ ├── migrations/
│ └── seeders/
├── routes/
│ └── api.php
├── public/
│ └── test-real-time.html
└── .env


## 🚀 Installation

### Prérequis
- PHP 8.3+
- Composer
- MySQL 8.0+
- Node.js (optionnel)

### Étapes d'installation

```bash
# 1. Cloner le projet
git clone https://github.com/votre-repo/GGManager.git
cd GGManager

# 2. Installer les dépendances
composer install

# 3. Copier le fichier d'environnement
cp .env.example .env

# 4. Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ggmanager
DB_USERNAME=root
DB_PASSWORD=

# 5. Générer la clé
php artisan key:generate

# 6. Exécuter les migrations
php artisan migrate

# 7. Exécuter les seeders
php artisan db:seed

# 8. Démarrer le serveur
php artisan serve


### Configuration Pusher (WebSockets)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
QUEUE_CONNECTION=sync

### Documentation API
 Authentification
Méthode	Endpoint	Description
POST	/api/v1/register	Inscription
POST	/api/v1/login	Connexion
POST	/api/v1/logout	Déconnexion
GET	/api/v1/me	Utilisateur courant
Tournois
Méthode	Endpoint	Description	Rôle
GET	/api/v1/tournaments	Liste des tournois	Public
POST	/api/v1/tournaments	Créer un tournoi	Organisateur
GET	/api/v1/tournaments/{id}	Détails tournoi	Public
PUT	/api/v1/tournaments/{id}	Modifier tournoi	Organisateur
DELETE	/api/v1/tournaments/{id}	Supprimer tournoi	Organisateur
Inscriptions
Méthode	Endpoint	Description	Rôle
POST	/api/v1/tournaments/{id}/register	S'inscrire	Joueur
GET	/api/v1/tournaments/{id}/participants	Liste participants	Organisateur
POST	/api/v1/tournaments/{id}/close-registrations	Fermer inscriptions	Organisateur
Matchs
Méthode	Endpoint	Description	Rôle
GET	/api/v1/tournaments/{id}/matches	Liste des matchs	Public
GET	/api/v1/tournaments/{id}/matches/{mid}	Détails match	Public
PUT	/api/v1/tournaments/{id}/matches/{mid}/score	Modifier score	Organisateur
GET	/api/v1/tournaments/{id}/bracket	Voir bracket	Public
Dashboard
Méthode	Endpoint	Description	Rôle
GET	/api/v1/my-tournaments	Mes tournois	Bearer
GET	/api/v1/my-matches	Mes matchs	Bearer
GET	/api/v1/my-participations	Mes participations	Bearer
GET	/api/v1/dashboard	Dashboard	Bearer

### 🧪 Tests
Tests avec Postman
Importer la collection Postman

Configurer les variables d'environnement

Exécuter les tests dans l'ordre

👥 Équipe
Développeur Backend : [Votre Nom]

📅 Version
Version : 1.0.0

Date : Mars 2026

📝 Licence
Ce projet est développé dans le cadre d'un projet de soutenance.


GGManager - Gérez vos tournois e-sport comme un pro ! 🎮🏆
