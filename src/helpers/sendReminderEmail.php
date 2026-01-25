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
    error_log("sendReminderEmail: SMTP credentials not found in candidate .env locations");
}

function sendReminderEmail($toEmail, $userName, $medicineName, $dosage, $time) {
    $mail = new PHPMailer(true);

    try {
        global $mailUsername, $mailPassword;

        if(empty($mailUsername) || empty($mailPassword)){
            error_log("sendReminderEmail: missing SMTP credentials");
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
        $mail->Subject = 'Medicine Reminder: ' . $medicineName;
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #137fec; text-align: center;'>It's Time for Your Medicine!</h2>
                <p>Hello <strong>$userName</strong>,</p>
                <p>This is a gentle reminder to take your scheduled medication:</p>
                
                <div style='background-color: #f6f7f8; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Medicine:</strong> <span style='font-size: 1.2em; color: #101922;'>$medicineName</span></p>
                    <p style='margin: 5px 0;'><strong>Dosage:</strong> $dosage</p>
                    <p style='margin: 5px 0;'><strong>Scheduled Time:</strong> $time</p>
                </div>

                <p>Please take your medicine as prescribed.</p>
                <br>
                <p style='font-size: 0.9em; color: #777; text-align: center;'>Stay Healthy,<br>The SUSHRUSHA Team</p>
            </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("sendReminderEmail Error: " . $mail->ErrorInfo);
        return false;
    }
}
