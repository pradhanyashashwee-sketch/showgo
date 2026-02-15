<?php
session_start();
include "db.php";

// Check if user is logged in and has booking info
if (!isset($_SESSION['users_logged_in']) || $_SESSION['users_logged_in'] !== true) {
    header("Location: user-login.php");
    exit();
}

if (!isset($_SESSION['booking_info'])) {
    header("Location: book_seats.php");
    exit();
}

$conn = getDB();

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$bookingInfo = $_SESSION['booking_info'];
$user_id = $_SESSION['users_id'];

// VERIFY that the show exists before processing
$verify_show = $conn->prepare("SELECT id FROM shows WHERE id = ?");
$verify_show->bind_param("i", $bookingInfo['show_id']);
$verify_show->execute();
$verify_show->store_result();

if ($verify_show->num_rows == 0) {
    die("Error: Invalid show ID. Please go back and select seats again.");
}
$verify_show->close();

// Process payment and save to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    try {
        $conn->begin_transaction();
        
        // IMPORTANT FIX: Insert into bookings with showtime_id (foreign key)
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                user_id, 
                movie_id, 
                show_id, 
                showtime_id, 
                seat_number, 
                price, 
                ticket_id, 
                status, 
                booked_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param(
            "iiiisds",
            $user_id,
            $bookingInfo['movie_id'],
            $bookingInfo['show_id'],
            $bookingInfo['show_id'], // This MUST match an existing shows.id
            $bookingInfo['seats_string'],
            $bookingInfo['total_amount'],
            $bookingInfo['ticket_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $booking_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert individual seats into booked_seats table
        foreach ($bookingInfo['seats'] as $seat) {
            $stmt2 = $conn->prepare("
                INSERT INTO booked_seats (booking_id, show_id, seat_number)
                VALUES (?, ?, ?)
            ");
            
            if (!$stmt2) {
                throw new Exception("Prepare failed for booked_seats: " . $conn->error);
            }
            
            $stmt2->bind_param("iis", $booking_id, $bookingInfo['show_id'], $seat);
            
            if (!$stmt2->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Seat $seat is already booked. Please select different seats.");
                }
                throw new Exception("Execute failed for booked_seats: " . $stmt2->error);
            }
            
            $stmt2->close();
        }
        
        // Update available seats in shows table
        $stmt3 = $conn->prepare("
            UPDATE shows 
            SET available_seats = available_seats - ? 
            WHERE id = ?
        ");
        
        $stmt3->bind_param("ii", $bookingInfo['num_seats'], $bookingInfo['show_id']);
        
        if (!$stmt3->execute()) {
            throw new Exception("Execute failed for shows update: " . $stmt3->error);
        }
        $stmt3->close();
        
        $conn->commit();
        
        // Store success message in session
        $_SESSION['booking_success'] = [
            'message' => 'Booking confirmed successfully! Your tickets have been booked.',
            'ticket_id' => $bookingInfo['ticket_id'],
            'booking_id' => $booking_id,
            'booking_info' => $bookingInfo
        ];
        
        // Clear temporary booking info
        unset($_SESSION['booking_info']);
        
        // Redirect to receipt
        header("Location: ticket_receipt.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowGo - Payment</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #1a1a2e;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .payment-header h2 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .payment-details {
            background: #16213e;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #333;
            color: #fff;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row span:first-child {
            color: #aaa;
        }
        
        .detail-row span:last-child {
            font-weight: 600;
            color: #fff;
        }
        
        .total-row {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #4a4a6a;
            font-size: 18px;
        }
        
        .total-row span:last-child {
            color: #ffd700;
            font-size: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .pay-btn {
            margin-top: 20px;
            width: 100%;
            padding: 16px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, #370ab2ff, #826ebeff);
            box-shadow: 0 8px 20px rgba(146, 112, 241, 0.4);
            transition: 0.3s ease;
        }
        
        .pay-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(124, 77, 255, 0.6);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #aaa;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #fff;
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
    
    <div class="payment-container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
                <br>
                <a href="book_seats.php?show_id=<?php echo $bookingInfo['show_id']; ?>" style="color: #721c24; font-weight: bold;">← Go back and select different seats</a>
            </div>
        <?php endif; ?>
        
        <div class="payment-header">
            <h2>Complete Your Payment</h2>
        </div>
        
        <div class="payment-details">
            <div class="detail-row">
                <span>Movie:</span>
                <span><?php echo htmlspecialchars($bookingInfo['movie_title']); ?></span>
            </div>
            <div class="detail-row">
                <span>Show Time:</span>
                <span><?php echo htmlspecialchars($bookingInfo['show_time']); ?></span>
            </div>
            <div class="detail-row">
                <span>Hall:</span>
                <span><?php echo isset($bookingInfo['hall']) ? htmlspecialchars($bookingInfo['hall']) : 'Hall 1'; ?></span>
            </div>
            <div class="detail-row">
                <span>Selected Seats:</span>
                <span><?php echo implode(', ', $bookingInfo['seats']); ?></span>
            </div>
            <div class="detail-row">
                <span>Number of Tickets:</span>
                <span><?php echo $bookingInfo['num_seats']; ?></span>
            </div>
            <div class="detail-row">
                <span>Price per Ticket:</span>
                <span>Rs. <?php echo number_format($bookingInfo['ticket_price'], 2); ?></span>
            </div>
            <div class="detail-row total-row">
                <span>Total Amount:</span>
                <span>Rs. <?php echo number_format($bookingInfo['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <form method="POST">
            <div class="payment-options">
                <button type="submit" name="confirm_payment" class="pay-btn">
                    🔒 Confirm Payment - Rs. <?php echo number_format($bookingInfo['total_amount'], 2); ?>
                </button>
            </div>
        </form>
        
        <a href="book_seats.php?show_id=<?php echo $bookingInfo['show_id']; ?>" class="back-link">← Cancel and go back</a>
    </div>
</body>
</html>