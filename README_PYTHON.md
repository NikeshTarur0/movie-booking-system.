# Movie Booking System (Python/Flask Version)

This is a modern, high-fidelity Python Flask port of the PHP Movie Ticket Booking System. It replicates all the backend routes, database structures, role-based controls, seat booking transactions, and administration panel capabilities of the original PHP project.

## Key Features Replicated

1. **User Auth**: Registration, login, and roles session control.
2. **Movies Search & Catalog**: Browse all movies, search by keywords.
3. **Showtimes & Details**: Select movies, view dates, times, and prices.
4. **Interactive Seat Map**: Graphical grid mapping of A-F rows with 10 columns showing real-time seat availability, selected seat counters, and price calculations.
5. **Booking Engine**: Complete SQL transaction-safe ticket reservation preventing double-bookings.
6. **User Profile**: Dashboard for users showing purchase history and order cancellation.
7. **Admin Control Panel**: Real-time stats (Total Movies, Total Users, Total Bookings, and Total Revenue), full Movie CRUD, Showtime scheduling, and User account administration.

## Prerequisites

- **Python 3.8+** installed on your system.

## Setup Instructions

1. **Navigate to the Project Directory** (if you aren't already there):
   ```bash
   cd c:\Users\Nikesh\Ic6\movie-booking-system
   ```

2. **Create a Virtual Environment** (Recommended):
   ```bash
   python -m venv venv
   ```

3. **Activate the Virtual Environment**:
   - **Windows (Command Prompt)**:
     ```cmd
     venv\Scripts\activate.bat
     ```
   - **Windows (PowerShell)**:
     ```powershell
     .\venv\Scripts\Activate.ps1
     ```
   - **macOS / Linux**:
     ```bash
     source venv/bin/activate
     ```

4. **Install Dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

5. **Run the Application**:
   ```bash
   python app.py
   ```

6. **Access the Website**:
   Open your browser and navigate to:
   ```
   http://127.0.0.1:5000
   ```

## Default Accounts

When the application runs for the first time, it automatically creates a SQLite database (`instance/movie_booking.db`) and seeds it with demo movies, showtimes for the next 7 days, and two default users:

### Administrator Account
- **Email**: `admin@admin.com`
- **Password**: `admin123`

### Standard User Account
- **Email**: `user@user.com`
- **Password**: `user123`

## File Comparison (PHP vs Python)

| PHP File | Python/Flask Equivalence |
| :--- | :--- |
| `config.php` | Config in `app.py` & SQLAlchemy ORM mapping |
| `database.sql` | `db.create_all()` in `app.py` |
| `index.php` | `@app.route('/')` + `templates/index.html` |
| `movies.php` | `@app.route('/movies')` + `templates/movies.html` |
| `movie_details.php` | `@app.route('/movie/<id>')` + `templates/movie_details.html` |
| `book_ticket.php` | `@app.route('/book/<id>')` + `templates/book_ticket.html` |
| `dashboard.php` | `@app.route('/dashboard')` + `templates/dashboard.html` |
| `login.php` / `logout.php` | `@app.route('/login')` / `/logout` + `templates/login.html` |
| `register.php` | `@app.route('/register')` + `templates/register.html` |
| `admin/index.php` | `@app.route('/admin')` + `templates/admin/index.html` |
| `admin/manage_movies.php` | `@app.route('/admin/movies')` + `templates/admin/manage_movies.html` |
| `admin/edit_movie.php` | `@app.route('/admin/movies/edit/<id>')` + `templates/admin/edit_movie.html` |
| `admin/manage_showtimes.php` | `@app.route('/admin/showtimes/<id>')` + `templates/admin/manage_showtimes.html` |
| `admin/manage_bookings.php` | `@app.route('/admin/bookings')` + `templates/admin/manage_bookings.html` |
| `admin/manage_users.php` | `@app.route('/admin/users')` + `templates/admin/manage_users.html` |
| `css/style.css` | `static/css/style.css` (Upgraded with dark premium design) |
| `js/script.js` | `static/js/script.js` |
