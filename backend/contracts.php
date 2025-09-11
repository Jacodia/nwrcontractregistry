<?php
header("Content-Type: application/json");
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/controllers/ContractController.php";

$controller = new ContractController($pdo);

try {
    // Basic routing with ?id= query
    if (isset($_GET['id'])) {
        $contract = $controller->getContract($_GET['id']);
        echo json_encode($contract ?: ["error" => "Contract not found"]);
    } else {
        $contracts = $controller->getContracts();
        echo json_encode($contracts);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
