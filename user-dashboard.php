<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['users_logged_in']) || !$_SESSION['users_logged_in']) {
    header("Location: user-login.php");
    exit();
}

$conn = getDB();
$user_id = $_SESSION['users_id'];

// Check for success message
$success_message = '';
if (isset($_SESSION['booking_success'])) {
    $success_message = 'Your booking has been confirmed! Ticket ID: ' . $_SESSION['booking_success']['ticket_id'];
    // Don't unset it yet - keep for ticket_receipt.php
}

// Fetch bookings from database - FIXED QUERY (removed duplicate b.status)
$stmt = $conn->prepare("
    SELECT 
        b.id as booking_id,
        b.ticket_id,
        b.seat_number,
        b.price as total_price,
        b.booked_at as booking_date,
        b.status,
        s.show_time,
        s.show_date,
        s.hall as theater,
        s.price_per_seat as ticket_price,
        m.title as movie_title,
        m.duration_minutes as duration
    FROM bookings b
    JOIN shows s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Group bookings by ticket_id
$grouped_bookings = [];
foreach ($bookings as $booking) {
    $ticket_id = $booking['ticket_id'];
    
    // If no ticket_id, generate one from booking_id
    if (empty($ticket_id)) {
        $ticket_id = 'BK-' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT);
    }
    $seats_array = !empty($booking['seat_number']) ? explode(',', $booking['seat_number']) : [];
    
    if (!isset($grouped_bookings[$ticket_id])) {
        $grouped_bookings[$ticket_id] = [
            'booking_id' => $booking['booking_id'],
            'ticket_id' => $ticket_id,
            'movie_title' => $booking['movie_title'],
            'show_time' => $booking['show_time'],
            'show_date' => $booking['show_date'],
            'theater' => $booking['theater'],
            'seats' => $seats_array,
            'total_amount' => $booking['total_price'],
            'booking_date' => $booking['booking_date'],
            'status' => $booking['status'],
            'duration' => $booking['duration']
        ];
    }
}

// Calculate statistics
$total_bookings = count($grouped_bookings);
$total_tickets = array_sum(array_map(function($booking) {
    return count($booking['seats']);
}, $grouped_bookings));
$total_spent = array_sum(array_column($bookings, 'total_price'));
$unique_movies = count(array_unique(array_column($bookings, 'movie_title')));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - ShowGO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Your existing CSS styles remain the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: blueviolet;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        nav a:hover, nav a.active {
            background: mediumpurple;
            color: black;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #06d6a0, #4cc9f0);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: #333;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .bookings-grid {
            display: grid;
            gap: 25px;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 5px solid #4cc9f0;
        }
        
        .booking-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            transform: translateY(-3px);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .booking-header h3 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .booking-id {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item h4 {
            color: #666;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-item p {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        
        .seats-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .seat-badge {
            background: white;
            border: 2px solid #4cc9f0;
            color: #4cc9f0;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .amount-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-unknown {
            background: #e9ecef;
            color: #495057;
        }
        
        .total-amount {
            font-size: 1.8rem;
            color: #06d6a0;
            font-weight: bold;
        }
        
        .no-bookings {
            text-align: center;
            padding: 60px 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .no-bookings h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        
        .no-bookings p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        .btn-book-now {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 12px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-book-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        
        .view-receipt-btn {
            display: inline-block;
            background: #4cc9f0;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .view-receipt-btn:hover {
            background: #3aa8d0;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .dashboard-container {
                padding: 10px;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .amount-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">ShowGO</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="booking.php">Movies</a></li>
                    <li><a href="ticket_rates.php">Ticket Rate</a></li>
                    <li><a href="user-dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="user-logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['users_name']); ?>!</h1>
            <p style="margin-top: 10px; opacity: 0.9;">Track your bookings and movie history here</p>
        </div>
        
        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_tickets; ?></div>
                <p>Tickets Booked</p>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unique_movies; ?></div>
                <p>Unique Movies</p>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    Rs.<?php echo number_format($total_spent, 2); ?>
                </div>
                <p>Total Spent</p>
            </div>
        </div>
        
        <!-- Bookings List -->
        <h2 class="section-title">My Bookings</h2>
        
        <?php if(empty($grouped_bookings)): ?>
            <div class="no-bookings">
                <h3>No bookings yet!</h3>
                <p>Start your movie journey by booking your first show.</p>
                <a href="booking.php" class="btn-book-now">Book Your First Movie</a>
            </div>
        <?php else: ?>
            <div class="bookings-grid">
                <?php foreach($grouped_bookings as $ticket_id => $booking): ?>
                <div class="booking-card" data-booking-id="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                    <div class="booking-header">
                        <h3><?php echo htmlspecialchars($booking['movie_title']); ?></h3>
                        <span class="booking-id">Ticket ID: <?php echo $ticket_id; ?></span>
                    </div>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <h4>Show Date & Time</h4>
                            <p>
                                <?php 
                                    echo date('M d, Y', strtotime($booking['show_date'])); 
                                    echo ' | '; 
                                    echo date('h:i A', strtotime($booking['show_time'])); 
                                ?>
                            </p>
                        </div>
                        <div class="detail-item">
                            <h4>Hall</h4>
                            <p><?php echo htmlspecialchars($booking['theater']); ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Duration</h4>
                            <p>
                                <?php 
                                    $hours = floor($booking['duration'] / 60);
                                    $minutes = $booking['duration'] % 60;
                                    echo $hours . 'h ' . $minutes . 'm';
                                ?>
                            </p>
                        </div>
                        <div class="detail-item">
                            <h4>Booked On</h4>
                            <p><?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="seats-section">
                        <strong style="color: #333; margin-right: 10px;">Seats:</strong>
                        <?php if(!empty($booking['seats'])): ?>
                            <?php foreach($booking['seats'] as $seat): ?>
                                <?php $seat = trim($seat); ?>
                                <?php if(!empty($seat)): ?>
                                    <span class="seat-badge"><?php echo htmlspecialchars($seat); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #666;">No seats assigned</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="amount-section">
                        <div>
                            <?php 
                            $status = strtolower($booking['status']);
                            $status_class = 'status-' . $status;
                            if (!in_array($status, ['confirmed', 'pending', 'cancelled'])) {
                                $status_class = 'status-unknown';
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="total-amount">Rs.<?php echo number_format($booking['total_amount'], 2); ?></span>
                            <a href="ticket_receipt.php?ticket_id=<?php echo urlencode($ticket_id); ?>" class="view-receipt-btn" style="margin-left: 15px;">
                                View Receipt
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/realtime.js"></script>
</body>
</html>