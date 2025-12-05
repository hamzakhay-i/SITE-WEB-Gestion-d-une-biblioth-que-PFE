<?php
session_start();
include 'db_connect.php'; 

// Vérification de la session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'membre') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Simplification: Nous allons seulement récupérer les compteurs pour le tableau de bord
// La logique complète des tableaux est déjà dans le fichier créé précédemment.

// Compteur des emprunts actifs
$loans_count = $conn->query("SELECT COUNT(*) FROM emprunts WHERE utilisateur_id = $user_id AND date_retour_reelle IS NULL")->fetch_row()[0];

// Compteur des réservations de place actives
$reservations_count = $conn->query("SELECT COUNT(*) FROM reservations_place WHERE utilisateur_id = $user_id AND statut = 'active'")->fetch_row()[0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord Membre</title>
    <style>
        /* Styles de base */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #e8eaf6; }
        .header { background-color: #3f51b5; color: white; padding: 20px; text-align: center; }
        .container { width: 80%; margin: 20px auto; }
        .navbar { background-color: #303f9f; padding: 10px 0; text-align: center; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
        .feature-box { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; border-bottom: 5px solid #3f51b5; transition: transform 0.2s;}
        .feature-box:hover { transform: translateY(-5px); }
        .feature-box h3 { color: #3f51b5; margin-top: 0; }
        .count { font-size: 2.5em; font-weight: bold; color: #ff9800; margin: 10px 0; }
        .btn-action { display: block; padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; }
        .btn-action:hover { background-color: #388e3c; }
    </style>
</head>
<body>

<div class="header">
    <h1>👨‍🎓 Tableau de Bord Membre</h1>
</div>

<div class="navbar">
    <a href="index.php">Catalogue Public</a>
    <a href="dashboard_membre.php">Mon Compte</a>
    <a href="logout.php">Déconnexion (<?php echo htmlspecialchars($user_name); ?>)</a>
</div>

<div class="container">
    <h2>Bonjour, <?php echo htmlspecialchars($user_name); ?> !</h2>
    <p>Ceci est votre espace personnel pour suivre vos activités et effectuer des réservations.</p>
    
    <div class="feature-grid">
        
        <div class="feature-box">
            <h3>📖 Emprunts Actifs</h3>
            <p class="count" style="color: <?php echo $loans_count > 0 ? '#d32f2f' : '#4CAF50'; ?>;"><?php echo $loans_count; ?></p>
            <p>livre(s) à retourner.</p>
            <a href="#emprunts_section" class="btn-action" style="background-color: #3f51b5;">Voir mes Emprunts</a>
        </div>
        
        <div class="feature-box">
            <h3>🪑 Réservations de Place</h3>
            <p class="count"><?php echo $reservations_count; ?></p>
            <p>place(s) réservée(s) active(s).</p>
            <a href="reserver_place.php" class="btn-action" style="background-color: #ff9800;">Faire une Réservation</a>
        </div>
        
        <div class="feature-box">
            <h3>📚 Catalogue des Livres</h3>
            <p class="count">🔍</p>
            <p>Rechercher des nouveaux ouvrages.</p>
            <a href="index.php" class="btn-action">Consulter le Catalogue</a>
        </div>
        
    </div>

    </div>

</body>
</html>