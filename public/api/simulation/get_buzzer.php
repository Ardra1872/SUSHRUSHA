<?php
// public/api/simulation/get_buzzer.php
header('Content-Type: application/json');

$buzzerFile = __DIR__ . '/buzzer_state.json';

// If file doesn't exist, create default
if (!file_exists($buzzerFile)) {
    file_put_contents($buzzerFile, json_encode(["buzzer" => "off"]));
}

// Return current state
$state = json_decode(file_get_contents($buzzerFile), true);
echo json_encode([
    "buzzer" => $state["buzzer"]
]);
