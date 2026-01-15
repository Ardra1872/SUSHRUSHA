<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Please log in.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ================= SEND MESSAGE (Patient -> Caretaker) =================
    case 'send':
        $patientId = $user_id; // patient logged in

        // Try to read JSON body first, then fall back to regular POST
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        if (!is_array($data) || empty($data)) {
            $data = $_POST;
        }

        if (!is_array($data) || !isset($data['caretaker_id'], $data['message'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Incomplete data']);
            exit;
        }

        $caretakerId = (int) ($data['caretaker_id'] ?? 0);
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            echo json_encode(['status' => 'error', 'msg' => 'Message cannot be empty']);
            exit;
        }

        // Optional: verify that this caretaker is actually assigned to this patient
        $check = $conn->prepare("SELECT id FROM caregivers WHERE patient_id = ? AND caregiver_id = ? LIMIT 1");
        $check->bind_param("ii", $patientId, $caretakerId);
        $check->execute();
        $hasLink = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$hasLink) {
            echo json_encode(['status' => 'error', 'msg' => 'Caretaker not linked to this patient']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO messages (patient_id, caretaker_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patientId, $caretakerId, $message);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'DB insert failed']);
        }
        $stmt->close();
        break;

    // ================= FETCH MESSAGES (for Caretaker notifications) =================
    case 'fetch':
        $caretakerId = $user_id; // caretaker logged in

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
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
}
