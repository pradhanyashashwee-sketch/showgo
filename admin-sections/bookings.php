<?php
$bookings = $conn->query("SELECT b.*, m.title as movie_title, 
                          s.show_date, s.show_time 
                          FROM bookings b 
                          JOIN shows s ON b.showtime_id = s.id 
                          JOIN movies m ON s.movie_id = m.id 
                          ORDER BY b.booked_at DESC");
if (!$bookings) {
    echo "<div class='alert alert-danger'>Error fetching bookings: " . $conn->error . "</div>";
    exit();
}                          
?>
<div class="section-header">
    <h2>Bookings Management</h2>
</div>
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Movie</th>
                <th>Show Date</th>
                <th>Show Time</th>
                <th>Seats</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Booking Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($booking = $bookings->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                <td><?php echo $booking['show_date']; ?></td>
                <td><?php echo date('h:i A', strtotime($booking['show_time'])); ?></td>
                <td><?php echo $booking['seat_number']; ?></td>
                <td>Rs.<?php echo number_format($booking['price'], 2); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </td>
                <td><?php echo date('Y M, d', strtotime($booking['booked_at'])); ?></td>
                <td>
                    <select class="status-select" data-booking-id="<?php echo $booking['id']; ?>">
                        <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>