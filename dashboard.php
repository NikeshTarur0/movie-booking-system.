<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Check if the booking belongs to this user
    $chk = $conn->prepare("SELECT * FROM bookings WHERE id = :bid AND user_id = :uid");
    $chk->execute(['bid' => $booking_id, 'uid' => $user_id]);
    $booking = $chk->fetch();

    if ($booking && $booking['status'] == 'confirmed') {
        try {
            $conn->beginTransaction();
            // Update booking status
            $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :bid");
            $upd->execute(['bid' => $booking_id]);

            // Delete seats
            $del = $conn->prepare("DELETE FROM seats WHERE booking_id = :bid");
            $del->execute(['bid' => $booking_id]);

            $conn->commit();
            $msg = "<div class='alert alert-success'>Booking cancelled successfully.</div>";
        }
        catch (Exception $e) {
            $conn->rollBack();
            $msg = "<div class='alert alert-danger'>Error cancelling booking.</div>";
        }
    }
}

// Fetch user's bookings
$stmt = $conn->prepare("
    SELECT b.*, m.title, m.poster_image, s.show_date, s.show_time,
           (SELECT GROUP_CONCAT(seat_number SEPARATOR ', ') FROM seats WHERE booking_id = b.id) as booked_seat_numbers
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.user_id = :user_id
    ORDER BY b.booking_date DESC
");
$stmt->execute(['user_id' => $user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "User Dashboard";
include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>My Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    
    <?php if (isset($msg))
    echo $msg; ?>

    <div class="row mt-4">
        <div class="col-12">
            <h4>My Ticket Bookings</h4>
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mt-3 shadow-sm bg-white">
                        <thead class="table-dark">
                            <tr>
                                <th>Booking ID</th>
                                <th>Movie</th>
                                <th>Show Date & Time</th>
                                <th>Seats</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($b['title']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($b['show_date'])); ?> at <?php echo date('h:i A', strtotime($b['show_time'])); ?></td>
                                    <td>
                                        <?php if ($b['status'] == 'confirmed'): ?>
                                            <?php echo htmlspecialchars($b['booked_seat_numbers']); ?> (<?php echo $b['total_seats']; ?>)
                                        <?php
        else: ?>
                                            -
                                        <?php
        endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($b['total_price'], 2); ?></td>
                                    <td>
                                        <?php if ($b['status'] == 'confirmed'): ?>
                                            <span class="badge bg-success">Confirmed</span>
                                        <?php
        else: ?>
                                            <span class="badge bg-danger">Cancelled</span>
                                        <?php
        endif; ?>
                                    </td>
                                    <td>
                                        <?php
        $show_datetime = strtotime($b['show_date'] . ' ' . $b['show_time']);
        if ($b['status'] == 'confirmed'):
            // Allow cancel only if show hasn't started
            // For simplicity, we just allow unless date is past
?>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                        <?php
        endif; ?>
                                    </td>
                                </tr>
                            <?php
    endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
else: ?>
                <div class="alert alert-info">You have not booked any tickets yet. <a href="movies.php">Browse movies</a>.</div>
            <?php
endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
