-- Populate genres
INSERT INTO genres (name) VALUES ('Action'), ('Comedy'), ('Drama'), ('Sci-Fi'), ('Romance'), ('Thriller'), ('Animation'), ('Horror');

-- Populate actors
INSERT INTO actors (name) VALUES ('Tom Hanks'), ('Emma Watson'), ('Leonardo DiCaprio'), ('Scarlett Johansson'), ('Morgan Freeman'), ('Chris Evans'), ('Jennifer Lawrence'), ('Will Smith');

-- Populate directors
INSERT INTO directors (name) VALUES ('Steven Spielberg'), ('Christopher Nolan'), ('Quentin Tarantino'), ('James Cameron'), ('Greta Gerwig');

-- Populate users
INSERT INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@cineverse.com', '$2y$10$6cGhp1a2/6UVNWjwPZ0GusH2dAtz4bQUZ.ZMPLOwQw6QwQwQwQwQw', 1),
('user1', 'user1@cineverse.com', '$2y$10$6cGhp1a2/6UVNWjwPZ0GusH2dAtz4bQUZ.ZMPLOwQw6QwQwQwQwQw', 0),
('user2', 'user2@cineverse.com', '$2y$10$6cGhp1a2/6UVNWjwPZ0GusH2dAtz4bQUZ.ZMPLOwQw6QwQwQwQwQw', 0);
-- password for all: password

-- Populate movies
INSERT INTO movies (title, description, release_year, poster_url, director_id)
VALUES
('Inception', 'A thief who steals corporate secrets through dream-sharing technology.', 2010, 'assets/posters/inception.jpg', 2),
('Forrest Gump', 'The presidencies of Kennedy and Johnson, the Vietnam War, and more through the eyes of Forrest.', 1994, 'assets/posters/forrestgump.jpg', 1),
('Pulp Fiction', 'The lives of two mob hitmen, a boxer, and others intertwine in four tales of violence and redemption.', 1994, 'assets/posters/pulpfiction.jpg', 3),
('Avatar', 'A paraplegic Marine dispatched to the moon Pandora.', 2009, 'assets/posters/avatar.jpg', 4),
('Little Women', 'Jo March reflects back and forth on her life.', 2019, 'assets/posters/littlewomen.jpg', 5);

-- Populate movie_genres
INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1, 1), (1, 4), (2, 3), (2, 1), (3, 1), (3, 3), (4, 4), (4, 6), (5, 3), (5, 5);

-- Populate movie_actors
INSERT INTO movie_actors (movie_id, actor_id) VALUES
(1, 3), (1, 4), (2, 1), (2, 5), (3, 3), (3, 6), (4, 7), (4, 8), (5, 2), (5, 7);

-- Populate reviews
INSERT INTO reviews (movie_id, user_id, rating, comment) VALUES
(1, 1, 5, 'Mind-bending and visually stunning!'),
(1, 2, 4, 'Great movie, a bit confusing.'),
(2, 1, 5, 'A classic. Tom Hanks is amazing.'),
(3, 3, 4, 'Tarantino at his best.'),
(4, 2, 3, 'Visually impressive but story is average.'),
(5, 1, 5, 'Beautiful adaptation!'),
(5, 3, 4, 'Loved the performances.'); 