<?php
// Test file save functionality with new naming convention
require_once 'config/db.php';
require_once 'models/Contract.php';

echo "=== Testing File Save Functionality ===\n\n";

// Create a Contract instance
$contract = new Contract($pdo);

// Test data for a new contract
$testData = [
    'parties' => 'Test Company A // Test Company B',
    'typeOfContract' => 'Service Agreement',
    'duration' => '12 months',
    'contractValue' => 50000.00,
    'description' => 'Test contract for file upload functionality',
    'expiryDate' => '2026-09-29',
    'reviewByDate' => '2026-06-29'
];

$userid = 1; // Test user ID

echo "1. Testing contract creation with file upload simulation...\n";

// Simulate file upload data
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "   - Created uploads directory\n";
}

// Create a test file to upload
$testFileName = 'test_contract_document.pdf';
$testFilePath = $uploadDir . $testFileName;
$testContent = "This is a test contract document content for testing file upload functionality.\nGenerated at: " . date('Y-m-d H:i:s');

file_put_contents($testFilePath, $testContent);
echo "   - Created test file: $testFileName\n";

// Simulate $_FILES array
$_FILES['contractFile'] = [
    'name' => $testFileName,
    'tmp_name' => $testFilePath,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent),
    'type' => 'application/pdf'
];

echo "   - Simulated file upload data\n";

// Test contract creation
try {
    $contractId = $contract->create($testData, $userid);
    
    if ($contractId) {
        echo "   ✅ Contract created successfully with ID: $contractId\n";
        
        // Verify the contract was created and check the filename
        $createdContract = $contract->getById($contractId);
        
        if ($createdContract) {
            echo "   ✅ Contract retrieved successfully\n";
            echo "   - Parties: {$createdContract['parties']}\n";
            echo "   - Type: {$createdContract['typeOfContract']}\n";
            echo "   - File path: {$createdContract['filepath']}\n";
            
            // Check if file exists with correct naming convention
            if (!empty($createdContract['filepath'])) {
                $fullFilePath = __DIR__ . '/' . $createdContract['filepath'];
                if (file_exists($fullFilePath)) {
                    echo "   ✅ File exists at: {$createdContract['filepath']}\n";
                    
                    // Parse filename to verify format
                    $fileName = basename($createdContract['filepath']);
                    $parts = explode('.', $fileName);
                    
                    if (count($parts) >= 3) {
                        $fileContractId = $parts[0];
                        $timestamp = $parts[1];
                        $extension = $parts[2];
                        
                        echo "   - Contract ID in filename: $fileContractId\n";
                        echo "   - Timestamp in filename: $timestamp\n";
                        echo "   - File extension: $extension\n";
                        
                        if ($fileContractId == $contractId) {
                            echo "   ✅ Contract ID matches in filename\n";
                        } else {
                            echo "   ❌ Contract ID mismatch in filename\n";
                        }
                        
                        // Verify timestamp format (YYYYMMDD-HHMMSS)
                        if (preg_match('/^\d{8}-\d{6}$/', $timestamp)) {
                            echo "   ✅ Timestamp format is correct (YYYYMMDD-HHMMSS)\n";
                        } else {
                            echo "   ❌ Timestamp format is incorrect\n";
                        }
                    } else {
                        echo "   ❌ Filename format is incorrect\n";
                    }
                } else {
                    echo "   ❌ File does not exist at specified path\n";
                }
            } else {
                echo "   ❌ No file path stored in database\n";
            }
        } else {
            echo "   ❌ Failed to retrieve created contract\n";
        }
        
    } else {
        echo "   ❌ Failed to create contract\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error during contract creation: " . $e->getMessage() . "\n";
}

echo "\n2. Testing file update functionality...\n";

if (isset($contractId) && $contractId) {
    // Create another test file for update
    $updateFileName = 'updated_test_contract.docx';
    $updateFilePath = $uploadDir . $updateFileName;
    $updateContent = "This is an updated contract document.\nUpdated at: " . date('Y-m-d H:i:s');
    
    file_put_contents($updateFilePath, $updateContent);
    echo "   - Created update test file: $updateFileName\n";
    
    // Simulate new file upload
    $_FILES['contractFile'] = [
        'name' => $updateFileName,
        'tmp_name' => $updateFilePath,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($updateContent),
        'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $updateData = [
        'description' => 'Updated test contract description'
    ];
    
    try {
        $updateResult = $contract->update($contractId, $updateData, $userid);
        
        if ($updateResult) {
            echo "   ✅ Contract updated successfully\n";
            
            // Verify the update
            $updatedContract = $contract->getById($contractId);
            
            if ($updatedContract) {
                echo "   - Updated description: {$updatedContract['description']}\n";
                echo "   - Updated file path: {$updatedContract['filepath']}\n";
                
                // Check new filename format
                if (!empty($updatedContract['filepath'])) {
                    $newFileName = basename($updatedContract['filepath']);
                    $newParts = explode('.', $newFileName);
                    
                    if (count($newParts) >= 3) {
                        echo "   - New filename: $newFileName\n";
                        echo "   - Contract ID: {$newParts[0]}\n";
                        echo "   - New timestamp: {$newParts[1]}\n";
                        echo "   - Extension: {$newParts[2]}\n";
                        
                        if ($newParts[0] == $contractId && $newParts[2] == 'docx') {
                            echo "   ✅ Updated filename format is correct\n";
                        }
                    }
                }
            }
        } else {
            echo "   ❌ Failed to update contract\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error during contract update: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Listing files in uploads directory:\n";
$uploadedFiles = glob($uploadDir . '*');
foreach ($uploadedFiles as $file) {
    $fileName = basename($file);
    $fileSize = filesize($file);
    echo "   - $fileName ($fileSize bytes)\n";
}

echo "\n=== Test Complete ===\n";
?>