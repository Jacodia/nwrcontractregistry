<?php
header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/controllers/ContractController.php";

$controller = new ContractController($pdo);

// Decide action from query string
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'view':
            $id = $_GET['id'] ?? null;
            if ($id) {
                echo json_encode($controller->view($id));
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Contract ID required"]);
            }
            break;

        case 'create':
            $data = json_decode(file_get_contents("php://input"), true);
            if ($controller->create($data)) {
                echo json_encode(["message" => "Contract created"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to create contract"]);
            }
            break;

        case 'update':
            $id = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents("php://input"), true);
            if ($id && $controller->update($id, $data)) {
                echo json_encode(["message" => "Contract updated"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update contract"]);
            }
            break;

        case 'delete':
            $id = $_GET['id'] ?? null;
            if ($id && $controller->delete($id)) {
                echo json_encode(["message" => "Contract deleted"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete contract"]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
