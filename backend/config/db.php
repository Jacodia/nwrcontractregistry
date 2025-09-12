<?php

$host = "locslhost";
$user = "root";
$pass = "";
$db = "nwr_crdb";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo("DB Connection failed: " . $e->getMessage());
    die("DB Connection failed: " . $e->getMessage());
}
?>