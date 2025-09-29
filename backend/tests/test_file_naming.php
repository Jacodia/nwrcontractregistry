<?php
// Direct test of file moving with new naming convention
echo "=== Direct File Move Test ===\n\n";

// Test parameters
$contractId = 999; // Test contract ID
$uploadDir = __DIR__ . '/uploads/';

// Ensure uploads directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "Created uploads directory\n";
}

// Create test files
$testFiles = [
    ['name' => 'test1.pdf', 'content' => 'PDF test content'],
    ['name' => 'test2.docx', 'content' => 'DOCX test content'],
    ['name' => 'test3.xlsx', 'content' => 'XLSX test content']
];

echo "Testing file naming convention:\n";

foreach ($testFiles as $index => $testFile) {
    // Create temporary source file
    $tempFile = $uploadDir . 'temp_' . $testFile['name'];
    file_put_contents($tempFile, $testFile['content'] . " - Created at " . date('Y-m-d H:i:s'));
    
    // Get file extension
    $extension = pathinfo($testFile['name'], PATHINFO_EXTENSION);
    
    // Generate new filename using the same logic as Contract.php
    $timestamp = date('Ymd-His'); // Format: YYYYMMDD-HHMMSS
    $newFileName = $contractId . '.' . $timestamp . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;
    
    // Move file (simulating move_uploaded_file)
    if (rename($tempFile, $targetPath)) {
        echo "  ✅ File " . ($index + 1) . ": $newFileName\n";
        echo "     Source: {$testFile['name']}\n";
        echo "     Target: $newFileName\n";
        echo "     Size: " . filesize($targetPath) . " bytes\n";
        
        // Verify filename format
        $parts = explode('.', $newFileName);
        if (count($parts) >= 3) {
            $fileContractId = $parts[0];
            $fileTimestamp = $parts[1]; 
            $fileExtension = $parts[2];
            
            echo "     Contract ID: $fileContractId " . ($fileContractId == $contractId ? "✅" : "❌") . "\n";
            echo "     Timestamp: $fileTimestamp " . (preg_match('/^\d{8}-\d{6}$/', $fileTimestamp) ? "✅" : "❌") . "\n";
            echo "     Extension: $fileExtension " . ($fileExtension == $extension ? "✅" : "❌") . "\n";
        }
    } else {
        echo "  ❌ Failed to move file: {$testFile['name']}\n";
    }
    
    echo "\n";
    sleep(1); // Ensure different timestamps
}

// Test with different contract IDs
echo "Testing with different contract IDs:\n";
$testContractIds = [1, 25, 150, 1000];

foreach ($testContractIds as $id) {
    $timestamp = date('Ymd-His');
    $filename = $id . '.' . $timestamp . '.pdf';
    echo "  Contract $id: $filename\n";
    sleep(1);
}

echo "\nTesting the alternative timestamp format:\n";
foreach ($testContractIds as $id) {
    $timestamp = date('Y-m-d-H-i-s');
    $filename = $id . '.' . $timestamp . '.pdf';
    echo "  Contract $id: $filename\n";
    sleep(1);
}

echo "\n=== Listing all files in uploads directory ===\n";
$allFiles = glob($uploadDir . '*');
foreach ($allFiles as $file) {
    $fileName = basename($file);
    $fileSize = filesize($file);
    $modified = date('Y-m-d H:i:s', filemtime($file));
    echo "  $fileName ($fileSize bytes) - Modified: $modified\n";
}

echo "\n=== Test Complete ===\n";
?>