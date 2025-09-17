<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Contract.php';

$contract = new Contract($pdo);
$contract->sendExpiryNotifications(); // no need to pass days now
