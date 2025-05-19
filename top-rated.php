<?php
session_start();
require_once 'config/database.php';

// Get all top rated movies
$sql = "
    SELECT m.*, d.name as director_name,
           (SELECT AVG(r.rating)  FROM reviews r WHERE r.movie_id = m.movie_id) as avg_rating_5,
           (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.movie_id) as review_count
    FROM movies m
    LEFT JOIN directors d ON m.director_id = d.director_id
    HAVING avg_rating_5 > 0
    ORDER BY avg_rating_5 DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Rated Movies - CineVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <input type="text" name="q" placeholder="Search movies, actors, directors..."style="background:#232323;color:#e5e5e5;border:1px solid #444;border-radius:4px;padding:0.5rem;width:200px;">
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
        <section class="page-header" style="background-image: url('assets/images/top-rated-bg.jpg');">
            <h1>Top Rated Movies</h1>
            <p>Discover the highest rated movies in our collection</p>
        </section>

        <div class="movie-grid">
            <?php if (empty($movies)): ?>
                <p>No movies found.</p>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <a href="movie.php?id=<?php echo $movie['movie_id']; ?>" class="movie-card">
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="director"><?php echo htmlspecialchars($movie['director_name']); ?></p>
                            <div class="rating">
                                <span class="inline-rating"><?php echo number_format($movie['avg_rating_5'], 1); ?> <span class="stars">★</span></span>
                                <span class="review-count">(<?php echo $movie['review_count']; ?> review<?php echo $movie['review_count'] == 1 ? '' : 's'; ?>)</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About CineVerse</h3>
                <p>Your personal movie diary and social network for film lovers.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="movies.php">Browse Movies</a></li>
                    <li><a href="top-rated.php">Top Rated</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 CineVerse. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 
