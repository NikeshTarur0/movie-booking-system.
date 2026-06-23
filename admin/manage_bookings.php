<?php
include 'header.php';

$stmt = $conn->query("
    SELECT b.*, u.name as user_name, u.email, m.title as movie_title, s.show_date, s.show_time 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    ORDER BY b.booking_date DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2 class="mb-4">All Bookings</h2>
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User (Email)</th>
                            <th>Movie</th>
                            <th>Showtime</th>
                            <th>Seats</th>
                            <th>Total Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="fw-bold">#<?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['user_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($b['email']); ?></small></td>
                            <td><?php echo htmlspecialchars($b['movie_title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($b['show_date'])); ?><br><small class="text-muted"><?php echo date('h:i A', strtotime($b['show_time'])); ?></small></td>
                            <td class="text-center"><?php echo $b['total_seats']; ?></td>
                            <td>$<?php echo number_format($b['total_price'], 2); ?></td>
                            <td>
                                <?php if ($b['status'] == 'confirmed'): ?>
                                    <span class="badge bg-success rounded-pill px-3">Confirmed</span>
                                <?php
    else: ?>
                                    <span class="badge bg-danger rounded-pill px-3">Cancelled</span>
                                <?php
    endif; ?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                        <?php if (count($bookings) == 0): ?>
                            <tr><td colspan="7" class="text-center py-4">No bookings found.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
