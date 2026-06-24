from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime, date, time, timedelta
from werkzeug.security import generate_password_hash, check_password_hash
import os

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///movie_booking.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SECRET_KEY'] = 'movie-booking-secret-key-12345'

db = SQLAlchemy(app)

# --- Models ---

class User(db.Model):
    __tablename__ = 'users'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(100), unique=True, nullable=False)
    password = db.Column(db.String(255), nullable=False)
    role = db.Column(db.Enum('user', 'admin'), default='user')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    bookings = db.relationship('Booking', backref='user', cascade='all, delete-orphan')

class Movie(db.Model):
    __tablename__ = 'movies'
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    duration = db.Column(db.Integer, nullable=False) # In minutes
    rating = db.Column(db.Numeric(3, 1), default=0.0)
    poster_image = db.Column(db.String(255))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    showtimes = db.relationship('Showtime', backref='movie', cascade='all, delete-orphan')

class Showtime(db.Model):
    __tablename__ = 'showtimes'
    id = db.Column(db.Integer, primary_key=True)
    movie_id = db.Column(db.Integer, db.ForeignKey('movies.id', ondelete='CASCADE'), nullable=False)
    show_date = db.Column(db.Date, nullable=False)
    show_time = db.Column(db.Time, nullable=False)
    price = db.Column(db.Numeric(10, 2), nullable=False)
    bookings = db.relationship('Booking', backref='showtime', cascade='all, delete-orphan')
    seats = db.relationship('Seat', backref='showtime', cascade='all, delete-orphan')

class Booking(db.Model):
    __tablename__ = 'bookings'
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id', ondelete='CASCADE'), nullable=False)
    showtime_id = db.Column(db.Integer, db.ForeignKey('showtimes.id', ondelete='CASCADE'), nullable=False)
    total_seats = db.Column(db.Integer, nullable=False)
    total_price = db.Column(db.Numeric(10, 2), nullable=False)
    booking_date = db.Column(db.DateTime, default=datetime.utcnow)
    status = db.Column(db.Enum('confirmed', 'cancelled'), default='confirmed')
    seats = db.relationship('Seat', backref='booking', cascade='all, delete-orphan')

class Seat(db.Model):
    __tablename__ = 'seats'
    id = db.Column(db.Integer, primary_key=True)
    booking_id = db.Column(db.Integer, db.ForeignKey('bookings.id', ondelete='CASCADE'), nullable=False)
    showtime_id = db.Column(db.Integer, db.ForeignKey('showtimes.id', ondelete='CASCADE'), nullable=False)
    seat_number = db.Column(db.String(10), nullable=False)

# --- Context Processor ---

@app.context_processor
def inject_user():
    return dict(
        current_user_name=session.get('user_name'),
        current_user_id=session.get('user_id'),
        current_user_role=session.get('user_role')
    )

# --- Authentication Helpers ---

def login_required(f):
    import functools
    @functools.wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash("Please log in to access this page.", "danger")
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    import functools
    @functools.wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session or session.get('user_role') != 'admin':
            flash("Unauthorized access.", "danger")
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated_function

# --- User Routes ---

@app.route('/')
def index():
    latest_movies = Movie.query.order_by(Movie.created_at.desc()).limit(4).all()
    return render_template('index.html', latest_movies=latest_movies)

@app.route('/movies')
def movies():
    search = request.args.get('search', '').strip()
    if search:
        movie_list = Movie.query.filter(Movie.title.like(f"%{search}%")).order_by(Movie.created_at.desc()).all()
    else:
        movie_list = Movie.query.order_by(Movie.created_at.desc()).all()
    return render_template('movies.html', movies=movie_list, search=search)

@app.route('/movie/<int:movie_id>')
def movie_details(movie_id):
    movie = Movie.query.get_or_404(movie_id)
    # Fetch showtimes from today onwards
    today = date.today()
    showtimes = Showtime.query.filter(
        Showtime.movie_id == movie_id,
        Showtime.show_date >= today
    ).order_by(Showtime.show_date.asc(), Showtime.show_time.asc()).all()
    return render_template('movie_details.html', movie=movie, showtimes=showtimes)

@app.route('/book/<int:showtime_id>', methods=['GET', 'POST'])
@login_required
def book_ticket(showtime_id):
    showtime = Showtime.query.get_or_404(showtime_id)
    movie = Movie.query.get(showtime.movie_id)
    
    # Get already booked seats
    booked_seats = [seat.seat_number for seat in Seat.query.filter_by(showtime_id=showtime_id).all()]
    
    if request.method == 'POST':
        selected_seats_str = request.form.get('selected_seats', '').strip()
        if not selected_seats_str:
            flash("No seats selected.", "danger")
            return redirect(url_for('book_ticket', showtime_id=showtime_id))
        
        seats_array = selected_seats_str.split(',')
        total_seats = len(seats_array)
        total_price = total_seats * float(showtime.price)
        user_id = session['user_id']
        
        # Check double booking
        already_booked = Seat.query.filter(
            Seat.showtime_id == showtime_id,
            Seat.seat_number.in_(seats_array)
        ).all()
        
        if already_booked:
            booked_list = [s.seat_number for s in already_booked]
            flash(f"Some seats have been booked by someone else: {', '.join(booked_list)}. Please try again.", "danger")
            return redirect(url_for('book_ticket', showtime_id=showtime_id))
        
        try:
            # Create booking
            booking = Booking(
                user_id=user_id,
                showtime_id=showtime_id,
                total_seats=total_seats,
                total_price=total_price,
                status='confirmed'
            )
            db.session.add(booking)
            db.session.flush() # Populate booking.id
            
            # Insert seats
            for seat_num in seats_array:
                seat = Seat(
                    booking_id=booking.id,
                    showtime_id=showtime_id,
                    seat_number=seat_num
                )
                db.session.add(seat)
            
            db.session.commit()
            flash("Booking confirmed! Redirecting...", "success")
            return render_template('booking_success.html', booking=booking)
        except Exception as e:
            db.session.rollback()
            flash("Booking failed. Please try again.", "danger")
            return redirect(url_for('book_ticket', showtime_id=showtime_id))
            
    # Rows A-F, 10 columns
    rows = ['A', 'B', 'C', 'D', 'E', 'F']
    cols = range(1, 11)
    
    return render_template('book_ticket.html', showtime=showtime, movie=movie, booked_seats=booked_seats, rows=rows, cols=cols)

@app.route('/dashboard', methods=['GET', 'POST'])
@login_required
def dashboard():
    user_id = session['user_id']
    
    if request.method == 'POST' and 'cancel_booking' in request.form:
        booking_id = int(request.form.get('booking_id', 0))
        booking = Booking.query.filter_by(id=booking_id, user_id=user_id).first()
        
        if booking and booking.status == 'confirmed':
            try:
                booking.status = 'cancelled'
                # Delete seats for this booking
                Seat.query.filter_by(booking_id=booking_id).delete()
                db.session.commit()
                flash("Booking cancelled successfully.", "success")
            except Exception as e:
                db.session.rollback()
                flash("Error cancelling booking.", "danger")
                
    # Fetch user's bookings with subqueries/joined loads
    bookings = Booking.query.filter_by(user_id=user_id).order_by(Booking.booking_date.desc()).all()
    
    # We will build seat numbers manually in the route or handle in template
    bookings_data = []
    for b in bookings:
        seats_booked = Seat.query.filter_by(booking_id=b.id).all()
        seat_numbers = ", ".join([s.seat_number for s in seats_booked])
        bookings_data.append({
            'booking': b,
            'seat_numbers': seat_numbers
        })
        
    return render_template('dashboard.html', bookings_data=bookings_data)

# --- Authentication Routes ---

@app.route('/login', methods=['GET', 'POST'])
def login():
    if 'user_id' in session:
        return redirect(url_for('index'))
        
    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '').strip()
        
        if not email or not password:
            flash("Please fill in all fields.", "danger")
        else:
            user = User.query.filter_by(email=email).first()
            if user and check_password_hash(user.password, password):
                session['user_id'] = user.id
                session['user_name'] = user.name
                session['user_role'] = user.role
                
                flash(f"Welcome back, {user.name}!", "success")
                if user.role == 'admin':
                    return redirect(url_for('admin_dashboard'))
                return redirect(url_for('dashboard'))
            else:
                flash("Invalid email or password.", "danger")
                
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if 'user_id' in session:
        return redirect(url_for('index'))
        
    if request.method == 'POST':
        name = request.form.get('name', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '').strip()
        confirm_password = request.form.get('confirm_password', '').strip()
        
        if not name or not email or not password:
            flash("Please fill in all fields.", "danger")
        elif password != confirm_password:
            flash("Passwords do not match.", "danger")
        elif len(password) < 6:
            flash("Password must be at least 6 characters.", "danger")
        else:
            # Check if email exists
            existing_user = User.query.filter_by(email=email).first()
            if existing_user:
                flash("Email already registered.", "danger")
            else:
                hashed_pw = generate_password_hash(password)
                new_user = User(name=name, email=email, password=hashed_pw, role='user')
                db.session.add(new_user)
                db.session.commit()
                flash("Registration successful! You can now log in.", "success")
                return redirect(url_for('login'))
                
    return render_template('register.html')

@app.route('/logout')
def logout():
    session.clear()
    flash("You have been logged out.", "info")
    return redirect(url_for('index'))

# --- Admin Routes ---

@app.route('/admin')
@admin_required
def admin_dashboard():
    movies_count = Movie.query.count()
    users_count = User.query.count()
    bookings_count = Booking.query.count()
    
    # Revenue sum
    revenue_query = db.session.query(db.func.sum(Booking.total_price)).filter(Booking.status == 'confirmed').scalar()
    revenue = float(revenue_query) if revenue_query else 0.0
    
    # Recent bookings
    recent_bookings = Booking.query.order_by(Booking.booking_date.desc()).limit(5).all()
    
    return render_template('admin/index.html', 
                           movies_count=movies_count, 
                           users_count=users_count, 
                           bookings_count=bookings_count, 
                           revenue=revenue,
                           recent_bookings=recent_bookings)

@app.route('/admin/movies', methods=['GET', 'POST'])
@admin_required
def admin_manage_movies():
    if request.method == 'POST' and 'add_movie' in request.form:
        title = request.form.get('title', '').strip()
        description = request.form.get('description', '').strip()
        duration = int(request.form.get('duration', 0))
        rating = float(request.form.get('rating', 0.0))
        poster_image = request.form.get('poster_image', '').strip()
        
        if not title or duration <= 0:
            flash("Please fill in all required fields.", "danger")
        else:
            new_movie = Movie(
                title=title,
                description=description,
                duration=duration,
                rating=rating,
                poster_image=poster_image or None
            )
            db.session.add(new_movie)
            db.session.commit()
            flash("Movie added successfully!", "success")
            
    movies_list = Movie.query.order_by(Movie.created_at.desc()).all()
    return render_template('admin/manage_movies.html', movies=movies_list)

@app.route('/admin/movies/edit/<int:movie_id>', methods=['GET', 'POST'])
@admin_required
def admin_edit_movie(movie_id):
    movie = Movie.query.get_or_404(movie_id)
    
    if request.method == 'POST':
        title = request.form.get('title', '').strip()
        description = request.form.get('description', '').strip()
        duration = int(request.form.get('duration', 0))
        rating = float(request.form.get('rating', 0.0))
        poster_image = request.form.get('poster_image', '').strip()
        
        if not title or duration <= 0:
            flash("Please fill in all required fields.", "danger")
        else:
            movie.title = title
            movie.description = description
            movie.duration = duration
            movie.rating = rating
            movie.poster_image = poster_image or None
            db.session.commit()
            flash("Movie updated successfully!", "success")
            return redirect(url_for('admin_manage_movies'))
            
    return render_template('admin/edit_movie.html', movie=movie)

@app.route('/admin/movies/delete/<int:movie_id>', methods=['POST'])
@admin_required
def admin_delete_movie(movie_id):
    movie = Movie.query.get_or_404(movie_id)
    db.session.delete(movie)
    db.session.commit()
    flash("Movie deleted successfully!", "success")
    return redirect(url_for('admin_manage_movies'))

@app.route('/admin/showtimes/<int:movie_id>', methods=['GET', 'POST'])
@admin_required
def admin_manage_showtimes(movie_id):
    movie = Movie.query.get_or_404(movie_id)
    
    if request.method == 'POST' and 'add_showtime' in request.form:
        show_date_str = request.form.get('show_date')
        show_time_str = request.form.get('show_time')
        price = float(request.form.get('price', 10.00))
        
        try:
            show_date = datetime.strptime(show_date_str, '%Y-%m-%d').date()
            show_time = datetime.strptime(show_time_str, '%H:%M').time()
            
            new_showtime = Showtime(
                movie_id=movie_id,
                show_date=show_date,
                show_time=show_time,
                price=price
            )
            db.session.add(new_showtime)
            db.session.commit()
            flash("Showtime added successfully!", "success")
        except Exception as e:
            flash("Error parsing date or time.", "danger")
            
    showtimes = Showtime.query.filter_by(movie_id=movie_id).order_by(Showtime.show_date.desc(), Showtime.show_time.desc()).all()
    return render_template('admin/manage_showtimes.html', movie=movie, showtimes=showtimes)

@app.route('/admin/showtimes/delete/<int:showtime_id>', methods=['POST'])
@admin_required
def admin_delete_showtime(showtime_id):
    showtime = Showtime.query.get_or_404(showtime_id)
    movie_id = showtime.movie_id
    db.session.delete(showtime)
    db.session.commit()
    flash("Showtime deleted successfully!", "success")
    return redirect(url_for('admin_manage_showtimes', movie_id=movie_id))

@app.route('/admin/bookings')
@admin_required
def admin_manage_bookings():
    bookings = Booking.query.order_by(Booking.booking_date.desc()).all()
    return render_template('admin/manage_bookings.html', bookings=bookings)

@app.route('/admin/users')
@admin_required
def admin_manage_users():
    users = User.query.order_by(User.created_at.desc()).all()
    return render_template('admin/manage_users.html', users=users)

@app.route('/admin/users/delete/<int:user_id>', methods=['POST'])
@admin_required
def admin_delete_user(user_id):
    if user_id == session['user_id']:
        flash("You cannot delete your own account.", "danger")
    else:
        user = User.query.get_or_404(user_id)
        db.session.delete(user)
        db.session.commit()
        flash("User deleted successfully!", "success")
    return redirect(url_for('admin_manage_users'))

# --- Database Auto-seed ---

def seed_database():
    # Only seed if database has no movies
    if Movie.query.first() is None:
        print("Seeding database with default movies and showtimes...")
        
        # 1. Admin user
        admin_email = 'admin@admin.com'
        if not User.query.filter_by(email=admin_email).first():
            admin_user = User(
                name='Administrator',
                email=admin_email,
                password=generate_password_hash('admin123'),
                role='admin'
            )
            db.session.add(admin_user)
            
        # 2. Demo normal user
        demo_email = 'user@user.com'
        if not User.query.filter_by(email=demo_email).first():
            demo_user = User(
                name='John Doe',
                email=demo_email,
                password=generate_password_hash('user123'),
                role='user'
            )
            db.session.add(demo_user)
            
        # 3. Movies
        movies_data = [
            {
                'title': 'Interstellar',
                'description': 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival. A cinematic masterpiece exploring love, gravity, and time dilation.',
                'duration': 169,
                'rating': 8.7,
                'poster_image': 'interstellar.jpg'
            },
            {
                'title': 'Inception',
                'description': 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
                'duration': 148,
                'rating': 8.8,
                'poster_image': 'inception.jpg'
            },
            {
                'title': 'The Dark Knight',
                'description': 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
                'duration': 152,
                'rating': 9.0,
                'poster_image': 'dark_knight.jpg'
            },
            {
                'title': 'Dune: Part Two',
                'description': 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
                'duration': 166,
                'rating': 8.6,
                'poster_image': 'dune2.jpg'
            }
        ]
        
        movies_objects = []
        for m_data in movies_data:
            movie = Movie(**m_data)
            db.session.add(movie)
            movies_objects.append(movie)
            
        db.session.flush() # Get IDs
        
        # 4. Showtimes
        # Create some showtimes for the next 7 days at 14:00, 18:00, and 21:00
        today = date.today()
        times_list = [time(14, 0), time(18, 0), time(21, 0)]
        prices_list = [10.50, 12.00, 14.50]
        
        for movie in movies_objects:
            for day_offset in range(7):
                show_date = today + timedelta(days=day_offset)
                for t, price in zip(times_list, prices_list):
                    showtime = Showtime(
                        movie_id=movie.id,
                        show_date=show_date,
                        show_time=t,
                        price=price
                    )
                    db.session.add(showtime)
                    
        db.session.commit()
        print("Database seeded successfully.")

# Create tables and seed within application context
with app.app_context():
    db.create_all()
    seed_database()

if __name__ == '__main__':
    app.run(debug=True, port=5000)
