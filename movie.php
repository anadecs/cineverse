<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$movie_id = (int)$_GET['id'];

// Get movie details
$stmt = $pdo->prepare("
    SELECT m.*, d.name as director_name,
           GROUP_CONCAT(DISTINCT g.name) as genres,
           GROUP_CONCAT(DISTINCT a.name) as actors,
           (SELECT AVG(r.rating) FROM reviews r WHERE r.movie_id = m.movie_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.movie_id) as review_count
    FROM movies m
    LEFT JOIN directors d ON m.director_id = d.director_id
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    LEFT JOIN movie_actors ma ON m.movie_id = ma.movie_id
    LEFT JOIN actors a ON ma.actor_id = a.actor_id
    WHERE m.movie_id = :movie_id
    GROUP BY m.movie_id
");
$stmt->execute(['movie_id' => $movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    header('Location: index.php');
    exit;
}
// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.profile_picture
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$movie_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle review submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $rating = (float)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (movie_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([$movie_id, $_SESSION['user_id'], $rating, $comment]);
            $success = 'Review submitted successfully!';
            header("Location: movie.php?id=$movie_id");
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}

// Fetch director image
$director_image_url = 'assets/images/profile.avif';
if (!empty($movie['director_name'])) {
    $stmt = $pdo->prepare("SELECT image_url FROM directors WHERE name = ? LIMIT 1");
    $stmt->execute([$movie['director_name']]);
    $director_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($director_row && !empty($director_row['image_url'])) {
        $director_image_url = $director_row['image_url'];
    }
}
// Fetch actors (name, image_url)
$actors = [];
$stmt = $pdo->prepare("SELECT a.name, a.image_url FROM movie_actors ma JOIN actors a ON ma.actor_id = a.actor_id WHERE ma.movie_id = ?");
$stmt->execute([$movie_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $actors[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineVerse</title>
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
        <div class="movie-details">
            <div class="movie-header">
                <div class="movie-poster">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                </div>
                <div class="movie-info">
                    <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <p class="movie-year"><?php echo htmlspecialchars($movie['release_year']); ?></p>
                    
                    <div class="movie-rating">
                        <span class="rating-value"><?php echo number_format($movie['avg_rating'], 1); ?> <span style='color:#f5c518;font-size:0.95em;'>★</span></span>
                        <span class="rating-count">(<?php echo $movie['review_count']; ?> review<?php echo $movie['review_count'] == 1 ? '' : 's'; ?>)</span>
                    </div>
                    
                    <div class="movie-meta">
                        <p><strong>Genres:</strong> <?php echo str_replace(',', ', ', htmlspecialchars($movie['genres'])); ?></p>
                    </div>
                    
                    <div class="movie-description">
                        <h3>Overview</h3>
                        <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                    </div>
                </div>
            </div>
            <!-- Cast & Crew Heading -->
            <h2 style="margin-bottom: 1rem; margin-top: 2rem; font-size: 1.4rem; font-weight: 700; color: #fff; letter-spacing: 0.5px;">Cast & Crew</h2>
            <!-- New: Director and Cast Cards -->
            <div class="person-cards" style="display:flex;gap:1.5rem;margin-top:1.5rem;margin-bottom:3.5rem;flex-wrap:wrap;align-items:flex-start;">
                <!-- Director Card -->
                <a href="search.php?q=<?php echo urlencode($movie['director_name']); ?>" class="person-card" style="display:flex;flex-direction:column;align-items:center;background:#232323;border-radius:10px;padding:0.75rem 1.25rem;text-align:center;text-decoration:none;color:#fff;min-width:110px;">
                    <img src="<?php echo htmlspecialchars($director_image_url); ?>" alt="<?php echo htmlspecialchars($movie['director_name']); ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:0.5rem;">
                    <span style="font-weight:600;">Director</span>
                    <span style="margin-top:0.25rem;"><?php echo htmlspecialchars($movie['director_name']); ?></span>
                </a>
                <!-- Actor Cards -->
                <?php foreach ($actors as $actor): ?>
                <a href="search.php?q=<?php echo urlencode($actor['name']); ?>" class="person-card" style="display:flex;flex-direction:column;align-items:center;background:#232323;border-radius:10px;padding:0.75rem 1.25rem;text-align:center;text-decoration:none;color:#fff;min-width:110px;">
                    <img src="<?php echo htmlspecialchars($actor['image_url'] ?: 'assets/images/profile.avif'); ?>" alt="<?php echo htmlspecialchars($actor['name']); ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:0.5rem;">
                    <span style="font-weight:600;">Actor</span>
                    <span style="margin-top:0.25rem;"><?php echo htmlspecialchars($actor['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <h2>Write a Review</h2>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST" action="movie.php?id=<?php echo $movie_id; ?>" class="review-form">
                    <input type="hidden" id="rating" name="rating" value="5">
                    <div class="form-group">
                        <label for="comment">Your Review</label>
                        <textarea id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit">Submit Review</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="login-prompt" style="background:#232323;color:#e5e5e5;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <p>Please <a href="login.php">login</a> to write a review.</p>
                </div>
            <?php endif; ?>
            <script src="assets/js/main.js"></script>
            <div class="reviews-section">
                <h2>Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet. Be the first to review this movie!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php $is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']; ?>
                        <div class="review-card" style="background:#232323;border-radius:8px;padding:1rem;margin-bottom:1rem;">
                            <div class="review-header" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                                <div class="reviewer-info" style="display:flex;align-items:center;gap:0.75rem;">
                                    <a href="<?php echo $is_own_profile ? 'profile.php' : 'profiles.php?id=' . $review['user_id']; ?>">
                                        <img src="<?php echo $review['profile_picture'] ?: 'assets/images/profile.avif'; ?>"
                                             alt="<?php echo htmlspecialchars($review['username']); ?>"
                                             class="review-avatar"
                                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    </a>
                                    <div>
                                        <h3 style="margin:0;font-size:1rem;font-weight:600;">
                                            <a href="profiles.php?id=<?php echo $review['user_id']; ?>" style="color:#fff;text-decoration:none;">
                                                <?php echo htmlspecialchars($review['username']); ?>
                                            </a>
                                        </h3>
                                        <p class="review-date" style="margin:0;font-size:0.85rem;color:#888;">
                                            <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="review-rating" style="margin-left:auto;color:#f5c518;font-weight:500;"><?php echo number_format($review['rating'], 1); ?> <span class="stars">★</span></span>
                            </div>
                            <p class="review-text" style="margin:0;line-height:1.5;color:#e5e5e5;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
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
