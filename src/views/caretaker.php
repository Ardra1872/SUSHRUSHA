<?php
session_start();
require '../config/db.php';
require '../helpers/sendOtp.php';

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

        if ($patient) {
            // Calculate Adherence Score
            $patientId = $patient['patient_id'];
            $adherencePct = 0;

            // 1. Check if patient has medicines
            $countMedStmt = $conn->prepare("SELECT COUNT(*) as total_meds FROM medicines WHERE patient_id = ?");
            $countMedStmt->bind_param("i", $patientId);
            $countMedStmt->execute();
            $totalMeds = $countMedStmt->get_result()->fetch_assoc()['total_meds'];
            $countMedStmt->close();

            // 2. Calculate based on logs if meds exist
            if ($totalMeds > 0) {
                $logStmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_attempts,
                        SUM(CASE WHEN dl.status = 'TAKEN' THEN 1 ELSE 0 END) as taken_count
                    FROM dose_logs dl
                    JOIN medicine_schedule ms ON dl.schedule_id = ms.id
                    JOIN medicines m ON ms.medicine_id = m.id
                    WHERE m.patient_id = ?
                ");
                $logStmt->bind_param("i", $patientId);
                $logStmt->execute();
                $logData = $logStmt->get_result()->fetch_assoc();
                $logStmt->close();

                $totalLogs = intval($logData['total_attempts']);
                $takenCount = intval($logData['taken_count']);

                if ($totalLogs > 0) {
                    $adherencePct = round(($takenCount / $totalLogs) * 100);
                }
            }
            
            $patient['adherence_score'] = $adherencePct;
        }

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
                (SELECT doctor_name FROM prescriptions WHERE patient_id = u.id ORDER BY prescription_date DESC, id DESC LIMIT 1) AS doctor_name,
                (SELECT hospital_name FROM prescriptions WHERE patient_id = u.id ORDER BY prescription_date DESC, id DESC LIMIT 1) AS hospital_name
            FROM caregivers c
            JOIN users u 
                ON u.id = c.patient_id
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
                dl.log_time AS intake_datetime,
                dl.status
            FROM medicines m
            JOIN medicine_schedule ms ON m.id = ms.medicine_id
            JOIN dose_logs dl ON ms.id = dl.schedule_id
            WHERE m.patient_id = ? AND (dl.status = 'Missed' OR dl.status = 'missed')
            ORDER BY dl.log_time DESC
        ");
        $stmt->bind_param("i", $patientId);
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
        try {
            date_default_timezone_set('Asia/Kolkata'); // Match system timezone
            
            $dayMap = [
                'Mon' => 'M', 'Tue' => 'T', 'Wed' => 'W', 'Thu' => 'Th', 'Fri' => 'F', 'Sat' => 'S', 'Sun' => 'Su'
            ];
            $todayAbbr = $dayMap[date('D')];
            $todayDate = date('Y-m-d');
            $nowTime = date('H:i:s');

            $stmt = $conn->prepare("
                SELECT 
                    m.id,
                    m.name,
                    m.dosage_value,
                    m.dosage_unit,
                    m.medicine_type,
                    m.days,
                    m.schedule_type,
                    m.start_date,
                    m.end_date,
                    ms.id AS schedule_id,
                    ms.intake_time,
                    CONCAT('$todayDate', ' ', COALESCE(ms.intake_time, '09:00:00')) AS intake_datetime,
                    COALESCE(dl.status, 'pending') AS status
                FROM medicines m
                LEFT JOIN medicine_schedule ms ON m.id = ms.medicine_id
                LEFT JOIN dose_logs dl ON ms.id = dl.schedule_id 
                    AND DATE(dl.log_time) = ?
                WHERE m.patient_id = ? 
                ORDER BY ms.intake_time ASC
            ");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            // Bind params: todayDate (for log), patientId (for medicines)
            $stmt->bind_param("si", $todayDate, $patientId);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $schedule = [];
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                // Filter by Day & Date Logic (Robust)
                $schedType = strtolower($row['schedule_type']);
                $daysJson = $row['days'];
                $startDate = $row['start_date'];
                $endDate = $row['end_date'];
                
                // 1. Date Range Check
                if ($startDate && $todayDate < $startDate) continue;
                if ($endDate && $endDate !== '0000-00-00' && $todayDate > $endDate) continue;

                // 2. Specific Days Check
                if ($schedType === 'custom' || $schedType === 'days' || $schedType === 'specific days') {
                     $allowedDays = json_decode($daysJson, true);
                     // Fallback for string format or bad JSON
                     if (!is_array($allowedDays)) {
                         // Remove brackets, quotes, spaces
                         $clean = str_replace(['[', ']', '"', "'"], '', $daysJson); 
                         $allowedDays = explode(',', $clean);
                     }
                     
                     // Check if today matches any format (Full: Mon, Short: M)
                     $todayFull = date('D'); // Mon
                     $isMatch = false;
                     if (is_array($allowedDays)) {
                        // Trim each day just in case
                        $allowedDays = array_map('trim', $allowedDays);
                        
                        if (in_array($todayAbbr, $allowedDays)) $isMatch = true;
                        if (in_array($todayFull, $allowedDays)) $isMatch = true; 
                     }
                     
                     if (!$isMatch) continue;
                }
                
                // 3. Status Calculation (Implicit Missed)
                $status = strtolower($row['status']);
                if ($status === 'pending') {
                    if ($row['intake_time'] < $nowTime) {
                         $status = 'missed';
                    }
                }

                $schedule[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'dosage_value' => $row['dosage_value'] ?? '--',
                    'dosage_unit' => $row['dosage_unit'] ?? '',
                    'frequency' => 'Daily',
                    'medicine_type' => $row['medicine_type'] ?? 'pill',
                    'intake_datetime' => $row['intake_datetime'],
                    'intake_time' => $row['intake_time'],
                    'status' => $status
                ];
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
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

        if ($medicineId === '' || $medicineId === 'null') {
            $medicineId = null;
        }

        $stmt = $conn->prepare("
            INSERT INTO caretaker_notes (patient_id, caretaker_id, medicine_id, note_type, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiiss",
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

    /* ===============================
       GET EMERGENCY CONTACTS
    =============================== */
    case 'getEmergencyContacts':
        // Get the assigned patient
        $stmt = $conn->prepare("SELECT patient_id FROM caregivers WHERE caregiver_id = ? LIMIT 1");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $patientRes = $stmt->get_result()->fetch_assoc();
        $patientId = $patientRes['patient_id'] ?? null;

        if (!$patientId) {
            echo json_encode(['status' => 'error', 'message' => 'No patient assigned']);
            exit;
        }

        // Fetch patient details and their emergency contacts
        // Assuming emergency contacts are in the users table or a separate table.
        // Based on previous code, let's fetch basic patient info + emergency contact info from users table
        
        $stmt = $conn->prepare("
            SELECT 
                u.name AS patient_name,
                u.phone AS contact_number,
                u.emergency_contact,
                u.doctor_name,
                u.hospital_name
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    /* ===============================
       SEND MESSAGE TO EMERGENCY CONTACT
    =============================== */
    case 'sendMessageToEmergencyContact':
        $patientId = $_POST['patient_id'] ?? null;
        $message = $_POST['message'] ?? '';
        $contactNumber = $_POST['contact_number'] ?? '';

        if (!$patientId || !$message || !$contactNumber) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        // In a real app, this would send an SMS or Email.
        // For now, we'll simulate success and maybe log it to a table if needed.
        // We can treat it as a "general" note or just return success.
        
        // Let's log it as a specific note type so it's recorded
        $stmt = $conn->prepare("
            INSERT INTO caretaker_notes (patient_id, caretaker_id, note_type, message)
            VALUES (?, ?, 'emergency_msg', ?)
        ");
        $logMessage = "Sent to EC ($contactNumber): $message";
        $stmt->bind_param("iis", $patientId, $caretakerId, $logMessage);
        
        if($stmt->execute()) {
             echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
        }
        break;

    /* ===============================
       GET PATIENT PRESCRIPTIONS
    =============================== */
    case 'getPatientPrescriptions':
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
                id,
                doctor_name,
                hospital_name,
                disease_name,
                prescription_date,
                created_at
            FROM prescriptions
            WHERE patient_id = ?
            ORDER BY prescription_date DESC
        ");
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $prescriptions
        ]);
        break;

    /* ===============================
       GET PRESCRIPTION DETAILS
    =============================== */
    case 'getPrescriptionDetails':
        $prescriptionId = $_GET['id'] ?? null;
        
        if (!$prescriptionId) {
            echo json_encode(['status' => 'error', 'message' => 'Prescription ID required']);
            exit;
        }

        // Get the assigned patient
        $stmt = $conn->prepare("SELECT patient_id FROM caregivers WHERE caregiver_id = ? LIMIT 1");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $patientRes = $stmt->get_result()->fetch_assoc();
        $patientId = $patientRes['patient_id'] ?? null;

        if (!$patientId) {
            echo json_encode(['status' => 'error', 'message' => 'No patient assigned']);
            exit;
        }

        // Fetch prescription details ensuring it belongs to the patient
        $stmt = $conn->prepare("
            SELECT 
                id,
                doctor_name,
                hospital_name,
                disease_name,
                disease_description,
                notes,
                prescription_date,
                created_at
            FROM prescriptions
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $prescriptionId, $patientId);
        $stmt->execute();
        $prescription = $stmt->get_result()->fetch_assoc();

        if (!$prescription) {
            echo json_encode(['status' => 'error', 'message' => 'Prescription not found']);
            exit;
        }

        // Fetch Medicines
        $stmt = $conn->prepare("
            SELECT id, medicine_name, dosage, frequency, duration, instructions
            FROM prescription_medicines
            WHERE prescription_id = ?
        ");
        $stmt->bind_param("i", $prescriptionId);
        $stmt->execute();
        $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch Tests
        $stmt = $conn->prepare("
            SELECT id, test_name, test_type, test_description, recommended_date, status
            FROM prescription_tests
            WHERE prescription_id = ?
        ");
        $stmt->bind_param("i", $prescriptionId);
        $stmt->execute();
        $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'prescription' => $prescription,
                'medicines' => $medicines,
                'tests' => $tests
            ]
        ]);
        break;

    /* ===============================
       CHANGE PASSWORD FLOW
    =============================== */
    case 'requestPasswordChangeOTP':
        // 1. Get user email
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $caretakerId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        $email = $user['email'];

        // 2. Generate OTP
        $otp = strval(rand(100000, 999999));
        // $expiry calculation removed to use DB time

        // 3. Update DB
        $stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
        $stmt->bind_param("si", $otp, $caretakerId);
        
        if ($stmt->execute()) {
            // 4. Send Email
            if (sendOTP($email, $otp)) {
                echo json_encode(['status' => 'success', 'message' => 'OTP sent to your registered email']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email']);
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
        break;

    case 'verifyPasswordChangeOTP':
        $otp = $_POST['otp'] ?? '';
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND reset_code = ? AND reset_expiry > NOW()");
        $stmt->bind_param("is", $caretakerId, $otp);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'OTP verified']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
        }
        break;

    case 'changePassword':
        $otp = $_POST['otp'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (strlen($newPassword) < 8) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
            exit;
        }

        // Verify OTP again to be safe
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND reset_code = ? AND reset_expiry > NOW()");
        $stmt->bind_param("is", $caretakerId, $otp);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            // Update Password
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE id = ?");
            $stmt->bind_param("si", $hash, $caretakerId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid session or OTP expired']);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}
