<?php
require 'config/db.php';           // Your PDO connection
require 'models/contract.php';     // The Contract class

$contract = new Contract($pdo);

// Fetch all contracts along with manager emails
$allContracts = $contract->getAllContracts();

foreach ($allContracts as $c) {

    // Use manager_email from the database
    $recipientEmail = $c['manager_email'];
    $contractType   = $c['typeOfContract'];
    $expiryDate     = $c['expiryDate'];

    // Send email directly
    $sent = $contract->sendEmailNotification($recipientEmail, $contractType, $expiryDate);

    if ($sent) {
        echo "Email sent for contract ID {$c['contractid']} ({$contractType}) to {$recipientEmail}<br>";
    } else {

        echo "Failed to send email for contract ID {$c['contractid']} ({$contractType}) to {$recipientEmail}<br>";
    }
}
