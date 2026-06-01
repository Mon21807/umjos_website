<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(0);
header('Content-Type: application/json');

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $firstName      = isset($_POST['firstName'])      ? trim($_POST['firstName'])      : '';
    $lastName       = isset($_POST['lastName'])       ? trim($_POST['lastName'])       : '';
    $senderEmail    = isset($_POST['contactEmail'])   ? trim($_POST['contactEmail'])   : '';
    $subject        = isset($_POST['subject'])        ? trim($_POST['subject'])        : '';
    $contactMessage = isset($_POST['contactMessage']) ? trim($_POST['contactMessage']) : '';

    if (empty($firstName) || empty($senderEmail) || empty($contactMessage)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'josurbanministry@gmail.com'; 
        $mail->Password   = 'zryyktbyfxqtmiqx'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('josurbanministry@gmail.com', 'Urban Ministry Website');
        $mail->addAddress('josurbanministry@gmail.com'); 
        $mail->addReplyTo($senderEmail, $firstName . ' ' . $lastName);

        $mail->isHTML(true);
        $mail->Subject = "New Website Message: " . $subject;
        
        $currentDate = date('d M, Y');
        $year = date('Y');

        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #c62828, #b71c1c); color: #ffffff; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; }
                .content { padding: 30px; color: #333; }
                .badge { display: inline-block; background: #fff5f5; color: #c62828; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; margin-bottom: 20px; border: 1px solid #ffcccc; }
                .details { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .details td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
                .label { color: #888; width: 100px; }
                .value { font-weight: 600; color: #222; }
                .msg-box { background: #fafafa; border-left: 4px solid #c62828; padding: 15px; margin-top: 10px; font-style: italic; }
                .btn-wrap { text-align: center; margin-top: 25px; }
                .btn { background: #c62828; color: #ffffff !important; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
                .footer { background: #1a0f0a; color: #888; padding: 20px; text-align: center; font-size: 11px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Urban Ministry Jos</h1>
                </div>
                <div class='content'>
                    <span class='badge'>NEW CONTACT INQUIRY</span>
                    <table class='details'>
                        <tr><td class='label'>Sender:</td><td class='value'>{$firstName} {$lastName}</td></tr>
                        <tr><td class='label'>Email:</td><td class='value'>{$senderEmail}</td></tr>
                        <tr><td class='label'>Date:</td><td class='value'>{$currentDate}</td></tr>
                        <tr><td class='label'>Subject:</td><td class='value'>{$subject}</td></tr>
                    </table>
                    <div style='font-size: 14px; font-weight: bold; color: #c62828;'>Message:</div>
                    <div class='msg-box'>\"" . nl2br(htmlspecialchars($contactMessage)) . "\"</div>
                    <div class='btn-wrap'>
                        <a href='mailto:{$senderEmail}' class='btn'>Reply Directly</a>
                    </div>
                </div>
                <div class='footer'>
                    &copy; {$year} Urban Ministry Jos. All rights reserved.<br>
                    Helping People to Help Themselves
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>