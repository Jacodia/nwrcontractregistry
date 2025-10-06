<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload
require '../../vendor/autoload.php';

echo "Testing basic email functionality...\n";

$mail = new PHPMailer(true);

try {
    // Gmail SMTP settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'uraniathomas23@gmail.com';
    $mail->Password   = 'lkmkivxthjizqojc';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('uraniathomas23@gmail.com', 'NWR Contracts');
    $mail->addAddress('uraniathomas23@gmail.com'); // Send test to same address

    // Email content
    $mail->isHTML(false);
    $mail->Subject = 'Test Email - Contract Notification System';
    $mail->Body    = 'This is a test email to verify the contract notification system can send emails.';

    $mail->send();
    echo "✅ Test email sent successfully!\n";
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}\n";
    echo "Exception: " . $e->getMessage() . "\n";
}
?>