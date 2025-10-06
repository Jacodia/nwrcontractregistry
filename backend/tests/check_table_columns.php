<?php
require_once '../config/db.php';

echo "=== Contracts Table Structure ===\n";
$stmt = $pdo->query('DESCRIBE contracts');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Sample Contracts ===\n";
$stmt = $pdo->query('SELECT parties, expiryDate FROM contracts LIMIT 3');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['parties'] . " (expires: " . $row['expiryDate'] . ")\n";
}
?>