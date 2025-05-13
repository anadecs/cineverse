<?php
set_time_limit(300);
require_once '../config/database.php';

// TMDB API configuration
$api_key = 'YOUR_API_KEY';
$base_url = 'https://api.themoviedb.org/3';
$image_base_url = 'https://image.tmdb.org/t/p/w500';


$movie_titles = [
    'Barbie',
    'Parasite 2019',
    'Interstellar',
    'Fight Club',
    'La la land',
    'Everthing Everywhere all at once',
    'Oppenheimer',
    'Whiplash',
    'Pulp fiction',
    'Joker',
    'Dune',
    'The Substance',
    'The truman show',
    'Get out',
    'Spider-man into the spider verse',
    'midsommar',
    'ethernal sunshine of the spotless mind',
    'batman 2022',
    'the dark knight',
    'inception',
    'knives out',
    'dune part two',
    'american psycho',
    'saltburn',
    'spider man across the spider verse',
    'poor things',
    'challengers',
    'lady bird',
    'spirited away',
    'the wolf of wall street',
    'nosferatu',
    'the grand budafest hotel',
    '10 things i hate about you',
    'the menu',
    'black swan',
    'se7en',
    'dead poets society',
    'the shining',
    'gone girl',
    'forest gump',
    'spider man no way home',
    'littel women',
    'anora',
    'inglourious basterds',
    'once upon a time in hollywood',
    'the silence of the lambs',
    'call me by your name',
    'kill bill: vol.1',
    'the godfather',
    'shutter island'
];

function fetchFromTMDB($endpoint, $params = []) {
    global $api_key, $base_url;
    $params['api_key'] = $api_key;
    $query = http_build_query($params);
    $url = "$base_url$endpoint?$query";
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function insertDirector($name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO directors (name) VALUES (?)");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function insertActor($name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO actors (name) VALUES (?)");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function insertMovie($title, $description, $release_year, $poster_url, $director_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO movies (title, description, release_year, poster_url, director_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $release_year, $poster_url, $director_id]);
    return $pdo->lastInsertId();
}

function insertMovieGenre($movie_id, $genre_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
    $stmt->execute([$movie_id, $genre_id]);
}

function insertMovieActor($movie_id, $actor_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO movie_actors (movie_id, actor_id) VALUES (?, ?)");
    $stmt->execute([$movie_id, $actor_id]);
}

function getOrInsertGenre($name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT genre_id FROM genres WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return $row['genre_id'];
    } else {
        $insert = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
        $insert->execute([$name]);
        return $pdo->lastInsertId();
    }
}


$sample_users = [
    ['username' => 'alice', 'email' => 'alice@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
    ['username' => 'bob', 'email' => 'bob@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
    ['username' => 'carol', 'email' => 'carol@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
    ['username' => 'dave', 'email' => 'dave@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
    ['username' => 'eve', 'email' => 'eve@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
];
$user_ids = [];
foreach ($sample_users as $user) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    $row = $stmt->fetch();
    if ($row) {
        $user_ids[] = $row['user_id'];
    } else {
        $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $insert->execute([$user['username'], $user['email'], $user['password']]);
        $user_ids[] = $pdo->lastInsertId();
    }
}

// --- Sample review comments ---
$sample_comments = [
    "Amazing movie! Highly recommended.",
    "Really enjoyed the plot and characters.",
    "A bit overrated, but still good.",
    "Would watch again!",
    "Not my cup of tea, but well made.",
    "Incredible cinematography.",
    "The acting was top notch.",
    "Story was a bit slow, but worth it.",
    "Loved the soundtrack!",
    "A modern classic."
];

$count = 0;
foreach ($movie_titles as $title) {
    // Search for the movie on TMDB
    $search = fetchFromTMDB('/search/movie', ['query' => $title]);
    if (!isset($search['results'][0])) {
        echo "Movie not found: $title\n";
        continue;
    }
    $movie = $search['results'][0];
    $movie_details = fetchFromTMDB("/movie/{$movie['id']}");
    $credits = fetchFromTMDB("/movie/{$movie['id']}/credits");
    $release_year = isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null;
    // Check if movie already exists
    $stmt = $pdo->prepare("SELECT movie_id FROM movies WHERE title = ? AND release_year = ?");
    $stmt->execute([$movie['title'], $release_year]);
    $row = $stmt->fetch();
    if ($row) {
        $movie_id = $row['movie_id'];
        echo "Movie already exists: {$movie['title']} ({$release_year})\n";
    } else {
       
        $director_name = '';
        foreach ($credits['crew'] as $crew) {
            if ($crew['job'] === 'Director') {
                $director_name = $crew['name'];
                break;
            }
        }
        $director_id = insertDirector($director_name);
      
        $poster_url = $movie['poster_path'] ? $image_base_url . $movie['poster_path'] : null;
        $movie_id = insertMovie(
            $movie['title'],
            $movie['overview'],
            $release_year,
            $poster_url,
            $director_id
        );
        echo "Inserted movie: {$movie['title']} ({$release_year})\n";
    }

    foreach ($movie_details['genres'] as $genre) {
        $genre_id = getOrInsertGenre($genre['name']);
        if ($genre_id) {
            insertMovieGenre($movie_id, $genre_id);
        }
    }
    $actor_count = 0;
    foreach ($credits['cast'] as $actor) {
        if ($actor_count >= 5) break;
        $actor_id = insertActor($actor['name']);
        insertMovieActor($movie_id, $actor_id);
        $actor_count++;
    }
    $num_reviews = rand(2, 3);
    for ($i = 0; $i < $num_reviews; $i++) {
        $user_id = $user_ids[array_rand($user_ids)];
        $rating = rand(10, 50) / 10; // 1.0 to 5.0
        $comment = $sample_comments[array_rand($sample_comments)];
        $stmt = $pdo->prepare("INSERT INTO reviews (movie_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$movie_id, $user_id, $rating, $comment]);
    }
    $count++;
    echo "Processed movie: {$movie['title']}\n";
}
echo "Database population from list completed successfully!\n"; 
