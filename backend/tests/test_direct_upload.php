<?php
// Direct test of controller and model with new file upload logic
echo "=== Direct Controller Test ===\n\n";

require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'controllers/ContractController.php';

// Mock authentication for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

Auth::init($pdo);

// Create controller
$controller = new ContractController($pdo);

// Test data
$testData = [
    'parties' => 'Direct Test Company A // Direct Test Company B',
    'typeOfContract' => 'Service Agreement',
    'duration' => '12 months',
    'contractValue' => '45000.00',
    'description' => 'Direct controller test contract',
    'expiryDate' => '2026-09-29',
    'reviewByDate' => '2026-06-29'
];

echo "Creating contract without file...\n";
$result = $controller->create($testData);

if ($result['success']) {
    $contractId = $result['contractid'];
    echo "✅ Contract created with ID: $contractId\n";
    
    // Now test file upload with the new function
    $uploadDir = __DIR__ . '/uploads/';
    $testFileName = 'direct_test.pdf';
    $testFilePath = $uploadDir . $testFileName;
    $testContent = "Direct test file content - " . date('Y-m-d H:i:s');
    
    file_put_contents($testFilePath, $testContent);
    
    // Simulate file upload array
    $mockFile = [
        'name' => $testFileName,
        'tmp_name' => $testFilePath,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($testContent)
    ];
    
    // Test the new handleFileUpload function
    require_once 'index.php'; // This will include the function
    
    echo "Testing handleFileUpload function...\n";
    $filepath = handleFileUpload($mockFile, $contractId, $testData['parties']);
    
    if ($filepath) {
        echo "✅ File upload successful: $filepath\n";
        
        // Update contract with file path
        $updateResult = $controller->update($contractId, ['filepath' => $filepath]);
        
        if ($updateResult['success']) {
            echo "✅ Contract updated with file path\n";
            
            // Verify in database
            $contract = $controller->view($contractId);
            if ($contract && $contract['filepath']) {
                echo "✅ File path saved to database: {$contract['filepath']}\n";
                
                // Check if file exists with correct naming
                $fileName = basename($contract['filepath']);
                $fullPath = __DIR__ . '/' . $contract['filepath'];
                
                if (file_exists($fullPath)) {
                    echo "✅ File exists on filesystem: $fileName\n";
                    
                    // Verify naming convention
                    $parts = explode('.', $fileName);
                    if (count($parts) >= 3) {
                        $fileContractId = $parts[0];
                        $timestamp = $parts[1];
                        $extension = $parts[2];
                        
                        echo "  - Contract ID in filename: $fileContractId " . ($fileContractId == $contractId ? "✅" : "❌") . "\n";
                        echo "  - Timestamp: $timestamp " . (preg_match('/^\d{8}-\d{6}$/', $timestamp) ? "✅" : "❌") . "\n";
                        echo "  - Extension: $extension ✅\n";
                        
                        if ($fileContractId == $contractId && preg_match('/^\d{8}-\d{6}$/', $timestamp)) {
                            echo "🎉 NEW NAMING CONVENTION WORKING PERFECTLY!\n";
                        }
                    }
                } else {
                    echo "❌ File does not exist on filesystem\n";
                }
                
            } else {
                echo "❌ File path not found in database\n";
            }
        } else {
            echo "❌ Failed to update contract with file path\n";
        }
    } else {
        echo "❌ File upload failed\n";
    }
    
    // Clean up test file
    if (file_exists($testFilePath)) {
        unlink($testFilePath);
        echo "Cleaned up original test file\n";
    }
    
} else {
    echo "❌ Failed to create contract: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\n=== Files with new naming convention ===\n";
$allFiles = glob(__DIR__ . '/uploads/*');
$newFormatFiles = [];

foreach ($allFiles as $file) {
    $fileName = basename($file);
    if (preg_match('/^\d+\.\d{8}-\d{6}\.\w+$/', $fileName)) {
        $newFormatFiles[] = $fileName;
        echo "  ✅ $fileName\n";
    }
}

if (empty($newFormatFiles)) {
    echo "  No files found with new naming convention\n";
} else {
    echo "\n🎉 Found " . count($newFormatFiles) . " files with correct naming convention!\n";
}

echo "\n=== Test Complete ===\n";
?>