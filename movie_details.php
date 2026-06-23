<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: movies.php");
    exit;
}

$movie_id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM movies WHERE id = :id");
$stmt->execute(['id' => $movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    echo "Movie not found.";
    exit;
}

// Fetch showtimes
$stmt_st = $conn->prepare("SELECT * FROM showtimes WHERE movie_id = :movie_id AND show_date >= CURRENT_DATE ORDER BY show_date ASC, show_time ASC");
$stmt_st->execute(['movie_id' => $movie_id]);
$showtimes = $stmt_st->fetchAll(PDO::FETCH_ASSOC);

$page_title = $movie['title'];
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <?php $poster = $movie['poster_image'] ? 'images/' . $movie['poster_image'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>
            <img src="<?php echo htmlspecialchars($poster); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($movie['title']); ?>">
        </div>
        <div class="col-md-8">
            <h1 class="mb-3"><?php echo htmlspecialchars($movie['title']); ?></h1>
            <div class="mb-3 d-flex gap-3 text-muted">
                <span><i class="bi bi-clock"></i> Duration: <?php echo $movie['duration']; ?> mins</span>
                <span><i class="bi bi-star-fill text-warning"></i> Rating: <?php echo $movie['rating']; ?>/10</span>
            </div>
            
            <h5 class="mt-4">Description</h5>
            <p class="lead" style="font-size: 1.1rem;"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
            
            <h5 class="mt-5 mb-3">Available Showtimes</h5>
            <?php if (count($showtimes) > 0): ?>
                <div class="list-group">
                    <?php foreach ($showtimes as $st): ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-2 shadow-sm rounded border">
                            <div>
                                <strong>Date:</strong> <?php echo date('d M Y', strtotime($st['show_date'])); ?> <br>
                                <strong>Time:</strong> <?php echo date('h:i A', strtotime($st['show_time'])); ?> <br>
                                <strong>Price:</strong> $<?php echo number_format($st['price'], 2); ?>
                            </div>
                            <a href="book_ticket.php?showtime_id=<?php echo $st['id']; ?>" class="btn btn-primary px-4">Book Ticket</a>
                        </div>
                    <?php
    endforeach; ?>
                </div>
            <?php
else: ?>
                <div class="alert alert-info">No valid showtimes available for this movie right now.</div>
            <?php
endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
