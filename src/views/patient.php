<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

$patientId = $_SESSION['user_id'] ?? null;

if (!$patientId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    /* ===============================
       GET MESSAGES FROM CAREGIVER
    =============================== */
    case 'getMessages':
        $stmt = $conn->prepare("
            SELECT 
                m.id,
                m.message,
                m.created_at AS sent_at,
                m.status AS is_read,
                u.name AS caregiver_name
            FROM messages m
            JOIN users u ON m.caretaker_id = u.id
            WHERE m.patient_id = ?
            ORDER BY m.created_at DESC
            LIMIT 20
        ");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();

        $messages = [];
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $messages
        ]);
        break;

    /* ===============================
       MARK MESSAGE AS READ
    =============================== */
    case 'markMessageAsRead':
        $messageId = $_POST['message_id'] ?? null;

        if (!$messageId) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing message ID'
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE messages 
            SET status = 'read' 
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $messageId, $patientId);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Message marked as read'
        ]);
        break;

    /* ===============================
       SEND MESSAGE TO CAREGIVER
    =============================== */
    case 'sendMessageToCaregiver':
        $caregiversStmt = $conn->prepare("
            SELECT caregiver_id FROM caregivers 
            WHERE patient_id = ? LIMIT 1
        ");
        $caregiversStmt->bind_param("i", $patientId);
        $caregiversStmt->execute();
        $caregiversRes = $caregiversStmt->get_result()->fetch_assoc();
        $caregiver_id = $caregiversRes['caregiver_id'] ?? null;

        if (!$caregiver_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No caregiver assigned'
            ]);
            exit;
        }

        $message = $_POST['message'] ?? '';

        if (!$message) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing message'
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO messages (patient_id, caretaker_id, message, status, created_at)
            VALUES (?, ?, ?, 'unread', NOW())
        ");
        
        $stmt->bind_param(
            "iis",
            $patientId,
            $caregiver_id,
            $message
        );

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Message sent successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to send message'
            ]);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}

?>
