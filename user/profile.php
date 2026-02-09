<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Récupérer les infos actuelles
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Mise à jour basique
    $sql = "UPDATE users SET name='$name', email='$email' WHERE id=$user_id";
    
    // Si l'utilisateur veut changer son mot de passe
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name='$name', email='$email', password='$hash' WHERE id=$user_id";
    }

    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
        // Mettre à jour la session
        $_SESSION['name'] = $name;
        // Rafraîchir les données
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
    } else {
        $msg = "<div class='alert alert-danger'>Erreur : " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - SmartLib</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; }
        .sidebar a:hover { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="p-4 text-center border-bottom border-secondary">
            <h3 class="fw-bold"><i class="fas fa-book-reader"></i> SmartLib</h3>
        </div>
        <a href="dashboard.php"><i class="fas fa-book-open me-2"></i> Catalogue</a>
        <a href="my_books.php"><i class="fas fa-bookmark me-2"></i> Mes Emprunts</a>
        <a href="profile.php" style="background: #34495e; border-left: 4px solid #3498db;"><i class="fas fa-user-cog me-2"></i> Mon Profil</a>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow border-0 mt-4">
                        <div class="card-header bg-primary text-white"><h4 class="mb-0">Modifier mon profil</h4></div>
                        <div class="card-body p-4">
                            <?php echo $msg; ?>
                            <form method="POST">
                                <div class="mb-3"><label class="form-label">Nom complet</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label><input type="password" name="password" class="form-control" placeholder="******"></div>
                                <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>