<?php
require_once 'ContractNotifier.php';

echo "=== Testing Range Logic ===\n";

$notifier = new ContractNotifier();

echo "\n1. Testing 30-day range (1-30 days, Mon-Fri frequency):\n";
$notifier->checkAndSend(30, 'daily');

echo "\n2. Testing 60-day range (31-60 days, Mon/Wed/Thu frequency):\n";
$notifier->checkAndSend(60, 'twice');

echo "\n3. Testing 90-day range (61-90 days, Mon/Wed frequency):\n";
$notifier->checkAndSend(90, 'weekly');

echo "\n=== Done ===\n";
?>