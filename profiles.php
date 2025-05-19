<?php
require_once 'config/database.php';

// Get user_id from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<h2>User not found.</h2>';
    exit;
}
$profile_user_id = (int)$_GET['id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$profile_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<h2>User not found.</h2>';
    exit;
}
$avatar = $user['profile_picture'] ?: 'assets/images/profile.avif';

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT movie_id) as movies_watched, COUNT(*) as review_count FROM reviews WHERE user_id = ?");
$stmt->execute([$profile_user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
$movies_watched = $stats['movies_watched'] ?? 0;
$review_count = $stats['review_count'] ?? 0;

// Get last 4 movies watched (by review date)
$stmt = $pdo->prepare("
    SELECT m.movie_id, m.title, m.poster_url, MAX(r.created_at) as last_watched
    FROM reviews r
    JOIN movies m ON r.movie_id = m.movie_id
    WHERE r.user_id = ?
    GROUP BY m.movie_id, m.title, m.poster_url
    ORDER BY last_watched DESC
    LIMIT 4
");
$stmt->execute([$profile_user_id]);
$last_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination for reviews
$reviews_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $reviews_per_page;
// Get total review count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$stmt->execute([$profile_user_id]);
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $reviews_per_page);
// Get paginated reviews
$stmt = $pdo->prepare("
    SELECT r.*, m.title as movie_title, m.poster_url, m.movie_id
    FROM reviews r
    JOIN movies m ON r.movie_id = m.movie_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT $reviews_per_page OFFSET $offset
");
$stmt->execute([$profile_user_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - CineVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .profile-movies-list {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .profile-movie-card {
            background: #232323;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            width: 140px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .profile-movie-card:hover {
            transform: translateY(-8px) scale(1.04);
            box-shadow: 0 8px 32px rgba(178,7,15,0.35);
        }
        .review-card {
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .review-card:hover {
            box-shadow: 0 8px 32px rgba(178,7,15,0.35) !important;
            transform: translateY(-6px) scale(1.01);
        }
    </style>
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
                <input type="text" name="q" placeholder="Search movies, actors, directors..." style="background:#232323;color:#e5e5e5;border:1px solid #444;border-radius:4px;padding:0.5rem;width:200px;">
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
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture" class="profile-avatar">
                <h1><?php echo htmlspecialchars($user['username']); ?>'s Profile</h1>
                <p class="profile-email">(<?php echo htmlspecialchars($user['email']); ?>)</p>
            </div>
            <div class="profile-stats" style="display:flex;gap:2rem;margin:2rem 0 2.5rem 0;justify-content:center;">
                <div style="background:#232323;padding:1.5rem 2.5rem;border-radius:12px;text-align:center;min-width:160px;">
                    <div style="font-size:2.2rem;font-weight:700;color:#f5c518;"><?php echo $movies_watched; ?></div>
                    <div style="color:#bbb;font-size:1.1rem;">Movies Watched</div>
                </div>
                <div style="background:#232323;padding:1.5rem 2.5rem;border-radius:12px;text-align:center;min-width:160px;">
                    <div style="font-size:2.2rem;font-weight:700;color:#f5c518;"><?php echo $review_count; ?></div>
                    <div style="color:#bbb;font-size:1.1rem;">Reviews</div>
                </div>
            </div>
            <div class="profile-movies">
                <h2>Last Movies Watched</h2><br>
                <?php if (empty($last_movies)): ?>
                    <p>No movies watched yet.</p>
                <?php else: ?>
                    
                    <div class="profile-movies-list">
                        <?php foreach ($last_movies as $movie): ?>
                            <div class="profile-movie-card">
                                <a href="movie.php?id=<?php echo $movie['movie_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" style="width:100px;height:150px;object-fit:cover;border-radius:8px;">
                                    <div style="margin-top:0.5rem;font-weight:600;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-reviews">
                <h2><br><?php echo htmlspecialchars($user['username']); ?>'s Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card" style="background:#232323;color:#e5e5e5;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.25);padding:1.5rem 2rem;margin-bottom:1.5rem;">
                            <div class="review-movie">
                                <img src="<?php echo htmlspecialchars($review['poster_url']); ?>" alt="<?php echo htmlspecialchars($review['movie_title']); ?>">
                                <div class="review-movie-info">
                                    <h3>
                                        <a href="movie.php?id=<?php echo $review['movie_id']; ?>" style="color:#fff;text-decoration:none;font-weight:600;">
                                            <?php echo htmlspecialchars($review['movie_title']); ?>
                                        </a>
                                    </h3>
                                    <div class="inline-rating" style="margin-top:0.5rem;">
                                        <?php echo number_format($review['rating'], 1); ?>/5 <span class="stars">★</span>
                                    </div>
                                    <p class="review-date" style="color:#bbb;font-size:0.95rem;margin-bottom:0.5rem;">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="review-content">
                                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="text-align:center;margin-top:1.5rem;">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="profiles.php?id=<?php echo $profile_user_id; ?>&page=<?php echo $i; ?>" class="page-link" style="display:inline-block;padding:0.5rem 1rem;margin:0 0.25rem;border-radius:6px;background:<?php echo $i == $page ? '#b2070f' : '#232323'; ?>;color:#fff;text-decoration:none;font-weight:600;<?php echo $i == $page ? 'box-shadow:0 2px 8px rgba(178,7,15,0.25);' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
</body>
</html> 