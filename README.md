# CineVerse

CineVerse is a movie diary and social network for film lovers. Users can browse, review, and manage movies, as well as view statistics and admin features.

## Features

- Browse, search, and review movies
- Add, edit, and delete movies (admin only)
- User profiles with statistics (movies watched, reviews, etc.)
- Genre and cast management
- Admin dashboard with statistics and user management

## Setup Instructions

### 1. Clone the Repository

```
git clone <your-repo-url>
cd cineverse
```

### 2. Database Setup

- Import the provided `config/database.sql` to create the database and tables.
- (Optional) Run the provided `config/populate.sql` to fill the database with sample movies, genres, actors, and users.

### 3. Configure Database Connection

- Edit `config/database.php` with your MySQL credentials.

### 4. Run the Project

- Place the project in your web server's root (e.g., `htdocs` for XAMPP).
- Access via `http://localhost/CineVerse/`

### 5. Admin Access

- Log in with an admin account (default: admin, password: 12345678) or set `is_admin=1` for a user in the `users` table.

## Directory Structure

- `/config` - Database config and SQL files
- `/assets` - Images, CSS, JS
- `/admin` - Admin pages (movies, stats, users, add/edit movie)
- `/includes` - Shared header/footer (if used)
- Main pages: `index.php`, `movies.php`, `top-rated.php`, `search.php`, `movie.php`, `profile.php`, `login.php`, `register.php`

## License

MIT
