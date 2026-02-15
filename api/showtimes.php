<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
$conn = getDB();

// Check if showtimes table exists
$dbRes = $conn->query("SELECT DATABASE() as db");
$dbName = $dbRes ? $dbRes->fetch_assoc()['db'] : '';
$tCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='showtimes'");
$tableExists = $tCheck && $tCheck->fetch_assoc()['c'] > 0;
if (!$tableExists) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Prefer joining movie title if movies table and title column exist
$joinMovies = false;
$mCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='movies'");
if ($mCheck && $mCheck->fetch_assoc()['c'] > 0) {
    // ensure title column exists
    $col = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='movies' AND COLUMN_NAME='title'");
    if ($col && $col->fetch_assoc()['c'] > 0) $joinMovies = true;
}

if ($joinMovies) {
    $sql = "SELECT s.*, m.title AS movie_title FROM showtimes s LEFT JOIN movies m ON m.id = s.movie_id ORDER BY s.show_date DESC, s.show_time ASC";
} else {
    $sql = "SELECT *, '' AS movie_title FROM showtimes ORDER BY show_date DESC, show_time ASC";
}

$res = $conn->query($sql);
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $out]);
