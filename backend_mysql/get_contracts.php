<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Contract.php';

// require_login(); // uncomment to require login

$c = new Contract();
$rows = $c->all();

// Return bare array for compatibility with frontend that expects an array
header('Content-Type: application/json');
echo json_encode($rows);

?>
