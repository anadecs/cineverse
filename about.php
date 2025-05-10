<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CineVerse</title>
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
        <section class="page-header" style="background-image: url('assets/images/about-bg.jpg');">
            <h1>About CineVerse</h1>
            <p>Your personal movie diary and social network for film lovers</p>
        </section>

        <section class="about-content">
            <div class="about-section">
                <h2>Our Mission</h2>
                <p>CineVerse is a social network for film lovers. We help you track your film watching, discover new movies, and connect with other film enthusiasts.</p>
            </div>

            <div class="about-section">
                <h2>What We Offer</h2>
                <ul>
                    <li>Track the movies you've watched</li>
                    <li>Write and share your reviews</li>
                    <li>Discover new films based on your taste</li>
                    <li>Connect with other movie lovers</li>
                    <li>Create and share your watchlists</li>
                </ul>
            </div>

            <div class="about-section">
                <h2>Join Our Community</h2>
                <p>Whether you're a casual movie watcher or a dedicated cinephile, CineVerse is the perfect place to share your love for film. Create an account today and start your movie journey!</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                    <a href="movies.php" class="btn btn-secondary">Browse Movies</a>
                </div>
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