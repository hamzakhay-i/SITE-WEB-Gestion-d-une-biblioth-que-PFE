<?php
/**
 * Fichier de connexion à la base de données MySQL (via l'extension MySQLi)
 */

// --- Paramètres de connexion ---
$servername = "localhost"; // L'adresse du serveur MySQL (XAMPP)
$username = "root";       // Nom d'utilisateur par défaut de XAMPP
$password = "";           // Mot de passe par défaut de XAMPP (laisser vide)
$dbname = "bibliotheque_pfe"; // Le nom de la DB que vous avez créé

// --- Créer la connexion ---
// La variable $conn stockera l'objet de connexion que nous utiliserons pour les requêtes.
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Vérifier la connexion ---
if ($conn->connect_error) {
    // Si la connexion échoue, on arrête le script et on affiche l'erreur.
    // Pour un site en production, ce message ne devrait pas s'afficher à l'utilisateur.
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

// Optionnel: Définir l'encodage des caractères à UTF-8 pour gérer les accents
$conn->set_charset("utf8mb4");

// La connexion est maintenant établie via l'objet $conn.
?>