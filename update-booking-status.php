<?php
// update-booking-status.php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    
    $conn = getDB();
    $sql = "UPDATE bookings SET status = '$status' WHERE id = $booking_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}