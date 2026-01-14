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

        $stmt = $conn->prepare("
            SELECT 
                name,
                role,
                profile_photo
            FROM users
            WHERE id = ? AND role = 'caretaker'
        ");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();

        $caretaker = $stmt->get_result()->fetch_assoc();

        echo json_encode(
            $caretaker
                ? ['status' => 'success', 'data' => $caretaker]
                : ['status' => 'error', 'message' => 'Caretaker not found']
        );
        break;

    /* ===============================
       2️⃣ SEARCH MEDICINES
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

        $stmt = $conn->prepare("
            SELECT 
                name AS title,
                dosage_value AS detail
            FROM medicines
            WHERE name LIKE ?
            LIMIT 5
        ");

        $like = "%$q%";
        $stmt->bind_param("s", $like);
        $stmt->execute();

        $results = [];
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'type'   => 'medicine',
                'title'  => $row['title'],
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
                u.id            AS patient_id,
                u.name          AS patient_name,
                c.relation,
                pp.profile_photo,
                pp.dob,
                pp.gender,
                pp.blood_group,
                pp.height_cm,
                pp.weight_kg
            FROM caregivers c
            JOIN users u 
                ON u.id = c.patient_id
            LEFT JOIN patient_profile pp 
                ON pp.patient_id = u.id
            WHERE c.caregiver_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();

        $patient = $stmt->get_result()->fetch_assoc();

        echo json_encode(
            $patient
                ? ['status' => 'success', 'data' => $patient]
                : ['status' => 'error', 'message' => 'No patient assigned']
        );
        break;
       /* ===============================
   4️⃣ GET PATIENT MEDICINES
=============================== */
case 'getPatientMedicines':

    // Get the assigned patient for this caretaker
    $stmt = $conn->prepare("
        SELECT c.patient_id
        FROM caregivers c
        WHERE c.caregiver_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $caretakerId);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    if (!$patient) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No patient assigned'
        ]);
        exit;
    }

    $patientId = $patient['patient_id'];

    // Fetch medicines for this patient
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            dosage,
            dosage_value,
            dosage_unit,
            frequency,
            schedule_type,
            specific_days,
            instructions,
            days,
            reminder_type,
            medicine_type,
            interval_hours
        FROM medicines
        WHERE patient_id = ?
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $res = $stmt->get_result();

    $medicines = [];
    while ($row = $res->fetch_assoc()) {
        $medicines[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $medicines
    ]);
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
