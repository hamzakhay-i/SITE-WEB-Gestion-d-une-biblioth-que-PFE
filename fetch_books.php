<?php
// Inclure la connexion à la base de données
include 'db_connect.php';

// Récupérer la requête de recherche envoyée par AJAX
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_param = "%" . $search_query . "%"; // Ajout des jokers % pour la recherche LIKE

// Requête SQL sécurisée
$sql = "SELECT l.titre, l.isbn, l.annee_publication, l.quantite_disponible, 
               CONCAT(a.prenom, ' ', a.nom) AS auteur_complet
        FROM livres l
        JOIN auteurs a ON l.auteur_id = a.id_auteur
        WHERE l.titre LIKE ? OR CONCAT(a.prenom, ' ', a.nom) LIKE ? OR l.isbn LIKE ?
        ORDER BY l.titre ASC";

// 1. Préparation de la requête
$stmt = $conn->prepare($sql);

// 2. Lier les paramètres à la requête (s = string)
$stmt->bind_param("sss", $search_param, $search_param, $search_param);

// 3. Exécuter la requête
$stmt->execute();
$result = $stmt->get_result();

// --- Construction de la sortie HTML ---
$output = '';

if ($result->num_rows > 0) {
    // Si des résultats sont trouvés, construire la table
    $output .= '<table>';
    $output .= '<thead><tr><th>Titre</th><th>Auteur</th><th>ISBN</th><th>Année</th><th>Disponibilité</th></tr></thead>';
    $output .= '<tbody>';
    
    while($row = $result->fetch_assoc()) {
        $dispo = (int)$row['quantite_disponible'];
        $class_dispo = ($dispo > 0) ? 'disponible' : 'indisponible';
        $texte_dispo = ($dispo > 0) ? $dispo . ' exemplaire(s)' : 'Indisponible';
        
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($row['titre']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['auteur_complet']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['isbn']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['annee_publication']) . '</td>';
        // Utilisation de htmlspecialchars pour prévenir les attaques XSS
        $output .= '<td><span class="' . $class_dispo . '">' . $texte_dispo . '</span></td>'; 
        $output .= '</tr>';
    }
    
    $output .= '</tbody>';
    $output .= '</table>';
} else {
    // Si aucun résultat
    $output .= '<p style="text-align: center;">Aucun livre trouvé correspondant à votre recherche.</p>';
}

// Afficher la sortie (c'est le contenu qui est envoyé à la fonction AJAX)
echo $output;

// Fermer la connexion et le statement
$stmt->close();
$conn->close();
?>