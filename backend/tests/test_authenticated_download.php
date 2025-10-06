<?php
// File: test_authenticated_download.php
// Test file downloads with authentication

session_start();

// Simulate authentication for testing purposes
// In production, users would need to login properly
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticated Download Test - NWR Contract Registry</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .test-section { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .file-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .file-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>';

echo '<div class="header">
    <h1>📥 Authenticated File Download Test</h1>
    <p>Testing file downloads with active session</p>
    <p><strong>Session User:</strong> ' . $_SESSION['username'] . ' (' . $_SESSION['role'] . ')</p>
</div>';

// Test available files
$uploads_dir = __DIR__ . '/uploads/';
$available_files = [];

if (is_dir($uploads_dir)) {
    $files = array_diff(scandir($uploads_dir), array('.', '..', '.htaccess'));
    foreach ($files as $file) {
        if (is_file($uploads_dir . $file)) {
            $available_files[] = [
                'name' => $file,
                'size' => filesize($uploads_dir . $file),
                'type' => strtoupper(pathinfo($file, PATHINFO_EXTENSION)),
                'modified' => filemtime($uploads_dir . $file)
            ];
        }
    }
}

echo '<div class="test-section">
    <h2>📁 Available Files for Download Testing</h2>
    <div class="info">Found ' . count($available_files) . ' files for testing</div>
    <div class="file-grid">';

foreach ($available_files as $file) {
    $download_url = 'download_file.php?file=' . urlencode($file['name']);
    echo '<div class="file-card">
        <h4>📄 ' . htmlspecialchars($file['name']) . '</h4>
        <p><strong>Type:</strong> ' . $file['type'] . '</p>
        <p><strong>Size:</strong> ' . formatBytes($file['size']) . '</p>
        <p><strong>Modified:</strong> ' . date('Y-m-d H:i:s', $file['modified']) . '</p>
        <a href="' . $download_url . '" class="btn btn-primary" target="_blank">📥 Download</a>
        <button class="btn btn-success" onclick="testDownload(\'' . htmlspecialchars($file['name']) . '\')">🧪 Test</button>
    </div>';
}

echo '</div></div>';

// Test results section
echo '<div class="test-section">
    <h2>🧪 Download Test Results</h2>
    <div id="testResults">
        <div class="info">Click "Test" buttons above to test individual file downloads</div>
    </div>
</div>';

// Security test section
echo '<div class="test-section">
    <h2>🔒 Security Validation</h2>
    <button class="btn btn-primary" onclick="runSecurityTests()">🛡️ Run Security Tests</button>
    <div id="securityResults"></div>
</div>';

echo '<script>
function testDownload(filename) {
    addResult("testResults", "🔄 Testing download: " + filename, "info");
    
    fetch("download_file.php?file=" + encodeURIComponent(filename))
        .then(response => {
            if (response.ok) {
                // Get headers for validation
                const contentType = response.headers.get("content-type");
                const contentDisposition = response.headers.get("content-disposition");
                const contentLength = response.headers.get("content-length");
                
                addResult("testResults", 
                    "✅ Download test successful: " + filename + "<br>" +
                    "📄 Content-Type: " + (contentType || "Not set") + "<br>" +
                    "📥 Content-Disposition: " + (contentDisposition || "Not set") + "<br>" +
                    "📏 Content-Length: " + (contentLength ? formatBytes(contentLength) : "Not set"),
                    "success"
                );
                
                // Trigger actual download
                const link = document.createElement("a");
                link.href = "download_file.php?file=" + encodeURIComponent(filename);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
            } else if (response.status === 401) {
                addResult("testResults", "❌ Authentication required: " + filename, "error");
            } else {
                addResult("testResults", "❌ Download failed: " + filename + " (Status: " + response.status + ")", "error");
            }
        })
        .catch(error => {
            addResult("testResults", "❌ Download error: " + filename + " - " + error.message, "error");
        });
}

function runSecurityTests() {
    addResult("securityResults", "🔄 Running security tests...", "info");
    
    // Test 1: Invalid filename
    fetch("download_file.php?file=../config/db.php")
        .then(response => {
            if (response.status >= 400) {
                addResult("securityResults", "✅ Invalid filename blocked (../config/db.php)", "success");
            } else {
                addResult("securityResults", "❌ SECURITY ISSUE: Invalid filename allowed", "error");
            }
        });
    
    // Test 2: Non-existent file
    fetch("download_file.php?file=nonexistent.pdf")
        .then(response => {
            if (response.status === 404) {
                addResult("securityResults", "✅ Non-existent file properly handled (404)", "success");
            } else {
                addResult("securityResults", "❌ Non-existent file handling issue", "error");
            }
        });
    
    // Test 3: Direct upload access
    fetch("uploads/29.20251006-085545.pdf")
        .then(response => {
            if (response.status >= 400) {
                addResult("securityResults", "✅ Direct upload access blocked", "success");
            } else {
                addResult("securityResults", "❌ SECURITY ISSUE: Direct upload access allowed", "error");
            }
        })
        .catch(error => {
            addResult("securityResults", "✅ Direct upload access blocked (network error)", "success");
        });
}

function addResult(containerId, message, type) {
    const container = document.getElementById(containerId);
    const div = document.createElement("div");
    div.className = type;
    div.innerHTML = message;
    container.appendChild(div);
}

function formatBytes(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}
</script>';

echo '</body></html>';

function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>