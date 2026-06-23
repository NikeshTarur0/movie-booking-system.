<?php
include 'header.php';

if (!isset($_GET['movie_id'])) {
    header("Location: manage_movies.php");
    exit;
}

$movie_id = (int)$_GET['movie_id'];

// Get movie details
$m_stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
$m_stmt->execute([$movie_id]);
$movie = $m_stmt->fetch();

// Add Showtime
if (isset($_POST['add_showtime'])) {
    $date = $_POST['show_date'];
    $time = $_POST['show_time'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("INSERT INTO showtimes (movie_id, show_date, show_time, price) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$movie_id, $date, $time, $price])) {
        $msg = "<div class='alert alert-success'>Showtime added successfully!</div>";
    }
    else {
        $msg = "<div class='alert alert-danger'>Error adding showtime!</div>";
    }
}

// Delete Showtime
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM showtimes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_showtimes.php?movie_id=" . $movie_id);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM showtimes WHERE movie_id = ? ORDER BY show_date DESC, show_time DESC");
$stmt->execute([$movie_id]);
$showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Showtimes: <?php echo htmlspecialchars($movie['title']); ?></h2>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addShowtimeModal"><i class="bi bi-clock-history"></i> Add Showtime</button>
            <a href="manage_movies.php" class="btn btn-secondary ms-2">Back</a>
        </div>
    </div>
    
    <?php if (isset($msg))
    echo $msg; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($showtimes as $s): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($s['show_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($s['show_time'])); ?></td>
                            <td>$<?php echo number_format($s['price'], 2); ?></td>
                            <td>
                                <a href="?movie_id=<?php echo $movie_id; ?>&delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this showtime?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                        <?php if (count($showtimes) == 0): ?>
                            <tr><td colspan="4" class="text-center">No showtimes found.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addShowtimeModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add Showtime</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Date</label>
                    <input type="date" name="show_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-3">
                    <label>Time</label>
                    <input type="time" name="show_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Ticket Price ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required value="10.00">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="add_showtime" class="btn btn-success">Save Showtime</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
