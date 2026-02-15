<?php
session_start();
include "db.php";

$conn = getDB();

// Fetch all movies with their shows
$movies_query = "
    SELECT DISTINCT 
        m.id,
        m.title,
        m.genre,
        m.duration_minutes,
        m.poster_url,
        s.show_time,
        s.id as show_id,
        s.price_per_seat,
        s.show_date
    FROM movies m
    LEFT JOIN shows s ON m.id = s.movie_id
    WHERE s.show_date >= CURDATE() OR s.show_date IS NULL
    ORDER BY m.title
";

$result = $conn->query($movies_query);
$movies = [];

while ($row = $result->fetch_assoc()) {
    $movie_id = $row['id'];
    if (!isset($movies[$movie_id])) {
        $movies[$movie_id] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'genre' => $row['genre'],
            'duration' => $row['duration_minutes'],
            'poster' => $row['poster_url'],
            'shows' => []
        ];
    }
    if ($row['show_time']) {
        // Format time for display (12-hour format)
        $display_time = date('h:i A', strtotime($row['show_time']));
        
        $movies[$movie_id]['shows'][] = [
            'time' => $row['show_time'], // Keep original DB format for submission
            'display_time' => $display_time, // Formatted for display
            'show_id' => $row['show_id'],
            'price' => $row['price_per_seat']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Now Showing - ShowGO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .movies-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .movie-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .movie-poster {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .movie-details {
            padding: 20px;
        }
        
        .movie-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .movie-meta {
            color: #666;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
        }
        
        .show-times {
            margin-top: 15px;
        }
        
        .show-time-badge {
            display: inline-block;
            background: #4cc9f0;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .show-time-badge:hover {
            background: #3aa8d0;
            transform: scale(1.05);
        }
        
        .show-time-badge.selected {
            background: #06d6a0;
            box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.3);
        }
        
        .btn-book {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        
        .btn-book:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .no-shows {
            color: #999;
            font-style: italic;
        }
        
        .login-prompt {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo-link">
            <div class="logo"><strong>ShowGO</strong></div>
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="booking.php" class="active">Movies</a></li>
                <li><a href="ticket_rates.php">Ticket Rate</a></li>
                <?php if (isset($_SESSION['users_logged_in']) && $_SESSION['users_logged_in']): ?>
                    <li><a href="user-dashboard.php">Dashboard</a></li>
                    <li><a href="user-logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="user-login.php">Login</a></li>
                    <li><a href="admin-login.php">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <div class="movies-container">
        <h1 style="color: white; font-size: 2.5rem; text-align: center;">Now Showing</h1>
        
        <?php if (!isset($_SESSION['users_logged_in']) || !$_SESSION['users_logged_in']): ?>
            <div class="login-prompt">
                <i class="fas fa-info-circle"></i> Please <a href="user-login.php" style="color: #856404; font-weight: bold;">login</a> to book tickets
            </div>
        <?php endif; ?>
        
        <div class="movies-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <div class="movie-poster">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="movie-details">
                        <h2 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h2>
                        <div class="movie-meta">
                            <span><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?> min</span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($movie['genre']); ?></span>
                        </div>
                        
                        <div class="show-times">
                            <h3 style="color: #666; font-size: 1rem; margin-bottom: 10px;">Show Times:</h3>
                            <?php if (empty($movie['shows'])): ?>
                                <p class="no-shows">No shows available</p>
                            <?php else: ?>
                                <?php foreach ($movie['shows'] as $index => $show): ?>
                                    <button class="show-time-badge" 
                                            onclick="selectShow(<?php echo $movie['id']; ?>, '<?php echo htmlspecialchars($movie['title']); ?>', '<?php echo htmlspecialchars($show['time']); ?>', this)">
                                        <?php echo htmlspecialchars($show['display_time']); ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($_SESSION['users_logged_in']) && $_SESSION['users_logged_in']): ?>
                            <form method="GET" action="book_seats.php" id="form-<?php echo $movie['id']; ?>">
                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                <input type="hidden" name="movie_title" value="<?php echo urlencode($movie['title']); ?>">
                                <input type="hidden" name="show_time" id="show_time_<?php echo $movie['id']; ?>">
                                <button type="submit" class="btn-book" id="bookBtn-<?php echo $movie['id']; ?>" disabled>
                                    Book Now
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="GET" action="user-login.php" id="form-<?php echo $movie['id']; ?>">
                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                <input type="hidden" name="movie_title" value="<?php echo urlencode($movie['title']); ?>">
                                <input type="hidden" name="show_time" id="show_time_<?php echo $movie['id']; ?>">
                                <button type="submit" class="btn-book" id="bookBtn-<?php echo $movie['id']; ?>" disabled>
                                    Login to Book
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <footer>Copyright ©2025 ShowGo</footer>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        let selectedShows = {};
        
        function selectShow(movieId, movieTitle, showTime, element) {
            // Remove selected class from all show buttons of this movie
            const buttons = document.querySelectorAll(`#form-${movieId} ~ .show-times .show-time-badge`);
            buttons.forEach(btn => btn.classList.remove('selected'));
            
            // Add selected class to clicked button
            element.classList.add('selected');
            
            // Store selected show time
            selectedShows[movieId] = showTime;
            
            // Update hidden input with the correct format (from database)
            document.getElementById(`show_time_${movieId}`).value = showTime;
            
            // Enable book button
            document.getElementById(`bookBtn-${movieId}`).disabled = false;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>