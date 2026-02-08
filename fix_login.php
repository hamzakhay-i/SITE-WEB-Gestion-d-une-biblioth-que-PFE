<?php
require 'config/db.php';

$email = "admin@library.com";
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

// Vérifier si l'admin existe
$check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

if (mysqli_num_rows($check) > 0) {
    // Mise à jour du mot de passe
    $sql = "UPDATE users SET password='$hash', role='admin' WHERE email='$email'";
    if (mysqli_query($conn, $sql)) {
        echo "<h1>✅ Mot de passe Réinitialisé !</h1><p>Email : <b>$email</b><br>Nouveau mot de passe : <b>admin123</b></p><br><a href='index.php' style='font-size:20px; font-weight:bold;'>👉 Cliquez ici pour vous connecter</a>";
    } else {
        echo "Erreur de mise à jour : " . mysqli_error($conn);
    }
} else {
    // Créer l'admin s'il n'existe pas
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('Super Admin', '$email', '$hash', 'admin')";
    if (mysqli_query($conn, $sql)) {
        echo "<h1>✅ Compte Admin Créé !</h1><p>Email : <b>$email</b><br>Mot de passe : <b>admin123</b></p><br><a href='index.php' style='font-size:20px; font-weight:bold;'>👉 Cliquez ici pour vous connecter</a>";
    } else {
        echo "Erreur de création : " . mysqli_error($conn);
    }
}
?>