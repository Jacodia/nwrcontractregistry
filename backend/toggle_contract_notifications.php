<?php
require 'config/db.php';

// Ensure user is logged in (or pass userid via POST)
$userid = intval($_POST['userid'] ?? 0);
$enabled = intval($_POST['enabled'] ?? 1); // 1 or 0

if ($userid > 0) {
    $stmt = $pdo->prepare("UPDATE users SET receive_notifications = :enabled WHERE userid = :userid");
    $stmt->execute(['enabled' => $enabled, 'userid' => $userid]);
    echo json_encode(['status' => 'success', 'enabled' => $enabled]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid userid']);
}
