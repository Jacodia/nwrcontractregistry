<?php
require_once 'config/db.php';

echo "Checking database for contracts with files:\n";

$stmt = $pdo->query("SELECT contractid, parties, filepath FROM contracts WHERE filepath IS NOT NULL AND filepath != '' ORDER BY contractid DESC LIMIT 10");
$contracts = $stmt->fetchAll();

echo "Found " . count($contracts) . " contracts with files:\n\n";

foreach($contracts as $c) {
    echo "ID: {$c['contractid']}\n";
    echo "Parties: {$c['parties']}\n";
    echo "File: {$c['filepath']}\n";
    
    // Check if file exists
    $fullPath = __DIR__ . '/' . $c['filepath'];
    if (file_exists($fullPath)) {
        $fileSize = filesize($fullPath);
        echo "Status: ✅ File exists ($fileSize bytes)\n";
        
        // Check naming convention
        $fileName = basename($c['filepath']);
        if (preg_match('/^(\d+)\.(\d{8}-\d{6})\.(\w+)$/', $fileName)) {
            echo "Naming: ✅ New convention\n";
        } else {
            echo "Naming: ❌ Old convention\n";
        }
    } else {
        echo "Status: ❌ File missing\n";
    }
    echo "---\n";
}
?>