<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch($action) {

    // ================= SEND MESSAGE =================
    case 'send':
        $patientId = $_SESSION['user_id']; // patient logged in
        $data = json_decode(file_get_contents('php://input'), true);
        
       
        if (!isset($data['caretaker_id'], $data['message'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Incomplete data']);
            exit;
        }
        $caretakerId = $data['caretaker_id'];
        $message = trim($data['message']);

        $stmt = $conn->prepare("INSERT INTO messages (patient_id, caretaker_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patientId, $caretakerId, $message);

        if($stmt->execute()){
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'DB insert failed']);
        }
        break;

    // ================= FETCH MESSAGES =================
    case 'fetch':
        $caretakerId = $_SESSION['user_id']; // caretaker logged in

        $stmt = $conn->prepare("
            SELECT m.id, u.name AS patient_name, m.message, m.status, m.created_at 
            FROM messages m 
            JOIN users u ON m.patient_id = u.id
            WHERE m.caretaker_id = ? 
            ORDER BY m.created_at DESC
        ");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
}
