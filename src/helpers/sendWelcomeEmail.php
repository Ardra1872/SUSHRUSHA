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
    error_log("sendWelcomeEmail: SMTP credentials not found in candidate .env locations");
}

function sendWelcomeEmail($toEmail, $userName) {
    $mail = new PHPMailer(true);

    try {
        global $mailUsername, $mailPassword;

        if(empty($mailUsername) || empty($mailPassword)){
            error_log("sendWelcomeEmail: missing SMTP credentials");
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
        $mail->Subject = 'Welcome to SUSHRUSHA!';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #2c3e50;'>Welcome to SUSHRUSHA, $userName!</h2>
                <p>Thank you for registering with us. We are excited to have you on board.</p>
                <p>With SUSHRUSHA, you can manage your medicine schedules and care seamlessly.</p>
                <br>
                <p>Best Regards,<br>The SUSHRUSHA Team</p>
            </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("sendWelcomeEmail Error: " . $mail->ErrorInfo);
        return false;
    }
}
