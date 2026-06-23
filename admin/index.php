<?php
include 'header.php';

// Fetch statistics
$movies_count = $conn->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$users_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$bookings_count = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$revenue = $conn->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

// Recent bookings
$stmt = $conn->query("
    SELECT b.*, u.name as user_name, m.title as movie_title, s.show_date, s.show_time 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    ORDER BY b.booking_date DESC LIMIT 5
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2 class="mb-4">Dashboard Overview</h2>
    
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Movies</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $movies_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $users_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Bookings</h5>
                    <p class="card-text display-6 fw-bold"><?php echo $bookings_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <p class="card-text display-6 fw-bold">$<?php echo number_format($revenue ?: 0, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4>Recent Bookings</h4>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Movie</th>
                            <th>Showtime</th>
                            <th>Seats</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td>#<?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['movie_title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($b['show_date'])) . ' ' . date('h:i A', strtotime($b['show_time'])); ?></td>
                            <td><?php echo $b['total_seats']; ?></td>
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
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
