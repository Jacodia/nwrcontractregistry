<?php
require_once __DIR__ . "/config/db.php";
// Add PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set("Africa/Windhoek"); // adjust for your timezone

// Setup logging
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/reminder_log.txt';

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
    logMessage("Created logs directory: $logDir");
}

function logMessage($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$timestamp - $msg" . PHP_EOL, FILE_APPEND);
}

// Optional: Log rotation to prevent huge files
if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB limit
    rename($logFile, $logDir . '/reminder_log_' . date('YmdHis') . '.txt');
    logMessage("Rotated log file");
}

$sql = "
    SELECT c.contractid, c.parties, c.typeOfContract, c.expiryDate, u.email, u.username
    FROM contracts c
    JOIN users u ON c.manager_id = u.userid
    WHERE c.expiryDate IS NOT NULL
";
$stmt = $pdo->query($sql);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTime();

foreach ($contracts as $contract) {
    $expiry   = new DateTime($contract['expiryDate']);
    $daysLeft = $today->diff($expiry)->days;

    if ($expiry < $today) continue; // already expired

    $send = false;

    if (in_array($daysLeft, [90, 60, 30, 3])) {
        $send = true; // exact milestones
    } elseif ($daysLeft < 30 && $daysLeft > 0) {
        $send = true; // daily under 30 days
    } elseif ($daysLeft <= 60 && $daysLeft > 30) {
        if ($today->format("N") == 1) { // Monday
            $send = true; // weekly under 60 days
        }
    } elseif ($daysLeft <= 90 && $daysLeft > 60) {
        if ($today->format("N") == 1) { // Monday
            $send = true; // weekly under 90 days
        }
    }

    if ($send) {
        $mail = new PHPMailer(true); // true enables exceptions

        try {
            // SMTP settings - read from environment variables to avoid hardcoding secrets
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?? '';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = intval($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587);

            // Validate SMTP credentials presence; if missing, log and skip sending
            if (empty($mail->Username) || empty($mail->Password)) {
                logMessage("SMTP credentials not configured; skipping email to {$contract['email']}");
                continue;
            }

            // Recipients
            $mail->setFrom($mail->Username, 'Contract Registry System'); // Sender
            $mail->addAddress($contract['email'], $contract['username']); // Manager's email

            // Content
            $mail->isHTML(false); // Plain text for now (you can set true for HTML)
            $mail->Subject = "Contract Expiry Reminder (Contract {$contract['typeOfContract']})";
            $mail->Body    = "Hello {$contract['username']},\n\n"
                           . "Contract {$contract['typeOfContract']} between {$contract['parties']} will expire on {$contract['expiryDate']} "
                           . "({$daysLeft} days left).\n\n"
                           . "Please take necessary action.\n\n"
                           . "Regards,\nContract Registry System";

            $mail->send();
            logMessage("Sent reminder to {$contract['email']} (expires in $daysLeft days)");
        } catch (Exception $e) {
            logMessage("Failed to send reminder to {$contract['email']}: {$mail->ErrorInfo}");
        }
    }
}