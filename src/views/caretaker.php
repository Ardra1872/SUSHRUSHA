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
       5️⃣ GET EMERGENCY CONTACTS
    =============================== */
    case 'getEmergencyContacts':

        $stmt = $conn->prepare("
            SELECT 
                u.id            AS patient_id,
                u.name          AS patient_name,
                u.contact_number,
                u.emergency_contact,
                md.doctor_name,
                md.hospital_name
            FROM caregivers c
            JOIN users u 
                ON u.id = c.patient_id
            LEFT JOIN medical_details md 
                ON md.patient_id = u.id
            WHERE c.caregiver_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();

        $contacts = $stmt->get_result()->fetch_assoc();

        echo json_encode(
            $contacts
                ? ['status' => 'success', 'data' => $contacts]
                : ['status' => 'error', 'message' => 'No patient assigned']
        );
        break;

    /* ===============================
       6️⃣ GET MISSED DOSES REPORT
    =============================== */
    case 'getMissedDosesReport':

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

        $stmt = $conn->prepare("
            SELECT 
                m.id,
                m.name,
                m.dosage_value,
                m.dosage_unit,
                dl.intake_datetime,
                dl.status
            FROM medicines m
            LEFT JOIN dose_logs dl ON m.id = dl.medicine_id AND dl.patient_id = ?
            WHERE m.patient_id = ? AND dl.status = 'Missed'
            ORDER BY dl.intake_datetime DESC
        ");
        $stmt->bind_param("ii", $patientId, $patientId);
        $stmt->execute();
        $res = $stmt->get_result();

        $missedDoses = [];
        while ($row = $res->fetch_assoc()) {
            $missedDoses[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $missedDoses
        ]);
        break;

    /* ===============================
       7️⃣ GET MEDICINES FOR CSV EXPORT
    =============================== */
    case 'exportMedicinesCSV':

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

        $stmt = $conn->prepare("
            SELECT 
                m.id,
                m.name,
                m.dosage_value,
                m.dosage_unit,
                m.frequency,
                m.schedule_type,
                m.start_date,
                m.end_date,
                m.instructions,
                m.medicine_type,
                m.interval_hours
            FROM medicines m
            WHERE m.patient_id = ?
            ORDER BY m.id DESC
        ");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $res = $stmt->get_result();

        // Get patient name
        $patientQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $patientQuery->bind_param("i", $patientId);
        $patientQuery->execute();
        $patientData = $patientQuery->get_result()->fetch_assoc();
        $patientName = $patientData['name'] ?? 'Patient';

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $patientName . '_medicines_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Medicine Name', 'Dosage', 'Unit', 'Frequency', 'Schedule Type', 'Start Date', 'End Date', 'Instructions', 'Type', 'Interval (hours)']);

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['dosage_value'],
                $row['dosage_unit'],
                $row['frequency'],
                $row['schedule_type'],
                $row['start_date'],
                $row['end_date'],
                $row['instructions'],
                $row['medicine_type'],
                $row['interval_hours']
            ]);
        }

        fclose($output);
        exit;
        break;

    /* ===============================
       GET TODAY'S SCHEDULE
    =============================== */
    case 'getTodaysSchedule':
        // Get the assigned patient
        $patientStmt = $conn->prepare("
            SELECT patient_id FROM caregivers 
            WHERE caregiver_id = ? LIMIT 1
        ");
        $patientStmt->bind_param("i", $caretakerId);
        $patientStmt->execute();
        $patientRes = $patientStmt->get_result()->fetch_assoc();
        $patientId = $patientRes['patient_id'] ?? null;

        if (!$patientId) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        // Get today's active medicines with their schedules
        $stmt = $conn->prepare("
            SELECT 
                m.id,
                m.name,
                m.dosage,
                ms.intake_time,
                CONCAT(DATE(NOW()), ' ', COALESCE(ms.intake_time, '09:00:00')) AS intake_datetime,
                COALESCE(dl.status, 'pending') AS status
            FROM medicines m
            LEFT JOIN medicine_schedule ms ON m.id = ms.medicine_id
            LEFT JOIN dose_logs dl ON m.id = dl.medicine_id 
                AND dl.patient_id = ? 
                AND DATE(dl.intake_datetime) = CURDATE()
            WHERE m.patient_id = ? 
            AND (m.end_date IS NULL OR m.end_date >= CURDATE())
            AND m.start_date <= CURDATE()
            ORDER BY ms.intake_time ASC
        ");
        $stmt->bind_param("ii", $patientId, $patientId);
        $stmt->execute();

        $schedule = [];
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            // Parse dosage if needed
            $dosageParts = explode(' ', $row['dosage'] ?? '');
            $schedule[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'dosage_value' => $dosageParts[0] ?? '--',
                'dosage_unit' => isset($dosageParts[1]) ? $dosageParts[1] : '',
                'frequency' => 'Daily',
                'intake_datetime' => $row['intake_datetime'],
                'intake_time' => $row['intake_time'],
                'status' => strtolower($row['status'])
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $schedule
        ]);
        break;


    /* ===============================
       ❌ INVALID ACTION
    =============================== */

    /* ===============================
       GET MESSAGES FROM PATIENT
    =============================== */
    case 'getMessages':
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
        $stmt->bind_param("i", $caretakerId);
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
        $messageId = $_GET['id'] ?? null;

        if (!$messageId) {
            echo json_encode(['status' => 'error', 'message' => 'Message ID required']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE messages 
            SET status = 'read' 
            WHERE id = ? AND caretaker_id = ?
        ");
        $stmt->bind_param("ii", $messageId, $caretakerId);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Message marked as read'
        ]);
        break;

    /* ===============================
       ADD NOTE
    =============================== */
    case 'addNote':
        $patientId = $_POST['patient_id'] ?? null;
        $noteType = $_POST['note_type'] ?? 'general';
        $message = $_POST['message'] ?? '';
        $medicineId = $_POST['medicine_id'] ?? null;

        if (!$patientId || !$message) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields'
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO caretaker_notes (patient_id, caretaker_id, medicine_id, note_type, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiiis",
            $patientId,
            $caretakerId,
            $medicineId,
            $noteType,
            $message
        );

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Note added successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add note'
            ]);
        }
        break;

    /* ===============================
       GET NOTES
    =============================== */
    case 'getNotes':
        // Get the assigned patient
        $stmt = $conn->prepare("
            SELECT patient_id FROM caregivers 
            WHERE caregiver_id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $patientRes = $stmt->get_result()->fetch_assoc();
        $patientId = $patientRes['patient_id'] ?? null;

        if (!$patientId) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT 
                cn.id,
                cn.message,
                cn.note_type,
                cn.medicine_id,
                m.name AS medicine_name,
                cn.created_at
            FROM caretaker_notes cn
            LEFT JOIN medicines m ON cn.medicine_id = m.id
            WHERE cn.patient_id = ?
            ORDER BY cn.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $notes
        ]);
        break;

    /* ===============================
       DELETE NOTE
    =============================== */
    case 'deleteNote':
        $noteId = $_POST['note_id'] ?? null;

        if (!$noteId) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing note ID'
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            DELETE FROM caretaker_notes 
            WHERE id = ? AND caretaker_id = ?
        ");
        $stmt->bind_param("ii", $noteId, $caretakerId);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Note deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete note'
            ]);
        }
        break;

    /* ===============================
       SEND MESSAGE TO PATIENT
    =============================== */
    case 'sendMessageToPatient':
        $patientId = $_POST['patient_id'] ?? null;
        $message = $_POST['message'] ?? '';

        if (!$patientId || !$message) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields'
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
            $caretakerId,
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
