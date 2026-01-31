<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Attempting connection...\n";

// Direct connection
$conn = mysqli_connect("localhost", "root", "", "sushrusha");

if (!$conn) {
    echo "Connect failed: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "Connected successfully. Info: " . mysqli_get_host_info($conn) . "\n";

// Query
$res = $conn->query("SELECT patient_id FROM patient_profile LIMIT 1");
if ($res) {
    echo "Query OK. Found " . $res->num_rows . " rows.\n";
    if ($row = $res->fetch_assoc()) {
        echo "Patient ID: " . $row['patient_id'] . "\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

$conn->close();
?>
