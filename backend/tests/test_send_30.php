<?php
require_once 'ContractNotifier.php';

$notifier = new ContractNotifier();
$notifier->checkAndSend(5, 'daily'); // Test with 5 days instead of 30

echo "\n\n";

echo "5 days email sent (testing modified 30-day script).";

?>