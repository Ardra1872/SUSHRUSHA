<?php
include '../config/db.php';

$res = $conn->query("SHOW COLUMNS FROM medicine_requests");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
