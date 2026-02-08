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

// Search Logic
 $search = "";
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = "SELECT * FROM books WHERE title LIKE '%$search%' OR author LIKE '%$search%'";
} else {
    $query = "SELECT * FROM books";
}
 $books = mysqli_query($conn, $query);
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
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
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
                                <?php if($row['stock'] > 0): ?>
                                    <a href="dashboard.php?borrow=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="return confirm('Voulez-vous emprunter ce livre ?')">Emprunter</a>
                                <?php else: ?>
                                    <button disabled class="btn btn-sm btn-secondary rounded-pill px-3">Indisponible</button>
                                <?php endif; ?>
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
</body>
</html>
