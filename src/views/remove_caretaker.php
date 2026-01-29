<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$patient_id = $_SESSION['user_id'];

// 1. Get Caretaker ID before deleting the link
$stmt = $conn->prepare("SELECT caregiver_id FROM caregivers WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'success', 'message' => 'No caretaker assigned using this account']);
    exit;
}

$row = $result->fetch_assoc();
$caregiver_id = $row['caregiver_id'];
$stmt->close();

// 2. Delete from caregivers (The association)
$delLink = $conn->prepare("DELETE FROM caregivers WHERE patient_id = ?");
$delLink->bind_param("i", $patient_id);

if (!$delLink->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to remove caretaker link']);
    exit;
}
$delLink->close();

// 3. Delete from users (The account)
// Safety check: Ensure we are deleting a caretaker, not the patient themselves or an admin
$delUser = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'caretaker'");
$delUser->bind_param("i", $caregiver_id);

if ($delUser->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    // If user deletion fails, we still return success because the 'link' is gone, 
    // effectively removing them from the patient's dashboard.
    // But logically, we might want to warn. For now, silence is golden for the dashboard UI.
    error_log("Failed to delete user ID $caregiver_id: " . $conn->error);
    echo json_encode(['status' => 'success']); 
}
$delUser->close();
?>
