<?php

ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineVerse - Movie Reviews</title>
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
        <section class="hero">
            <h1>Discover and Review Movies</h1>
            <p>Join our community of movie enthusiasts</p>
        </section>

        <section class="featured-movies">
            <h2>Featured Movies</h2>
            <div class="movie-grid">
                <?php
                $stmt = $pdo->query("
                    SELECT m.*, d.name as director_name,
                           COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.movie_id = m.movie_id), 0) as avg_rating_5,
                           COALESCE((SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.movie_id), 0) as review_count
                    FROM movies m
                    LEFT JOIN directors d ON m.director_id = d.director_id
                    ORDER BY m.movie_id DESC
                    LIMIT 5
                ");
                while ($movie = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
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
                <?php endwhile; ?>
            </div>
        </section>

        <section class="latest-reviews">
            <h2>Latest Reviews</h2>
            <div class="review-list">
                <?php
                $stmt = $pdo->query("
                    SELECT r.*, m.title as movie_title, m.poster_url, u.username, u.profile_picture, m.movie_id
                    FROM reviews r
                    JOIN movies m ON r.movie_id = m.movie_id
                    JOIN users u ON r.user_id = u.user_id
                    ORDER BY r.created_at DESC
                    LIMIT 5
                ");
                while ($review = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $avatar = $review['profile_picture'] ?: 'assets/images/profile.avif';
                ?>
                <div class="review-card" style="display: flex; gap: 1rem; background: #232323; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; flex: 1;">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>'s avatar" class="review-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div class="review-card-content" style="flex: 1;">
                            <h3 style="margin: 0 0 0.5rem 0;"><a href="movie.php?id=<?php echo $review['movie_id']; ?>" style="color: #fff; text-decoration: none;"><?php echo htmlspecialchars($review['movie_title']); ?></a></h3>
                            <div class="review-meta" style="margin-bottom: 0.5rem;">
                                <span class="reviewer" style="color: #888;">by <?php echo htmlspecialchars($review['username']); ?></span>
                                <span class="inline-rating" style="color: #f5c518; margin-left: 1rem;"><?php echo number_format($review['rating'], 1); ?> <span class="stars">★</span></span>
                            </div>
                            <p class="review-text" style="margin: 0; color: #e5e5e5;"><?php echo htmlspecialchars(substr($review['comment'], 0, 150)) . '...'; ?></p>
                        </div>
                    </div>
                    <a href="movie.php?id=<?php echo $review['movie_id']; ?>" style="flex-shrink: 0; margin-left: 24px;">
                        <img src="<?php echo htmlspecialchars($review['poster_url']); ?>" alt="<?php echo htmlspecialchars($review['movie_title']); ?>" style="width: 100px; height: 150px; object-fit: cover; border-radius: 4px;">
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
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
