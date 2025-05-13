<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Fetch top 5 movies by review count
$stmt = $pdo->query("SELECT m.title, COUNT(r.review_id) as review_count FROM movies m LEFT JOIN reviews r ON m.movie_id = r.movie_id GROUP BY m.movie_id ORDER BY review_count DESC, m.title ASC LIMIT 5");
$top_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
$titles = array_map(function($m){return $m['title'];}, $top_movies);
$counts = array_map(function($m){return (int)$m['review_count'];}, $top_movies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Statistics - CineVerse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/images/logo-cineverse.svg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .navbar { background: #181818; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 1rem; }
        .nav-logo { display: flex; align-items: center; text-decoration: none; }
        .logo { height: 40px; }
        .nav-links { display: flex; gap: 1.5rem; }
        .nav-links a { color: #fff; text-decoration: none; font-weight: 500; font-size: 1.1rem; padding: 0.5rem 1.2rem; border-radius: 4px; transition: background 0.2s; }
        .nav-links a.active, .nav-links a:hover {
            background: #232323;
            color: #fff;
            text-decoration: underline;
        }
        .nav-auth { display: flex; gap: 0.5rem; }
        .admin-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .stats-section { background: #232323; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; }
        .stats-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; }
    </style>
</head>
<body>
<nav class="navbar">
        <div class="nav-container">
            <a href="admin.php" class="nav-logo">
                <img src="../assets/images/logo-cineverse.svg" alt="CineVerse" class="logo">
                <span style="font-size:1.5rem;font-weight:700;margin-left:0.5rem;color:#fff;letter-spacing:1px;">CineVerse</span>
            </a>
            <div class="nav-links">
                <a href="admin.php" >Movies</a>
                <a href="admin-stats.php"class="active">Statistics</a>
                <a href="admin-users.php">Users</a>
            </div>
            <div class="nav-auth">
                <a href="../logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </nav>
    <main>
        <div class="admin-container">
            <div class="stats-section">
                <div class="stats-title">Top 5 Reviewed Movies</div>
                <canvas id="topMoviesChart" height="120"></canvas>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About CineVerse</h3>
                <p>Your personal movie diary and social network for film lovers.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 CineVerse. All rights reserved.</p>
        </div>
    </footer>
    <script>
        const ctx = document.getElementById('topMoviesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($titles); ?>,
                datasets: [{
                    label: 'Number of Reviews',
                    data: <?php echo json_encode($counts); ?>,
                    backgroundColor: '#f5c518',
                    borderRadius: 6,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#fff', font: { weight: 600 } } },
                    y: { grid: { color: '#444' }, ticks: { color: '#fff' }, beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html> 
