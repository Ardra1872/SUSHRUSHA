<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

$caretakerId = $_SESSION['user_id'] ?? null;

if (!$caretakerId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    /* ===============================
       1️⃣ GET CARETAKER PROFILE
    =============================== */
    case 'getCaretaker':

        $stmt = $conn->prepare(
            "SELECT name, role, profile_photo 
             FROM users 
             WHERE id = ? AND role = 'caretaker'"
        );
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $caretaker = $result->fetch_assoc();

        if ($caretaker) {
            echo json_encode([
                'status' => 'success',
                'data' => $caretaker
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Caretaker not found'
            ]);
        }
        break;

    /* ===============================
       2️⃣ SEARCH (medicine / notes)
    =============================== */
    case 'search':

        $q = trim($_GET['q'] ?? '');

        if ($q === '') {
            echo json_encode([
                'status' => 'success',
                'results' => []
            ]);
            exit;
        }

        $results = [];

        // 🔹 Example medicine search
        $stmt = $conn->prepare(
            "SELECT medicine_name AS title, dosage AS detail 
             FROM medicines 
             WHERE medicine_name LIKE ? 
             LIMIT 5"
        );
        $like = "%$q%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'type' => 'medicine',
                'title' => $row['title'],
                'detail' => $row['detail']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'results' => $results
        ]);
        break;

    /* ===============================
       3️⃣ GET ASSIGNED PATIENT PROFILE
    =============================== */
    case 'getPatientProfile':

        $stmt = $conn->prepare("
            SELECT 
                u.id AS patient_id,
                u.name AS patient_name,
                c.relation,
                pp.profile_photo,
                pp.dob,
                pp.gender,
                pp.blood_group,
                pp.height_cm,
                pp.weight_kg
            FROM caregivers c
            JOIN users u ON u.id = c.patient_id
            LEFT JOIN patient_profile pp ON pp.patient_id = u.id
            WHERE c.caregiver_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();

        if ($patient) {
            echo json_encode([
                'status' => 'success',
                'data' => $patient
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No patient assigned to this caretaker'
            ]);
        }
        break;

    /* ===============================
       ❌ INVALID ACTION
    =============================== */
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}
