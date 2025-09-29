<?php
// Test the updated file upload system via web interface
echo "=== Testing Updated File Upload System ===\n\n";

// Create test file for upload
$uploadDir = __DIR__ . '/uploads/';
$testFileName = 'web_interface_test.pdf';
$testFilePath = $uploadDir . $testFileName;
$testContent = "Web Interface Test Document\nCreated: " . date('Y-m-d H:i:s');

file_put_contents($testFilePath, $testContent);
echo "Created test file: $testFileName\n";

// Simulate form data
$formData = [
    'parties' => 'Web Test Company A // Web Test Company B',
    'typeOfContract' => 'Service Agreement',
    'duration' => '18 months',
    'contractValue' => '85000.00',
    'description' => 'Test contract via updated web interface',
    'expiryDate' => '2027-03-29',
    'reviewByDate' => '2026-12-29'
];

// Prepare cURL with proper multipart form data
$postFields = $formData;
$postFields['contractFile'] = new CURLFile($testFilePath, 'application/pdf', $testFileName);

echo "Sending request to create contract...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/nwrcontractregistry/backend/index.php?action=create',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ],
    CURLOPT_COOKIEFILE => '', // Enable cookie handling for session
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else {
    echo "Response: $response\n\n";
    
    $responseData = json_decode($response, true);
    
    if ($responseData && isset($responseData['success']) && $responseData['success']) {
        $contractId = $responseData['contractid'];
        echo "✅ Contract created successfully with ID: $contractId\n";
        
        // Look for files with the new naming convention
        $pattern = $uploadDir . $contractId . '.*';
        $matchingFiles = glob($pattern);
        
        if (!empty($matchingFiles)) {
            echo "✅ Files found with new naming convention:\n";
            foreach ($matchingFiles as $file) {
                $fileName = basename($file);
                $fileSize = filesize($file);
                echo "  - $fileName ($fileSize bytes)\n";
                
                // Verify filename format
                $parts = explode('.', $fileName);
                if (count($parts) >= 3) {
                    $fileContractId = $parts[0];
                    $timestamp = $parts[1];
                    $extension = $parts[2];
                    
                    echo "    Contract ID: $fileContractId " . ($fileContractId == $contractId ? "✅" : "❌") . "\n";
                    echo "    Timestamp: $timestamp " . (preg_match('/^\d{8}-\d{6}$/', $timestamp) ? "✅" : "❌") . "\n";
                    echo "    Extension: $extension ✅\n";
                }
            }
        } else {
            echo "❌ No files found with new naming convention\n";
        }
        
        // Check database to see if filepath was saved
        require_once 'config/db.php';
        $stmt = $pdo->prepare("SELECT contractid, parties, filepath FROM contracts WHERE contractid = ?");
        $stmt->execute([$contractId]);
        $dbContract = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dbContract) {
            echo "✅ Contract found in database:\n";
            echo "  - ID: {$dbContract['contractid']}\n";
            echo "  - Parties: {$dbContract['parties']}\n";
            echo "  - File path: " . ($dbContract['filepath'] ?: 'NOT SET') . "\n";
            
            if ($dbContract['filepath']) {
                echo "  ✅ File path saved to database\n";
            } else {
                echo "  ❌ File path NOT saved to database\n";
            }
        }
        
    } else {
        echo "❌ Contract creation failed\n";
        if ($responseData && isset($responseData['error'])) {
            echo "Error: {$responseData['error']}\n";
        }
    }
}

// Clean up test file
if (file_exists($testFilePath)) {
    unlink($testFilePath);
    echo "\nCleaned up test file\n";
}

echo "\n=== Current files in uploads directory ===\n";
$allFiles = glob($uploadDir . '*');
foreach ($allFiles as $file) {
    $fileName = basename($file);
    $fileSize = filesize($file);
    echo "  $fileName ($fileSize bytes)\n";
}

echo "\n=== Test Complete ===\n";
?>
