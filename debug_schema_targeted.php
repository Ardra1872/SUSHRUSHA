<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'src/config/db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'emergency_contact'");

if (!$result) {
    die("Query failed: " . $conn->error);
}

$row = $result->fetch_assoc();

if (!$row) {
    echo "Column 'emergency_contact' NOT FOUND in table 'users'.\n";
} else {
    print_r($row);
}
?>
