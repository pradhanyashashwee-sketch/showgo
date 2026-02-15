<?php
function getDB() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "show_go_db";
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn; 
}
?>