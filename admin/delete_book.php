<?php
session_start();
require '../config/db.php';

if ($_SESSION['role'] == 'admin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM books WHERE id=$id");
}
header("Location: dashboard.php");
?>
