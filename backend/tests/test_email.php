<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config/db.php';
require 'models/Contract.php';

$contract = new Contract($pdo);

// ----------------------------
// Determine frequency for testing
// ----------------------------
$day = date('N'); // 1 = Monday, 7 = Sunday

$frequencies = [];
if ($day == 1) {             // Monday
    $frequencies[] = 'weekly';
    $frequencies[] = 'twice';
}
if ($day == 4) {             // Thursday
    $frequencies[] = 'twice';
}
$frequencies[] = 'daily';    // Always include daily

// Map frequency to number of days ahead
$daysMap = [
    'weekly' => 90,   // 3 months
    'twice'  => 60,   // 2 months
    'daily'  => 30,   // 1 month
];

$emailsSent = 0;

foreach ($frequencies as $freq) {
    $days = $daysMap[$freq];

    // Fetch only contracts expiring in the next $days
    $sql = "SELECT c.*, u.email AS manager_email, u.receive_notifications
            FROM contracts c
            INNER JOIN users u ON c.manager_id = u.userid
            WHERE u.receive_notifications = 1
              AND c.expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['days' => $days]);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$contracts) {
        echo "No contracts to notify for '{$freq}' frequency.<br>";
        continue;
    }

    foreach ($contracts as $c) {
        $recipientEmail = $c['manager_email'];
        $contractType   = $c['typeOfContract'];
        $expiryDate     = $c['expiryDate'];

        $sent = $contract->sendEmailNotification($recipientEmail, $contractType, $expiryDate);

        if ($sent) {
            echo "Email sent for contract ID {$c['contractid']} ({$contractType}) to {$recipientEmail} [Frequency: {$freq}]<br>";
            $emailsSent++;
        } else {
            echo "Failed to send email for contract ID {$c['contractid']} ({$contractType}) to {$recipientEmail}<br>";
        }
    }
}

if ($emailsSent === 0) {
    echo "No emails were sent today.<br>";
} else {
    echo "Total emails sent: {$emailsSent}<br>";
}
