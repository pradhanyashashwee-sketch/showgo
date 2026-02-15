<?php
session_start();
include "db.php";
if (!isset($_SESSION['users_logged_in']) || !$_SESSION['users_logged_in']) {
    header("Location: user-login.php");
    exit();
}
if (!isset($_SESSION['last_booking'])) {
    header("Location: index.php");
    exit();
}

$booking = $_SESSION['last_booking'];
$conn = getDB();
$stmt = $conn->prepare("SELECT * FROM bookings WHERE ticket_id LIKE ? AND user_id = ?");
$search_ticket = $booking['ticket_id'] . '%';
$stmt->bind_param("si", $search_ticket, $_SESSION['users_id']);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - ShowGO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .ticket {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .ticket:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.1)" d="M0,0 L100,0 L100,100 Z"/></svg>');
            background-size: cover;
        }
        
        .ticket-content {
            position: relative;
            z-index: 1;
        }
        
        .ticket-hole {
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .ticket-hole.left {
            left: -10px;
        }
        
        .ticket-hole.right {
            right: -10px;
        }
        
        .booking-details {
            background: white;
            color: #333;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .details-table th, .details-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .details-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-print, .btn-dashboard, .btn-home {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #6c757d;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-dashboard {
            background: #0d6efd;
            color: white;
        }
        
        .btn-home {
            background: #198754;
            color: white;
        }
        
        .btn-print:hover, .btn-dashboard:hover, .btn-home:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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
                <li><a href="booking.php">Movies</a></li>
                <li><a href="ticket_rates.php">Ticket Rate</a></li>
                <li><a href="user-dashboard.php">Dashboard</a></li>
                <li><a href="user-logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        <p class="lead">Your tickets have been booked successfully.</p>
        <div class="ticket">
            <div class="ticket-hole left"></div>
            <div class="ticket-hole right"></div>
            
            <div class="ticket-content">
                <h2><?php echo htmlspecialchars($booking['movie_title']); ?></h2>
                <div style="display: flex; justify-content: space-around; margin: 30px 0;">
                    <div>
                        <p><strong>Booking ID:</strong><br>
                           <?php echo $booking['ticket_id']; ?></p>
                        <p><strong>Date:</strong><br>
                           <?php echo date('M d, Y', strtotime($booking['show_date'])); ?></p>
                    </div>
                    <div>
                        <p><strong>Time:</strong><br>
                           <?php echo $booking['show_time']; ?></p>
                        <p><strong>Seats:</strong><br>
                           <?php echo implode(', ', $booking['seats']); ?></p>
                    </div>
                </div>
                
                <hr style="border-color: rgba(255,255,255,0.3);">
                
                <h3 style="margin-top: 20px;">Total Amount: Rs.<?php echo $booking['total_price']; ?></h3>
                
                <p style="margin-top: 20px; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Please arrive 30 minutes before showtime
                </p>
            </div>
        </div>
        <div class="booking-details">
            <h3>Booking Details</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Seat Number</th>
                        <th>Ticket ID</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $b): ?>
                    <tr>
                        <td><?php echo $b['seat_number']; ?></td>
                        <td><?php echo $b['ticket_id']; ?></td>
                        <td>₹<?php echo $b['price']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th>₹<?php echo $booking['total_price']; ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="action-buttons">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <a href="user-dashboard.php" class="btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4>Important Information</h4>
            <ul style="text-align: left; max-width: 600px; margin: 0 auto;">
                <li>Please carry a valid ID proof along with this e-ticket</li>
                <li>Tickets are non-transferable and non-refundable</li>
                <li>Children below 3 years are free (without seat)</li>
                <li>For any queries, contact support@showgo.com</li>
            </ul>
        </div>
    </div>
    <?php unset($_SESSION['last_booking']); ?>
    <script>
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                header, .action-buttons, .booking-details {
                    display: none !important;
                }
                .ticket {
                    margin: 0 !important;
                    box-shadow: none !important;
                }
                body {
                    background: white !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>