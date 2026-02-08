<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Return Action
if (isset($_GET['return_id']) && isset($_GET['book_id'])) {
    $b_id = $_GET['return_id'];
    $book_id = $_GET['book_id'];
    $date = date('Y-m-d');

    // Update borrowing status
    mysqli_query($conn, "UPDATE borrowings SET status='returned', return_date='$date' WHERE id=$b_id");
    // Increase stock
    mysqli_query($conn, "UPDATE books SET stock = stock + 1 WHERE id=$book_id");
    
    header("Location: borrowings.php");
}

// Fetch Borrowings with User and Book details
 $query = "SELECT b.*, u.name as user_name, bk.title as book_title 
          FROM borrowings b 
          JOIN users u ON b.user_id = u.id 
          JOIN books bk ON b.book_id = bk.id 
          ORDER BY b.status ASC, b.due_date ASC";
 $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emprunts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
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
            <small class="text-white-50">Espace Administrateur</small>
        </div>
        <a href="dashboard.php"><i class="fas fa-th-large me-2"></i> Tableau de bord</a>
        <a href="borrowings.php" class="active"><i class="fas fa-hand-holding me-2"></i> Gestion Emprunts</a>
        <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> Voir le site</a>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <h4 class="m-0 text-dark">📖 Historique des Emprunts</h4>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Livre</th>
                                <th>Date Emprunt</th>
                                <th>Date Retour Prévue</th>
                                <th>Statut</th>
                                <th>Pénalité</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $penalty = 0;
                                if($row['status'] == 'borrowed' && date('Y-m-d') > $row['due_date']) {
                                    $diff = strtotime(date('Y-m-d')) - strtotime($row['due_date']);
                                    $days = floor($diff / (60*60*24));
                                    $penalty = $days * 1; 
                                }
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['user_name']; ?></td>
                                <td><?php echo $row['book_title']; ?></td>
                                <td><?php echo $row['borrow_date']; ?></td>
                                <td class="<?php echo ($penalty > 0) ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo $row['due_date']; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'borrowed' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $penalty > 0 ? "<span class='text-danger fw-bold'>$".$penalty."</span>" : "-"; ?></td>
                                <td>
                                    <?php if($row['status'] == 'borrowed'): ?>
                                        <a href="borrowings.php?return_id=<?php echo $row['id']; ?>&book_id=<?php echo $row['book_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Retourner</a>
                                    <?php else: ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Terminé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
