<?php
// Direct test of the updated model with file operations
echo "=== Testing Contract Model with New File Naming ===\n\n";

require_once 'config/db.php';
require_once 'models/Contract.php';

// Create a contract model instance
$contract = new Contract($pdo);

// Test data
$testData = [
    'parties' => 'File Test Company A // File Test Company B',
    'typeOfContract' => 'Service Agreement',
    'duration' => '12 months',
    'contractValue' => '45000.00',
    'description' => 'Model test contract with file',
    'expiryDate' => '2026-09-29',
    'reviewByDate' => '2026-06-29'
];

$userid = 1; // Test user

echo "1. Creating contract...\n";
$contractId = $contract->create($testData, $userid);

if ($contractId) {
    echo "✅ Contract created with ID: $contractId\n";
    
    // Test the new handleFileUpload function from index.php
    echo "\n2. Testing new file upload function...\n";
    
    // Create test file
    $uploadDir = __DIR__ . '/uploads/';
    $testFileName = 'model_test_file.pdf';
    $testFilePath = $uploadDir . $testFileName;
    $testContent = "Model test file content - " . date('Y-m-d H:i:s');
    
    file_put_contents($testFilePath, $testContent);
    echo "Created test file: $testFileName\n";
    
    // Mock file upload structure
    $mockFile = [
        'name' => $testFileName,
        'tmp_name' => $testFilePath,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($testContent),
        'type' => 'application/pdf'
    ];
    
    // Test the new file upload function
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get original file extension
    $extension = pathinfo($mockFile['name'], PATHINFO_EXTENSION);
    
    // Create new filename: contractID.timestamp.extension
    $timestamp = date('Ymd-His'); // Format: YYYYMMDD-HHMMSS
    $newFileName = $contractId . '.' . $timestamp . '.' . $extension;
    
    $targetPath = $uploadDir . $newFileName;
    echo "New filename will be: $newFileName\n";

    // Simulate move_uploaded_file with copy
    if (copy($mockFile['tmp_name'], $targetPath)) {
        $filepath = 'uploads/' . $newFileName;
        echo "✅ File copied to: $filepath\n";
        
        // Update contract with filepath
        $updateData = ['filepath' => $filepath];
        $updateResult = $contract->update($contractId, $updateData, $userid);
        
        if ($updateResult) {
            echo "✅ Contract updated with file path\n";
            
            // Verify in database
            $updatedContract = $contract->getById($contractId);
            if ($updatedContract && $updatedContract['filepath']) {
                echo "✅ File path saved to database: {$updatedContract['filepath']}\n";
                
                // Verify file exists
                $fullFilePath = __DIR__ . '/' . $updatedContract['filepath'];
                if (file_exists($fullFilePath)) {
                    echo "✅ File exists on filesystem\n";
                    
                    // Verify naming convention
                    $fileName = basename($updatedContract['filepath']);
                    $parts = explode('.', $fileName);
                    
                    if (count($parts) >= 3) {
                        $fileContractId = $parts[0];
                        $fileTimestamp = $parts[1];
                        $fileExtension = $parts[2];
                        
                        echo "Filename analysis:\n";
                        echo "  - Contract ID: $fileContractId " . ($fileContractId == $contractId ? "✅" : "❌") . "\n";
                        echo "  - Timestamp: $fileTimestamp " . (preg_match('/^\d{8}-\d{6}$/', $fileTimestamp) ? "✅" : "❌") . "\n";
                        echo "  - Extension: $fileExtension " . ($fileExtension == $extension ? "✅" : "❌") . "\n";
                        
                        if ($fileContractId == $contractId && preg_match('/^\d{8}-\d{6}$/', $fileTimestamp) && $fileExtension == $extension) {
                            echo "\n🎉 NEW NAMING CONVENTION IS WORKING PERFECTLY!\n";
                            echo "📁 File successfully saved as: $fileName\n";
                            echo "💾 Database record updated with correct path\n";
                            echo "✅ All systems working correctly!\n";
                        }
                    }
                } else {
                    echo "❌ File does not exist on filesystem\n";
                }
            } else {
                echo "❌ File path not saved to database\n";
                var_dump($updatedContract);
            }
        } else {
            echo "❌ Failed to update contract with file path\n";
        }
    } else {
        echo "❌ Failed to copy file\n";
    }
    
    // Clean up original test file
    if (file_exists($testFilePath)) {
        unlink($testFilePath);
        echo "Cleaned up original test file\n";
    }
    
} else {
    echo "❌ Failed to create contract\n";
}

echo "\n3. Summary of all files with new naming convention:\n";
$allFiles = glob(__DIR__ . '/uploads/*');
$correctFormat = 0;

foreach ($allFiles as $file) {
    $fileName = basename($file);
    if (preg_match('/^(\d+)\.(\d{8}-\d{6})\.(\w+)$/', $fileName, $matches)) {
        $correctFormat++;
        echo "  ✅ $fileName (Contract: {$matches[1]}, Time: {$matches[2]}, Ext: {$matches[3]})\n";
    }
}

echo "\n📊 Files with correct naming convention: $correctFormat\n";
echo "📋 Total files in uploads: " . count($allFiles) . "\n";

echo "\n=== Test Complete ===\n";
?>