<?php
/**
 * Script de déconnexion.
 * Supprime la session utilisateur et redirige vers la page d'accueil.
 */
session_start(); // Nécessaire pour accéder à la session

// Supprime toutes les variables de session
session_unset(); 

// Détruit la session active
session_destroy(); 

// Redirige l'utilisateur vers la page d'accueil (index.php)
header("Location: index.php"); 
exit();
?>
