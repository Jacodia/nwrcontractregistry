<?php
require 'config/db.php';           // Your PDO connection
require 'models/contract.php';    // The Contract class

$contract = new Contract($pdo);

// Fetch all contracts from the database
$allContracts = $contract->getAllContracts();

foreach ($allContracts as $c) {
    $recipientEmail = 'uraniathomas@gmail.com'; // Replace with the actual manager email from your users table
    $contractType   = $c['typeOfContract'];
    $expiryDate     = $c['expiryDate'];

    // Send email directly
    $sent = $contract->sendEmailNotification($recipientEmail, $contractType, $expiryDate);

    if ($sent) {
        echo "Email sent for contract ID {$c['contractid']} ({$contractType})<br>";
    } else {
        echo "Failed to send email for contract ID {$c['contractid']}<br>";
    }
}
