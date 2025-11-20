<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/auth.php';

logout_user();

echo json_encode(['success' => true]);

?>
