<?php
include __DIR__ . '/../config/db.php';

echo "Starting database migration...\n";

// 1. Add columns to prescription_medicines
$cols_to_add = [
    "frequency_type VARCHAR(50) DEFAULT 'once'",
    "time_slots TEXT",
    "start_date DATE",
    "end_date DATE",
    "before_after_food ENUM('Before Food', 'After Food') DEFAULT 'After Food'",
    "notes TEXT"
];

foreach ($cols_to_add as $col) {
    preg_match('/^(\w+)/', $col, $matches);
    $col_name = $matches[1];
    
    $check = $conn->query("SHOW COLUMNS FROM prescription_medicines LIKE '$col_name'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE prescription_medicines ADD COLUMN $col")) {
            echo "Added column $col_name to prescription_medicines\n";
        } else {
            echo "Error adding column $col_name: " . $conn->error . "\n";
        }
    } else {
        echo "Column $col_name already exists in prescription_medicines\n";
    }
}

// 2. Create doses table
$create_doses_sql = "
CREATE TABLE IF NOT EXISTS doses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_medicine_id INT NOT NULL,
    scheduled_datetime DATETIME NOT NULL,
    status ENUM('upcoming', 'taken', 'missed') DEFAULT 'upcoming',
    taken_at DATETIME NULL,
    FOREIGN KEY (prescription_medicine_id) REFERENCES prescription_medicines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_doses_sql)) {
    echo "Table 'doses' created or already exists.\n";
} else {
    echo "Error creating table 'doses': " . $conn->error . "\n";
}

echo "Migration completed.\n";
?>
