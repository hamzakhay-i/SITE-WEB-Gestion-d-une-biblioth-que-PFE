<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user' || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = (int)$_GET['id'];

// Auto-fix: Créer la table wishlist si elle n'existe pas
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'wishlist'");
if (mysqli_num_rows($check_table) == 0) {
    mysqli_query($conn, "CREATE TABLE wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wish (user_id, book_id)
    )");
}

// Vérifier si déjà dans la liste
$check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id=$user_id AND book_id=$book_id");

if (mysqli_num_rows($check) > 0) {
    // Supprimer
    mysqli_query($conn, "DELETE FROM wishlist WHERE user_id=$user_id AND book_id=$book_id");
} else {
    // Ajouter
    mysqli_query($conn, "INSERT INTO wishlist (user_id, book_id) VALUES ($user_id, $book_id)");
}

// Retour à la page précédente
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
header("Location: $redirect");
exit();
?>