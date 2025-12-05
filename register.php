<?php
session_start();
include 'db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Récupération des données et échappement des caractères spéciaux
    $nom = $conn->real_escape_string($_POST['nom']);
    $prenom = $conn->real_escape_string($_POST['prenom']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['mot_de_passe'];

    // 2. Hachage du mot de passe (STANDARD DE SÉCURITÉ)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Vérification de l'existence de l'email
    $check_sql = "SELECT id_utilisateur FROM utilisateurs WHERE email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "Cet email est déjà utilisé. Veuillez vous connecter.";
    } else {
        // 4. Insertion du nouvel utilisateur (role='membre' par défaut)
        $insert_sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, 'membre')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $nom, $prenom, $email, $hashed_password);

        if ($insert_stmt->execute()) {
            $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } else {
            $message = "Erreur lors de l'inscription : " . $conn->error;
        }
    }
    $stmt->close();
    $insert_stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Membre</title>
    </head>
<body>
    <div class="header"><h1>📝 Inscription Membre</h1></div>
    <div class="container">
        <p style="color:blue;"><?php echo $message; ?></p>
        
        <form method="POST" action="register.php">
            <label for="prenom">Prénom:</label><br><input type="text" name="prenom" required><br><br>
            <label for="nom">Nom:</label><br><input type="text" name="nom" required><br><br>
            <label for="email">Email:</label><br><input type="email" name="email" required><br><br>
            <label for="mot_de_passe">Mot de passe:</label><br><input type="password" name="mot_de_passe" required><br><br>
            <input type="submit" value="S'inscrire">
        </form>
        <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</body>
</html>