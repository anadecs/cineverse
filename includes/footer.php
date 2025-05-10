    <footer class="footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="movies.php">Movies</a></li>
                        <li><a href="top-rated.php">Top Rated</a></li>
                        <li><a href="about.php">About</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Account</h3>
                    <ul>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="profile.php">My Profile</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>About Letterboxd</h3>
                    <p>Letterboxd is a social platform for sharing your taste in film. Use it as a diary to record your opinion about films as you watch them, or just to keep track of films you've seen in the past.</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Letterboxd. All rights reserved.</p>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html> 