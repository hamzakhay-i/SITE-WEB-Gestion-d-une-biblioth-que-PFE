<?php
session_start();
include 'db_connect.php';

$message_erreur = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['mot_de_passe'];

    // 1. Récupérer l'utilisateur par email
    // Récupérer le mot de passe haché et le rôle
    $sql = "SELECT id_utilisateur, nom, prenom, mot_de_passe, role FROM utilisateurs WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 2. Vérifier le mot de passe haché
        if (password_verify($password, $user['mot_de_passe'])) {
            // Connexion réussie, initialisation de la session
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_role'] = $user['role'];

            // 3. Redirection basée sur le rôle
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php"); 
            } else {
                header("Location: dashboard_membre.php"); 
            }
            exit();
        } else {
            $message_erreur = "Mot de passe incorrect.";
        }
    } else {
        $message_erreur = "Aucun utilisateur trouvé avec cet email.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    </head>
<body>
    <div class="header"><h1>🔐 Connexion</h1></div>
    <div class="container">
        <?php if ($message_erreur): ?>
            <p style="color:red; font-weight: bold;"><?php echo $message_erreur; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"><br><br>
            
            <label for="mot_de_passe">Mot de passe:</label><br>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required><br><br>
            
            <input type="submit" value="Se Connecter">
        </form>
        <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
    </div>
</body>
</html>