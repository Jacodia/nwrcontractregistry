<?php
// File: download_file.php
// Secure file download handler

session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get requested file
$filename = $_GET['file'] ?? '';
$contract_id = $_GET['contract_id'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File not specified']);
    exit;
}

// Validate filename format (contract_id.date-time.extension)
if (!preg_match('/^\d+\.\d{8}-\d{6}\.(pdf|doc|docx)$/i', $filename)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid filename format']);
    exit;
}

// Build file path
$uploads_dir = __DIR__ . '/uploads/';
$file_path = $uploads_dir . $filename;

// Check if file exists
if (!file_exists($file_path)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Additional security: Check if user has access to this contract
// This would typically check database permissions
// For now, we'll allow any logged-in user to download

// Get file info
$file_size = filesize($file_path);
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Download headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $file_size);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Stream the file
$handle = fopen($file_path, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to open file']);
}

exit;
?>