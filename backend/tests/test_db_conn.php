<?php
header("Content-Type: application/json");

$host = "localhost:3306";
$user = "root";
$pass = "";
$db = "nwr_crdb";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test if contracts table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully",
        "contracts_count" => $result['count']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
}
?>