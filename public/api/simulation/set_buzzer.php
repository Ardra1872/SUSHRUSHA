<?php
// public/api/simulation/set_buzzer.php
header('Content-Type: application/json');

$buzzerFile = __DIR__ . '/buzzer_state.json';

// Get parameters
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null);

if ($action === 'on' || $action === 'off') {
    file_put_contents($buzzerFile, json_encode(["buzzer" => $action]));
    echo json_encode(["status" => "success", "buzzer" => $action]);
} else {
    // If no action provided, just return current state (or error)
    if (file_exists($buzzerFile)) {
        $state = json_decode(file_get_contents($buzzerFile), true);
        echo json_encode(["status" => "info", "buzzer" => $state["buzzer"]]);
    } else {
        file_put_contents($buzzerFile, json_encode(["buzzer" => "off"]));
        echo json_encode(["status" => "info", "buzzer" => "off"]);
    }
}
?>
