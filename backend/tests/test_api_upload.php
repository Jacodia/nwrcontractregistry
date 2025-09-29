<?php
// Test real contract creation through the API endpoint
echo "=== Testing Contract Creation via API ===\n\n";

// Create a test file first
$uploadDir = __DIR__ . '/uploads/';
$testFileName = 'api_test_contract.pdf';
$testFilePath = $uploadDir . $testFileName;
$testContent = "API Test Contract Document\nCreated: " . date('Y-m-d H:i:s');

file_put_contents($testFilePath, $testContent);
echo "Created test file: $testFileName\n";

// Prepare POST data
$postData = [
    'parties' => 'API Test Company A // API Test Company B',
    'typeOfContract' => 'Service Agreement',
    'duration' => '24 months',
    'contractValue' => '75000.00',
    'description' => 'Test contract created via API for file upload testing',
    'expiryDate' => '2027-09-29',
    'reviewByDate' => '2027-03-29'
];

// Prepare file data for cURL
$fileData = new CURLFile($testFilePath, 'application/pdf', $testFileName);
$postData['contractFile'] = $fileData;

echo "Sending API request to create contract...\n";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/nwrcontractregistry/backend/index.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest'
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else {
    echo "Response: $response\n";
    
    // Try to decode JSON response
    $responseData = json_decode($response, true);
    if ($responseData) {
        if (isset($responseData['success']) && $responseData['success']) {
            $contractId = $responseData['contractid'] ?? 'unknown';
            echo "✅ Contract created successfully with ID: $contractId\n";
            
            // Check if file was uploaded with correct naming
            if ($contractId !== 'unknown') {
                // Look for files with this contract ID
                $uploadedFiles = glob($uploadDir . $contractId . '.*');
                if (!empty($uploadedFiles)) {
                    echo "✅ Files found for contract $contractId:\n";
                    foreach ($uploadedFiles as $file) {
                        $fileName = basename($file);
                        echo "  - $fileName\n";
                        
                        // Verify filename format
                        $parts = explode('.', $fileName);
                        if (count($parts) >= 3 && $parts[0] == $contractId) {
                            echo "    ✅ Filename format is correct\n";
                        }
                    }
                } else {
                    echo "❌ No files found for contract $contractId\n";
                }
            }
        } else {
            echo "❌ API returned error: " . ($responseData['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "Raw response (not JSON): $response\n";
    }
}

// Clean up test file
if (file_exists($testFilePath)) {
    unlink($testFilePath);
    echo "Cleaned up test file\n";
}

echo "\n=== Current files in uploads directory ===\n";
$allFiles = glob($uploadDir . '*');
foreach ($allFiles as $file) {
    $fileName = basename($file);
    echo "  $fileName\n";
}

echo "\n=== Test Complete ===\n";
?>