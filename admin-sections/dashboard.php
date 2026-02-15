<?php
$movies_count = $conn->query("SELECT COUNT(*) as count FROM movies")->fetch_assoc()['count'];
$bookings_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$recent_movies = $conn->query("SELECT * FROM movies ORDER BY id DESC LIMIT 5");
?>
<div class="dashboard-home">
    <h2>Dashboard Overview</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-film"></i>
            <h3><?php echo $movies_count; ?></h3>
            <p>Total Movies</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-ticket-alt"></i>
            <h3><?php echo $bookings_count; ?></h3>
            <p>Total Bookings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h3><?php echo $users_count; ?></h3>
            <p>Registered Users</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-line"></i>
            <h3>Rs.<?php 
                $revenue = $conn->query("SELECT price as total FROM bookings WHERE status='confirmed'");
                $revenue_data = $revenue->fetch_assoc();
                echo number_format($revenue_data['total'] ?? 0);
            ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="recent-section">
        <h3>Recent Movies Added</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($movie = $recent_movies->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $movie['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                        </td>
                        <td><?php echo $movie['duration_minutes']; ?> min</td>
                        <td>
                            <span class="status-badge status-<?php echo $movie['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $movie['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo $movie['release_date']; ?></td>
                        <td>
                            <a href="admin_dashboard.php?tab=movies&edit=<?php echo $movie['id']; ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>