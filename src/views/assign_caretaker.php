<?php
session_start();
include '../config/db.php';
require_once '../config/env.php'; // load env
require '../helpers/PHPMailer/src/PHPMailer.php';
require '../helpers/PHPMailer/src/SMTP.php';
require '../helpers/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// Ensure patient is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// Get patient info
$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['user_name'];

// Get caretaker form input
$caretaker_name  = trim($_POST['name']);
$caretaker_email = trim($_POST['email']);
$relation        = trim($_POST['relation']);

if (!$caretaker_name || !$caretaker_email || !$relation) {
    die("All fields are required");
}

// 1️⃣ Check if caretaker already exists
$stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt_check->bind_param("s", $caretaker_email);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Caretaker exists, get ID
    $caretaker_id = $result_check->fetch_assoc()['id'];
} else {
    $tempPassword = bin2hex(random_bytes(4)); // 8-char temp password
$hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

$stmt_insert = $conn->prepare("
    INSERT INTO users (name, email, role, password, patient_id, first_login)
    VALUES (?, ?, 'caretaker', ?, ?, 1)
");
$stmt_insert->bind_param("sssi", $caretaker_name, $caretaker_email, $hashedPassword, $patient_id);
$stmt_insert->execute();

    $caretaker_id = $conn->insert_id;
}

// 3️⃣ Insert into caregivers table (avoid duplicate)
$stmt_cg_check = $conn->prepare("SELECT id FROM caregivers WHERE patient_id = ? AND caregiver_id = ?");
$stmt_cg_check->bind_param("ii", $patient_id, $caretaker_id);
$stmt_cg_check->execute();
$result_cg = $stmt_cg_check->get_result();

if ($result_cg->num_rows === 0) {
    $stmt_cg = $conn->prepare("INSERT INTO caregivers (patient_id, caregiver_id, relation) VALUES (?, ?, ?)");
    $stmt_cg->bind_param("iis", $patient_id, $caretaker_id, $relation);
    $stmt_cg->execute();
}

// 4️⃣ Send email with PHPMailer
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
    $mail->Body    = "
        Hello {$caretaker_name},<br><br>
        You have been assigned as a caretaker by {$patient_name}.<br>
        Your temporary password is: <strong>{$tempPassword}</strong><br>
        Please log in and reset your password immediately.<br><br>
        Thanks,<br>SUSHRUSHA Team
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
    // optionally notify user: "Could not send email, but caretaker added"
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>
