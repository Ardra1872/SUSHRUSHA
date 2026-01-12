<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.message,
        b.created_at,
        CASE 
            WHEN br.id IS NULL THEN 1
            ELSE 0
        END AS is_new
    FROM broadcasts b
    LEFT JOIN broadcast_reads br
        ON b.id = br.broadcast_id
        AND br.user_id = ?
    ORDER BY b.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$alerts = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "status" => "success",
    "alerts" => $alerts
]);

