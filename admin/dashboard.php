<?php
session_start();
// Remonter d'un niveau pour inclure db_connect.php
include '../db_connect.php'; 

// --- VÉRIFICATION DE LA SÉCURITÉ (CRUCIAL) ---
// Si l'utilisateur n'est pas connecté ou n'a pas le rôle 'admin', il est redirigé.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user_name = $_SESSION['user_name'];

// Optionnel: Compteurs rapides pour le tableau de bord
$total_livres = $conn->query("SELECT COUNT(*) FROM livres")->fetch_row()[0];
$total_membres = $conn->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'membre'")->fetch_row()[0];
$emprunts_actifs = $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour_reelle IS NULL")->fetch_row()[0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Administrateur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; }
        .header { background-color: #ff9800; color: white; padding: 20px; text-align: center; }
        .container { width: 80%; margin: 20px auto; }
        .navbar { background-color: #fb8c00; padding: 10px 0; text-align: center; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-box { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .stat-box h3 { color: #ff9800; margin-top: 0; }
        .stat-box p { font-size: 2em; font-weight: bold; margin: 5px 0; }
    </style>
</head>
<body>

<div class="header">
    <h1>👑 Tableau de Bord Administrateur</h1>
</div>

<div class="navbar">
    <a href="dashboard.php">Accueil Admin</a>
    <a href="manage_books.php">Gérer les Livres</a>
    <a href="manage_places.php">Gérer les Places</a>
    <a href="../logout.php">Déconnexion</a>
</div>

<div class="container">
    <h2>Bonjour, Admin <?php echo htmlspecialchars($user_name); ?></h2>
    
    <div class="stats-grid">
        <div class="stat-box">
            <h3>Livres au Catalogue</h3>
            <p><?php echo $total_livres; ?></p>
        </div>
        <div class="stat-box">
            <h3>Membres Inscrits</h3>
            <p><?php echo $total_membres; ?></p>
        </div>
        <div class="stat-box">
            <h3>Emprunts Actifs</h3>
            <p style="color: <?php echo $emprunts_actifs > 0 ? 'red' : 'green'; ?>;"><?php echo $emprunts_actifs; ?></p>
        </div>
    </div>
    
    <h3 style="margin-top: 40px;">Actions de Gestion</h3>
    <p>Utilisez les liens de navigation pour effectuer les opérations CRUD :</p>
    <ul>
        <li>**Gérer les Livres :** Ajouter de nouveaux ouvrages, modifier les détails, gérer les stocks.</li>
        <li>**Gérer les Places :** Définir les bureaux/tables disponibles pour la réservation.</li>
        <li>**Gérer les Emprunts/Retours :** Enregistrer les transactions et gérer les pénalités (prochaine étape !).</li>
    </ul>
    
</div>
</body>
</html>