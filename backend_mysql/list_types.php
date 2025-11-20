<?php
header('Content-Type: application/json');
// Minimal stub: return an array of types for the frontend to consume
echo json_encode([
    ['name' => 'Standard'],
    ['name' => 'Service Level Agreement'],
    ['name' => 'NDA']
]);
?>
