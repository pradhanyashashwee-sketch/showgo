<?php
session_start();
include "db.php";

// Check if user is logged in and has a completed booking
if (!isset($_SESSION['users_logged_in']) || $_SESSION['users_logged_in'] !== true) {
    header("Location: user-login.php");
    exit();
}

if (!isset($_SESSION['booking_success'])) {
    header("Location: user-dashboard.php");
    exit();
}

$conn = getDB();
$bookingSuccess = $_SESSION['booking_success'];
$bookingInfo = $bookingSuccess['booking_info'];

// Safely retrieve all values
$ticket_id = $bookingSuccess['ticket_id'] ?? 'N/A';
$movieTitle = $bookingInfo['movie_title'] ?? 'N/A';
$showTime = $bookingInfo['show_time'] ?? 'N/A';
$seats = $bookingInfo['seats'] ?? [];
$numSeats = $bookingInfo['num_seats'] ?? count($seats);
$totalAmount = $bookingInfo['total_amount'] ?? 0;

// Format seats as string
$seatsString = is_array($seats) ? implode(', ', $seats) : $seats;

// Get show details from database
$show_details = [];
if (isset($bookingInfo['show_id'])) {
    $query = $conn->query("
        SELECT s.show_date, s.show_time, s.hall, m.title as movie_title 
        FROM shows s 
        JOIN movies m ON s.movie_id = m.id 
        WHERE s.id = {$bookingInfo['show_id']}
    ");
    if ($query && $query->num_rows > 0) {
        $show_details = $query->fetch_assoc();
    }
}

$showDate = isset($show_details['show_date']) ? date('F j, Y', strtotime($show_details['show_date'])) : date('F j, Y');
$hall = $show_details['hall'] ?? 'Hall 1';
$currentDate = date('F j, Y');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowGo - Booking Confirmation</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #ffd700, #ff8e53);
            padding: 25px;
            text-align: center;
            color: #1a1a2e;
        }
        
        .receipt-header h2 {
            margin: 0 0 8px 0;
            font-size: 2rem;
            font-weight: 800;
        }
        
        .receipt-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .confirmation-badge {
            background: #06d6a0;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .receipt-content {
            padding: 25px;
        }
        
        .booking-id {
            text-align: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px dashed #4cc9f0;
        }
        
        .booking-id h3 {
            color: #1a1a2e;
            margin: 0 0 8px 0;
            font-size: 1rem;
            text-transform: uppercase;
        }
        
        .booking-id-code {
            font-size: 1.3rem;
            color: #ff6b6b;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }
        
        .ticket-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            color: #666;
            font-weight: 600;
        }
        
        .detail-value {
            color: #1a1a2e;
            font-weight: 600;
            text-align: right;
        }
        
        .seats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .seat-badge {
            background: #4cc9f0;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .total-amount {
            background: linear-gradient(135deg, #06d6a0, #05b387);
            color: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(6,214,160,0.3);
        }
        
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #4cc9f0, #7209b7);
            margin: 0 auto 15px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            box-shadow: 0 5px 15px rgba(76,201,240,0.3);
        }
        
        .important-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .important-notice h4 {
            color: #856404;
            margin: 0 0 10px 0;
        }
        
        .important-notice p {
            color: #856404;
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .receipt-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn-print, .btn-home {
            flex: 1;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-print {
            background: #4cc9f0;
            color: white;
        }
        
        .btn-print:hover {
            background: #3aa8d0;
            transform: translateY(-2px);
        }
        
        .btn-home {
            background: #7209b7;
            color: white;
        }
        
        .btn-home:hover {
            background: #5a08a3;
            transform: translateY(-2px);
        }
        
        .movie-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 10px;
            text-align: center;
        }
        
        @media print {
            body { background: white; }
            .receipt-actions { display: none; }
            .qr-placeholder { border: 1px solid #ccc; background: none; color: #000; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h2>🎬 ShowGo</h2>
            <p>Booking Confirmed!</p>
            <div class="confirmation-badge">
                <i class="fas fa-check-circle"></i> Payment Successful
            </div>
        </div>
        
        <div class="receipt-content">
            <div class="booking-id">
                <h3>Booking Reference</h3>
                <div class="booking-id-code"><?php echo htmlspecialchars($ticket_id); ?></div>
            </div>
            
            <div class="movie-title"><?php echo htmlspecialchars($movieTitle); ?></div>
            
            <div class="ticket-details">
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar"></i> Date</span>
                    <span class="detail-value"><?php echo $showDate; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-clock"></i> Time</span>
                    <span class="detail-value"><?php echo date('h:i A', strtotime($showTime)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-building"></i> Hall</span>
                    <span class="detail-value"><?php echo htmlspecialchars($hall); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-chair"></i> Seats</span>
                    <div class="seats-container">
                        <?php 
                        foreach ($seats as $seat) {
                            echo '<span class="seat-badge">' . htmlspecialchars($seat) . '</span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-ticket"></i> Tickets</span>
                    <span class="detail-value"><?php echo $numSeats; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar-check"></i> Booked On</span>
                    <span class="detail-value"><?php echo $currentDate; ?></span>
                </div>
            </div>
            
            <div class="total-amount">
                💰 Total: Rs. <?php echo number_format($totalAmount, 2); ?>
            </div>
            
            <div class="qr-section">
                <h3>📱 Digital Ticket</h3>
                <div class="qr-placeholder">
                    <i class="fas fa-qrcode"></i>
                </div>
                <p style="color: #666;">Show this QR at the counter</p>
            </div>
            
            <div class="important-notice">
                <h4><i class="fas fa-exclamation-triangle"></i> Important</h4>
                <p>• Please arrive 30 minutes before showtime</p>
                <p>• Take a screenshot of this receipt</p>
                <p>• Valid ID may be required at entry</p>
            </div>
            
            <div class="receipt-actions">
                <button class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="user-dashboard.php" class="btn-home">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() { window.scrollTo(0, 0); };
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>