<?php
session_start();
include 'db_connect.php'; 

// --- VÉRIFICATION DE LA SÉCURITÉ (Membre uniquement) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header("Location: login.php");
    exit();
}

$message = '';
$available_places = [];
$search_performed = false;
$date_reservation = date('Y-m-d'); // Date par défaut : aujourd'hui
$heure_debut = '';
$heure_fin = '';

// Fonction pour générer les options d'heure par intervalles de 30 min (ex: 08:00 à 18:00)
function generateTimeOptions($start = 8, $end = 18, $interval = 30) {
    $options = [];
    $current = strtotime("$start:00");
    $endTime = strtotime("$end:00");
    while ($current <= $endTime) {
        $time_str = date('H:i', $current);
        $options[] = $time_str;
        $current = strtotime("+$interval minutes", $current);
    }
    return $options;
}

// --- LOGIQUE DE RECHERCHE ET DE RÉSERVATION ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Logique de Recherche de Disponibilité
    if (isset($_POST['action']) && $_POST['action'] === 'search_availability') {
        $date_reservation = trim($_POST['date_reservation']);
        $heure_debut = trim($_POST['heure_debut']);
        $heure_fin = trim($_POST['heure_fin']);
        $search_performed = true;

        if (empty($date_reservation) || empty($heure_debut) || empty($heure_fin)) {
            $message = "Veuillez remplir la date et les heures pour la recherche.";
        } elseif ($heure_debut >= $heure_fin) {
             $message = "L'heure de début doit être strictement antérieure à l'heure de fin.";
        } else {
            // Requête pour trouver les places DISPONIBLES pour la date/heure demandée.
            // Utilise un sous-select pour exclure les places qui ont un chevauchement de réservation.
            $sql = "
                SELECT 
                    id_place, numero_place, description 
                FROM 
                    places_etude 
                WHERE 
                    est_disponible = 1 
                AND 
                    id_place NOT IN (
                        SELECT 
                            place_id 
                        FROM 
                            reservations_place 
                        WHERE 
                            date_reservation = ? 
                        AND 
                            statut = 'active'
                        AND 
                            (? < heure_fin AND ? > heure_debut)
                    )
                ORDER BY numero_place ASC
            ";
            
            $stmt = $conn->prepare($sql);
            // Les paramètres sont : date_reservation, heure_fin (pour l'exclusion), heure_debut (pour l'exclusion)
            $stmt->bind_param("sss", $date_reservation, $heure_debut, $heure_fin);
            $stmt->execute();
            $result = $stmt->get_result();
            $available_places = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }

    // 2. Logique de Création de Réservation
    if (isset($_POST['action']) && $_POST['action'] === 'make_reservation') {
        $place_id = (int)$_POST['place_id'];
        $date_res = trim($_POST['res_date_reservation']);
        $heure_deb = trim($_POST['res_heure_debut']);
        $heure_f = trim($_POST['res_heure_fin']);
        $member_id = $_SESSION['user_id'];
        
        // Nouvelle vérification de conflit avant l'insertion (sécurité)
        $check_sql = "
            SELECT 
                COUNT(*) 
            FROM 
                reservations_place 
            WHERE 
                place_id = ? 
            AND 
                date_reservation = ? 
            AND 
                statut = 'active'
            AND 
                (? < heure_fin AND ? > heure_debut)
        ";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("isss", $place_id, $date_res, $heure_deb, $heure_f);
        $stmt_check->execute();
        $conflict_count = $stmt_check->get_result()->fetch_row()[0];
        $stmt_check->close();

        if ($conflict_count > 0) {
            $message = "Erreur de réservation : La place n'est plus disponible pour ce créneau.";
        } else {
            // Insertion de la réservation
            $insert_sql = "INSERT INTO reservations_place (membre_id, place_id, date_reservation, heure_debut, heure_fin, statut, date_creation) 
                           VALUES (?, ?, ?, ?, ?, 'active', NOW())";
            $stmt_insert = $conn->prepare($insert_sql);
            $statut_active = 'active';
            $stmt_insert->bind_param("iissi", $member_id, $place_id, $date_res, $heure_deb, $heure_f);
            
            if ($stmt_insert->execute()) {
                // Redirection pour éviter la soumission multiple et effacer les variables POST de la recherche
                header("Location: reserver_place.php?success=true&place=$place_id&date=$date_res&start=$heure_deb&end=$heure_f");
                exit();
            } else {
                $message = "Erreur lors de la réservation : " . $conn->error;
            }
            $stmt_insert->close();
        }
    }
}

// Gérer le message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $place_id = (int)$_GET['place'];
    $date = htmlspecialchars($_GET['date']);
    $start = htmlspecialchars($_GET['start']);
    $end = htmlspecialchars($_GET['end']);
    
    // Récupérer le numéro de la place pour un message convivial
    $place_info = $conn->query("SELECT numero_place FROM places_etude WHERE id_place = $place_id")->fetch_assoc();
    $place_num = $place_info ? $place_info['numero_place'] : "ID $place_id";

    $message = "✅ Réservation confirmée ! Vous avez réservé la place **$place_num** pour le $date de **$start** à **$end**.";
}

$conn->close();
$time_options = generateTimeOptions();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réserver une Place d'Étude</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #e8eaf6; }
        .header { background-color: #3f51b5; color: white; padding: 20px; text-align: center; }
        .container { width: 80%; margin: 20px auto; }
        .navbar a { color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; }
        .navbar { background-color: #303f9f; padding: 10px 0; text-align: center; }
        .content-section { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .content-section h3 { color: #3f51b5; border-bottom: 2px solid #e0e7ff; padding-bottom: 10px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="date"], select, input[type="text"] { padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; text-decoration: none; display: inline-block;}
        button[type="submit"] { background-color: #3f51b5; }
        .search-form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 20px; align-items: end; }
        .place-card { border: 1px solid #bbdefb; padding: 15px; margin-top: 10px; border-radius: 4px; background-color: #e3f2fd; display: flex; justify-content: space-between; align-items: center;}
        .place-card .details { flex-grow: 1; }
        .place-card .details strong { color: #3f51b5; }
        .reservation-button { background-color: #ff9800; }
    </style>
</head>
<body>

<div class="header">
    <h1>📚 Réservation de Places d'Étude</h1>
</div>

<div class="navbar">
    <a href="index.php">Accueil</a>
    <a href="reserver_place.php">Réserver une Place</a>
    <a href="logout.php">Déconnexion (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
</div>

<div class="container">
    <?php if ($message): ?>
        <p style="color: black; font-weight: bold; background-color: #c8e6c9; padding: 15px; border-radius: 5px; border-left: 5px solid #4CAF50;"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <div class="content-section">
        <h3>1. Rechercher la Disponibilité</h3>
        
        <form method="POST" action="reserver_place.php">
            <input type="hidden" name="action" value="search_availability">
            
            <div class="search-form-grid">
                <div>
                    <label for="date_reservation">Date:</label>
                    <input type="date" id="date_reservation" name="date_reservation" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo htmlspecialchars($date_reservation); ?>" required>
                </div>
                
                <div>
                    <label for="heure_debut">Heure de Début:</label>
                    <select id="heure_debut" name="heure_debut" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($time_options as $time): ?>
                            <option value="<?php echo $time; ?>" 
                                <?php echo ($search_performed && $heure_debut === $time) ? 'selected' : ''; ?>>
                                <?php echo $time; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="heure_fin">Heure de Fin:</label>
                    <select id="heure_fin" name="heure_fin" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($time_options as $time): ?>
                            <option value="<?php echo $time; ?>" 
                                <?php echo ($search_performed && $heure_fin === $time) ? 'selected' : ''; ?>>
                                <?php echo $time; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit">Rechercher les Places</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($search_performed): ?>
    <div class="content-section">
        <h3>2. Places Disponibles pour le <?php echo htmlspecialchars($date_reservation); ?> de <?php echo htmlspecialchars($heure_debut); ?> à <?php echo htmlspecialchars($heure_fin); ?></h3>
        
        <?php if (!empty($available_places)): ?>
            <p style="font-weight: bold; color: #388e3c;"><?php echo count($available_places); ?> place(s) trouvée(s) :</p>
            
            <?php foreach ($available_places as $place): ?>
                <div class="place-card">
                    <div class="details">
                        <strong>Place n° <?php echo htmlspecialchars($place['numero_place']); ?></strong><br>
                        <?php echo htmlspecialchars($place['description']); ?>
                    </div>
                    
                    <form method="POST" action="reserver_place.php">
                        <input type="hidden" name="action" value="make_reservation">
                        <input type="hidden" name="place_id" value="<?php echo $place['id_place']; ?>">
                        <input type="hidden" name="res_date_reservation" value="<?php echo htmlspecialchars($date_reservation); ?>">
                        <input type="hidden" name="res_heure_debut" value="<?php echo htmlspecialchars($heure_debut); ?>">
                        <input type="hidden" name="res_heure_fin" value="<?php echo htmlspecialchars($heure_fin); ?>">
                        
                        <button type="submit" class="reservation-button" 
                                onclick="return confirm('Confirmez-vous la réservation de la place <?php echo htmlspecialchars($place['numero_place']); ?> pour ce créneau ?');">
                            Réserver
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <p style="color: #d32f2f; font-weight: bold;">😔 Aucune place d'étude disponible pour ce créneau. Veuillez modifier votre recherche.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>