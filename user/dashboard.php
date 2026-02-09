<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

// Initialisation des messages
$msg = "";
$error = "";

// Borrow Logic
if (isset($_GET['borrow'])) {
    $book_id = (int)$_GET['borrow'];
    $user_id = $_SESSION['user_id'];
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime($borrow_date. ' + 14 days')); // 2 weeks due

    // Check stock again
    $check = mysqli_query($conn, "SELECT stock FROM books WHERE id=$book_id");
    $stock_data = mysqli_fetch_assoc($check);

    if ($stock_data && $stock_data['stock'] > 0) {
        // 1. Insert Borrowing
        mysqli_query($conn, "INSERT INTO borrowings (user_id, book_id, borrow_date, due_date) VALUES ($user_id, $book_id, '$borrow_date', '$due_date')");
        // 2. Decrease Stock
        mysqli_query($conn, "UPDATE books SET stock = stock - 1 WHERE id=$book_id");
        $msg = "Livre emprunté avec succès !";
    } else {
        $error = "Rupture de stock !";
    }
}

// Search & Filter Logic
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM books ORDER BY category");

 $search = "";
 $category_filter = "";
 $where_clauses = [];

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "(title LIKE '%$search%' OR author LIKE '%$search%')";
}
if(isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = mysqli_real_escape_string($conn, $_GET['category']);
    $where_clauses[] = "category = '$category_filter'";
}

$query = "SELECT * FROM books";
if(!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

 $books = mysqli_query($conn, $query);

// Récupérer les IDs de la wishlist pour l'utilisateur connecté
$wishlist_ids = [];
$check_wish = mysqli_query($conn, "SHOW TABLES LIKE 'wishlist'");
if (mysqli_num_rows($check_wish) > 0) {
    $w_query = mysqli_query($conn, "SELECT book_id FROM wishlist WHERE user_id = " . $_SESSION['user_id']);
    while($w_row = mysqli_fetch_assoc($w_query)) {
        $wishlist_ids[] = $w_row['book_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Lecteur - SmartLib</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        [data-bs-theme="dark"] body { background-color: #212529; color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; z-index: 100; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .book-card { transition: transform 0.2s; border: none; border-radius: 10px; overflow: hidden; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .book-cover { height: 200px; object-fit: cover; width: 100%; }
        @media (max-width: 768px) { .sidebar { width: 100%; position: relative; min-height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="p-4 text-center border-bottom border-secondary">
            <h3 class="fw-bold"><i class="fas fa-book-reader"></i> SmartLib</h3>
            <small class="text-white-50">Espace Lecteur</small>
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-book-open me-2"></i> Catalogue</a>
        <a href="my_books.php"><i class="fas fa-bookmark me-2"></i> Mes Emprunts</a>
        <a href="wishlist.php"><i class="fas fa-heart me-2"></i> Mes Favoris</a>
        <a href="profile.php"><i class="fas fa-user-cog me-2"></i> Mon Profil</a>
        <button id="darkModeToggle" class="btn btn-outline-light mx-3 mt-3 btn-sm"><i class="fas fa-moon me-2"></i> Mode Sombre</button>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-body p-3 rounded shadow-sm">
            <h4 class="m-0 text-dark">📚 Catalogue des Livres</h4>
            <div class="d-flex align-items-center">
                <span class="me-3 fw-bold text-secondary">Bonjour, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Lecteur'; ?></span>
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-user"></i></div>
            </div>
        </div>

        <!-- Search & Alerts -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form method="GET" class="d-flex shadow-sm">
                    <input type="text" name="search" class="form-control border-0 p-3" placeholder="Rechercher un livre par titre ou auteur..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category" class="form-select border-0 p-3 bg-body" style="max-width: 200px; border-left: 1px solid #eee;">
                        <option value="">Toutes catégories</option>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php if($category_filter == $cat['category']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Books Grid -->
        <div class="row g-4">
            <?php while($row = mysqli_fetch_assoc($books)): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card book-card shadow-sm h-100">
                    <div class="position-relative">
                        <img src="<?php echo !empty($row['cover_url']) ? $row['cover_url'] : 'https://placehold.co/300x450?text=Livre'; ?>" class="book-cover" alt="<?php echo $row['title']; ?>">
                        <span class="position-absolute top-0 end-0 badge m-2 <?php echo $row['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $row['stock'] > 0 ? 'Dispo' : 'Épuisé'; ?>
                        </span>
                        <!-- Bouton Wishlist -->
                        <?php $in_wishlist = in_array($row['id'], $wishlist_ids); ?>
                        <a href="toggle_wishlist.php?id=<?php echo $row['id']; ?>" class="position-absolute top-0 start-0 m-2 btn btn-sm rounded-circle shadow-sm <?php echo $in_wishlist ? 'btn-danger' : 'btn-light text-danger'; ?>" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </a>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <small class="text-muted text-uppercase mb-1"><?php echo $row['category']; ?></small>
                        <h5 class="card-title fw-bold text-truncate"><?php echo $row['title']; ?></h5>
                        <p class="card-text text-secondary small mb-3">Par <?php echo $row['author']; ?></p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-bold <?php echo $row['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    Stock: <?php echo $row['stock']; ?>
                                </small>
                                <div>
                                    <?php if($row['stock'] > 0): ?>
                                        <a href="dashboard.php?borrow=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="return confirm('Voulez-vous emprunter ce livre ?')">Emprunter</a>
                                    <?php else: ?>
                                        <button disabled class="btn btn-sm btn-secondary rounded-pill px-3">Indisponible</button>
                                    <?php endif; ?>
                                    <?php if(!empty($row['pdf_url'])): ?>
                                        <a href="<?php echo $row['pdf_url']; ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-1" title="Lire le PDF"><i class="fas fa-file-pdf"></i></a>
                                    <?php endif; ?>
                                    <a href="book_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 ms-1" title="Voir les avis"><i class="fas fa-info-circle"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <?php if(mysqli_num_rows($books) == 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>Aucun livre trouvé.</p>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark Mode Logic
        const toggleBtn = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        const icon = toggleBtn.querySelector('i');

        // Load saved theme
        if (localStorage.getItem('theme') === 'dark') {
            html.setAttribute('data-bs-theme', 'dark');
            icon.classList.replace('fa-moon', 'fa-sun');
            toggleBtn.innerHTML = '<i class="fas fa-sun me-2"></i> Mode Clair';
        }

        toggleBtn.addEventListener('click', () => {
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
            icon.classList.replace(isDark ? 'fa-sun' : 'fa-moon', isDark ? 'fa-moon' : 'fa-sun');
            toggleBtn.innerHTML = isDark ? '<i class="fas fa-moon me-2"></i> Mode Sombre' : '<i class="fas fa-sun me-2"></i> Mode Clair';
        });
    </script>
</body>
</html>
