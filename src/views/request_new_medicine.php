<?php
ob_start(); // Buffer output to prevent garbage
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();
    
    if (!file_exists(__DIR__ . '/../config/db.php')) throw new Exception('Database config missing');
    include __DIR__ . '/../config/db.php';

    $patient_id = $_SESSION['user_id'] ?? null;
    if (!$patient_id) {
        throw new Exception('Unauthorized: Please log in');
    }

    // Get JSON body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        // Fallback to $_POST if JSON fails (though frontend sends JSON)
        $data = $_POST;
    }

    $name = trim($data['name'] ?? '');
    $dosage = trim($data['dosage'] ?? '');
    $form = trim($data['form'] ?? '');
    $reason = trim($data['reason'] ?? '');

    if (!$name) {
        throw new Exception('Medicine name is required');
    }
    if (!$dosage) {
        throw new Exception('Dosage is required (e.g. 500mg)');
    }

    // Check if already in catalog (Exact match on name + dosage?)
    // For now, just check name to warn user, or let them request anyway?
    // User wants to request *new* medicine, implies it's not found.
    // Let's allow duplicates in request table, admin will sort it out.

    // Insert into medicine_requests table
    $stmt = $conn->prepare("
        INSERT INTO medicine_requests (requested_by, name, dosage, form, reason, created_at, status) 
        VALUES (?, ?, ?, ?, ?, NOW(), 'Pending')
    ");
    
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->bind_param("issss", $patient_id, $name, $dosage, $form, $reason);

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Request submitted successfully! We will review it shortly.'
        ]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>
