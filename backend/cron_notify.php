<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Contract.php';

$contract = new Contract($pdo);

try {
    $contract->sendExpiryNotifications();
    echo " Contract expiry notifications processed successfully.\n";
} catch (Exception $e) {
    echo " Error sending notifications: " . $e->getMessage() . "\n";
    error_log($e->getMessage());
}
