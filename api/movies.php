<?php
ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
$conn = getDB();

// Determine if table exists
$dbRes = $conn->query("SELECT DATABASE() as db");
$dbName = $dbRes ? $dbRes->fetch_assoc()['db'] : '';
$tableCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='movies'");
$tableExists = $tableCheck && $tableCheck->fetch_assoc()['c'] > 0;
if (!$tableExists) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Check whether poster_url column exists
$colCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='movies' AND COLUMN_NAME='poster_url'");
$hasPoster = $colCheck && $colCheck->fetch_assoc()['c'] > 0;

$cols = 'id, title, description, duration_minutes, release_date, status';
if ($hasPoster) $cols = 'id, title, description, duration_minutes, poster_url, release_date, status';
else $cols .= ", '' AS poster_url";

$res = $conn->query("SELECT $cols FROM movies ORDER BY id DESC");
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $out]);
