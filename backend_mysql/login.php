<?php
// POST { "username": "...", "password": "..." }
header('Content-Type: application/json');
// Allow CORS for local dev (adjust in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/config/auth.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

if (empty($data['username']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'username and password required']);
    exit;
}

$username = $data['username'];
$password = $data['password'];

$userModel = new User();
$user = $userModel->findByUsername($username);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid credentials']);
    exit;
}

// Support hashed passwords (recommended) or legacy plain text
$stored = $user['password'] ?? '';
$verified = false;
if (strlen($stored) > 0 && password_needs_rehash($stored, PASSWORD_DEFAULT) === false && password_verify($password, $stored)) {
    $verified = true;
} elseif ($stored === $password) {
    // fallback for plain text (not recommended)
    $verified = true;
}

if (!$verified) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid credentials']);
    exit;
}

login_user($user['id']);

echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'username' => $user['username']]]);

?>
