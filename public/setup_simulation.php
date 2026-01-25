<?php
// public/setup_simulation.php
require '../src/config/db.php';

echo "<h2>Setting up Simulation Database...</h2>";

// 1. Create table `dose_logs`
$sql1 = "CREATE TABLE IF NOT EXISTS dose_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    status ENUM('TAKEN', 'MISSED') NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    simulated_action_time DATETIME NULL
)";

if ($conn->query($sql1) === TRUE) {
    echo "Table `dose_logs` created or already exists.<br>";
} else {
    echo "Error creating `dose_logs`: " . $conn->error . "<br>";
}

// 2. Create table `simulation_config`
$sql2 = "CREATE TABLE IF NOT EXISTS simulation_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(50) UNIQUE NOT NULL,
    config_value VARCHAR(255) NOT NULL
)";

if ($conn->query($sql2) === TRUE) {
    echo "Table `simulation_config` created or already exists.<br>";
} else {
    echo "Error creating `simulation_config`: " . $conn->error . "<br>";
}

// 3. Insert default config if not exists
$defaultGrace = 5; // minutes
$sql3 = "INSERT IGNORE INTO simulation_config (config_key, config_value) VALUES ('grace_period_minutes', '$defaultGrace')";

if ($conn->query($sql3) === TRUE) {
    echo "Default configuration inserted.<br>";
} else {
    echo "Error inserting config: " . $conn->error . "<br>";
}

echo "<h3>Setup Complete.</h3>";
echo "<a href='simulation/index.php'>Go to Simulation Dashboard</a>";
?>
