<?php
require_once 'ContractNotifier.php';

$notifier = new ContractNotifier();
$notifier->checkAndSend(60, 'twice'); // 31-60 days, Mon/Wed/Thu

echo "\n\n";

echo "60 days email sent.";

?>