<?php
require_once '../config/db.php';

echo "=== Users Table Structure ===\n";
$stmt = $pdo->query('DESCRIBE users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ") - ";
    echo "Null: " . $row['Null'] . " - ";
    echo "Default: " . ($row['Default'] ?: 'NULL') . "\n";
}

echo "\n=== Sample Users ===\n";
$stmt = $pdo->query('SELECT userid, email, username, role FROM users LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['userid'] . " | ";
    echo "Email: " . $row['email'] . " | ";
    echo "Username: " . ($row['username'] ?: 'NULL') . " | ";
    echo "Role: " . ($row['role'] ?: 'NULL') . "\n";
}
?>