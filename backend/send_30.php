<?php
require_once 'ContractNotifier.php';

$notifier = new ContractNotifier();
$notifier->checkAndSend(30, 'daily'); // 1-30 days, Mon-Fri

echo "\n\n";

echo "30 days email sent.";

?>