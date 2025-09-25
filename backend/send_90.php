<?php
require_once 'ContractNotifier.php';

$notifier = new ContractNotifier();
$notifier->checkAndSend(90, 'weekly'); // 61-90 days, Mon/Wed

echo "\n\n";

echo "90 days email sent.";

?>