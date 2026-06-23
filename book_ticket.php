<?php
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['showtime_id'])) {
    header("Location: movies.php");
    exit;
}

$showtime_id = (int)$_GET['showtime_id'];

// Get showtime and movie details
$stmt = $conn->prepare("
    SELECT s.*, m.title, m.poster_image 
    FROM showtimes s 
    JOIN movies m ON s.movie_id = m.id 
    WHERE s.id = :id
");
$stmt->execute(['id' => $showtime_id]);
$showtime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$showtime) {
    echo "Showtime not found.";
    exit;
}

// Handle booking submission
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
    $seats_array = explode(',', $_POST['selected_seats']);
    $total_seats = count($seats_array);
    $total_price = $total_seats * $showtime['price'];
    $user_id = $_SESSION['user_id'];

    // Check if seats are already booked
    $placeholders = str_repeat('?,', count($seats_array) - 1) . '?';
    $check_stmt = $conn->prepare("SELECT seat_number FROM seats WHERE showtime_id = ? AND seat_number IN ($placeholders)");
    $params = array_merge([$showtime_id], $seats_array);
    $check_stmt->execute($params);
    $already_booked = $check_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($already_booked) > 0) {
        $error_msg = "Some seats have been booked by someone else: " . implode(', ', $already_booked) . ". Please try again.";
    }
    else {
        try {
            $conn->beginTransaction();
            // Create booking
            $b_stmt = $conn->prepare("INSERT INTO bookings (user_id, showtime_id, total_seats, total_price, status) VALUES (?, ?, ?, ?, 'confirmed')");
            $b_stmt->execute([$user_id, $showtime_id, $total_seats, $total_price]);
            $booking_id = $conn->lastInsertId();

            // Insert seats
            $seat_stmt = $conn->prepare("INSERT INTO seats (booking_id, showtime_id, seat_number) VALUES (?, ?, ?)");
            foreach ($seats_array as $seat) {
                $seat_stmt->execute([$booking_id, $showtime_id, $seat]);
            }

            $conn->commit();
            $success_msg = "Booking confirmed! You will be redirected to your dashboard...";
            echo "<script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 3000);</script>";
        }
        catch (PDOException $e) {
            $conn->rollBack();
            $error_msg = "Booking failed. Please try again.";
        }
    }
}

// Get already booked seats for this showtime
$stmt_booked = $conn->prepare("SELECT seat_number FROM seats WHERE showtime_id = :sid");
$stmt_booked->execute(['sid' => $showtime_id]);
$booked_seats = $stmt_booked->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Book Ticket - " . $showtime['title'];
include 'includes/header.php';

// Define layout variables
$rows = ['A', 'B', 'C', 'D', 'E', 'F'];
$cols = 10;
?>

<div class="container mt-4">
    <h2>Book Tickets: <?php echo htmlspecialchars($showtime['title']); ?></h2>
    <p class="text-muted">
        <strong>Date:</strong> <?php echo date('d M Y', strtotime($showtime['show_date'])); ?> | 
        <strong>Time:</strong> <?php echo date('h:i A', strtotime($showtime['show_time'])); ?> | 
        <strong>Price per seat:</strong> $<span id="price_display"><?php echo number_format($showtime['price'], 2); ?></span>
    </p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php
endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php
endif; ?>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm p-4">
                <div class="screen text-uppercase">Screen</div>
                <div class="seat-map d-flex flex-column align-items-center">
                    <?php foreach ($rows as $row): ?>
                        <div class="d-flex mb-2">
                            <div class="me-3 d-flex align-items-center fw-bold text-muted"><?php echo $row; ?></div>
                            <div class="d-flex gap-2">
                                <?php for ($i = 1; $i <= $cols; $i++):
        $seatName = $row . $i;
        $isBooked = in_array($seatName, $booked_seats);
        $class = $isBooked ? 'booked' : 'available';
?>
                                    <div class="seat <?php echo $class; ?>" data-seat="<?php echo $seatName; ?>" title="<?php echo $seatName; ?>">
                                        <?php echo $i; ?>
                                    </div>
                                <?php
    endfor; ?>
                            </div>
                        </div>
                    <?php
endforeach; ?>
                </div>

                <div class="mt-4 d-flex justify-content-center gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="seat available border" style="width:20px;height:20px;margin:0;"></div> Available
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="seat selected" style="width:20px;height:20px;margin:0;"></div> Selected
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="seat booked" style="width:20px;height:20px;margin:0;"></div> Booked
                    </div>
                </div>

                <form method="POST" action="" class="mt-4 border-top pt-4 text-center">
                    <input type="hidden" id="price_per_seat" value="<?php echo $showtime['price']; ?>">
                    <input type="hidden" id="selected_seats" name="selected_seats" value="">
                    
                    <p class="fs-5">
                        <span class="fw-bold">Selected Seats:</span> <span id="total_seats_display">0</span> | 
                        <span class="fw-bold">Total Price:</span> $<span id="total_price_display">0.00</span>
                    </p>
                    <button type="submit" id="book_btn" class="btn btn-success btn-lg px-5 mt-2" disabled>Confirm Booking</button>
                    <a href="movie_details.php?id=<?php echo $showtime['movie_id']; ?>" class="btn btn-outline-secondary mt-2 ms-2">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
