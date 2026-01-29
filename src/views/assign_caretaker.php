<?php
// Namespaces must be at the top, global scope
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

// Start output buffering immediately
ob_start();

// Disable error display (log them instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();
    
    // Check if db.php or others exist before including
    if (!file_exists('../config/db.php')) throw new Exception('db.php missing');
    include '../config/db.php';

    if (!file_exists('../config/env.php')) throw new Exception('env.php missing');
    require_once '../config/env.php'; 

    // Include files (classes will be available to the aliases defined at top)
    require '../helpers/PHPMailer/src/PHPMailer.php';
    require '../helpers/PHPMailer/src/SMTP.php';
    require '../helpers/PHPMailer/src/Exception.php';

    // Ensure patient is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get patient info
    $patient_id = $_SESSION['user_id'];
    $patient_name = $_SESSION['user_name'] ?? 'Patient';

    // 🔐 CARETAKER LIMIT CHECK
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM caregivers WHERE patient_id = ?");
    $countStmt->bind_param("i", $patient_id);
    $countStmt->execute();
    $caretakerCount = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // 2️⃣ Check subscription status
    // Defaulting to 0 (Free Plan) since 'is_subscribed' column is missing in schema
    $isSubscribed = 0; 

    if ($caretakerCount >= 1 && !$isSubscribed) {
        ob_end_clean();
        echo json_encode([
            "status" => "error",
            "message" => "Free plan allows only one caretaker. Please upgrade."
        ]);
        exit;
    }

    // Input Validation
    $caretaker_name  = trim($_POST['name'] ?? '');
    $caretaker_email = trim($_POST['email'] ?? '');
    $relation        = trim($_POST['relation'] ?? '');

    if (!$caretaker_name || !$caretaker_email || !$relation) {
        throw new Exception('All fields are required');
    }

    $tempPassword = null;
    $hashedPassword = null;

    // Check / Create User
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $caretaker_email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $caretaker_id = $result_check->fetch_assoc()['id'];
    } else {
        $tempPassword = bin2hex(random_bytes(4)); 
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        $stmt_insert = $conn->prepare("INSERT INTO users (name, email, role, password, patient_id, first_login) VALUES (?, ?, 'caretaker', ?, ?, 1)");
        $stmt_insert->bind_param("sssi", $caretaker_name, $caretaker_email, $hashedPassword, $patient_id);
        if (!$stmt_insert->execute()) throw new Exception('Failed to create user: ' . $conn->error);
        $caretaker_id = $conn->insert_id;
    }

    // Assign
    $stmt_cg_check = $conn->prepare("SELECT id FROM caregivers WHERE patient_id = ? AND caregiver_id = ?");
    $stmt_cg_check->bind_param("ii", $patient_id, $caretaker_id);
    $stmt_cg_check->execute();
    
    if ($stmt_cg_check->get_result()->num_rows === 0) {
        $stmt_cg = $conn->prepare("INSERT INTO caregivers (patient_id, caregiver_id, relation) VALUES (?, ?, ?)");
        $stmt_cg->bind_param("iis", $patient_id, $caretaker_id, $relation);
        if (!$stmt_cg->execute()) throw new Exception('Failed to assign caretaker');
    }

    // Send Email (silently catch errors)
    if ($tempPassword) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']; 
            $mail->Password   = $_ENV['MAIL_PASSWORD']; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            $mail->setFrom($_ENV['SMTP_EMAIL'], 'SUSHRUSHA');
            $mail->addAddress($caretaker_email, $caretaker_name);
            $mail->isHTML(true);
            $mail->Subject = 'SUSHRUSHA: Caretaker Account - One-Time Password';
            $mail->Body    = "Hello {$caretaker_name},<br>You have been assigned as a caretaker.<br>Password: <strong>{$tempPassword}</strong>";
            $mail->send();
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
    }

    ob_end_clean(); 
    echo json_encode(['status' => 'success']);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
