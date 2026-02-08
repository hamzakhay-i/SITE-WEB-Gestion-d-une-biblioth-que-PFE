<?php
require 'config/db.php';
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Vérifier si l'email existe déjà
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $msg = "<div class='alert alert-danger'>Cet email est déjà utilisé.</div>";
    } else {
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'user')";
        if(mysqli_query($conn, $sql)){
            $msg = "<div class='alert alert-success'>Compte créé avec succès ! <a href='index.php' class='fw-bold'>Se connecter</a></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Erreur lors de l'inscription.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Smart Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg p-4">
                    <h3 class="text-center mb-4 fw-bold text-primary">Créer un compte</h3>
                    <?php echo $msg; ?>
                    <form method="POST">
                        <div class="mb-3"><label class="form-label">Nom complet</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required></div>
                        <button type="submit" class="btn btn-success w-100 btn-lg mt-2">S'inscrire</button>
                    </form>
                    <p class="text-center mt-3 mb-0">Déjà inscrit ? <a href="index.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>