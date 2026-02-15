<?php
ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';
$conn = getDB();

// Only fetch if user is logged in
if (!isset($_SESSION['users_id'])) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$user_id = (int)$_SESSION['users_id'];

// Check if bookings table exists
$dbRes = $conn->query("SELECT DATABASE() as db");
$dbName = $dbRes ? $dbRes->fetch_assoc()['db'] : '';
$tableCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='bookings'");
$tableExists = $tableCheck && $tableCheck->fetch_assoc()['c'] > 0;
if (!$tableExists) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Fetch booking statuses for current user
$sql = "SELECT id as booking_id, status FROM bookings WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$out = [];
while ($row = $result->fetch_assoc()) {
    $out[] = $row;
}

echo json_encode(['success' => true, 'data' => $out]);
