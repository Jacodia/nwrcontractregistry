<?php

$host = "localhost:3306";
$user = "root";
$pass = "";
$db = "nwr_crdb";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Return JSON error instead of HTML
    http_response_code(500);
    header("Content-Type: application/json");
    echo("DB Connection failed: " . $e->getMessage());
    die("DB Connection failed: " . $e->getMessage());
}
?>