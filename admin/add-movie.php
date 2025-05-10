<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Get all genres for the checkboxes
$stmt = $pdo->query("SELECT genre_id, name FROM genres ORDER BY name");
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $release_year = (int)$_POST['release_year'];
    $director_name = trim($_POST['director_name']);
    $description = trim($_POST['description']);
    $selected_genres = [];
    $genres_input = trim($_POST['genres'] ?? '');
    $cast = trim($_POST['cast'] ?? '');

    // Handle file upload
    $poster_url = '';
    if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['poster_file']['tmp_name'];
        $fileName = basename($_FILES['poster_file']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = uniqid('poster_', true) . '.' . $fileExt;
            $destPath = 'assets/posters/' . $newFileName;
            if (!is_dir('assets/posters')) { mkdir('assets/posters', 0777, true); }
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = $destPath;
            } else {
                $error = 'Failed to upload poster image.';
            }
        } else {
            $error = 'Invalid poster file type.';
        }
    } else {
        $error = 'Poster image is required.';
    }

    if (empty($title) || empty($release_year) || empty($director_name) || empty($description) || empty($poster_url)) {
        $error = 'All fields are required';
    } else if (!$error) {
        try {
            $pdo->beginTransaction();

            // Insert or get director
            $stmt = $pdo->prepare("SELECT director_id FROM directors WHERE name = ?");
            $stmt->execute([$director_name]);
            $director = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($director) {
                $director_id = $director['director_id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO directors (name) VALUES (?)");
                $stmt->execute([$director_name]);
                $director_id = $pdo->lastInsertId();
            }

            // Insert movie
            $stmt = $pdo->prepare("
                INSERT INTO movies (title, release_year, director_id, description, poster_url)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $release_year, $director_id, $description, $poster_url]);
            $movie_id = $pdo->lastInsertId();

            // Insert movie genres
            if (!empty($genres_input)) {
                $genre_names = array_map('trim', explode(',', $genres_input));
                foreach ($genre_names as $genre_name) {
                    if ($genre_name === '') continue;
                    // Insert or get genre
                    $stmt = $pdo->prepare("SELECT genre_id FROM genres WHERE name = ?");
                    $stmt->execute([$genre_name]);
                    $genre = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($genre) {
                        $genre_id = $genre['genre_id'];
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
                        $stmt->execute([$genre_name]);
                        $genre_id = $pdo->lastInsertId();
                    }
                    // Link genre to movie
                    $stmt = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                    $stmt->execute([$movie_id, $genre_id]);
                }
            }

            // Insert cast (actors)
            if (!empty($cast)) {
                $actors = array_map('trim', explode(',', $cast));
                foreach ($actors as $actor_name) {
                    if ($actor_name === '') continue;
                    // Insert or get actor
                    $stmt = $pdo->prepare("SELECT actor_id FROM actors WHERE name = ?");
                    $stmt->execute([$actor_name]);
                    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($actor) {
                        $actor_id = $actor['actor_id'];
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO actors (name) VALUES (?)");
                        $stmt->execute([$actor_name]);
                        $actor_id = $pdo->lastInsertId();
                    }
                    // Link actor to movie
                    $stmt = $pdo->prepare("INSERT INTO movie_actors (movie_id, actor_id) VALUES (?, ?)");
                    $stmt->execute([$movie_id, $actor_id]);
                }
            }

            $pdo->commit();
            $success = 'Movie added successfully!';
            header('Location: admin.php?success=added');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to add movie. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie - CineVerse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #232323;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #444;
            border-radius: 4px;
            background: #1a1a1a;
            color: #fff;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .genre-checkboxes, .genre-checkbox { display: none; }
        .btn-submit {
            background: #f5c518;
            color: #000;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #b2070f;
            color: #fff;
        }
        .alert-success {
            background: #28a745;
            color: #fff;
        }
        .navbar { background: #181818; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 1rem; }
        .nav-logo { display: flex; align-items: center; text-decoration: none; }
        .logo { height: 40px; }
        .nav-links { display: flex; gap: 1.5rem; }
        .nav-links a { color: #fff; text-decoration: none; font-weight: 500; font-size: 1.1rem; padding: 0.5rem 1.2rem; border-radius: 4px; transition: background 0.2s; }
        .nav-links a.active, .nav-links a:hover { background: #232323; color: #fff; text-decoration: underline; }
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
        <div class="form-container">
            <h1>Add New Movie</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="add-movie.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Movie Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="release_year">Release Year</label>
                    <input type="number" id="release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="director_name">Director</label>
                    <input type="text" id="director_name" name="director_name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="poster_file">Poster Image</label>
                    <input type="file" id="poster_file" name="poster_file" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label for="genres">Genres (comma-separated)</label>
                    <input type="text" id="genres" name="genres" placeholder="e.g. Action, Comedy, Drama" required>
                </div>

                <div class="form-group">
                    <label for="cast">Cast (comma-separated)</label>
                    <textarea id="cast" name="cast" placeholder="e.g. Tom Hanks, Emma Watson" style="min-height:40px;"></textarea>
                </div>

                <button type="submit" class="btn-submit">Add Movie</button>
            </form>
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