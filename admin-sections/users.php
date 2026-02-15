<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Post processing is now done in admin_dashboard.php
$edit_user = null;
if ($action === 'edit' && $user_id > 0) {
    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
}
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}
$total_result = $conn->query("SELECT COUNT(*) as total FROM users $where_clause");
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$query = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$users = $conn->query($query);
?>
<div class="section-header">
    <h2>Users Management</h2>
    <div class="header-actions">
        <form method="GET" class="search-form">
            <input type="hidden" name="tab" value="users">
            <div class="search-box">
                <input type="text" name="search" placeholder="Search users..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       onkeyup="if(event.keyCode===13) this.form.submit()">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <button class="btn btn-primary" id="addUserBtn">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>
</div>
<?php if ($action === 'add' || ($action === 'edit' && $edit_user)): ?>
<div class="card form-card">
    <h3><?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?></h3>
    <form method="POST">
        <?php if ($edit_user): ?>
            <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
            </div>
        </div>    
        <div class="form-row">
            <div class="form-group">
                <label for="password">
                    <?php echo $action === 'add' ? 'Password *' : 'New Password (leave blank to keep current)'; ?>
                </label>
                <input type="password" id="password" name="password" 
                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                <small class="text-muted">Minimum 6 characters</small>
            </div>    
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
        </div>
        <div class="form-actions">
            <a href="admin_dashboard.php?tab=users" class="btn-cancel">Cancel</a>
            <button type="submit" name="<?php echo $action === 'add' ? 'add_user' : 'update_user'; ?>" 
                    class="btn-primary">
                <?php echo $action === 'add' ? 'Add User' : 'Update User'; ?>
            </button>
        </div>
    </form>
</div>
<?php endif; ?>
<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3><?php echo $total_users; ?></h3>
        <p>Total Users</p>
    </div>
    <?php
    $active_users = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM bookings WHERE user_id IS NOT NULL");
    $active_count = $active_users->fetch_assoc()['count'];
    ?>
    <div class="stat-card">
        <i class="fas fa-ticket-alt"></i>
        <h3><?php echo $active_count; ?></h3>
        <p>Active Users (with bookings)</p>
    </div>
    <?php
    $recent_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $recent_count = $recent_users->fetch_assoc()['count'];
    ?>
    
    <div class="stat-card">
        <i class="fas fa-user-plus"></i>
        <h3><?php echo $recent_count; ?></h3>
        <p>New Users (30 days)</p>
    </div>
</div>
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Bookings</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($users->num_rows > 0):
                while($user = $users->fetch_assoc()): 
                    $booking_count = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = {$user['id']}");
                    $bookings = $booking_count->fetch_assoc()['count'];
            ?>
            <tr>
                <td>#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <?php if ($bookings > 0): ?>
                        <a href="admin_dashboard.php?tab=bookings&user_id=<?php echo $user['id']; ?>" 
                           class="btn btn-sm btn-info" title="View bookings">
                            <i class="fas fa-eye"></i> <?php echo $bookings; ?> booking(s)
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No bookings</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    if ($user['created_at']) {
                        echo date('M d, Y', strtotime($user['created_at']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td>
                    <a href="admin_dashboard.php?tab=users&action=edit&id=<?php echo $user['id']; ?>" 
                       class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    
                    <?php if ($bookings == 0): ?>
                    <button class="btn btn-sm btn-danger ajax-delete-user" data-id="<?php echo $user['id']; ?>" onclick="deleteUser(<?php echo $user['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-secondary" disabled 
                            title="Cannot delete user with bookings">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php 
                endwhile;
            else: 
            ?>
            <tr>
                <td colspan="6" class="text-center">
                    <?php if (!empty($search)): ?>
                        No users found matching "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        No users found. Add your first user!
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin_dashboard.php?tab=users&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>
        <span class="page-info">
            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
        </span> 
        <?php if ($page < $total_pages): ?>
            <a href="admin_dashboard.php?tab=users&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            window.location.href = 'admin_dashboard.php?tab=users&action=add';
        });
    }
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});
</script>
<style>
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    gap: 15px;
}
.pagination a {
    padding: 8px 15px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.pagination a:hover {
    background: #2980b9;
}
.page-info {
    padding: 8px 15px;
    background: #f8f9fa;
    border-radius: 5px;
    font-weight: 600;
}
.btn-secondary:disabled {
    background-color: #95a5a6;
    cursor: not-allowed;
}
.btn-info {
    background-color: #17a2b8;
    color: white;
}
.btn-info:hover {
    background-color: #138496;
}
.text-muted {
    color: #6c757d;
    font-size: 0.9em;
}
small.text-muted {
    display: block;
    margin-top: 5px;
    font-size: 0.8em;
    color: #6c757d;
}
</style>