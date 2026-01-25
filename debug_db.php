<?php
require 'src/config/db.php'; 

$table = 'dose_logs';
$result = $conn->query("DESCRIBE $table");

if ($result) {
    echo "Columns in $table:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error describing $table: " . $conn->error;
}
?>
