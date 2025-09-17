<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Contract.php';

// Set test recipient and contract details
$testRecipient = 'raybicycle@gmail.com'; // Change to your email for testing
$testContractType = 'Test Contract';
$testExpiryDate = date('Y-m-d', strtotime('+10 days'));

$contract = new Contract($pdo);

// Directly test the email notification method
$contract->sendEmailNotification1($testRecipient, $testContractType, $testExpiryDate);

echo "Test email sent (if no errors above). Check your inbox.";

?>