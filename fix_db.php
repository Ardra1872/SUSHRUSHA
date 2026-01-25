<?php
require 'src/config/db.php'; 

echo "Fixing Database Schema...<br>";

// Drop the old table to ensure clean state
$sqlDrop = "DROP TABLE IF EXISTS dose_logs";
if ($conn->query($sqlDrop) === TRUE) {
    echo "Dropped existing `dose_logs`.<br>";
} else {
    echo "Error dropping: " . $conn->error . "<br>";
}

// Recreate with correct schema
$sqlCreate = "CREATE TABLE dose_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    status ENUM('TAKEN', 'MISSED') NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    simulated_action_time DATETIME NULL
)";

if ($conn->query($sqlCreate) === TRUE) {
    echo "Created `dose_logs` with CORRECT schema.<br>";
} else {
    echo "Error creating: " . $conn->error . "<br>";
}

echo "Done.";
?>
