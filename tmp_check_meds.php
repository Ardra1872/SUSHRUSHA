<?php
require 'src/config/db.php';
$res = $conn->query("SELECT id, name, compartment_number, patient_id FROM medicines WHERE patient_id = 10");
echo "ID | Name | Compartment | Patient_ID" . PHP_EOL;
echo "-------------------------------------" . PHP_EOL;
while($row = $res->fetch_assoc()) {
    echo $row['id'] . " | " . $row['name'] . " | " . $row['compartment_number'] . " | " . $row['patient_id'] . PHP_EOL;
}
?>
