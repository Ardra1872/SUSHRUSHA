<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Mock session for quick test if needed, or rely on browser cookie
// $_SESSION['user_id'] = 1; // Un-comment to force ID if needed

require '../config/db.php';

header('Content-Type: text/plain');

$caretakerId = $_SESSION['user_id'] ?? null;
echo "Caretaker ID: " . var_export($caretakerId, true) . "\n";

if (!$caretakerId) {
    die("No session user_id\n");
}

$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.message,
        m.created_at AS sent_at,
        m.status AS is_read,
        u.name AS patient_name
    FROM messages m
    JOIN users u ON m.patient_id = u.id
    WHERE m.caretaker_id = ?
    ORDER BY m.created_at DESC
    LIMIT 20
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

$stmt->bind_param("i", $caretakerId);
$stmt->execute();
$res = $stmt->get_result();

echo "Rows found: " . $res->num_rows . "\n";

while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
