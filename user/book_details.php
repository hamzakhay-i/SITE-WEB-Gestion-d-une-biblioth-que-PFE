<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

// Auto-fix: Création de la table reviews si elle n'existe pas
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if (mysqli_num_rows($check_table) == 0) {
    $sql = "CREATE TABLE reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$book_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$msg = "";

// Traitement de l'ajout d'un avis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5) {
        $sql = "INSERT INTO reviews (user_id, book_id, rating, comment) VALUES ($user_id, $book_id, $rating, '$comment')";
        if (mysqli_query($conn, $sql)) {
            $msg = "<div class='alert alert-success'>Merci pour votre avis !</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Erreur : " . mysqli_error($conn) . "</div>";
        }
    }
}

// Récupération des infos du livre
$book_query = mysqli_query($conn, "SELECT * FROM books WHERE id = $book_id");
$book = mysqli_fetch_assoc($book_query);

if (!$book) {
    die("Livre introuvable.");
}

// Récupération des avis
$reviews_query = mysqli_query($conn, "SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = $book_id ORDER BY r.created_at DESC");

// Calcul de la moyenne
$avg_query = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE book_id = $book_id");
$stats = mysqli_fetch_assoc($avg_query);
$avg_rating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$total_reviews = $stats['count'];

// Fonction pour afficher les étoiles
function renderStars($rating) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $stars;
}

// Vérifier si dans wishlist
$in_wishlist = false;
$check_wish = mysqli_query($conn, "SHOW TABLES LIKE 'wishlist'");
if (mysqli_num_rows($check_wish) > 0) {
    $w_check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id=$user_id AND book_id=$book_id");
    if(mysqli_num_rows($w_check) > 0) $in_wishlist = true;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['title']); ?> - Détails</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        [data-bs-theme="dark"] body { background-color: #212529; color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; }
        .sidebar a:hover { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .book-cover-lg { max-width: 100%; height: auto; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .rating-select { direction: rtl; unicode-bidi: bidi-override; font-size: 2rem; display: inline-block; }
        .rating-select input { display: none; }
        .rating-select label { color: #ddd; cursor: pointer; transition: 0.2s; }
        .rating-select label:hover, .rating-select label:hover ~ label, .rating-select input:checked ~ label { color: #ffc107; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="p-4 text-center border-bottom border-secondary">
            <h3 class="fw-bold"><i class="fas fa-book-reader"></i> SmartLib</h3>
        </div>
        <a href="dashboard.php"><i class="fas fa-book-open me-2"></i> Catalogue</a>
        <a href="my_books.php"><i class="fas fa-bookmark me-2"></i> Mes Emprunts</a>
        <a href="wishlist.php"><i class="fas fa-heart me-2"></i> Mes Favoris</a>
        <a href="profile.php"><i class="fas fa-user-cog me-2"></i> Mon Profil</a>
        <button id="darkModeToggle" class="btn btn-outline-light mx-3 mt-3 btn-sm"><i class="fas fa-moon me-2"></i> Mode Sombre</button>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <a href="dashboard.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left"></i> Retour au catalogue</a>
            
            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?php echo !empty($book['cover_url']) ? $book['cover_url'] : 'https://placehold.co/300x450?text=Livre'; ?>" class="book-cover-lg mb-3" alt="Cover">
                        </div>
                        <div class="col-md-9">
                            <h2 class="fw-bold"><?php echo htmlspecialchars($book['title']); ?></h2>
                            <h5 class="text-muted mb-3">Par <?php echo htmlspecialchars($book['author']); ?></h5>
                            <span class="badge bg-info text-dark mb-2"><?php echo htmlspecialchars($book['category']); ?></span>
                            
                            <div class="mb-3">
                                <span class="fs-4 me-2"><?php echo $avg_rating; ?>/5</span>
                                <?php echo renderStars($avg_rating); ?>
                                <span class="text-muted small ms-2">(<?php echo $total_reviews; ?> avis)</span>
                            </div>

                            <p class="lead">
                                Stock disponible : <span class="<?php echo $book['stock'] > 0 ? 'text-success' : 'text-danger'; ?> fw-bold"><?php echo $book['stock']; ?></span>
                            </p>

                            <div class="d-flex gap-2">
                                <?php if($book['stock'] > 0): ?>
                                    <a href="dashboard.php?borrow=<?php echo $book['id']; ?>" class="btn btn-primary btn-lg" onclick="return confirm('Emprunter ce livre ?')">Emprunter</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg" disabled>Indisponible</button>
                                <?php endif; ?>
                                
                                <?php if(!empty($book['pdf_url'])): ?>
                                    <a href="<?php echo $book['pdf_url']; ?>" target="_blank" class="btn btn-danger btn-lg"><i class="fas fa-file-pdf me-2"></i> Lire le PDF</a>
                                <?php endif; ?>

                                <a href="toggle_wishlist.php?id=<?php echo $book['id']; ?>" class="btn btn-lg <?php echo $in_wishlist ? 'btn-outline-danger active' : 'btn-outline-secondary'; ?>">
                                    <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $in_wishlist ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Avis -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow border-0">
                        <div class="card-header bg-body fw-bold">Laisser un avis</div>
                        <div class="card-body">
                            <?php echo $msg; ?>
                            <form method="POST">
                                <div class="mb-3 text-center">
                                    <label class="form-label d-block">Votre note</label>
                                    <div class="rating-select">
                                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 étoiles"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 étoiles"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 étoiles"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 étoiles"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 étoile"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Votre commentaire</label>
                                    <textarea name="comment" class="form-control" rows="3" placeholder="Qu'avez-vous pensé de ce livre ?" required></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-success w-100">Publier l'avis</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h4 class="mb-3">Derniers avis</h4>
                    <?php if(mysqli_num_rows($reviews_query) > 0): ?>
                        <?php while($review = mysqli_fetch_assoc($reviews_query)): ?>
                            <div class="card shadow-sm border-0 mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($review['name']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="mb-2"><?php echo renderStars($review['rating']); ?></div>
                                    <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Aucun avis pour le moment. Soyez le premier !</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <script>
        const toggleBtn = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        const icon = toggleBtn.querySelector('i');
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