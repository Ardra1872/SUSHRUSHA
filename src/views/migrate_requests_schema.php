<?php
include '../config/db.php';

// Add columns if they don't exist
$queries = [
    "ALTER TABLE medicine_requests ADD COLUMN IF NOT EXISTS dosage VARCHAR(50)",
    "ALTER TABLE medicine_requests ADD COLUMN IF NOT EXISTS form VARCHAR(50)",
    "ALTER TABLE medicine_requests ADD COLUMN IF NOT EXISTS reason TEXT",
    "ALTER TABLE medicine_requests ADD COLUMN IF NOT EXISTS status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending'"
];

foreach ($queries as $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            echo "SQL execution success: $sql\n";
        } else {
             // Ignore "Duplicate column" error if "IF NOT EXISTS" isn't supported by this MySQL version
             echo "SQL execution info: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "Schema update complete.";
?>
