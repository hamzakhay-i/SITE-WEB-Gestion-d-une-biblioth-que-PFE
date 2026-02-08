<?php
ob_start(); // Ajout pour éviter les erreurs de header
session_start();
require 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit();
    } else {
        $error = "Email ou mot de passe incorrect !";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Library - V2 (Updated)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style pour le Slider plein écran */
        .carousel-item { height: 100vh; min-height: 300px; background: no-repeat center center scroll; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover; }
        
        /* Style pour le formulaire Login flottant */
        .login-overlay { position: absolute; top: 50%; right: 10%; transform: translateY(-50%); width: 400px; z-index: 1000; }
        .card-glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; }
        .form-control { padding: 12px; background: rgba(255,255,255,0.9); border: 1px solid #e0e0e0; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); border-color: #0d6efd; background: #fff; }
        .btn-primary { border-radius: 10px; padding: 12px; font-weight: bold; background: linear-gradient(135deg, #0d6efd, #0a58ca); border: none; transition: all 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        
        /* Responsive: Sur mobile, le login prend toute la largeur */
        @media (max-width: 768px) { .login-overlay { position: relative; width: 100%; top: 0; right: 0; transform: none; padding: 20px; } .carousel-item { height: 40vh; } }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: rgba(0,0,0,0.6);">
      <div class="container">
        <a class="navbar-brand fw-bold" href="#">📚 SmartLib</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link active" href="#">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Livres</a></li>
            <li class="nav-item"><a class="nav-link" href="register.php">Inscription</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Slider (Carousel) -->
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
      <div class="carousel-inner">
        <!-- Slide 1 -->
        <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1507842217153-e212234b6605?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')">
          <div class="carousel-caption d-none d-md-block text-start" style="bottom: 100px; left: 10%;">
            <h1 class="display-3 fw-bold">Bienvenue à SmartLib</h1>
            <p class="lead fs-4">Gérez vos emprunts et découvrez des milliers de livres.</p>
          </div>
        </div>
        <!-- Slide 2 -->
        <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')">
          <div class="carousel-caption d-none d-md-block text-start" style="bottom: 100px; left: 10%;">
            <h1 class="display-3 fw-bold">Un espace de savoir</h1>
            <p class="lead fs-4">Accédez à une collection riche et variée.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Login Form Overlay -->
    <div class="login-overlay">
        <div class="card card-glass shadow-lg border-0">
            <div class="text-center pt-4 px-4">
                <img src="https://cdn-icons-png.flaticon.com/512/2232/2232688.png" alt="Library Icon" width="70" class="mb-3">
                <h3 class="fw-bold text-dark">Bon retour !</h3>
                <p class="text-muted small">Connectez-vous pour accéder à la bibliothèque</p>
            </div>
            
            <div class="p-4 pt-2">
            <?php if($error) echo "<div class='alert alert-danger py-2'>$error</div>"; ?>
            
            <!-- Admin Hint pour toi -->
            <div class="alert alert-info py-1 mb-3" style="font-size: 0.85rem;">
                <strong>Admin:</strong> admin@library.com / admin123
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="nom@exemple.com" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="******" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Se connecter</button>
            </form>
            <div class="text-center mt-3">
                <p class="mb-0">Pas de compte ? <a href="register.php" class="fw-bold text-decoration-none">Créer un compte</a></p>
            </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
