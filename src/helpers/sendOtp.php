<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Try multiple locations for credentials. Prefer project root .env, otherwise public/credential.env
$mailUsername = '';
$mailPassword = '';
$candidates = [
    __DIR__ . '/../../.env',           // project root .env
    __DIR__ . '/../../../.env',        // alternative relative path
    __DIR__ . '/../../public/credential.env',
    __DIR__ . '/../../public/.env',
];
foreach($candidates as $envPath){
    if(file_exists($envPath)){
        $env = @parse_ini_file($envPath);
        if($env && is_array($env)){
            $mailUsername = $env['MAIL_USERNAME'] ?? '';
            $mailPassword = $env['MAIL_PASSWORD'] ?? '';
            break;
        }
    }
}
if(empty($mailUsername) || empty($mailPassword)){
    // don't reveal paths to users; log for developer and continue (sendOTP will fail gracefully)
    error_log("sendOtp: SMTP credentials not found in candidate .env locations");
}

function sendOTP($toEmail, $otp) {
    $mail = new PHPMailer(true);

    try {
        // bring credentials into function scope
        global $mailUsername, $mailPassword;

        // guard: if credentials missing, skip sending
        if(empty($mailUsername) || empty($mailPassword)){
            error_log("sendOTP: missing SMTP credentials");
            return false;
        }

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $mailUsername;
        $mail->Password = $mailPassword;

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('ardrasnair2028@mca.ajce.in', 'SUSHRUSHA');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'SUSHRUSHA Password Reset OTP';
        $mail->Body = "
            <h3>Password Reset</h3>
            <p>Your OTP is:</p>
            <h2>$otp</h2>
            <p>Valid for 10 minutes.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Optional: log $mail->ErrorInfo
        return false;
    }
}
