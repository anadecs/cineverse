<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Handle movie deletion
if (isset($_POST['delete_movie'])) {
    $movie_id = (int)$_POST['movie_id'];
    // Delete related rows first
    $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = ?")->execute([$movie_id]);
    $pdo->prepare("DELETE FROM reviews WHERE movie_id = ?")->execute([$movie_id]);
    $pdo->prepare("DELETE FROM movie_actors WHERE movie_id = ?")->execute([$movie_id]);
    // Now delete the movie
    $stmt = $pdo->prepare("DELETE FROM movies WHERE movie_id = ?");
    $stmt->execute([$movie_id]);
    header('Location: admin.php?success=deleted');
    exit;
}

// Pagination and sorting
$sort = $_GET['sort'] ?? 'title_asc';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$order_by = 'm.title ASC';
switch ($sort) {
    case 'rating_desc': $order_by = 'avg_rating DESC'; break;
    case 'rating_asc': $order_by = 'avg_rating ASC'; break;
    case 'latest': $order_by = 'm.movie_id DESC'; break;
    case 'oldest': $order_by = 'm.movie_id ASC'; break;
    case 'reviews_desc': $order_by = 'review_count DESC'; break;
    case 'reviews_asc': $order_by = 'review_count ASC'; break;
    case 'title_desc': $order_by = 'm.title DESC'; break;
    case 'title_asc': default: $order_by = 'm.title ASC'; break;
}

// Get total count
$total_stmt = $pdo->query("SELECT COUNT(*) FROM movies");
$total_movies = $total_stmt->fetchColumn();
$total_pages = ceil($total_movies / $per_page);

// Get paginated, sorted movies
$stmt = $pdo->query("
    SELECT m.*, d.name as director_name,
           (SELECT AVG(r.rating) FROM reviews r WHERE r.movie_id = m.movie_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.movie_id) as review_count
    FROM movies m
    LEFT JOIN directors d ON m.director_id = d.director_id
    ORDER BY $order_by
    LIMIT $per_page OFFSET $offset
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CineVerse</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/logo-cineverse.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #232323;
            border-radius: 8px;
            overflow: hidden;
        }
        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        .admin-table th {
            background: #1a1a1a;
            font-weight: 600;
        }
        .admin-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-edit,
        .btn-delete {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-edit {
            background: #f5c518;
            color: #000;
        }
        .btn-delete {
            background: #b2070f;
            color: #fff;
        }
        .btn-add {
            background: #f5c518;
            color: #000;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }
        .admin-filters {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .admin-filters select {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: #232323;
            color: #fff;
            border: 1px solid #444;
        }
        .pagination {
            margin-top: 1.5rem;
            text-align: center;
        }
        .pagination .page-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 6px;
            background: #232323;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .pagination .page-link.active, .pagination .page-link:hover {
            background: #b2070f;
            color: #fff;
        }
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
                <a href="admin.php" class="active">Movies</a>
                <a href="admin-stats.php">Statistics</a>
                <a href="admin-users.php">Users</a>
            </div>
            <div class="nav-auth">
                <a href="../logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <a href="add-movie.php" class="btn-add">Add New Movie</a>
            </div>
            <form method="GET" class="admin-filters">
                <label for="sort" style="color:#fff;font-weight:500;">Sort by:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="title_asc" <?php if($sort=='title_asc') echo 'selected'; ?>>A-Z</option>
                    <option value="title_desc" <?php if($sort=='title_desc') echo 'selected'; ?>>Z-A</option>
                    <option value="rating_desc" <?php if($sort=='rating_desc') echo 'selected'; ?>>Highest Rated</option>
                    <option value="rating_asc" <?php if($sort=='rating_asc') echo 'selected'; ?>>Lowest Rated</option>
                    <option value="latest" <?php if($sort=='latest') echo 'selected'; ?>>Latest Added</option>
                    <option value="oldest" <?php if($sort=='oldest') echo 'selected'; ?>>Oldest Added</option>
                    <option value="reviews_desc" <?php if($sort=='reviews_desc') echo 'selected'; ?>>Most Reviews</option>
                    <option value="reviews_asc" <?php if($sort=='reviews_asc') echo 'selected'; ?>>Least Reviews</option>
                </select>
            </form>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Director</th>
                        <th>Year</th>
                        <th>Rating</th>
                        <th>Reviews</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                            <td><?php echo htmlspecialchars($movie['director_name']); ?></td>
                            <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            <td><?php echo number_format($movie['avg_rating'], 1); ?> <span class="stars">★</span></td>
                            <td><?php echo $movie['review_count']; ?></td>
                            <td class="admin-actions">
                                <a href="edit-movie.php?id=<?php echo $movie['movie_id']; ?>" class="btn-edit">Edit</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this movie?');">
                                    <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                                    <button type="submit" name="delete_movie" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="admin.php?page=<?php echo $i; ?>&sort=<?php echo urlencode($sort); ?>" class="page-link<?php if ($i == $page) echo ' active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
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
