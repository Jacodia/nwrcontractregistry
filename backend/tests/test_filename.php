<?php
// Test file naming convention
$contractId = 123;
$timestamp = date('Ymd-His'); // Format: YYYYMMDD-HHMMSS
$extension = 'pdf';

$newFileName = $contractId . '.' . $timestamp . '.' . $extension;

echo "Contract ID: $contractId\n";
echo "Timestamp: $timestamp\n"; 
echo "Extension: $extension\n";
echo "Generated filename: $newFileName\n";

// Alternative format if you prefer YYYY-MM-DD-HH-MM-SS
$timestamp2 = date('Y-m-d-H-i-s');
$newFileName2 = $contractId . '.' . $timestamp2 . '.' . $extension;
echo "\nAlternative format:\n";
echo "Timestamp: $timestamp2\n";
echo "Generated filename: $newFileName2\n";

// Show examples with different contract IDs
echo "\nExample filenames:\n";
for ($i = 1; $i <= 5; $i++) {
    $ts = date('Ymd-His');
    echo "Contract $i: $i.$ts.pdf\n";
    sleep(1); // Small delay to show different timestamps
}
?>