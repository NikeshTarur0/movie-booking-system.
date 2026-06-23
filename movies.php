<?php
include 'includes/header.php';

// Fetch all movies
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE title LIKE :search ORDER BY created_at DESC");
    $stmt->execute(['search' => "%$search%"]);
}
else {
    $stmt = $conn->query("SELECT * FROM movies ORDER BY created_at DESC");
}
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>All Movies</h2>
        <form class="d-flex" action="movies.php" method="GET">
            <input class="form-control me-2" type="search" name="search" placeholder="Search movies..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>
    </div>

    <div class="row g-4">
        <?php if (count($movies) > 0): ?>
            <?php foreach ($movies as $movie): ?>
                <div class="col-md-3">
                    <div class="card movie-card h-100 shadow-sm border-0">
                        <?php $poster = $movie['poster_image'] ? 'images/' . $movie['poster_image'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>
                        <img src="<?php echo htmlspecialchars($poster); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title movie-title" title="<?php echo htmlspecialchars($movie['title']); ?>"><?php echo htmlspecialchars($movie['title']); ?></h5>
                            <p class="card-text text-muted small mb-2">Duration: <?php echo $movie['duration']; ?> mins | Rating: <?php echo $movie['rating']; ?>/10</p>
                            <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn btn-outline-primary mt-auto w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php
    endforeach; ?>
        <?php
else: ?>
            <div class="col-12 text-center text-muted">
                <p>No movies found matching your criteria.</p>
            </div>
        <?php
endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
