<?php
require 'config/db.php';
session_start();

// Get logged-in user ID
$userid = $_SESSION['userid'] ?? 0;
$enabled = intval($_POST['enabled'] ?? 1); // 1 = on, 0 = off

if ($userid > 0) {
    $stmt = $pdo->prepare("UPDATE users SET receive_notifications = :enabled WHERE userid = :userid");
    $stmt->execute(['enabled' => $enabled, 'userid' => $userid]);
    echo json_encode(['status' => 'success', 'enabled' => $enabled]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
}
