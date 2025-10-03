<?php
/**
 * Simple test endpoint for file upload validation testing
 */

// Include the upload handler
require_once '../index.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h3>Server-Side Upload Test Result</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadFile'])) {
    try {
        $file = $_FILES['uploadFile'];
        
        echo "<p><strong>File Information:</strong></p>";
        echo "<ul>";
        echo "<li>Original Name: " . htmlspecialchars($file['name']) . "</li>";
        echo "<li>Size: " . formatBytes($file['size']) . " (" . number_format($file['size']) . " bytes)</li>";
        echo "<li>Type: " . htmlspecialchars($file['type']) . "</li>";
        echo "<li>Upload Error: " . $file['error'] . "</li>";
        echo "</ul>";
        
        // Test the upload handler (but don't actually save the file)
        // We'll use a fake contract ID for testing
        $contractId = 999; // Test contract ID
        
        // Call our validation function
        $result = handleFileUpload($file, $contractId);
        
        echo "<div style='color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>✅ Success!</strong><br>";
        echo "File passed all validation checks.<br>";
        echo "Would be saved as: " . htmlspecialchars($result);
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>❌ Validation Failed!</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
    }
} else {
    echo "<p>No file uploaded or invalid request method.</p>";
}

echo "<p><a href='test_file_upload_page.html' style='color: #007bff; text-decoration: none;'>← Back to Test Page</a></p>";
?>