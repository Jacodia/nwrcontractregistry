<?php
require_once './ContractNotifier.php';

echo "Testing contract notifications for contracts expiring in 5 days...\n";

// Create a custom notification for contracts expiring in 5 days
$notifier = new ContractNotifier();

// Test with contracts expiring in 5 days (since we have some)
$notifier->checkAndSend(5, 'daily');

echo "Done.\n";
?>