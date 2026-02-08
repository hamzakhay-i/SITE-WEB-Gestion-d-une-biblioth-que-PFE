<?php
session_start();
require '../config/db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Auto-fix: Créer la colonne cover_url si elle n'existe pas (évite l'erreur SQL)
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM books LIKE 'cover_url'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE books ADD COLUMN cover_url VARCHAR(500) DEFAULT 'https://placehold.co/300x450?text=Livre'");
}

// Add Book Logic
if (isset($_POST['add_book'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $stock = (int)$_POST['stock'];
    
    // Gestion Upload Image
    $cover_url = "https://placehold.co/300x450?text=Livre"; // Image par défaut
    
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = uniqid("book_", true) . "." . $ext;
            move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $filename);
            $cover_url = "../uploads/" . $filename;
        }
    } elseif (!empty($_POST['cover_url'])) {
        $cover_url = mysqli_real_escape_string($conn, $_POST['cover_url']);
    }

    mysqli_query($conn, "INSERT INTO books (title, author, category, stock, cover_url) VALUES ('$title', '$author', '$category', $stock, '$cover_url')");
}

// Fetch Stats
 $total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as count FROM books"))['count'];
 $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'"))['count'];
 $active_borrows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status='borrowed'"))['count'];

// Fetch Books
 $books = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Admin</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; overflow-x: hidden; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { font-size: 2.5rem; opacity: 0.3; }
        .book-cover { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        footer { margin-top: 50px; padding: 20px; border-top: 1px solid #ddd; color: #777; font-size: 0.9rem; }
        @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; min-height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <!-- Sidebar (Menu) -->
    <div class="sidebar d-flex flex-column">
        <div class="p-4 text-center border-bottom border-secondary">
            <h3 class="fw-bold"><i class="fas fa-book-reader"></i> SmartLib</h3>
            <small class="text-white-50">Espace Administrateur</small>
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-th-large me-2"></i> Tableau de bord</a>
        <a href="borrowings.php"><i class="fas fa-hand-holding me-2"></i> Gestion Emprunts</a>
        <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> Voir le site</a>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <h4 class="m-0 text-dark">📚 Gestion des Livres</h4>
            <div class="d-flex align-items-center">
                <span class="me-3 fw-bold text-secondary">Admin</span>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">A</div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card stat-card bg-primary text-white p-3 shadow">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><h6 class="text-white-50">Total Livres</h6><h2 class="fw-bold"><?php echo $total_books ? $total_books : 0; ?></h2></div>
                        <i class="fas fa-book icon-box"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white p-3 shadow">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><h6 class="text-white-50">Utilisateurs</h6><h2 class="fw-bold"><?php echo $total_users; ?></h2></div>
                        <i class="fas fa-users icon-box"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-warning text-dark p-3 shadow">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><h6 class="text-dark-50">Emprunts Actifs</h6><h2 class="fw-bold"><?php echo $active_borrows; ?></h2></div>
                        <i class="fas fa-clock icon-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Book Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3"><h5 class="m-0 fw-bold text-primary"><i class="fas fa-plus-circle"></i> Ajouter un nouveau livre</h5></div>
            <div class="card-body">
                <form method="POST" class="row g-3" enctype="multipart/form-data">
                    <div class="col-md-3"><label class="form-label">Titre</label><input type="text" name="title" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Auteur</label><input type="text" name="author" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Catégorie</label><input type="text" name="category" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Stock</label><input type="number" name="stock" class="form-control" required></div>
                    <!-- Champ Image modifié pour accepter Fichier -->
                    <div class="col-md-2"><label class="form-label">Image (Fichier)</label><input type="file" name="cover_file" class="form-control" accept="image/*"></div>
                    <div class="col-12 text-end"><button type="submit" name="add_book" class="btn btn-success px-4"><i class="fas fa-save"></i> Enregistrer</button></div>
                </form>
            </div>
        </div>

        <!-- Books Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>#</th><th>Couverture</th><th>Titre</th><th>Auteur</th><th>Catégorie</th><th>Stock</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($books)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><img src="<?php echo !empty($row['cover_url']) ? $row['cover_url'] : 'https://placehold.co/40x60?text=No+Img'; ?>" class="book-cover" alt="Cover"></td>
                                <td class="fw-bold"><?php echo $row['title']; ?></td>
                                <td><?php echo $row['author']; ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $row['category']; ?></span></td>
                                <td><span class="badge <?php echo $row['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>"><?php echo $row['stock']; ?> dispo</span></td>
                                <td>
                                    <a href="edit_book.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                    <a href="delete_book.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce livre ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center">
            &copy; <?php echo date('Y'); ?> SmartLib System. Tous droits réservés. <br>
            <small>Développé avec ❤️ pour le PFE</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
