<?php
// ============================================
// COMPLETE FIXED SHOWS MANAGEMENT
// Using 'shows' table (NOT showtimes)
// ============================================

// Get all movies for dropdown
$movies = $conn->query("SELECT id, title FROM movies ORDER BY title");
if (!$movies) {
    die("Error fetching movies: " . $conn->error);
}

// Handle edit mode - using 'shows' table
$edit_show = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM shows WHERE id = $eid LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $edit_show = $res->fetch_assoc();
    } elseif ($res === false) {
        echo "<div class='alert alert-danger'>Error fetching show: " . $conn->error . "</div>";
    }
}

// Get all shows with movie titles - using 'shows' table
$shows = $conn->query("
    SELECT s.*, m.title as movie_title 
    FROM shows s 
    JOIN movies m ON s.movie_id = m.id 
    ORDER BY s.show_date DESC, s.show_time DESC
");

if (!$shows) {
    echo "<div class='alert alert-danger'>Error fetching shows: " . $conn->error . "</div>";
    $shows = null;
}
?>

<!-- ========== EDIT SHOW FORM ========== -->
<?php if ($edit_show): ?>
<div class="card form-card">
    <h3><i class="fas fa-edit"></i> Edit Show #<?php echo $edit_show['id']; ?></h3>
    
    <form method="POST" action="admin_dashboard.php?tab=showtimes">
        <input type="hidden" name="id" value="<?php echo $edit_show['id']; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="edit_movie_id">Movie *</label>
                <select id="edit_movie_id" name="movie_id" required class="form-control">
                    <option value="">-- Select Movie --</option>
                    <?php 
                    // Reset movies pointer
                    $movies = $conn->query("SELECT id, title FROM movies ORDER BY title");
                    while($mrow = $movies->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $mrow['id']; ?>" 
                            <?php echo $mrow['id'] == $edit_show['movie_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mrow['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_show_date">Date *</label>
                <input type="date" id="edit_show_date" name="show_date" 
                       value="<?php echo $edit_show['show_date']; ?>" required class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="edit_show_time">Time *</label>
                <input type="time" id="edit_show_time" name="show_time" 
                       value="<?php echo date('H:i', strtotime($edit_show['show_time'])); ?>" 
                       required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_hall">Hall *</label>
                <input type="text" id="edit_hall" name="hall" 
                       value="<?php echo htmlspecialchars($edit_show['hall'] ?? 'Hall 1'); ?>" 
                       required class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="edit_price">Price per Seat (Rs.) *</label>
                <input type="number" step="0.01" id="edit_price" name="price_per_seat" 
                       value="<?php echo $edit_show['price_per_seat']; ?>" 
                       min="0" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_available_seats">Available Seats *</label>
                <input type="number" id="edit_available_seats" name="available_seats" 
                       value="<?php echo $edit_show['available_seats']; ?>" 
                       min="0" required class="form-control">
            </div>
        </div>
        
        <div class="form-actions">
            <a href="admin_dashboard.php?tab=showtimes" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" name="update_show" class="btn-primary">
                <i class="fas fa-save"></i> Update Show
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ========== ADD NEW SHOW FORM ========== -->
<div class="section-header">
    <h2>Shows Management</h2>
    <p class="text-muted">Manage movie shows and schedules</p>
</div>

<div class="card form-card">
    <h3><i class="fas fa-plus-circle"></i> Add New Show</h3>
    
    <form method="POST" action="admin_dashboard.php?tab=showtimes">
        <div class="form-row">
            <div class="form-group">
                <label for="movie_id">Movie *</label>
                <select id="movie_id" name="movie_id" required class="form-control">
                    <option value="">-- Select Movie --</option>
                    <?php 
                    // Reset movies pointer
                    $movies = $conn->query("SELECT id, title FROM movies ORDER BY title");
                    while($movie = $movies->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $movie['id']; ?>">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="show_date">Date *</label>
                <input type="date" id="show_date" name="show_date" required class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="show_time">Time *</label>
                <input type="time" id="show_time" name="show_time" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="hall">Hall *</label>
                <input type="text" id="hall" name="hall" value="Hall 1" required class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price per Seat (Rs.) *</label>
                <input type="number" step="0.01" id="price" name="price_per_seat" 
                       min="0" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="available_seats">Available Seats *</label>
                <input type="number" id="available_seats" name="available_seats" 
                       value="50" min="1" required class="form-control">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="reset" class="btn-cancel">
                <i class="fas fa-undo"></i> Reset
            </button>
            <button type="submit" name="add_show" class="btn-primary">
                <i class="fas fa-plus"></i> Add Show
            </button>
        </div>
    </form>
</div>

<!-- ========== SHOWS LIST TABLE ========== -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> All Shows</h3>
        <span class="badge badge-info">Total: <?php echo $shows ? $shows->num_rows : 0; ?> shows</span>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Movie</th>
                <th>Date</th>
                <th>Time</th>
                <th>Hall</th>
                <th>Price</th>
                <th>Available Seats</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($shows && $shows->num_rows > 0): ?>
                <?php while($show = $shows->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?php echo $show['id']; ?></strong></td>
                    <td>
                        <strong><?php echo htmlspecialchars($show['movie_title']); ?></strong>
                    </td>
                    <td><?php echo date('d M Y', strtotime($show['show_date'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($show['show_time'])); ?></td>
                    <td><?php echo htmlspecialchars($show['hall'] ?? 'Hall 1'); ?></td>
                    <td>Rs. <?php echo number_format($show['price_per_seat'], 2); ?></td>
                    <td>
                        <?php 
                        $seat_class = 'badge-success';
                        $seat_text = $show['available_seats'] . ' seats';
                        
                        if ($show['available_seats'] <= 0) {
                            $seat_class = 'badge-danger';
                            $seat_text = 'Sold Out';
                        } elseif ($show['available_seats'] <= 10) {
                            $seat_class = 'badge-warning';
                            $seat_text = 'Only ' . $show['available_seats'] . ' left';
                        }
                        ?>
                        <span class="badge <?php echo $seat_class; ?>">
                            <?php echo $seat_text; ?>
                        </span>
                    </td>
                    <td>
                        <a href="admin_dashboard.php?tab=showtimes&edit=<?php echo $show['id']; ?>" 
                           class="btn btn-sm btn-warning" title="Edit Show">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button class="btn btn-sm btn-danger ajax-delete-show" 
                                data-id="<?php echo $show['id']; ?>" 
                                onclick="deleteShow(<?php echo $show['id']; ?>)"
                                title="Delete Show">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div style="padding: 40px; text-align: center;">
                            <i class="fas fa-calendar-times fa-3x" style="color: #adb5bd; margin-bottom: 15px;"></i>
                            <h4 style="color: #495057; margin-bottom: 10px;">No Shows Found</h4>
                            <p style="color: #6c757d;">Add your first show using the form above.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.text-muted {
    color: #6c757d;
    font-size: 14px;
}

.fancy-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.fancy-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}
</style>

<script>
// Set default date and time for add form
document.addEventListener('DOMContentLoaded', function() {
    const dateEl = document.getElementById('show_date');
    const timeEl = document.getElementById('show_time');
    const now = new Date();
    
    if (dateEl) {
        dateEl.valueAsDate = now;
        dateEl.min = now.toISOString().slice(0, 10);
    }
    
    if (timeEl) {
        const nextHour = new Date(now);
        nextHour.setHours(nextHour.getHours() + 1);
        nextHour.setMinutes(0);
        timeEl.value = nextHour.toTimeString().slice(0, 5);
    }
});

// Delete show function
async function deleteShow(id) {
    if (!confirm('Delete this show? This will also delete all associated bookings.')) return;
    
    try {
        const fd = new FormData();
        fd.append('ajax_action', 'delete_show');
        fd.append('id', id);
        
        const res = await fetch('admin_dashboard.php', {
            method: 'POST',
            body: fd
        });
        
        const j = await res.json();
        
        if (j.success) {
            const btn = document.querySelector(`.ajax-delete-show[data-id="${id}"]`);
            if (btn) {
                const row = btn.closest('tr');
                row.style.backgroundColor = '#f8d7da';
                setTimeout(() => row.remove(), 300);
            }
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.innerHTML = '<i class="fas fa-check-circle"></i> Show deleted successfully!';
            document.querySelector('.content-area').insertBefore(alert, document.querySelector('.table-container'));
            
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('Delete failed: ' + (j.error || 'Unknown error'));
        }
    } catch (e) {
        console.error(e);
        alert('Delete failed: ' + e.message);
    }
}

// Make function globally available
window.deleteShow = deleteShow;
</script>