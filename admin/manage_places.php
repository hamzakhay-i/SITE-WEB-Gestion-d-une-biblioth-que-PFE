<?php
session_start();
include '../db_connect.php'; 

// --- VÉRIFICATION DE LA SÉCURITÉ ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$edit_mode = false;
$place_data = [];

// --- FONCTIONS DE GESTION CRUD ---

// 1. Gérer l'ajout ou la modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $numero_place = trim($_POST['numero_place']);
    $description = trim($_POST['description']);
    $est_disponible = isset($_POST['est_disponible']) ? 1 : 0;
    $place_id = isset($_POST['id_place']) ? (int)$_POST['id_place'] : 0;

    if ($place_id > 0) {
        // MODE MODIFICATION
        $sql = "UPDATE places_etude SET numero_place=?, description=?, est_disponible=? WHERE id_place=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $numero_place, $description, $est_disponible, $place_id);
        if ($stmt->execute()) {
            $message = "Place d'étude modifiée avec succès !";
        } else {
            // Gérer l'erreur d'ISBN unique si l'utilisateur essaie de dupliquer un numéro de place
            if ($conn->errno == 1062) {
                $message = "Erreur : Ce numéro de place existe déjà.";
            } else {
                $message = "Erreur lors de la modification : " . $conn->error;
            }
        }
    } else {
        // MODE AJOUT
        $sql = "INSERT INTO places_etude (numero_place, description, est_disponible) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $numero_place, $description, $est_disponible);
         if ($stmt->execute()) {
            $message = "Nouvelle place d'étude ajoutée avec succès !";
        } else {
             if ($conn->errno == 1062) {
                $message = "Erreur : Ce numéro de place existe déjà.";
            } else {
                $message = "Erreur lors de l'ajout : " . $conn->error;
            }
        }
    }
    if (isset($stmt)) $stmt->close();
}

// 2. Gérer la suppression
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Vérifier si des réservations actives existent pour cette place
    $today = date('Y-m-d');
    $check_reservation = $conn->query("SELECT COUNT(*) FROM reservations_place WHERE place_id = $id AND statut = 'active' AND date_reservation >= '$today'")->fetch_row()[0];
    
    if ($check_reservation > 0) {
        $message = "Impossible de supprimer: Cette place a encore $check_reservation réservation(s) active(s) ou future(s).";
    } else {
        $sql = "DELETE FROM places_etude WHERE id_place = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Place d'étude supprimée avec succès.";
        } else {
            $message = "Erreur lors de la suppression : " . $conn->error;
        }
        $stmt->close();
    }
}

// 3. Gérer l'édition (affichage du formulaire pré-rempli)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $edit_mode = true;
    $sql = "SELECT * FROM places_etude WHERE id_place = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $place_data = $result->fetch_assoc();
    $stmt->close();
}

// 4. Lecture des données pour l'affichage du tableau
$read_sql = "SELECT * FROM places_etude ORDER BY numero_place ASC";
$places_result = $conn->query($read_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Places d'Étude - Admin</title>
    <style>
        /* Réutilisation et adaptation des styles du dashboard admin/livres */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; }
        .header { background-color: #ff9800; color: white; padding: 20px; text-align: center; }
        .container { width: 90%; margin: 20px auto; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; }
        .navbar { background-color: #fb8c00; padding: 10px 0; text-align: center; }
        .form-section { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-section h3 { color: #ff9800; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button, .btn { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; text-decoration: none; display: inline-block;}
        button[type="submit"] { background-color: #ff9800; }
        .btn-edit { background-color: #2196F3; }
        .btn-delete { background-color: #F44336; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #ffe0b2; }
        .dispo { color: green; font-weight: bold; }
        .indispo { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>🪑 Gestion des Places d'Étude</h1>
</div>

<div class="navbar">
    <a href="dashboard.php">Accueil Admin</a>
    <a href="manage_books.php">Gérer les Livres</a>
    <a href="manage_places.php">Gérer les Places</a>
    <a href="../logout.php">Déconnexion</a>
</div>

<div class="container">
    <?php if ($message): ?>
        <p style="color: blue; font-weight: bold; background-color: #e3f2fd; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <div class="form-section">
        <h3><?php echo $edit_mode ? 'Modifier la Place : ' . htmlspecialchars($place_data['numero_place']) : 'Ajouter une Nouvelle Place'; ?></h3>
        
        <form method="POST" action="manage_places.php">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_place" value="<?php echo htmlspecialchars($place_data['id_place']); ?>">
            <?php endif; ?>

            <label for="numero_place">Numéro/Nom de la Place (Ex: P-01, T-B05):</label>
            <input type="text" id="numero_place" name="numero_place" value="<?php echo $edit_mode ? htmlspecialchars($place_data['numero_place']) : ''; ?>" required>

            <label for="description">Description (Ex: Bureau individuel, Grande table de groupe):</label>
            <textarea id="description" name="description" rows="3"><?php echo $edit_mode ? htmlspecialchars($place_data['description']) : ''; ?></textarea>
            
            <label style="margin-top: 20px;">
                <input type="checkbox" name="est_disponible" value="1" <?php echo ($edit_mode && $place_data['est_disponible'] == 0) ? '' : 'checked'; ?>>
                Marquer comme disponible pour la réservation
            </label>
            
            <button type="submit"><?php echo $edit_mode ? 'Modifier la Place' : 'Ajouter la Place'; ?></button>
            <?php if ($edit_mode): ?>
                <a href="manage_places.php" class="btn">Annuler la Modification</a>
            <?php endif; ?>
        </form>
    </div>
    
    <h3>Liste des Places d'Étude</h3>
    <?php if ($places_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Numéro de Place</th>
                    <th>Description</th>
                    <th>Disponibilité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $places_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_place']; ?></td>
                    <td><?php echo htmlspecialchars($row['numero_place']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <span class="<?php echo $row['est_disponible'] ? 'dispo' : 'indispo'; ?>">
                            <?php echo $row['est_disponible'] ? 'Oui' : 'Non'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="manage_places.php?action=edit&id=<?php echo $row['id_place']; ?>" class="btn-edit">Modifier</a>
                        <a href="manage_places.php?action=delete&id=<?php echo $row['id_place']; ?>" class="btn-delete" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette place ? Assurez-vous qu\'il n\'y ait aucune réservation future.');">Supprimer</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune place d'étude n'est enregistrée. Veuillez en ajouter une ci-dessus.</p>
    <?php endif; ?>
</div>

</body>
</html>