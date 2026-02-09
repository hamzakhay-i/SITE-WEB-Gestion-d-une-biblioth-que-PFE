<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les livres de la wishlist
$query = "SELECT b.* FROM books b 
          JOIN wishlist w ON b.id = w.book_id 
          WHERE w.user_id = $user_id 
          ORDER BY w.created_at DESC";
$books = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Favoris - SmartLib</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; }
        .sidebar a:hover { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .book-cover { height: 150px; object-fit: cover; width: 100px; border-radius: 5px; }
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
        <a href="wishlist.php" style="background: #34495e; border-left: 4px solid #3498db;"><i class="fas fa-heart me-2"></i> Mes Favoris</a>
        <a href="profile.php"><i class="fas fa-user-cog me-2"></i> Mon Profil</a>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <h3 class="mb-4"><i class="fas fa-heart text-danger"></i> Mes Livres Favoris</h3>
            
            <?php if(mysqli_num_rows($books) == 0): ?>
                <div class="alert alert-info">Vous n'avez aucun livre en favoris. <a href="dashboard.php">Parcourir le catalogue</a></div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <table class="table align-middle">
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($books)): ?>
                                <tr>
                                    <td width="120"><img src="<?php echo !empty($row['cover_url']) ? $row['cover_url'] : 'https://placehold.co/300x450?text=Livre'; ?>" class="book-cover"></td>
                                    <td>
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($row['author']); ?></p>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['category']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="book_details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm mb-1">Voir Détails</a>
                                        <a href="toggle_wishlist.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm mb-1"><i class="fas fa-trash"></i> Retirer</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>