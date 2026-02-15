<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['users_logged_in']) || $_SESSION['users_logged_in'] !== true) {
    header("Location: user-login.php");
    exit();
}

// Check if movie and show time are selected
if (!isset($_SESSION['selected_movie_id']) || !isset($_SESSION['selected_movie_title']) || !isset($_SESSION['selected_show_time'])) {
    header("Location: index.php");
    exit();
}
// Allow movie selection via GET parameters (from booking page)
if (isset($_GET['movie_id']) && isset($_GET['movie_title']) && isset($_GET['show_time'])) {
    $_SESSION['selected_movie_id'] = intval($_GET['movie_id']);
    $_SESSION['selected_movie_title'] = urldecode($_GET['movie_title']);
    $_SESSION['selected_show_time'] = $_GET['show_time'];
}
$conn = getDB();
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$movie_id = $_SESSION['selected_movie_id'];
$selectedMovie = $_SESSION['selected_movie_title'];
$show_time = $_SESSION['selected_show_time'];

if (empty($selectedMovie) || empty($show_time)) {
    header("Location: index.php");
    exit();
}

// Calculate ticket price
function calculateTicketPrice($time) {
    $dayOfWeek = date('w');
    $time = strtolower(trim($time));
    $hour = 0;
    
    if (strpos($time, 'am') !== false) {
        $time = str_replace('am', '', $time);
        $hour = intval(trim($time));
        if ($hour == 12) $hour = 0;
    } else {
        $time = str_replace('pm', '', $time);
        $hour = intval(trim($time));
        if ($hour < 12) $hour += 12;
    }
    
    if ($hour < 12) {
        if ($dayOfWeek >= 5 || $dayOfWeek == 0) { 
            return 200;
        } else {
            return 150;
        }
    } else {
        if ($dayOfWeek >= 5 || $dayOfWeek == 0) {
            return 400;
        } else {
            return 300;
        }
    }
}

$ticketPrice = calculateTicketPrice($show_time);

// IMPORTANT FIX: First check if show exists with correct date
$show_date = date('Y-m-d'); // Today's date

$check_show = $conn->prepare("SELECT id FROM shows WHERE movie_id = ? AND show_time = ? AND show_date = ?");
if (!$check_show) {
    die("Prepare failed: " . $conn->error);
}
$check_show->bind_param("iss", $movie_id, $show_time, $show_date);
$check_show->execute();
$check_show->store_result();

if ($check_show->num_rows == 0) {
    // Create new show with all required fields
    $insert_show = $conn->prepare("
        INSERT INTO shows (movie_id, show_time, show_date, price_per_seat, available_seats, hall) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$insert_show) {
        die("Prepare failed: " . $conn->error);
    }
    
    $available_seats = 50;
    $hall = 'Hall 1';
    $insert_show->bind_param("issdis", $movie_id, $show_time, $show_date, $ticketPrice, $available_seats, $hall);
    $insert_show->execute();
    
    if ($insert_show->affected_rows > 0) {
        $show_id = $insert_show->insert_id;
    } else {
        die("Failed to create show: " . $conn->error);
    }
    $insert_show->close();
} else {
    $check_show->bind_result($show_id);
    $check_show->fetch();
}
$check_show->close();

// VERIFY the show exists in database
$verify_show = $conn->prepare("SELECT id FROM shows WHERE id = ?");
$verify_show->bind_param("i", $show_id);
$verify_show->execute();
$verify_show->store_result();

if ($verify_show->num_rows == 0) {
    die("Error: Show ID $show_id does not exist in database.");
}
$verify_show->close();

// Get occupied seats
$occupiedSeats = [];
if (isset($show_id) && $show_id > 0) {
    // Get seats from booked_seats table
    $stmt2 = $conn->prepare("SELECT seat_number FROM booked_seats WHERE show_id = ?");
    if ($stmt2) {
        $stmt2->bind_param("i", $show_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            $occupiedSeats[] = $row['seat_number'];
        }
        $stmt2->close();
    }
}

// Process seat selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
    $selectedSeats = explode(',', $_POST['selected_seats']);
    $numSeats = count($selectedSeats);
    $totalAmount = $numSeats * $ticketPrice;
    $ticket_id = 'TKT-' . strtoupper(uniqid());
    
    $_SESSION['booking_info'] = [
        'movie_id' => $movie_id,
        'movie_title' => $selectedMovie,
        'show_id' => $show_id,
        'show_time' => $show_time,
        'show_date' => $show_date,
        'seats' => $selectedSeats,
        'seats_string' => implode(',', $selectedSeats),
        'num_seats' => $numSeats,
        'ticket_price' => $ticketPrice,
        'total_amount' => $totalAmount,
        'ticket_id' => $ticket_id,
        'hall' => 'Hall 1'
    ];
    
    header("Location: payment.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ShowGo - Book Seats for <?php echo htmlspecialchars($selectedMovie); ?></title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .seat-container {
      max-width: 800px;
      margin: 30px auto;
      padding: 20px;
      background: #1a1a2e;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .movie-info {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #ff6b6b;
    }
    
    .movie-info h2 {
      color: #ffd166;
      font-size: 2rem;
      margin-bottom: 10px;
    }
    
    .movie-info .time {
      background: #4cc9f0;
      color: white;
      padding: 8px 20px;
      border-radius: 20px;
      display: inline-block;
      font-weight: bold;
      margin: 10px 0;
    }
    
    .movie-info .price {
      color: #06d6a0;
      font-size: 1.2rem;
      margin-top: 10px;
    }
    
    .error-message {
      background: #f8d7da;
      color: #721c24;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .screen {
      text-align: center;
      background: #2d3047;
      padding: 15px;
      margin: 20px 0 40px 0;
      border-radius: 5px;
      position: relative;
      color: #ffd166;
      font-weight: bold;
      letter-spacing: 3px;
    }
    
    .screen:before {
      content: "";
      position: absolute;
      top: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80%;
      height: 15px;
      background: #ffd166;
      border-radius: 50%;
      opacity: 0.3;
    }
    
    .seats-grid {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
      margin-bottom: 30px;
    }
    
    .seat-row {
      display: flex;
      gap: 10px;
      justify-content: center;
    }
    
    .seat {
      width: 40px;
      height: 40px;
      background: #0f3460;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      font-weight: bold;
      transition: all 0.2s;
    }
    
    .seat:hover {
      background: #4cc9f0;
      transform: scale(1.1);
    }
    
    .seat.selected {
      background: #06d6a0;
      transform: scale(1.1);
    }
    
    .seat.occupied {
      background: #ff6b6b;
      cursor: not-allowed;
    }
    
    .seat.occupied:hover {
      background: #ff6b6b;
      transform: none;
    }
    
    .seat-legend {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: white;
    }
    
    .legend-box {
      width: 20px;
      height: 20px;
      border-radius: 4px;
    }
    
    .available { background: #0f3460; }
    .selected-leg { background: #06d6a0; }
    .occupied-leg { background: #ff6b6b; }
    
    .booking-summary {
      background: #0f3460;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      color: white;
    }
    
    .total-row {
      font-size: 1.2rem;
      font-weight: bold;
      color: #ffd166;
      border-top: 2px solid #4cc9f0;
      padding-top: 10px;
      margin-top: 10px;
    }
    
    .booking-form {
      text-align: center;
    }
    
    .btn-proceed {
      background: #06d6a0;
      color: white;
      border: none;
      padding: 15px 40px;
      font-size: 1.1rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s;
    }
    
    .btn-proceed:hover {
      background: #05b387;
    }
    
    .btn-proceed:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
    
    .hidden-input {
      display: none;
    }
    
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: #4cc9f0;
      text-decoration: none;
    }
    
    .back-link:hover {
      text-decoration: underline;
    }
    
    .selected-info {
      background: rgba(76, 201, 240, 0.1);
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .selected-info h3 {
      color: #4cc9f0;
      margin-top: 0;
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
  
  <div class="seat-container">
    <a href="index.php" class="back-link">← Back to Movies</a>
    
    <?php if (!isset($show_id)): ?>
      <div class="error-message">
        Error: Could not create or find show. Please try again.
      </div>
    <?php endif; ?>
    
    <div class="selected-info">
      <h3>You selected:</h3>
    </div>
    
    <div class="movie-info">
      <h2><?php echo htmlspecialchars($selectedMovie); ?></h2>
      <div class="time"><?php echo htmlspecialchars($show_time); ?></div>
      <div class="price">Price per ticket: Rs. <?php echo $ticketPrice; ?></div>
    </div>
    
    <div class="screen">SCREEN</div>
    
    <div class="seats-grid" id="seatsGrid">
    </div>
    <div class="seat-legend">
      <div class="legend-item">
        <div class="legend-box available"></div>
        <span>Available</span>
      </div>
      <div class="legend-item">
        <div class="legend-box selected-leg"></div>
        <span>Selected</span>
      </div>
      <div class="legend-item">
        <div class="legend-box occupied-leg"></div>
        <span>Occupied</span>
      </div>
    </div>
    
    <div class="booking-summary">
      <div class="summary-row">
        <span>Selected Seats:</span>
        <span id="selectedSeatsText">None</span>
      </div>
      <div class="summary-row">
        <span>Number of Seats:</span>
        <span id="seatCount">0</span>
      </div>
      <div class="summary-row">
        <span>Price per Ticket:</span>
        <span>Rs. <span id="ticketPrice"><?php echo $ticketPrice; ?></span></span>
      </div>
      <div class="summary-row total-row">
        <span>Total Amount:</span>
        <span>Rs. <span id="totalAmount">0</span></span>
      </div>
    </div>
    
    <form method="POST" class="booking-form" id="bookingForm">
      <input type="hidden" name="selected_seats" id="selectedSeatsInput">
      <button type="submit" class="btn-proceed" id="proceedBtn" disabled <?php echo !isset($show_id) ? 'disabled' : ''; ?>>
        Proceed to Payment (Rs. <span id="btnAmount">0</span>)
      </button>
    </form>
  </div>
  
  <footer>Copyright ©2025 ShowGo</footer>
  
  <script>
    const rows = ['A', 'B', 'C', 'D', 'E', 'F'];
    const seatsPerRow = 10;
    const occupiedSeats = new Set(<?php echo json_encode($occupiedSeats); ?>);
    const ticketPrice = <?php echo $ticketPrice; ?>;
    let selectedSeats = new Set();
    function generateSeats() {
      const seatsGrid = document.getElementById('seatsGrid');
      if (!seatsGrid) return;
      seatsGrid.innerHTML = '';
      rows.forEach(row => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        for (let i = 1; i <= seatsPerRow; i++) {
          const seatId = `${row}${i}`;
          const seatDiv = document.createElement('div');
          seatDiv.className = 'seat';
          seatDiv.id = `seat-${seatId}`;
          seatDiv.textContent = seatId;
          if (occupiedSeats.has(seatId)) {
            seatDiv.classList.add('occupied');
          } else {
            seatDiv.addEventListener('click', () => toggleSeat(seatId));
          }
          if (selectedSeats.has(seatId)) {
            seatDiv.classList.add('selected');
          }
          rowDiv.appendChild(seatDiv);
        }
        seatsGrid.appendChild(rowDiv);
      });
      updateSummary();
    }
    function toggleSeat(seatId) {
      if (selectedSeats.has(seatId)) {
        selectedSeats.delete(seatId);
      } else {
        if (selectedSeats.size >= 35) {
          alert('Maximum 35 seats per booking');
          return;
        }
        selectedSeats.add(seatId);
      }
      const seatElement = document.getElementById(`seat-${seatId}`);
      if (seatElement) {
        seatElement.classList.toggle('selected');
      }
      updateSummary();
    }
    function updateSummary() {
      const seatArray = Array.from(selectedSeats);
      const seatCount = seatArray.length;
      const totalAmount = seatCount * ticketPrice;
      const selectedSeatsText = document.getElementById('selectedSeatsText');
      const seatCountElement = document.getElementById('seatCount');
      const totalAmountElement = document.getElementById('totalAmount');
      const btnAmountElement = document.getElementById('btnAmount');
      const proceedBtn = document.getElementById('proceedBtn');
      const selectedSeatsInput = document.getElementById('selectedSeatsInput');
      if (selectedSeatsText) {
        selectedSeatsText.textContent = seatCount > 0 ? seatArray.join(', ') : 'None';
      }
      if (seatCountElement) {
        seatCountElement.textContent = seatCount;
      }
      if (totalAmountElement) {
        totalAmountElement.textContent = totalAmount;
      }
      if (btnAmountElement) {
        btnAmountElement.textContent = totalAmount;
      }
      if (selectedSeatsInput) {
        selectedSeatsInput.value = seatArray.join(',');
      }
      if (proceedBtn) {
        proceedBtn.disabled = seatCount === 0;
      }
    }
    window.onload = generateSeats;
// Update revenue when ticket receipt is viewed (confirmation)
    async function confirmBookingAndUpdateRevenue() {
    const urlParams = new URLSearchParams(window.location.search);
    const ticketId = urlParams.get('ticket_id');
    const amount = document.querySelector('.total-amount')?.dataset.amount;
    
    if (ticketId && amount) {
        try {
            // Get booking ID from ticket
            const response = await fetch(`get_booking_id.php?ticket_id=${ticketId}`);
            const data = await response.json();
            
            if (data.booking_id) {
                await updateAdminRevenue(data.booking_id, parseFloat(amount));
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

// Run when page loads
document.addEventListener('DOMContentLoaded', confirmBookingAndUpdateRevenue);
</script>
  </scrip>
</body>
</html>
<?php
$conn->close();
?>