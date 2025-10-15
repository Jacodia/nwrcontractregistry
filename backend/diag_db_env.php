<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$keys = ['DB_TYPE','DB_HOST','DB_PORT','DB_INSTANCE','DB_USER','DB_NAME'];
$out = [];
foreach ($keys as $k) {
    $out[$k] = $_ENV[$k] ?? getenv($k) ?? null;
}
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
