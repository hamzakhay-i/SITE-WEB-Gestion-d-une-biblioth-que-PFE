<?php
require 'config/db.php';

// Ajouter la colonne 'cover_url' si elle n'existe pas
$check = mysqli_query($conn, "SHOW COLUMNS FROM books LIKE 'cover_url'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE books ADD COLUMN cover_url VARCHAR(500) DEFAULT 'https://placehold.co/300x450?text=Livre'");
    echo "<h1>✅ C'est fait ! Colonne 'cover_url' ajoutée.</h1>";
} else {
    echo "<h1>⚠️ La colonne existe déjà.</h1>";
}
echo "<br><a href='index.php'>Retour à l'accueil</a>";
?>