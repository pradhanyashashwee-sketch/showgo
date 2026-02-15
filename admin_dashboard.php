<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['login_error'] = 'Please login to access dashboard';
    header('Location: admin-login.php');
    exit();
}
require_once 'db.php';
$conn = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle lightweight AJAX actions (delete movie/showtime/user)
    if (isset($_POST['ajax_action'])) {
        $action = $_POST['ajax_action'];
        if ($action === 'update_movie_status' && isset($_POST['id']) && isset($_POST['status'])) {
            $id = (int)$_POST['id'];
            $status = $conn->real_escape_string($_POST['status']);
            $ok = $conn->query("UPDATE movies SET status='$status' WHERE id=$id");
            echo json_encode(['success' => (bool)$ok]);
            exit();
        }
        if ($action === 'delete_movie' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $ok = $conn->query("DELETE FROM movies WHERE id=$id");
            echo json_encode(['success' => (bool)$ok]);
            exit();
        }
        if ($action === 'delete_showtime' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $ok = $conn->query("DELETE FROM showtimes WHERE id=$id");
            echo json_encode(['success' => (bool)$ok]);
            exit();
        }
        if ($action === 'delete_user' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $ok = $conn->query("DELETE FROM users WHERE id=$id");
            echo json_encode(['success' => (bool)$ok]);
            exit();
        }
    }

    if (isset($_POST['add_movie'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $duration = (int)$_POST['duration'];
    $release_date = $conn->real_escape_string($_POST['release_date']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $poster_url = 'images/default.jpg'; // Default poster
    
    // Handle file upload
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['poster']['name']);
        $targetFile = $uploadDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['poster']['tmp_name']);
        if ($check !== false) {
            // Allow certain file formats
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
                    $poster_url = $targetFile;
                }
            }
        }
    }
    
    $sql = "INSERT INTO movies (title, description, duration_minutes, poster_url, release_date, status) 
            VALUES ('$title', '$description', $duration, '$poster_url', '$release_date', '$status')";
    if ($conn->query($sql)) {
        $_SESSION['message'] = "Movie added successfully!";
    } else {
        $_SESSION['error'] = "Error adding movie: " . $conn->error;
    }
    header("Location: admin_dashboard.php?tab=movies");
    exit();
}
    if (isset($_POST['update_movie'])) {
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $duration = (int)$_POST['duration'];
        $release_date = $conn->real_escape_string($_POST['release_date']);
        $status = $conn->real_escape_string($_POST['status']);

        // Get existing poster URL from DB to avoid undefined POST key
        $poster_url = 'images/default.jpg';
        $resPoster = $conn->query("SELECT poster_url FROM movies WHERE id=$id LIMIT 1");
        if ($resPoster && $rowPoster = $resPoster->fetch_assoc()) {
            if (!empty($rowPoster['poster_url'])) {
                $poster_url = $rowPoster['poster_url'];
            }
        }

        // Handle file upload for update (if a new file was sent)
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['poster']['name']);
            $targetFile = $uploadDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            $check = getimagesize($_FILES['poster']['tmp_name']);
            if ($check !== false) {
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
                        $poster_url = $targetFile;
                    }
                }
            }
        }

        $sql = "UPDATE movies SET 
                title='$title', 
                description='$description', 
                duration_minutes=$duration, 
                poster_url='$poster_url', 
                release_date='$release_date', 
                status='$status' 
                WHERE id=$id";

        if ($conn->query($sql)) {
            $_SESSION['message'] = "Movie updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating movie: " . $conn->error;
        }
        header("Location: admin_dashboard.php?tab=movies");
        exit();
    }
    if (isset($_POST['delete_movie'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM movies WHERE id=$id";
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Movie deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting movie: " . $conn->error;
        }
        header("Location: admin_dashboard.php");
        exit();
    }
    if (isset($_POST['add_showtime'])) {
        $movie_id = (int)$_POST['movie_id'];
        $show_time = $conn->real_escape_string($_POST['show_time']);
        $show_date = $conn->real_escape_string($_POST['show_date']);
        $theater = $conn->real_escape_string($_POST['theater']);
        $price = (float)$_POST['price'];
        $available_seats = (int)$_POST['available_seats'];
        
        $sql = "INSERT INTO showtimes (movie_id, show_time, show_date, theater, price, available_seats) 
                VALUES ($movie_id, '$show_time', '$show_date', '$theater', $price, $available_seats)";
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Showtime added successfully!";
        } else {
            $_SESSION['error'] = "Error adding showtime: " . $conn->error;
        }
        header("Location: admin_dashboard.php?tab=showtimes");
        exit();
    }
    if (isset($_POST['update_showtime'])) {
        $id = (int)$_POST['id'];
        $movie_id = (int)$_POST['movie_id'];
        $show_time = $conn->real_escape_string($_POST['show_time']);
        $show_date = $conn->real_escape_string($_POST['show_date']);
        $theater = isset($_POST['theater']) ? $conn->real_escape_string($_POST['theater']) : '';
        $price = (float)$_POST['price'];
        $available_seats = (int)$_POST['available_seats'];

        $sql = "UPDATE showtimes SET movie_id=$movie_id, show_time='$show_time', show_date='$show_date', theater='".$theater."', price=$price, available_seats=$available_seats WHERE id=$id";
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Showtime updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating showtime: " . $conn->error;
        }
        header("Location: admin_dashboard.php?tab=showtimes");
        exit();
    }
    if (isset($_POST['add_user'])) {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
        if (empty($password)) $errors[] = "Password is required";
        elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
        elseif ($password !== $confirm_password) $errors[] = "Passwords do not match";
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check_email->num_rows > 0) $errors[] = "Email already exists";
        if (empty($errors)) {
            $sql = "INSERT INTO users (full_name, email, password) VALUES ('$full_name', '$email', '$password')";
            if ($conn->query($sql)) {
                $_SESSION['message'] = "User added successfully!";
                header("Location: admin_dashboard.php?tab=users");
                exit();
            }
        }
        $_SESSION['error'] = empty($errors) ? "Error adding user" : implode("<br>", $errors);
        header("Location: admin_dashboard.php?tab=users");
        exit();
    }
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['id'];
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'] ?? '';
        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $id");
        if ($check_email->num_rows > 0) $errors[] = "Email already exists";
        if (!empty($password) && strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
        if (empty($errors)) {
            if (!empty($password)) {
                $sql = "UPDATE users SET full_name = '$full_name', email = '$email', password = '$password' WHERE id = $id";
            } else {
                $sql = "UPDATE users SET full_name = '$full_name', email = '$email' WHERE id = $id";
            }
            if ($conn->query($sql)) {
                $_SESSION['message'] = "User updated successfully!";
                header("Location: admin_dashboard.php?tab=users");
                exit();
            }
        }
        $_SESSION['error'] = empty($errors) ? "Error updating user" : implode("<br>", $errors);
        header("Location: admin_dashboard.php?tab=users");
        exit();
    }

}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShowGo</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
    <style>
/* Form Container Styling */
.form-card {
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    border: none;
    position: relative;
    overflow: hidden;
}

.form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #667eea, #764ba2, #06beb6);
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

.form-card h3 {
    color: #2d3436;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 30px;
    position: relative;
    padding-bottom: 15px;
}

.form-card h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

/* Form Group Styling */
.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2d3436;
    font-size: 16px;
    transition: all 0.3s;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

.form-group input:hover,
.form-group textarea:hover,
.form-group select:hover {
    border-color: #adb5bd;
}

/* Form Row for side-by-side inputs */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

/* File Upload Styling */
input[type="file"] {
    padding: 15px;
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

input[type="file"]:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

input[type="file"]::file-selector-button {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    margin-right: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

input[type="file"]::file-selector-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Form Actions - Button Container */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 20px;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f1f3f5;
}

/* Cancel Button */
.btn-cancel {
    padding: 16px 32px;
    background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
    color: #495057;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 140px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

.btn-cancel::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s;
}

.btn-cancel:hover {
    background: linear-gradient(135deg, #e9ecef, #adb5bd);
    color: #212529;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.btn-cancel:hover::before {
    left: 100%;
}

.btn-cancel:active {
    transform: translateY(-1px);
}

/* Update/Submit Button */
.btn-primary {
    padding: 16px 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 140px;
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:active {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(102, 126, 234, 0.3);
}

/* Button Icons */
.btn-cancel i, .btn-primary i {
    margin-right: 10px;
    font-size: 18px;
}

/* Current Poster Preview */
.current-poster {
    text-align: center;
    margin-bottom: 25px;
}

.preview-poster {
    width: 100%;
    max-width: 300px;
    height: auto;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    transition: all 0.3s;
}

.preview-poster:hover {
    transform: scale(1.02);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

/* Help Text */
.help-text {
    font-size: 14px;
    color: #6c757d;
    margin-top: 8px;
    font-style: italic;
}

/* Animation */
@keyframes gradientShift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

/* Floating Label Effect (Optional Enhancement) */
.form-group.floating-label {
    position: relative;
}

.form-group.floating-label label {
    position: absolute;
    top: 20px;
    left: 20px;
    background: white;
    padding: 0 10px;
    transition: all 0.3s;
    pointer-events: none;
    color: #6c757d;
}

.form-group.floating-label input:focus + label,
.form-group.floating-label input:not(:placeholder-shown) + label {
    top: -12px;
    left: 15px;
    font-size: 14px;
    color: #667eea;
    font-weight: 600;
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-card {
        padding: 20px;
        margin: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-cancel,
    .btn-primary {
        width: 100%;
        min-width: unset;
    }
    
    .form-card h3 {
        font-size: 24px;
    }
}

/* Optional: Add glow effect on focus */
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    animation: glow 1.5s infinite alternate;
}

@keyframes glow {
    from {
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    }
    to {
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
}
</style>
</style>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>ShowGo Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo $tab == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin_dashboard.php?tab=movies" class="<?php echo $tab == 'movies' ? 'active' : ''; ?>">
                        <i class="fas fa-film"></i> Movies</a></li>
                    <li><a href="admin_dashboard.php?tab=showtimes" class="<?php echo $tab == 'showtimes' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Showtimes</a></li>
                    <li><a href="admin_dashboard.php?tab=bookings" class="<?php echo $tab == 'bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i> Bookings</a></li>
                    <li><a href="admin_dashboard.php?tab=users" class="<?php echo $tab == 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users</a></li>
                    <li><a href="index.php" target="_blank"><i class="fas fa-eye"></i> View Site</a></li>
                    <li><a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <header class="top-header">
                <h1>Admin Dashboard</h1>
                <div class="header-actions">
                    <div class="admin-profile">
                        <img src="images/admin.jpeg<?php echo urlencode($_SESSION['admin_username'] ?? 'Admin'); ?>&background=4A90E2&color=fff" alt="Admin">
                    </div>
                </div>
            </header>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            <div class="content-area">
                <?php
                switch($tab) {
                    case 'movies':
                        include 'admin-sections/movies.php';
                        break;
                    case 'showtimes':
                        include 'admin-sections/showtimes.php';
                        break;
                    case 'bookings':
                        include 'admin-sections/bookings.php';
                        break;
                    case 'users':
                        include 'admin-sections/users.php';
                        break;
                    default:
                        include 'admin-sections/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    <div id="movieModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="modalTitle">Add New Movie</h3>
        <form id="movieForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="movieId" name="id">
            
            <div class="form-group">
                <label for="title">Movie Title *</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" required>
                </div>
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="date" id="release_date" name="release_date">
                </div>
            </div>
            
            <div class="form-group">
                <label for="poster">Poster Image</label>
                <input type="file" id="poster" name="poster" accept="image/*">
                <p class="help-text">Upload poster (JPG, PNG, GIF - Max 2MB)</p>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="now_showing">Now Showing</option>
                    <option value="coming_soon">Coming Soon</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel close-modal">Cancel</button>
                <button type="submit" name="add_movie" id="submitBtn" class="btn-primary">Add Movie</button>
            </div>
        </form>
    </div>
</div>
    <script src="js/admin-script.js"></script>
    <script src="js/realtime.js"></script>
    <script>
    // Fallback delete functions in case event binding didn't attach
    async function deleteMovie(id) {
        if (!confirm('Delete this movie?')) return;
        try {
            const fd = new FormData(); fd.append('ajax_action','delete_movie'); fd.append('id', id);
            const res = await fetch('admin_dashboard.php', {method:'POST', body: fd});
            const j = await res.json();
            if (j.success) {
                const btn = document.querySelector('.ajax-delete-movie[data-id="'+id+'"]');
                if (btn) btn.closest('tr').remove();
                try{ if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate(); }catch(e){}
            } else alert('Delete failed');
        } catch (e) { console.error(e); alert('Delete failed'); }
    }
    async function deleteShowtime(id) {
        if (!confirm('Delete this showtime?')) return;
        try {
            const fd = new FormData(); fd.append('ajax_action','delete_showtime'); fd.append('id', id);
            const res = await fetch('admin_dashboard.php', {method:'POST', body: fd});
            const j = await res.json();
            if (j.success) {
                const btn = document.querySelector('.ajax-delete-showtime[data-id="'+id+'"]');
                if (btn) btn.closest('tr').remove();
                try{ if (window.triggerRealtimeUpdate) window.triggerRealtimeUpdate(); }catch(e){}
            } else alert('Delete failed');
        } catch (e) { console.error(e); alert('Delete failed'); }
    }
    async function deleteUser(id) {
        if (!confirm('Delete this user?')) return;
        try {
            const fd = new FormData(); fd.append('ajax_action','delete_user'); fd.append('id', id);
            const res = await fetch('admin_dashboard.php', {method:'POST', body: fd});
            const j = await res.json();
            if (j.success) {
                const btn = document.querySelector('.ajax-delete-user[data-id="'+id+'"]');
                if (btn) btn.closest('tr').remove();
            } else alert('Delete failed');
        } catch (e) { console.error(e); alert('Delete failed'); }
    }
    window.deleteMovie = deleteMovie;
    window.deleteShowtime = deleteShowtime;
    window.deleteUser = deleteUser;
    </script>
</body>
</html>