<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="rapport_emprunts_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Utilisateur', 'Livre', 'Date Emprunt', 'Date Retour Prevue', 'Statut', 'Date Retour Reel'));

$query = "SELECT b.id, u.name, bk.title, b.borrow_date, b.due_date, b.status, b.return_date 
          FROM borrowings b 
          JOIN users u ON b.user_id = u.id 
          JOIN books bk ON b.book_id = bk.id 
          ORDER BY b.borrow_date DESC";
$result = mysqli_query($conn, $query);

while($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}
fclose($output);
?>