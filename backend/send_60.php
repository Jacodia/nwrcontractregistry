<?php
require_once 'ContractNotifier.php';

$notifier = new ContractNotifier();
$notifier->checkAndSend(60, 'twice'); // 2 months
?>