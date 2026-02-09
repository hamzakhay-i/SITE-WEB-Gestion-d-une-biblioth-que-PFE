<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 1. Données pour le Graphique : Emprunts par mois (6 derniers mois)
$months = [];
$borrows_data = [];
for ($i = 5; $i >= 0; $i--) {
    $date_search = date("Y-m", strtotime("-$i months"));
    $month_label = date("M Y", strtotime("-$i months")); // Ex: Feb 2024
    
    $query = "SELECT COUNT(*) as count FROM borrowings WHERE DATE_FORMAT(borrow_date, '%Y-%m') = '$date_search'";
    $res = mysqli_fetch_assoc(mysqli_query($conn, $query));
    
    $months[] = $month_label;
    $borrows_data[] = $res['count'];
}

// 2. Données pour le Graphique : Répartition par Catégorie
$cat_labels = [];
$cat_data = [];
$cat_query = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM books GROUP BY category");
while($row = mysqli_fetch_assoc($cat_query)) {
    $cat_labels[] = $row['category'];
    $cat_data[] = $row['count'];
}

// 3. Top 5 Livres les plus empruntés
$top_books = mysqli_query($conn, "SELECT b.title, COUNT(br.id) as count FROM borrowings br JOIN books b ON br.book_id = b.id GROUP BY br.book_id ORDER BY count DESC LIMIT 5");

// 4. Top 5 Lecteurs
$top_users = mysqli_query($conn, "SELECT u.name, COUNT(br.id) as count FROM borrowings br JOIN users u ON br.user_id = u.id GROUP BY br.user_id ORDER BY count DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques & Rapports - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f3f4f6; }
        [data-bs-theme="dark"] body { background-color: #212529; color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; position: fixed; width: 250px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; padding-left: 25px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card-stat { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
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
        <a href="borrowings.php"><i class="fas fa-hand-holding me-2"></i> Gestion Emprunts</a>
        <a href="stats.php" class="active"><i class="fas fa-chart-line me-2"></i> Statistiques</a>
        <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> Voir le site</a>
        <button id="darkModeToggle" class="btn btn-outline-light mx-3 mt-3 btn-sm"><i class="fas fa-moon me-2"></i> Mode Sombre</button>
        <div class="mt-auto p-3">
            <a href="../logout.php" class="btn btn-danger w-100 text-white"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 bg-body p-3 rounded shadow-sm">
            <h4 class="m-0 text-dark">📊 Statistiques & Rapports</h4>
            <a href="export.php" class="btn btn-success"><i class="fas fa-file-csv me-2"></i> Exporter les Emprunts (CSV)</a>
        </div>

        <div class="row g-4 mb-4">
            <!-- Graphique Emprunts -->
            <div class="col-md-8">
                <div class="card card-stat h-100">
                    <div class="card-header bg-body fw-bold">Évolution des Emprunts (6 derniers mois)</div>
                    <div class="card-body">
                        <canvas id="borrowChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Graphique Catégories -->
            <div class="col-md-4">
                <div class="card card-stat h-100">
                    <div class="card-header bg-body fw-bold">Répartition par Catégorie</div>
                    <div class="card-body">
                        <canvas id="catChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top Livres -->
            <div class="col-md-6">
                <div class="card card-stat">
                    <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-crown me-2"></i> Top 5 Livres</div>
                    <ul class="list-group list-group-flush">
                        <?php while($row = mysqli_fetch_assoc($top_books)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-body">
                                <?php echo htmlspecialchars($row['title']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $row['count']; ?> emprunts</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <!-- Top Lecteurs -->
            <div class="col-md-6">
                <div class="card card-stat">
                    <div class="card-header bg-success text-white fw-bold"><i class="fas fa-users me-2"></i> Top 5 Lecteurs</div>
                    <ul class="list-group list-group-flush">
                        <?php while($row = mysqli_fetch_assoc($top_users)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-body">
                                <?php echo htmlspecialchars($row['name']); ?>
                                <span class="badge bg-success rounded-pill"><?php echo $row['count']; ?> emprunts</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Graphique Ligne (Emprunts)
        new Chart(document.getElementById('borrowChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{ label: 'Nombre d\'emprunts', data: <?php echo json_encode($borrows_data); ?>, borderColor: '#3498db', tension: 0.3, fill: true, backgroundColor: 'rgba(52, 152, 219, 0.1)' }]
            }
        });

        // Graphique Donut (Catégories)
        new Chart(document.getElementById('catChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: ['#e74c3c', '#3498db', '#f1c40f', '#2ecc71', '#9b59b6', '#34495e'] }]
            }
        });

        // Dark Mode Logic (Simple)
        const toggleBtn = document.getElementById('darkModeToggle');
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
        toggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            document.documentElement.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });
    </script>
</body>
</html>