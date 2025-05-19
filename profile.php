<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    
    // Verify the review belongs to the user
    $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch();
    
    if ($review && $review['user_id'] === $user_id) {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
        try {
            $stmt->execute([$review_id]);
            $success = 'Review deleted successfully';
        } catch (PDOException $e) {
            $error = 'Failed to delete review';
        }
    } else {
        $error = 'Unauthorized action';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $profile_picture_path = $user['profile_picture'] ?? 'assets/images/profile.avif';
    
    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_name = basename($_FILES['profile_picture']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (in_array($file_ext, $allowed_exts)) {
            $new_name = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $dest = 'assets/images/' . $new_name;
            if (move_uploaded_file($file_tmp, $dest)) {
                $profile_picture_path = $dest;
            } else {
                $error = 'Failed to upload profile picture.';
            }
        } else {
            $error = 'Invalid file type for profile picture.';
        }
    }
    // Update username and profile picture
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_picture = ? WHERE user_id = ?");
        try {
            $stmt->execute([$new_username, $profile_picture_path, $user_id]);
            $success = 'Profile updated successfully!';
            $_SESSION['username'] = $new_username;
            $user['username'] = $new_username;
            $user['profile_picture'] = $profile_picture_path;
            $avatar = $profile_picture_path;
        } catch (PDOException $e) {
            $error = 'Failed to update profile.';
        }
    }
}

// Pagination for reviews
$reviews_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $reviews_per_page;
// Get total review count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$stmt->execute([$user_id]);
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
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT movie_id) as movies_watched, COUNT(*) as review_count FROM reviews WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
$movies_watched = $stats['movies_watched'] ?? 0;
$unique_watches = $movies_watched; // same as movies_watched
$review_count = $stats['review_count'] ?? 0;

// Get user info (updated)
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = $user['profile_picture'] ?: 'assets/images/profile.avif';

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
$stmt->execute([$user_id]);
$last_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - CineVerse</title>
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
        <div class="profile-container">
            <div class="profile-header">
                <form method="POST" action="profile.php" enctype="multipart/form-data" class="edit-profile-form" id="edit-profile-form">
                    <label for="profile_picture" class="profile-avatar-label">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture" class="profile-avatar clickable-avatar">
                    </label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;">
                    <h1><?php echo htmlspecialchars($user['username']); ?>'s Profile</h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="form-group">
                        <label for="username">Display Name</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
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

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-movies">
                <h2>Last 4 Movies Watched</h2>
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
                <h2>Your Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p>You haven't written any reviews yet.</p>
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
                            <div class="review-actions">
                                <form method="POST" action="profile.php" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <button type="submit" name="delete_review" class="delete-button">Delete Review</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="text-align:center;margin-top:1.5rem;">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="profile.php?page=<?php echo $i; ?>" class="page-link" style="display:inline-block;padding:0.5rem 1rem;margin:0 0.25rem;border-radius:6px;background:<?php echo $i == $page ? '#b2070f' : '#232323'; ?>;color:#fff;text-decoration:none;font-weight:600;<?php echo $i == $page ? 'box-shadow:0 2px 8px rgba(178,7,15,0.25);' : ''; ?>"><?php echo $i; ?></a>
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
