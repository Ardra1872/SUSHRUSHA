<?php
// src/setup/update_doses_schema.php
require '../config/db.php';

echo "<h3>Updating 'doses' table schema...</h3>";

// 1. Add patient_id column
$check = $conn->query("SHOW COLUMNS FROM doses LIKE 'patient_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE doses ADD COLUMN patient_id INT AFTER id")) {
        echo "Added column 'patient_id' to doses<br>";
        // Populate existing patient_ids
        $updateSql = "
            UPDATE doses d
            JOIN prescription_medicines pm ON d.prescription_medicine_id = pm.id
            JOIN prescriptions p ON pm.prescription_id = p.id
            SET d.patient_id = p.patient_id
        ";
        $conn->query($updateSql);
        echo "Populated existing patient_ids in doses<br>";
    }
}

// 2. Add manual_medicine_id column
$check = $conn->query("SHOW COLUMNS FROM doses LIKE 'manual_medicine_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE doses ADD COLUMN manual_medicine_id INT NULL AFTER patient_id")) {
        echo "Added column 'manual_medicine_id' to doses<br>";
    }
}

// 3. Make prescription_medicine_id nullable
$conn->query("ALTER TABLE doses MODIFY COLUMN prescription_medicine_id INT NULL");
echo "Made 'prescription_medicine_id' nullable in doses<br>";

// 4. Add index for faster reporting
$conn->query("CREATE INDEX IF NOT EXISTS idx_patient_status ON doses(patient_id, status)");
echo "Added index for reporting performance<br>";

echo "<h3>Migration Complete.</h3>";
?>
