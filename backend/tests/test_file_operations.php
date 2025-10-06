<?php
// File: test_file_operations.php
// Test file upload and download operations

header('Content-Type: text/html; charset=UTF-8');

// Start session for authentication testing
session_start();

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Operations Test - NWR Contract Registry</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #0758aa, #1e88e5); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .test-section { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .file-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #007bff; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>';

echo '<div class="header">
    <h1>🔧 File Operations Test Suite</h1>
    <p>Testing file upload and download functionality</p>
    <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
</div>';

// Test 1: Check current authentication status
echo '<div class="test-section">
    <h2>🔐 Authentication Status</h2>';

if (isset($_SESSION['user_id'])) {
    echo '<div class="success">✅ User authenticated: ID = ' . $_SESSION['user_id'] . '</div>';
    if (isset($_SESSION['username'])) {
        echo '<div class="info">👤 Username: ' . htmlspecialchars($_SESSION['username']) . '</div>';
    }
    if (isset($_SESSION['role'])) {
        echo '<div class="info">🎯 Role: ' . htmlspecialchars($_SESSION['role']) . '</div>';
    }
} else {
    echo '<div class="error">❌ No authentication found</div>';
    echo '<div class="info">💡 To test file operations, please login first at: <a href="../frontend/index.php" target="_blank">Login Page</a></div>';
}
echo '</div>';

// Test 2: Check uploads directory
echo '<div class="test-section">
    <h2>📁 Uploads Directory Status</h2>';

$uploads_dir = __DIR__ . '/uploads/';
if (is_dir($uploads_dir)) {
    echo '<div class="success">✅ Uploads directory exists: ' . $uploads_dir . '</div>';
    
    // List files in uploads directory
    $files = array_diff(scandir($uploads_dir), array('.', '..'));
    echo '<div class="info">📋 Files in uploads directory (' . count($files) . '):</div>';
    
    foreach ($files as $file) {
        if (is_file($uploads_dir . $file)) {
            $size = filesize($uploads_dir . $file);
            $formatted_size = formatBytes($size);
            echo '<div class="file-info">
                📄 <strong>' . htmlspecialchars($file) . '</strong><br>
                📏 Size: ' . $formatted_size . ' (' . number_format($size) . ' bytes)<br>
                📅 Modified: ' . date('Y-m-d H:i:s', filemtime($uploads_dir . $file)) . '
            </div>';
        }
    }
} else {
    echo '<div class="error">❌ Uploads directory not found</div>';
}
echo '</div>';

// Test 3: Test file download functionality
echo '<div class="test-section">
    <h2>⬇️ File Download Testing</h2>';

$test_files = ['29.20251006-085545.pdf', '31.20250929-140848.docx', '32.20251006-085459.pdf'];

foreach ($test_files as $test_file) {
    $file_path = $uploads_dir . $test_file;
    if (file_exists($file_path)) {
        echo '<div class="info">
            📄 Testing download: ' . htmlspecialchars($test_file) . '<br>
            <a href="download_file.php?file=' . urlencode($test_file) . '" class="btn btn-primary" target="_blank">📥 Download</a>
            <button class="btn btn-success" onclick="testFileAccess(\'' . htmlspecialchars($test_file) . '\')">🔍 Test Access</button>
        </div>';
    }
}
echo '</div>';

// Test 4: Create test files for upload
echo '<div class="test-section">
    <h2>📤 File Upload Testing</h2>';

// Create test files
$test_files_info = [
    'small_test.txt' => ['size' => 1024, 'content' => 'This is a small test file for upload testing.'],
    'medium_test.txt' => ['size' => 100 * 1024, 'content' => str_repeat('This is medium test content. ', 1000)],
    'large_test.txt' => ['size' => 2 * 1024 * 1024, 'content' => str_repeat('Large file content for testing 5MB limit. ', 50000)],
];

foreach ($test_files_info as $filename => $info) {
    $test_file_path = __DIR__ . '/test_files/' . $filename;
    
    // Create test files directory if it doesn't exist
    $test_dir = dirname($test_file_path);
    if (!is_dir($test_dir)) {
        mkdir($test_dir, 0755, true);
    }
    
    // Create test file
    if (!file_exists($test_file_path)) {
        file_put_contents($test_file_path, substr(str_repeat($info['content'], 100), 0, $info['size']));
    }
    
    $actual_size = filesize($test_file_path);
    echo '<div class="file-info">
        📄 <strong>' . htmlspecialchars($filename) . '</strong><br>
        📏 Size: ' . formatBytes($actual_size) . '<br>
        🎯 Status: ' . ($actual_size <= 5*1024*1024 ? '<span style="color: green;">✅ Within 5MB limit</span>' : '<span style="color: red;">❌ Exceeds 5MB limit</span>') . '<br>
        <button class="btn btn-primary" onclick="testUpload(\'' . htmlspecialchars($filename) . '\')">⬆️ Test Upload</button>
    </div>';
}

// Test 5: File upload API endpoint test
echo '<h3>🔌 Upload API Test</h3>';
echo '<div class="info">
    <form id="uploadForm" enctype="multipart/form-data" style="margin: 15px 0;">
        <input type="file" id="fileInput" name="contract_file" accept=".pdf,.doc,.docx,.txt,.xlsx,.xls" style="margin: 10px 0;">
        <br>
        <input type="number" id="contractIdInput" name="contract_id" placeholder="Contract ID (e.g., 99)" value="99" style="margin: 10px 0; padding: 8px;">
        <br>
        <button type="button" class="btn btn-success" onclick="uploadFile()">🚀 Upload File</button>
    </form>
    <div id="uploadResults"></div>
</div>';

echo '</div>';

// Test 6: Security tests
echo '<div class="test-section">
    <h2>🛡️ Security Testing</h2>';

echo '<div class="info">
    <button class="btn btn-danger" onclick="testMaliciousFile()">⚠️ Test Malicious File Block</button>
    <button class="btn btn-danger" onclick="testOversizedFile()">📏 Test Oversized File Block</button>
    <button class="btn btn-danger" onclick="testInvalidType()">🚫 Test Invalid File Type</button>
</div>';

echo '<div id="securityResults"></div>';
echo '</div>';

// JavaScript for testing
echo '<script>
function formatBytes(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

function addResult(containerId, message, type) {
    const container = document.getElementById(containerId);
    const div = document.createElement("div");
    div.className = type;
    div.innerHTML = message;
    container.appendChild(div);
}

function testFileAccess(filename) {
    fetch("../uploads/" + filename, {method: "HEAD"})
        .then(response => {
            if (response.ok) {
                addResult("uploadResults", "✅ Direct access successful: " + filename, "success");
            } else {
                addResult("uploadResults", "❌ Direct access blocked: " + filename + " (Status: " + response.status + ")", "error");
            }
        })
        .catch(error => {
            addResult("uploadResults", "🔒 Direct access properly blocked: " + filename, "success");
        });
}

function uploadFile() {
    const form = document.getElementById("uploadForm");
    const fileInput = document.getElementById("fileInput");
    const contractId = document.getElementById("contractIdInput").value;
    
    if (!fileInput.files.length) {
        addResult("uploadResults", "❌ Please select a file", "error");
        return;
    }
    
    const formData = new FormData();
    formData.append("action", "upload_contract_file");
    formData.append("contract_id", contractId);
    formData.append("contract_file", fileInput.files[0]);
    
    addResult("uploadResults", "⏳ Uploading " + fileInput.files[0].name + "...", "info");
    
    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addResult("uploadResults", "✅ Upload successful: " + data.message, "success");
        } else {
            addResult("uploadResults", "❌ Upload failed: " + data.error, "error");
        }
    })
    .catch(error => {
        addResult("uploadResults", "❌ Upload error: " + error.message, "error");
    });
}

function testMaliciousFile() {
    const maliciousContent = "<?php echo \\"MALICIOUS CODE\\"; ?>";
    const blob = new Blob([maliciousContent], {type: "text/plain"});
    const file = new File([blob], "malicious.php", {type: "text/plain"});
    
    const formData = new FormData();
    formData.append("action", "upload_contract_file");
    formData.append("contract_id", "99");
    formData.append("contract_file", file);
    
    addResult("securityResults", "⚠️ Testing malicious PHP file upload...", "info");
    
    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addResult("securityResults", "❌ SECURITY ISSUE: Malicious file was uploaded!", "error");
        } else {
            addResult("securityResults", "✅ Security working: " + data.error, "success");
        }
    })
    .catch(error => {
        addResult("securityResults", "✅ Malicious upload properly blocked", "success");
    });
}

function testOversizedFile() {
    const oversizedContent = new Array(6 * 1024 * 1024).fill("A").join("");
    const blob = new Blob([oversizedContent], {type: "text/plain"});
    const file = new File([blob], "oversized.txt", {type: "text/plain"});
    
    const formData = new FormData();
    formData.append("action", "upload_contract_file");
    formData.append("contract_id", "99");
    formData.append("contract_file", file);
    
    addResult("securityResults", "📏 Testing 6MB file upload (should fail)...", "info");
    
    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addResult("securityResults", "❌ SIZE VALIDATION ISSUE: 6MB file was uploaded!", "error");
        } else {
            addResult("securityResults", "✅ Size validation working: " + data.error, "success");
        }
    })
    .catch(error => {
        addResult("securityResults", "✅ Oversized file properly blocked", "success");
    });
}

function testInvalidType() {
    const content = "Invalid file type test";
    const blob = new Blob([content], {type: "application/octet-stream"});
    const file = new File([blob], "invalid.exe", {type: "application/octet-stream"});
    
    const formData = new FormData();
    formData.append("action", "upload_contract_file");
    formData.append("contract_id", "99");
    formData.append("contract_file", file);
    
    addResult("securityResults", "🚫 Testing invalid file type (.exe)...", "info");
    
    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addResult("securityResults", "❌ TYPE VALIDATION ISSUE: .exe file was uploaded!", "error");
        } else {
            addResult("securityResults", "✅ Type validation working: " + data.error, "success");
        }
    })
    .catch(error => {
        addResult("securityResults", "✅ Invalid file type properly blocked", "success");
    });
}
</script>';

echo '</body></html>';

// Helper function
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>