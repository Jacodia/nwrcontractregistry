<?php
header('Content-Type: application/json');
// Accept POST JSON and return success (no persistence)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$name = $data['name'] ?? null;
if (!$name) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'name required']);
    exit;
}

echo json_encode(['success' => true, 'name' => $name]);
?>
