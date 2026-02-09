# 📚 SmartLib - Système de Gestion de Bibliothèque

**SmartLib** est une application web complète de gestion de bibliothèque développée en PHP et MySQL. Conçue pour faciliter l'interaction entre les lecteurs et l'administration, elle propose une interface moderne, responsive et intuitive.

## 🚀 Fonctionnalités

### 👤 Espace Lecteur (Utilisateur)
*   **Catalogue Interactif** : Recherche de livres par titre, auteur ou catégorie.
*   **Système d'Emprunt** : Réservation de livres avec gestion automatique des stocks.
*   **Espace Personnel** : Suivi des emprunts en cours, historique et gestion du profil.
*   **Favoris (Wishlist)** : Sauvegarde de livres à lire plus tard.
*   **Avis & Notes** : Système de notation (étoiles) et commentaires sur les livres.
*   **Lecture Numérique** : Accès direct aux fichiers PDF pour les E-books.
*   **Mode Sombre** : Interface basculable en Dark Mode pour le confort visuel.

### 🛡️ Espace Administrateur
*   **Tableau de Bord** : Vue synthétique avec indicateurs clés (KPIs) : total livres, utilisateurs, emprunts actifs.
*   **Gestion des Livres** : Ajout, modification et suppression de livres (upload d'images de couverture et PDF).
*   **Gestion des Emprunts** : Suivi des retours et des dates d'échéance.
*   **Statistiques Avancées** : Graphiques visuels (Chart.js) pour analyser les tendances d'emprunt et la répartition par catégories.
*   **Export** : Exportation des données au format CSV.

## 🛠️ Technologies Utilisées

*   **Backend** : PHP (Natif), MySQL (via MySQLi)
*   **Frontend** : HTML5, CSS3, Bootstrap 5
*   **Scripting** : JavaScript (Vanilla), Chart.js (Graphiques)
*   **Icônes** : FontAwesome 6

## ⚙️ Installation et Configuration

1.  **Environnement** : Assurez-vous d'avoir un serveur local type XAMPP ou WAMP installé.
2.  **Fichiers** : Placez le dossier du projet dans votre répertoire racine (ex: `c:\xampp\htdocs\bibliopfe`).
3.  **Base de Données** :
    *   Créez une base de données MySQL.
    *   Configurez la connexion dans le fichier `config/db.php`.
    *   Les tables nécessaires sont : `users`, `books`, `borrowings`, `reviews`, `wishlist`.
4.  **Initialisation** :
    *   Lancez `populate_books.php` pour remplir la bibliothèque avec des données de test.
    *   Lancez `fix_login.php` pour créer le compte administrateur par défaut.

## 🔑 Identifiants par Défaut

Pour accéder au panneau d'administration, utilisez le compte suivant (généré par le script `fix_login.php`) :

*   **Email** : `admin@library.com`
*   **Mot de passe** : `admin123`

## 📂 Structure des Dossiers

*   `/admin` : Contrôleurs et vues de l'interface d'administration.
*   `/user` : Contrôleurs et vues de l'espace lecteur.
*   `/config` : Fichiers de configuration (connexion BDD).
*   `/uploads` : Stockage des images de couverture et des fichiers PDF.

## 📝 Scripts Utilitaires

*   `populate_books.php` : Script d'importation automatique de livres via l'API OpenLibrary.
*   `update_db_images.php` : Met à jour la structure de la base de données (ajout colonnes images).
*   `fix_login.php` : Réinitialise le mot de passe administrateur en cas d'oubli.

---
*Projet réalisé dans le cadre d'un PFE (Projet de Fin d'Études).*
