<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json'); // important for fetch

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle AJAX deletion request
if (isset($_POST['action']) && $_POST['action'] === 'delete_request') {
    $request_id = intval($_POST['request_id']);
    $stmt = $conn->prepare("DELETE FROM medicine_requests WHERE id = ? AND requested_by = ?");
    $stmt->bind_param("ii", $request_id, $user_id);

    if($stmt->execute()){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete']);
    }
    exit; // stop further rendering
}

// Fetch user requests (for JS)
$stmt = $conn->prepare("SELECT * FROM medicine_requests WHERE requested_by = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$medicineRequests = [];
while($row = $result->fetch_assoc()){
    $medicineRequests[] = $row;
}

// Return JSON to frontend
echo json_encode([
    'status' => 'success',
    'requests' => $medicineRequests
]);
exit;
