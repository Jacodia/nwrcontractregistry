<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload
require '../vendor/autoload.php';

echo "=== Quick Email Test ===\n";

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
    $mail->setFrom('uraniathomas23@gmail.com', 'NWR Contract Registry');
    $mail->addAddress('uraniathomas23@gmail.com'); // Send test to same address

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Contract Reminder Test - ' . date('Y-m-d H:i:s');
    $mail->Body    = '
    <h2>Contract Reminder System Test</h2>
    <p>This is a test email from the NWR Contract Registry notification system.</p>
    <p><strong>Test Details:</strong></p>
    <ul>
        <li>Date: ' . date('Y-m-d H:i:s') . '</li>
        <li>System: Email notification functionality</li>
        <li>Status: Testing contract reminders</li>
    </ul>
    <p>If you receive this email, the notification system is working correctly.</p>
    ';

    $mail->send();
    echo "✅ Test email sent successfully!\n";
    echo "📧 Email sent to: uraniathomas23@gmail.com\n";
    echo "📅 Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}\n";
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "=== Test Complete ===\n";
?>