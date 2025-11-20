<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Contract.php';

// require_login(); // uncomment to require login

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

if (empty($data['title'])) {
    http_response_code(400);
    echo json_encode(['error' => 'title required']);
    exit;
}

$c = new Contract();
$id = $c->create($data);

echo json_encode(['success' => true, 'id' => $id]);

?>
