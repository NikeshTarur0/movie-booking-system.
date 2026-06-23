<?php
include 'includes/header.php';

// Fetch latest movies
$stmt = $conn->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 4");
$latest_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5 mb-5 text-center">
    <div class="container py-5">
        <h1 class="display-4 fw-bold">Book Your Movie Tickets Now</h1>
        <p class="lead">Experience the best movies in the best quality.</p>
        <a href="movies.php" class="btn btn-light btn-lg mt-3 fw-bold shadow-sm">Browse Movies</a>
    </div>
</div>

<div class="container">
    <h2 class="mb-4">Now Showing</h2>
    <div class="row g-4">
        <?php if (count($latest_movies) > 0): ?>
            <?php foreach ($latest_movies as $movie): ?>
                <div class="col-md-3">
                    <div class="card movie-card h-100 shadow-sm border-0">
                        <?php $poster = $movie['poster_image'] ? 'images/' . $movie['poster_image'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>
                        <img src="<?php echo htmlspecialchars($poster); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title movie-title" title="<?php echo htmlspecialchars($movie['title']); ?>"><?php echo htmlspecialchars($movie['title']); ?></h5>
                            <p class="card-text text-muted small mb-2">Duration: <?php echo $movie['duration']; ?> mins | Rating: <?php echo $movie['rating']; ?>/10</p>
                            <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn btn-outline-primary mt-auto w-100">View Details & Book</a>
                        </div>
                    </div>
                </div>
            <?php
    endforeach; ?>
        <?php
else: ?>
            <div class="col-12 text-center">
                <p class="text-muted">No movies currently showing. Come back later!</p>
            </div>
        <?php
endif; ?>
    </div>
    <div class="text-center mt-5">
        <a href="movies.php" class="btn btn-primary">View All Movies</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
