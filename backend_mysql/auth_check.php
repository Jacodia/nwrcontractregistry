<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

// Simple auth check: returns loggedIn and basic user info if session exists
$userId = current_user_id();
if (!$userId) {
    echo json_encode(['loggedIn' => false]);
    exit;
}

// Try to fetch user info if users table exists
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo json_encode(['loggedIn' => true, 'user' => $user]);
        exit;
    }
} catch (Exception $e) {
    // ignore DB errors and fallthrough to minimal response
}

// Fallback: return bare user id
echo json_encode(['loggedIn' => true, 'user' => ['id' => $userId]]);

?>
