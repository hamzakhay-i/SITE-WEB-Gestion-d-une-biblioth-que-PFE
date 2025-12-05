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
$book_data = [];

// --- FONCTIONS DE GESTION CRUD ---

// 1. Gérer l'ajout ou la modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = trim($_POST['titre']);
    $isbn = trim($_POST['isbn']);
    $annee = (int)$_POST['annee_publication'];
    $quantite_totale = (int)$_POST['quantite_totale'];
    $auteur_nom = trim($_POST['auteur_nom']);
    $auteur_prenom = trim($_POST['auteur_prenom']);
    $book_id = isset($_POST['id_livre']) ? (int)$_POST['id_livre'] : 0;

    // --- LOGIQUE AUTEUR (Ajouter ou récupérer l'ID) ---
    $auteur_sql = "SELECT id_auteur FROM auteurs WHERE nom = ? AND prenom = ?";
    $stmt_auteur = $conn->prepare($auteur_sql);
    $stmt_auteur->bind_param("ss", $auteur_nom, $auteur_prenom);
    $stmt_auteur->execute();
    $auteur_result = $stmt_auteur->get_result();
    
    $auteur_id = 0;
    if ($auteur_row = $auteur_result->fetch_assoc()) {
        $auteur_id = $auteur_row['id_auteur']; // Auteur trouvé
    } else {
        // Ajouter un nouvel auteur
        $insert_auteur_sql = "INSERT INTO auteurs (nom, prenom) VALUES (?, ?)";
        $stmt_insert_auteur = $conn->prepare($insert_auteur_sql);
        $stmt_insert_auteur->bind_param("ss", $auteur_nom, $auteur_prenom);
        if ($stmt_insert_auteur->execute()) {
            $auteur_id = $conn->insert_id; // Récupérer l'ID du nouvel auteur
        } else {
            $message = "Erreur lors de l'ajout de l'auteur.";
        }
        $stmt_insert_auteur->close();
    }
    $stmt_auteur->close();


    if ($auteur_id > 0) {
        // --- LOGIQUE LIVRE (AJOUT ou MODIFICATION) ---
        if ($book_id > 0) {
            // MODE MODIFICATION
            // IMPORTANT : Ne pas permettre de diminuer la quantité disponible en dessous de zéro
            $current_available = $conn->query("SELECT quantite_disponible FROM livres WHERE id_livre = $book_id")->fetch_row()[0];
            $available_update = $quantite_totale - ($quantite_totale - $current_available); // Simplement: $current_available si $quantite_totale est > $current_available
            if ($quantite_totale < $current_available) {
                $message = "Erreur: La quantité totale ne peut pas être inférieure à la quantité disponible actuelle.";
            } else {
                 $sql = "UPDATE livres SET titre=?, isbn=?, annee_publication=?, quantite_totale=?, quantite_disponible=?, auteur_id=? WHERE id_livre=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiiiii", $titre, $isbn, $annee, $quantite_totale, $quantite_totale, $auteur_id, $book_id);
                if ($stmt->execute()) {
                    $message = "Livre modifié avec succès !";
                } else {
                    $message = "Erreur lors de la modification : " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            // MODE AJOUT
            $sql = "INSERT INTO livres (titre, isbn, annee_publication, quantite_totale, quantite_disponible, auteur_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiii", $titre, $isbn, $annee, $quantite_totale, $quantite_totale, $auteur_id);
            if ($stmt->execute()) {
                $message = "Nouveau livre ajouté avec succès !";
            } else {
                $message = "Erreur lors de l'ajout du livre : " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// 2. Gérer la suppression
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Vérifier si des emprunts actifs existent
    $check_loan = $conn->query("SELECT COUNT(*) FROM emprunts WHERE livre_id = $id AND date_retour_reelle IS NULL")->fetch_row()[0];
    
    if ($check_loan > 0) {
        $message = "Impossible de supprimer: Ce livre a encore $check_loan emprunt(s)