<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'src/config/db.php';

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "ALTER TABLE users MODIFY emergency_contact VARCHAR(255)";
if ($conn->query($sql) === TRUE) {
    echo "Schema updated successfully: emergency_contact size increased to 255.\n";
} else {
    echo "Error updating schema: " . $conn->error . "\n";
}
?>
