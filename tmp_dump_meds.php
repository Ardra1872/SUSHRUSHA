<?php
require 'src/config/db.php';
header('Content-Type: text/plain');

$result = $conn->query("SELECT id, name, compartment_number FROM medicines");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Compartment: {$row['compartment_number']}\n";
}
?>
