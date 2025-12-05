<?php
session_start();
include '../db_connect.php'; 

// --- VÉRIFICATION DE LA SÉCURITÉ ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$default_loan_duration = 7; // Durée standard de l'emprunt en jours

// --- FONCTIONS UTILITAIRES ---

/**
 * Calcule le nombre de jours de retard et la pénalité.
 * Pénalité: 1 MAD par jour de retard (exemple)
 */
function calculate_penalty($due_date, $return_date, $conn) {
    $due = new DateTime($due_date);
    $return = new DateTime($return_date);
    
    // Si la date de retour est après la date d'échéance
    if ($return > $due) {
        $interval = $due->diff($return);
        $days_late = $interval->days;
        $penalty_rate = 1; // 1 MAD par jour
        $penalty_amount = $days_late * $penalty_rate;
        return ['days' => $days_late, 'amount' => $penalty_amount];
    }
    
    return ['days' => 0, 'amount' => 0];
}

// --- LOGIQUE DE GESTION DES TRANSACTIONS ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Gérer l'ENREGISTREMENT D'UN NOUVEL EMPRUNT (Sortie de Livre)
    if (isset($_POST['action']) && $_POST['action'] === 'new_loan') {
        $member_id = (int)$_POST['member_id'];
        $book_isbn = trim($_POST['book_isbn']);
        $date_emprunt = date('Y-m-d');
        $date_retour_prevu = date('Y-m-d', strtotime("+$default_loan_duration days"));
        $admin_id = $_SESSION['user_id'];

        // a. Vérifier si le livre existe et est disponible (stock > 0)
        $sql_book = "SELECT id_livre, stock FROM livres WHERE isbn = ?";
        $stmt_book = $conn->prepare($sql_book);
        $stmt_book->bind_param("s", $book_isbn);
        $stmt_book->execute();
        $book_result = $stmt_book->get_result();
        $book_data = $book_result->fetch_assoc();
        $stmt_book->close();

        if (!$book_data || $book_data['stock'] <= 0) {
            $message = "❌ Erreur : Livre introuvable ou stock épuisé pour l'ISBN **" . htmlspecialchars($book_isbn) . "**.";
        } else {
            $book_id = $book_data['id_livre'];
            
            // b. Vérifier si le membre existe (optionnel, mais bonne pratique)
            $sql_member = "SELECT COUNT(*) FROM utilisateurs WHERE id_utilisateur = ? AND type_utilisateur = 'member'";
            $stmt_member = $conn->prepare($sql_member);
            $stmt_member->bind_param("i", $member_id);
            $stmt_member->execute();
            $member_exists = $stmt_member->get_result()->fetch_row()[0];
            $stmt_member->close();

            if ($member_exists == 0) {
                 $message = "❌ Erreur : L'ID Membre spécifié (**$member_id**) est invalide ou n'est pas un membre.";
            } else {
                
                // c. Créer l'emprunt (Insertion)
                $conn->begin_transaction();
                try {
                    $sql_insert = "INSERT INTO emprunts (livre_id, membre_id, date_emprunt, date_retour_prevu, emprunte_par) 
                                   VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("iissi", $book_id, $member_id, $date_emprunt, $date_retour_prevu, $admin_id);
                    $stmt_insert->execute();
                    $stmt_insert->close();
                    
                    // d. Mettre à jour le stock (Décrémentation)
                    $sql_update_stock = "UPDATE livres SET stock = stock - 1 WHERE id_livre = ?";
                    $stmt_update = $conn->prepare($sql_update_stock);
                    $stmt_update->bind_param("i", $book_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    $conn->commit();
                    $message = "✅ Emprunt enregistré avec succès ! Retour prévu le **" . $date_retour_prevu . "**.";
                    
                } catch (mysqli_sql_exception $e) {
                    $conn->rollback();
                    $message = "Erreur lors de l'opération d'emprunt : " . $e->getMessage();
                }
            }
        }
    }
    
    // 2. Gérer le MARQUAGE D'UN RETOUR (Entrée de Livre)
    if (isset($_POST['action']) && $_POST['action'] === 'return_book') {
        $loan_id = (int)$_POST['loan_id'];
        $actual_return_date = date('Y-m-d'); // La date d'aujourd'hui
        $admin_id = $_SESSION['user_id'];
        
        // a. Récupérer les détails de l'emprunt
        $sql_loan = "SELECT livre_id, date_retour_prevu FROM emprunts WHERE id_emprunt = ? AND date_retour_reel IS NULL";
        $stmt_loan = $conn->prepare($sql_loan);
        $stmt_loan->bind_param("i", $loan_id);
        $stmt_loan->execute();
        $loan_data = $stmt_loan->get_result()->fetch_assoc();
        $stmt_loan->close();

        if (!$loan_data) {
            $message = "❌ Erreur : Emprunt introuvable ou déjà retourné.";
        } else {
            $book_id = $loan_data['livre_id'];
            $due_date = $loan_data['date_retour_prevu'];
            
            // b. Calculer la pénalité
            $penalty = calculate_penalty($due_date, $actual_return_date, $conn);
            $days_late = $penalty['days'];
            $penalty_amount = $penalty['amount'];

            $conn->begin_transaction();
            try {
                // c. Mettre à jour l'emprunt (Marquer comme retourné)
                $sql_update_loan = "UPDATE emprunts SET date_retour_reel = ?, retourne_par = ?, jours_retard = ?, montant_penalite = ? WHERE id_emprunt = ?";
                $stmt_update_loan = $conn->prepare($sql_update_loan);
                $stmt_update_loan->bind_param("siiii", $actual_return_date, $admin_id, $days_late, $penalty_amount, $loan_id);
                $stmt_update_loan->execute();
                $stmt_update_loan->close();
                
                // d. Mettre à jour le stock (Incrémentation)
                $sql_update_stock = "UPDATE livres SET stock = stock + 1 WHERE id_livre = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                $stmt_update_stock->bind_param("i", $book_id);
                $stmt_update_stock->execute();
                $stmt_update_stock->close();

                $conn->commit();
                
                $return_msg = "✅ Retour enregistré ! ";
                if ($days_late > 0) {
                    $return_msg .= "Attention : **$days_late jours de retard**. Pénalité de **$penalty_amount MAD**.";
                } else {
                    $return_msg .= "Livre retourné à temps.";
                }
                $message = $return_msg;
                
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $message = "Erreur lors de l'opération de retour : " . $e->getMessage();
            }
        }
    }
}

// --- LECTURE DES DONNÉES POUR L'AFFICHAGE ---

// 1. Emprunts ACTIFS (Non retournés)
$active_loans_sql = "
    SELECT 
        e.id_emprunt, e.date_emprunt, e.date_retour_prevu,
        l.titre AS livre_titre, l.isbn, 
        u.id_utilisateur AS membre_id, u.nom_utilisateur AS membre_nom
    FROM 
        emprunts e
    JOIN 
        livres l ON e.livre_id = l.id_livre
    JOIN 
        utilisateurs u ON e.membre_id = u.id_utilisateur
    WHERE 
        e.date_retour_reel IS NULL
    ORDER BY 
        e.date_retour_prevu ASC
";
$active_loans_result = $conn->query($active_loans_sql);

// 2. Historique des Retours (Exemple : 10 derniers)
$history_sql = "
    SELECT 
        e.id_emprunt, e.date_emprunt, e.date_retour_prevu, e.date_retour_reel, e.montant_penalite,
        l.titre AS livre_titre, 
        u.nom_utilisateur AS membre_nom
    FROM 
        emprunts e
    JOIN 
        livres l ON e.livre_id = l.id_livre
    JOIN 
        utilisateurs u ON e.membre_id = u.id_utilisateur
    WHERE 
        e.date_retour_reel IS NOT NULL
    ORDER BY 
        e.date_retour_reel DESC
    LIMIT 10
";
$history_result = $conn->query($history_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emprunts et Retours - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; }
        .header { background-color: #00bcd4; color: white; padding: 20px; text-align: center; }
        .container { width: 90%; margin: 20px auto; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; }
        .navbar { background-color: #0097a7; padding: 10px 0; text-align: center; }
        .form-section { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; border-left: 5px solid #00bcd4; }
        .form-section h3 { color: #00bcd4; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"], select { padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; text-decoration: none; display: inline-block;}
        .btn-emprunt { background-color: #00bcd4; }
        .btn-retour { background-color: #ff9800; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #b2ebf2; }
        .late { color: red; font-weight: bold; }
        .ontime { color: green; }
    </style>
</head>
<body>

<div class="header">
    <h1>📚 Gestion des Emprunts et Retours</h1>
</div>

<div class="navbar">
    <a href="dashboard.php">Accueil Admin</a>
    <a href="manage_books.php">Gérer les Livres</a>
    <a href="manage_places.php">Gérer les Places</a>
    <a href="../logout.php">Déconnexion</a>
</div>

<div class="container">
    <?php if ($message): ?>
        <p style="color: black; font-weight: bold; background-color: #e0f7fa; padding: 15px; border-radius: 5px; border-left: 5px solid #00bcd4;"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <div class="form-section">
        <h3>Nouvel Emprunt (Sortie de Livre)</h3>
        <form method="POST" action="manage_loans.php">
            <input type="hidden" name="action" value="new_loan">
            
            <label for="member_id">ID du Membre :</label>
            <input type="number" id="member_id" name="member_id" required placeholder="Ex: 5" style="width: 200px;">

            <label for="book_isbn">ISBN du Livre (à emprunter) :</label>
            <input type="text" id="book_isbn" name="book_isbn" required placeholder="Ex: 978-1234567890" style="width: 300px;">
            
            <button type="submit" class="btn-emprunt">Enregistrer l'Emprunt</button>
            <p style="font-size: 0.9em; color: #555;">(Durée d'emprunt par défaut : <?php echo $default_loan_duration; ?> jours)</p>
        </form>
    </div>

    <hr>
    
    <div class="form-section" style="border-left-color: #ff9800;">
        <h3>Livres Actuellement Empruntés (<?php echo $active_loans_result->num_rows; ?>)</h3>
        
        <?php if ($active_loans_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Emprunt</th>
                        <th>Membre (ID)</th>
                        <th>Livre (Titre/ISBN)</th>
                        <th>Date Emprunt</th>
                        <th>Retour Prévu</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $active_loans_result->fetch_assoc()): 
                        $due_date = new DateTime($row['date_retour_prevu']);
                        $today = new DateTime(date('Y-m-d'));
                        $is_late = $today > $due_date;
                        $status_class = $is_late ? 'late' : 'ontime';
                        
                        // Calculer les jours de retard pour l'affichage (si en retard)
                        $days_late_display = 0;
                        if ($is_late) {
                            $interval = $due_date->diff($today);
                            $days_late_display = $interval->days;
                        }
                    ?>
                    <tr>
                        <td><?php echo $row['id_emprunt']; ?></td>
                        <td><?php echo htmlspecialchars($row['membre_nom']); ?> (<?php echo $row['membre_id']; ?>)</td>
                        <td><?php echo htmlspecialchars($row['livre_titre']); ?><br><small>(ISBN: <?php echo htmlspecialchars($row['isbn']); ?>)</small></td>
                        <td><?php echo $row['date_emprunt']; ?></td>
                        <td class="<?php echo $status_class; ?>">
                            <?php echo $row['date_retour_prevu']; ?>
                        </td>
                        <td>
                            <span class="<?php echo $status_class; ?>">
                                <?php echo $is_late ? "EN RETARD ($days_late_display j)" : 'Actif'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="manage_loans.php" style="display:inline;">
                                <input type="hidden" name="action" value="return_book">
                                <input type="hidden" name="loan_id" value="<?php echo $row['id_emprunt']; ?>">
                                <button type="submit" class="btn-retour" 
                                   onclick="return confirm('Confirmer le retour du livre (ID Emprunt: <?php echo $row['id_emprunt']; ?>) ? La pénalité sera calculée.');">
                                    Marquer Retourné
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun livre n'est actuellement en cours d'emprunt.</p>
        <?php endif; ?>
    </div>

    <hr>
    
    <div class="form-section">
        <h3>Historique des 10 Derniers Retours</h3>
        
        <?php if ($history_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Emprunt</th>
                        <th>Membre</th>
                        <th>Livre</th>
                        <th>Emprunté le</th>
                        <th>Retour Prévu</th>
                        <th>Retourné le</th>
                        <th>Pénalité (MAD)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id_emprunt']; ?></td>
                        <td><?php echo htmlspecialchars($row['membre_nom']); ?></td>
                        <td><?php echo htmlspecialchars($row['livre_titre']); ?></td>
                        <td><?php echo $row['date_emprunt']; ?></td>
                        <td><?php echo $row['date_retour_prevu']; ?></td>
                        <td><?php echo $row['date_retour_reel']; ?></td>
                        <td class="<?php echo $row['montant_penalite'] > 0 ? 'late' : 'ontime'; ?>">
                            <?php echo number_format($row['montant_penalite'], 2); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun retour n'a encore été enregistré dans l'historique.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>