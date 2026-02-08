<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

 $user_id = $_SESSION['user_id'];

// Return Logic (User initiates return)
if (isset($_GET['return'])) {
    $b_id = (int)$_GET['return'];
    $book_id = (int)$_GET['book_id'];
    $date = date('Y-m-d');

    mysqli_query($conn, "UPDATE borrowings SET status='returned', return_date='$date' WHERE id=$b_id AND user_id=$user_id");
    mysqli_query($conn, "UPDATE books SET stock = stock + 1 WHERE id=$book_id");
    header("Location: my_books.php");
}

 $query = "SELECT b.*, bk.title, bk.author 
          FROM borrowings b 
          JOIN books bk ON b.book_id = bk.id 
          WHERE b.user_id = $user_id 
          ORDER BY b.id DESC";
 $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Emprunts - SmartLib</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; z-index: 100; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
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
        <a href="dashboard.php"><i class="fas fa-book-open me-2"></i> Catalogue</a>
        <a href="my_books.php" class="active"><i class="fas fa-bookmark me-2"></i> Mes Emprunts</a>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <h4 class="m-0 text-dark">📖 Historique des Emprunts</h4>
            <div class="d-flex align-items-center">
                <span class="me-3 fw-bold text-secondary">Bonjour, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Lecteur'; ?></span>
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-user"></i></div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Livre</th>
                                <th>Date Emprunt</th>
                                <th>Date Retour Prévue</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['title']; ?></td>
                                <td><?php echo $row['borrow_date']; ?></td>
                                <td><?php echo $row['due_date']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'borrowed' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'borrowed'): ?>
                                        <a href="my_books.php?return=<?php echo $row['id']; ?>&book_id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-outline-primary" onclick="return confirm('Retourner ce livre ?')"><i class="fas fa-undo"></i> Retourner</a>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fas fa-check"></i> Retourné le <?php echo $row['return_date']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                        <p>Vous n'avez aucun emprunt pour le moment.</p>
                        <a href="dashboard.php" class="btn btn-primary">Emprunter un livre</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
