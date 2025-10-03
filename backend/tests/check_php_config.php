<?php
// Check PHP configuration for file uploads
echo "<h2>PHP Upload Configuration</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='padding: 10px; background: #f0f0f0;'>Setting</th><th style='padding: 10px; background: #f0f0f0;'>Value</th></tr>";

$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off',
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'memory_limit' => ini_get('memory_limit')
];

foreach ($settings as $setting => $value) {
    $color = '';
    if ($setting === 'upload_max_filesize') {
        $color = (intval($value) >= 5) ? 'color: green;' : 'color: red;';
    }
    echo "<tr><td style='padding: 8px;'>$setting</td><td style='padding: 8px; $color'>$value</td></tr>";
}

echo "</table>";

echo "<h3>Upload Test Results</h3>";

// Convert sizes to bytes for comparison
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int) $val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

$upload_max_bytes = convertToBytes(ini_get('upload_max_filesize'));
$post_max_bytes = convertToBytes(ini_get('post_max_size'));
$target_bytes = 5 * 1024 * 1024; // 5MB

echo "<ul>";
echo "<li>Upload max: " . number_format($upload_max_bytes) . " bytes (" . ini_get('upload_max_filesize') . ")</li>";
echo "<li>Post max: " . number_format($post_max_bytes) . " bytes (" . ini_get('post_max_size') . ")</li>";
echo "<li>Target: " . number_format($target_bytes) . " bytes (5MB)</li>";
echo "</ul>";

if ($upload_max_bytes >= $target_bytes) {
    echo "<p style='color: green; font-weight: bold;'>✅ Upload limit is sufficient for 5MB files</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Upload limit is too low for 5MB files</p>";
    echo "<p>Current limit allows files up to " . ini_get('upload_max_filesize') . ", but we need at least 5M.</p>";
    echo "<p><strong>To fix this:</strong> Update XAMPP's php.ini file or create a local configuration override.</p>";
}

if ($post_max_bytes >= $target_bytes) {
    echo "<p style='color: green; font-weight: bold;'>✅ POST size limit is sufficient</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ POST size limit is too low</p>";
}

echo "<h3>Recommendation</h3>";
echo "<p>For optimal functionality, please ensure these PHP settings in your XAMPP php.ini:</p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo "upload_max_filesize = 5M\n";
echo "post_max_size = 6M\n";
echo "max_execution_time = 300\n";
echo "max_input_time = 300\n";
echo "</pre>";

echo "<p><a href='test_file_upload_page.html'>← Back to Upload Test Page</a></p>";
?>