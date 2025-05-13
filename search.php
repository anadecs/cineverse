<?php
session_start();
require_once 'config/database.php';

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;
$sort = $_GET['sort'] ?? 'rating_desc';

$results = [];
$total_results = 0;

if ($query) {
    $search_conditions = [];
    $params = [];
    
    // Check if query matches a genre
    $genre_stmt = $pdo->prepare("SELECT genre_id FROM genres WHERE name LIKE ? LIMIT 1");
    $genre_stmt->execute(["%$query%"]);
    $genre = $genre_stmt->fetch(PDO::FETCH_ASSOC);
    $genre_match = $genre ? $genre['genre_id'] : null;

    switch ($type) {
        case 'movies':
            $search_conditions[] = "m.title LIKE ?";
            $params[] = "%$query%";
            if ($genre_match) {
                $search_conditions[] = "mg.genre_id = ?";
                $params[] = $genre_match;
            }
            break;
            
        case 'actors':
            $search_conditions[] = "a.name LIKE ?";
            $params[] = "%$query%";
            break;
            
        case 'directors':
            $search_conditions[] = "d.name LIKE ?";
            $params[] = "%$query%";
            break;
            
        default:
            $search_conditions[] = "(m.title LIKE ? OR a.name LIKE ? OR d.name LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%"];
            if ($genre_match) {
                $search_conditions[] = "mg.genre_id = ?";
                $params[] = $genre_match;
            }
    }
    
    $where_clause = implode(' OR ', $search_conditions);
    
    // Get total count
    $count_sql = "
        SELECT COUNT(DISTINCT m.movie_id) as total
        FROM movies m
        LEFT JOIN movie_actors ma ON m.movie_id = ma.movie_id
        LEFT JOIN actors a ON ma.actor_id = a.actor_id
        LEFT JOIN directors d ON m.director_id = d.director_id
    ";
    if ($genre_match) {
        $count_sql .= " LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id\n";
    }
    $count_sql .= "        WHERE $where_clause
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_results = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get results
    $sql = "
        SELECT DISTINCT m.*, d.name as director_name,
               GROUP_CONCAT(DISTINCT g.name) as genres,
               AVG(r.rating) as avg_rating,
               COUNT(DISTINCT r.review_id) as review_count
        FROM movies m
        LEFT JOIN directors d ON m.director_id = d.director_id
        LEFT JOIN movie_actors ma ON m.movie_id = ma.movie_id
        LEFT JOIN actors a ON ma.actor_id = a.actor_id
        LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        LEFT JOIN reviews r ON m.movie_id = r.movie_id
        WHERE $where_clause
        GROUP BY m.movie_id
    ";
    
    // Sorting logic
    switch ($sort) {
        case 'reviews_desc':
            $sql .= " ORDER BY review_count DESC ";
            break;
        case 'reviews_asc':
            $sql .= " ORDER BY review_count ASC ";
            break;
        case 'title_asc':
            $sql .= " ORDER BY m.title ASC ";
            break;
        case 'title_desc':
            $sql .= " ORDER BY m.title DESC ";
            break;
        case 'rating_asc':
            $sql .= " ORDER BY avg_rating ASC ";
            break;
        case 'rating_desc':
        default:
            $sql .= " ORDER BY avg_rating DESC ";
            break;
    }
    $sql .= " LIMIT $per_page OFFSET $offset\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_pages = ceil($total_results / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Letterboxd</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/images/logo-cineverse.svg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <img src="assets/images/logo-cineverse.svg" alt="CineVerse" class="logo">
                <span style="font-size:1.5rem;font-weight:700;margin-left:0.5rem;color:#fff;letter-spacing:1px;">CineVerse</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="movies.php">Movies</a>
                <a href="top-rated.php">Top Rated</a>
                <a href="about.php">About</a>
            </div>
            <div class="nav-search">
                <form action="search.php" method="GET">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search movies, actors, directors..." style="background:#232323;color:#e5e5e5;border:1px solid #444;border-radius:4px;padding:0.5rem;width:200px;">
                    <button type="submit" style="background:#b2070f;color:#fff  ;border:none;border-radius:4px;padding:0.5rem 1rem;cursor:pointer;">Search</button>
                </form>
            </div>
            <div class="nav-auth">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-secondary">Profile</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <div class="search-container">
            <div class="search-filters">
                <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
                <div class="filter-options">
                    <a href="?q=<?php echo urlencode($query); ?>&type=all" class="<?php echo $type === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?q=<?php echo urlencode($query); ?>&type=movies" class="<?php echo $type === 'movies' ? 'active' : ''; ?>">Movies</a>
                    <a href="?q=<?php echo urlencode($query); ?>&type=actors" class="<?php echo $type === 'actors' ? 'active' : ''; ?>">Actors</a>
                    <a href="?q=<?php echo urlencode($query); ?>&type=directors" class="<?php echo $type === 'directors' ? 'active' : ''; ?>">Directors</a>
                </div>
                <form method="get" style="margin-top:1rem;display:inline-block;">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <label for="sort" style="color:#fff;font-weight:500;margin-right:0.5rem;">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()" style="padding:0.4rem 1rem;border-radius:4px;background:#232323;color:#fff;border:1px solid #444;">
                        <option value="rating_desc" <?php if($sort=='rating_desc') echo 'selected'; ?>>Highest Rated</option>
                        <option value="rating_asc" <?php if($sort=='rating_asc') echo 'selected'; ?>>Lowest Rated</option>
                        <option value="reviews_desc" <?php if($sort=='reviews_desc') echo 'selected'; ?>>Most Reviews</option>
                        <option value="reviews_asc" <?php if($sort=='reviews_asc') echo 'selected'; ?>>Fewest Reviews</option>
                        <option value="title_asc" <?php if($sort=='title_asc') echo 'selected'; ?>>A-Z</option>
                        <option value="title_desc" <?php if($sort=='title_desc') echo 'selected'; ?>>Z-A</option>
                    </select>
                </form>
            </div>

            <?php if (empty($results)): ?>
                <div class="no-results" style="background:#232323;color:#e5e5e5;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <p>No results found for "<?php echo htmlspecialchars($query); ?>"</p>
                </div>
            <?php else: ?>
                <div class="movie-grid">
                    <?php foreach ($results as $movie): ?>
                        <div class="movie-card">
                            <a href="movie.php?id=<?php echo $movie['movie_id']; ?>">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                <div class="movie-info">
                                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <p class="director"><?php echo htmlspecialchars($movie['director_name']); ?></p>
                                    <div class="rating">
                                        <span class="inline-rating"><?php echo number_format($movie['avg_rating'], 1); ?> <span class="stars">★</span></span>
                                        <span class="review-count">(<?php echo $movie['review_count']; ?> review<?php echo $movie['review_count'] == 1 ? '' : 's'; ?>)</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $page - 1; ?>" class="page-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&page=<?php echo $page + 1; ?>" class="page-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Letterboxd</h3>
                <p>Your personal movie diary and social network for film lovers.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Letterboxd. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 
