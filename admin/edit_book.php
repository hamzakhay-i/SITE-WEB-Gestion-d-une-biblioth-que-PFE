<?php
session_start();
require '../config/db.php';

// Vérification Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Récupération du livre
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
$book = mysqli_fetch_assoc($result);

if (!$book) {
    die("Livre introuvable.");
}

$msg = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $stock = (int)$_POST['stock'];
    $cover_url = mysqli_real_escape_string($conn, $_POST['cover_url']);
    $pdf_url = isset($book['pdf_url']) ? $book['pdf_url'] : ''; // Garder l'ancien par défaut

    // Gestion Upload Image (si une nouvelle image est envoyée)
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid("book_", true) . "." . $ext;
            move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $filename);
            $cover_url = "../uploads/" . $filename; // Mise à jour de l'URL avec le fichier local
        }
    }

    // Gestion Upload PDF
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        if ($ext == 'pdf') {
            $filename = uniqid("ebook_", true) . ".pdf";
            move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $filename);
            $pdf_url = "../uploads/" . $filename;
        }
    }

    $sql = "UPDATE books SET title='$title', author='$author', category='$category', stock=$stock, cover_url='$cover_url', pdf_url='$pdf_url' WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='alert alert-success'>Livre modifié avec succès ! <a href='dashboard.php'>Retour au dashboard</a></div>";
        // Rafraîchir les données affichées
        $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id = $id"));
    } else {
        $msg = "<div class='alert alert-danger'>Erreur SQL : " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Livre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-edit"></i> Modifier le livre</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $msg; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3"><label class="form-label">Titre</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Auteur</label><input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Catégorie</label><input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($book['category']); ?>" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Stock</label><input type="number" name="stock" class="form-control" value="<?php echo $book['stock']; ?>" required></div>
                            <div class="mb-3"><label class="form-label">URL Image (ou laisser tel quel)</label><input type="text" name="cover_url" class="form-control" value="<?php echo htmlspecialchars($book['cover_url']); ?>"></div>
                            <div class="mb-4"><label class="form-label">Ou uploader une nouvelle image</label><input type="file" name="cover_file" class="form-control"></div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fichier PDF (E-book)</label>
                                <input type="file" name="pdf_file" class="form-control" accept="application/pdf">
                                <?php if(!empty($book['pdf_url'])): ?>
                                    <div class="form-text text-success"><i class="fas fa-check-circle"></i> PDF actuel : <a href="<?php echo $book['pdf_url']; ?>" target="_blank">Voir le fichier</a></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-warning w-100">Enregistrer les modifications</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>