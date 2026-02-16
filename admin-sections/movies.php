<?php
$edit_movie = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM movies WHERE id=$id");
    $edit_movie = $result->fetch_assoc();
}

// Handle file uploads for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_movie'])) {
        // Handle movie update with file upload
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $duration = (int)$_POST['duration'];
        $release_date = $conn->real_escape_string($_POST['release_date']);
        $status = $conn->real_escape_string($_POST['status']);
        
        // ========== BACKEND VALIDATION ==========
        $errors = [];
        
        // Check if release date is empty for now_showing or coming_soon
        if (empty($release_date) && in_array($status, ['now_showing', 'coming_soon'])) {
            $errors[] = "Release date is required for '" . ucfirst(str_replace('_', ' ', $status)) . "' movies";
        }
        
        // Check if trying to set now_showing with future date
        if (!empty($release_date) && $status === 'now_showing') {
            $today = date('Y-m-d');
            if ($release_date > $today) {
                $errors[] = "Cannot set 'Now Showing' for a future release date";
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: admin_dashboard.php?tab=movies&edit=$id");
            exit();
        }
        
        // Handle file upload
        $poster_url = $edit_movie['poster_url']; // Keep existing poster by default
        
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/posters/';
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
        
        // Handle NULL for empty release_date
        if (empty($release_date)) {
            $sql = "UPDATE movies SET 
                    title='$title', 
                    description='$description', 
                    duration_minutes=$duration, 
                    poster_url='$poster_url', 
                    release_date=NULL, 
                    status='$status' 
                    WHERE id=$id";
        } else {
            $sql = "UPDATE movies SET 
                    title='$title', 
                    description='$description', 
                    duration_minutes=$duration, 
                    poster_url='$poster_url', 
                    release_date='$release_date', 
                    status='$status' 
                    WHERE id=$id";
        }
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Movie updated successfully!";
            header("Location: admin_dashboard.php?tab=movies");
            exit();
        } else {
            $_SESSION['error'] = "Error updating movie: " . $conn->error;
        }
    }
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT * FROM movies";
if ($search) {
    $query .= " WHERE title LIKE '%$search%' OR description LIKE '%$search%'";
}
$query .= " ORDER BY id DESC";
$movies = $conn->query($query);
?>
<div class="section-header">
    <h2>Movies Management</h2>
    <div class="header-actions">
        <form method="GET" class="search-form">
            <input type="hidden" name="tab" value="movies">
            <div class="search-box">
                <input type="text" name="search" placeholder="Search movies..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <button class="btn btn-primary" id="addMovieBtn">
            <i class="fas fa-plus"></i> Add New Movie
        </button>
    </div>
</div>

<?php if ($edit_movie): ?>
<div class="card form-card">
    <h3>Edit Movie</h3>
    <form method="POST" enctype="multipart/form-data" id="editMovieForm">
        <input type="hidden" name="id" value="<?php echo $edit_movie['id']; ?>">
        
        <div class="form-group">
            <label for="edit_title">Movie Title *</label>
            <input type="text" id="edit_title" name="title" value="<?php echo htmlspecialchars($edit_movie['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="edit_description">Description</label>
            <textarea id="edit_description" name="description" rows="4"><?php echo htmlspecialchars($edit_movie['description']); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="edit_duration">Duration (minutes) *</label>
                <input type="number" id="edit_duration" name="duration" value="<?php echo $edit_movie['duration_minutes']; ?>" required>
            </div>
            <div class="form-group">
                <label for="edit_release_date">Release Date <span id="editReleaseRequired" style="color: red; display: none;">*</span></label>
                <input type="date" id="edit_release_date" name="release_date" value="<?php echo $edit_movie['release_date']; ?>">
                <small class="text-muted" id="editReleaseHelp">Required for "Now Showing" and "Coming Soon"</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="edit_poster">Poster Image</label>
            <?php if($edit_movie['poster_url']): ?>
                <div class="current-poster">
                    <img src="<?php echo htmlspecialchars($edit_movie['poster_url']); ?>" 
                         alt="Current Poster" class="preview-poster">
                    <p class="help-text">Current poster</p>
                </div>
            <?php endif; ?>
            <input type="file" id="edit_poster" name="poster" accept="image/*">
            <p class="help-text">Upload new poster (JPG, PNG, GIF - Max 2MB)</p>
            <input type="hidden" name="current_poster" value="<?php echo htmlspecialchars($edit_movie['poster_url']); ?>">
        </div>
        
        <div class="form-group">
            <label for="edit_status">Status *</label>
            <select id="edit_status" name="status" required>
                <option value="now_showing" <?php echo $edit_movie['status'] == 'now_showing' ? 'selected' : ''; ?>>Now Showing</option>
                <option value="coming_soon" <?php echo $edit_movie['status'] == 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                <option value="archived" <?php echo $edit_movie['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>
        
        <div class="form-actions">
            <a href="admin_dashboard.php?tab=movies" class="btn-cancel">Cancel</a>
            <button type="submit" name="update_movie" class="btn-primary">Update Movie</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Poster</th>
                <th>Title</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Release Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($movie = $movies->fetch_assoc()): ?>
            <tr>
                <td><?php echo $movie['id']; ?></td>
                <td>
                    <?php if($movie['poster_url'] && file_exists($movie['poster_url'])): ?>
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             class="table-poster">
                    <?php else: ?>
                        <div class="no-poster">No Image</div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($movie['title']); ?></strong><br>
                    <small class="text-muted"><?php echo substr($movie['description'], 0, 100); ?>...</small>
                </td>
                <td><?php echo $movie['duration_minutes']; ?> min</td>
                <td>
                    <select class="status-select movie-status-select" data-movie-id="<?php echo $movie['id']; ?>" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; background: white; cursor: pointer;">
                        <option value="now_showing" <?php echo $movie['status'] == 'now_showing' ? 'selected' : ''; ?>>Now Showing</option>
                        <option value="coming_soon" <?php echo $movie['status'] == 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                        <option value="archived" <?php echo $movie['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </td>
                <td><?php echo $movie['release_date']; ?></td>
                <td>
                    <a onclick='editMovie(
                        <?php echo $movie['id']; ?>, 
                        "<?php echo addslashes($movie['title']); ?>", 
                        "<?php echo addslashes($movie['description']); ?>", 
                        <?php echo (int)($movie['duration_minutes'] ?? 0); ?>, 
                        "<?php echo $movie['release_date'] ?? ''; ?>", 
                        "<?php echo $movie['status']; ?>"
                    )' class="btn btn-sm btn-warning" style="cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm btn-danger ajax-delete-movie" data-id="<?php echo $movie['id']; ?>" onclick="deleteMovie(<?php echo $movie['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($movies->num_rows == 0): ?>
            <tr>
                <td colspan="7" class="text-center">No movies found. Add your first movie!</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.form-card {
    margin-bottom: 30px;
    padding: 20px;
}

.current-poster {
    margin-bottom: 15px;
}

.preview-poster {
    max-width: 200px;
    max-height: 300px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.help-text {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.table-poster {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 3px;
}

.no-poster {
    width: 60px;
    height: 80px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 12px;
    border-radius: 3px;
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
}
</style>

<script>
// ============================================
// MOVIE FORM VALIDATION - BOTH ADD AND EDIT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // ========== EDIT FORM VALIDATION ==========
    const editReleaseDate = document.getElementById('edit_release_date');
    const editStatus = document.getElementById('edit_status');
    const editForm = document.getElementById('editMovieForm');
    const editReleaseRequired = document.getElementById('editReleaseRequired');
    const editReleaseHelp = document.getElementById('editReleaseHelp');
    const editNowShowing = editStatus?.querySelector('option[value="now_showing"]');

    function updateEditForm() {
        if (!editStatus || !editReleaseDate) return;
        
        const selectedStatus = editStatus.value;
        const selectedDate = editReleaseDate.value;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Show/hide required indicator
        if (selectedStatus === 'now_showing' || selectedStatus === 'coming_soon') {
            if (editReleaseRequired) editReleaseRequired.style.display = 'inline';
            if (editReleaseHelp) editReleaseHelp.style.color = '#dc3545';
            editReleaseDate.setAttribute('required', 'required');
        } else {
            if (editReleaseRequired) editReleaseRequired.style.display = 'none';
            if (editReleaseHelp) editReleaseHelp.style.color = '#6c757d';
            editReleaseDate.removeAttribute('required');
        }
        
        // Disable "Now Showing" if date is in future
        if (editNowShowing && selectedDate) {
            const dateObj = new Date(selectedDate);
            if (dateObj > today) {
                editNowShowing.disabled = true;
                if (editStatus.value === 'now_showing') {
                    editStatus.value = 'coming_soon';
                }
            } else {
                editNowShowing.disabled = false;
            }
        }
    }

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const status = editStatus.value;
            const releaseDate = editReleaseDate.value;
            const errors = [];
            
            if ((status === 'now_showing' || status === 'coming_soon') && !releaseDate) {
                errors.push('Release date is required for "' + status.replace('_', ' ') + '" movies');
            }
            
            if (status === 'now_showing' && releaseDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(releaseDate);
                
                if (selectedDate > today) {
                    errors.push('Cannot set "Now Showing" for a future release date');
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Validation Errors:\n• ' + errors.join('\n• '));
            }
        });
    }

    if (editStatus) {
        editStatus.addEventListener('change', updateEditForm);
    }
    
    if (editReleaseDate) {
        editReleaseDate.addEventListener('change', updateEditForm);
    }
    
    updateEditForm();

    // ========== ADD FORM VALIDATION (MODAL) ==========
    // These elements are in the parent page (admin_dashboard.php)
    // We need to access them through the modal
    
    const addMovieBtn = document.getElementById('addMovieBtn');
    if (addMovieBtn) {
        addMovieBtn.addEventListener('click', function() {
            // Small delay to ensure modal is loaded
            setTimeout(setupAddFormValidation, 100);
        });
    }
    
    function setupAddFormValidation() {
        const addReleaseDate = document.getElementById('release_date');
        const addStatus = document.getElementById('status');
        const addForm = document.getElementById('movieForm');
        const addNowShowing = addStatus?.querySelector('option[value="now_showing"]');
        
        // Create required indicator if it doesn't exist
        let addReleaseRequired = document.getElementById('addReleaseRequired');
        let addReleaseHelp = document.querySelector('#release_date + .help-text');
        
        if (!addReleaseRequired && addReleaseDate) {
            addReleaseRequired = document.createElement('span');
            addReleaseRequired.id = 'addReleaseRequired';
            addReleaseRequired.style.color = 'red';
            addReleaseRequired.style.display = 'none';
            addReleaseRequired.textContent = '*';
            
            const label = document.querySelector('label[for="release_date"]');
            if (label) {
                label.appendChild(addReleaseRequired);
            }
        }
        
        function updateAddForm() {
            if (!addStatus || !addReleaseDate) return;
            
            const selectedStatus = addStatus.value;
            const selectedDate = addReleaseDate.value;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Show/hide required indicator
            if (selectedStatus === 'now_showing' || selectedStatus === 'coming_soon') {
                if (addReleaseRequired) addReleaseRequired.style.display = 'inline';
                if (addReleaseHelp) addReleaseHelp.style.color = '#dc3545';
                addReleaseDate.setAttribute('required', 'required');
            } else {
                if (addReleaseRequired) addReleaseRequired.style.display = 'none';
                if (addReleaseHelp) addReleaseHelp.style.color = '#6c757d';
                addReleaseDate.removeAttribute('required');
            }
            
            // Disable "Now Showing" if date is in future
            if (addNowShowing && selectedDate) {
                const dateObj = new Date(selectedDate);
                if (dateObj > today) {
                    addNowShowing.disabled = true;
                    if (addStatus.value === 'now_showing') {
                        addStatus.value = 'coming_soon';
                    }
                } else {
                    addNowShowing.disabled = false;
                }
            }
        }
        
        if (addForm) {
            // Remove any existing listener to prevent duplicates
            addForm.removeEventListener('submit', handleAddSubmit);
            addForm.addEventListener('submit', handleAddSubmit);
        }
        
        function handleAddSubmit(e) {
            const status = addStatus.value;
            const releaseDate = addReleaseDate.value;
            const errors = [];
            
            if ((status === 'now_showing' || status === 'coming_soon') && !releaseDate) {
                errors.push('Release date is required for "' + status.replace('_', ' ') + '" movies');
            }
            
            if (status === 'now_showing' && releaseDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(releaseDate);
                
                if (selectedDate > today) {
                    errors.push('Cannot set "Now Showing" for a future release date');
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Validation Errors:\n• ' + errors.join('\n• '));
                return false;
            }
        }
        
        if (addStatus) {
            addStatus.removeEventListener('change', updateAddForm);
            addStatus.addEventListener('change', updateAddForm);
        }
        
        if (addReleaseDate) {
            addReleaseDate.removeEventListener('change', updateAddForm);
            addReleaseDate.addEventListener('change', updateAddForm);
        }
        
        updateAddForm();
    }
    
    // Try to setup add form validation immediately in case modal is already loaded
    setTimeout(setupAddFormValidation, 500);
});

// Preview image before upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('edit_poster');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Remove current preview if exists
                    let currentPreview = document.querySelector('.current-poster');
                    if (currentPreview) {
                        currentPreview.innerHTML = '';
                    } else {
                        currentPreview = document.createElement('div');
                        currentPreview.className = 'current-poster';
                        fileInput.parentNode.insertBefore(currentPreview, fileInput);
                    }
                    
                    // Create new preview
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-poster';
                    img.alt = 'New Poster Preview';
                    
                    const text = document.createElement('p');
                    text.className = 'help-text';
                    text.textContent = 'New poster preview';
                    
                    currentPreview.appendChild(img);
                    currentPreview.appendChild(text);
                }
                reader.readAsDataURL(file);
            }
        });
    }
});

// Edit movie function (from parent page)
function editMovie(id, title, description, duration, releaseDate, status) {
    if (window.opener && window.opener.editMovie) {
        window.opener.editMovie(id, title, description, duration, releaseDate, status);
    } else {
        // Fallback: redirect to edit URL
        window.location.href = 'admin_dashboard.php?tab=movies&edit=' + id;
    }
}
</script>
