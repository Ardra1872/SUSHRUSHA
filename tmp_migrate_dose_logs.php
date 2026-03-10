<?php
require 'src/config/db.php';

// Migration to standardize dose_logs for Reed switch intake logging
$queries = [
    "ALTER TABLE dose_logs ADD COLUMN IF NOT EXISTS schedule_id INT AFTER id",
    "ALTER TABLE dose_logs ADD INDEX IF NOT EXISTS (schedule_id)",
    "ALTER TABLE dose_logs DROP FOREIGN KEY IF EXISTS dose_logs_ibfk_1", // Likely old medicine_id fk
    "ALTER TABLE dose_logs ADD CONSTRAINT fk_schedule FOREIGN KEY (schedule_id) REFERENCES medicine_schedule(id) ON DELETE CASCADE",
    "ALTER TABLE dose_logs MODIFY COLUMN log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Executed: $sql\n";
    } else {
        echo "Error executing $sql: " . $conn->error . "\n";
    }
}
?>
