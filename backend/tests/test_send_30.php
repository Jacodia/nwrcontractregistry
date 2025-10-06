<?php
require_once './ContractNotifier.php';

echo "Testing 30-day contract notifications...\n";

$notifier = new ContractNotifier();
$notifier->checkAndSend(30, 'daily'); // Test 30-day notifications

echo "\n\n";
echo "30-day email notification test completed.";

?>