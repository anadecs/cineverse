<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->query("SELECT user_id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - CineVerse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-nav-link { color: #fff; padding: 0.5rem 1.2rem; border-radius: 4px; text-decoration: none; font-weight: 500; margin-right: 0.5rem; background: transparent; transition: background 0.2s; }
        .admin-nav-link.active, .admin-nav-link:hover { background: #f5c518; color: #000; }
        .admin-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .admin-table { width: 100%; border-collapse: collapse; background: #232323; border-radius: 8px; overflow: hidden; }
        .admin-table th, .admin-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #444; }
        .admin-table th { background: #1a1a1a; font-weight: 600; }
        .admin-table tr:last-child td { border-bottom: none; }
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
                <a href="admin-stats.php">Statistics</a>
                <a href="admin-users.php"class="active">Users</a>
            </div>
            <div class="nav-auth">
                <a href="../logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </nav>
    <main>
        <div class="admin-container">
            <h1>All Users</h1>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Admin?</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
</body>
</html> 