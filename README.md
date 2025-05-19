# CineVerse

CineVerse is a movie diary and social network for film lovers. Users can browse, review, and manage movies, as well as view statistics and admin features.
You can access the online version here https://mgakuya.infy.uk/index.php
## Features

- Browse, search, and review movies
- Add, edit, and delete movies (admin only)
- User profiles with statistics (movies watched, reviews, etc.)
- Genre and cast management
- Admin dashboard with statistics and user management

## Setup Instructions

### 1. Clone the Repository

```
git clone https://github.com/anadecs/cineverse.git
cd cineverse
```

### 2. Database Setup

You have two options to populate the database:

#### Option 1: Using populate.sql

- Import the provided `config/database.sql` to create the database and tables
- Import `config/populate.sql` to fill the database with sample data
- Quick and simple way to get started

#### Option 2: Using populate.php

- Import the provided `config/database.sql` to create the database and tables
- Get your API key from [TMDB](https://www.themoviedb.org/settings/api) (REQUIRED)
- Add your API key to `config/populate.php`
- Run `populate.php` in your browser to automatically fetch popular movies from TMDB
- Includes movie details like title, year, poster, director, and rating
- More dynamic and up-to-date data

### 3. Configure Database Connection

- Edit `config/database.php` with your MySQL credentials.

### 4. Run the Project

- Place the project in your web server's root (e.g., `htdocs` for XAMPP).
- Access via `http://localhost/CineVerse/`

### 5. Admin Access

- Log in with an admin account (default: admin, password: 12345678)

## Directory Structure

- `/config` - Database config and SQL files
- `/assets` - Images, CSS, JS
- `/admin` - Admin pages (movies, stats, users, add/edit movie)
- `/includes` - Shared header/footer (if used)
- Main pages: `index.php`, `movies.php`, `top-rated.php`, `search.php`, `movie.php`, `profile.php`, `login.php`, `register.php`
