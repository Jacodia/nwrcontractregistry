<?php
header("Content-Type: application/json");

require_once "../config/db.php"; // include database connection
require_once "../models/Contract.php"; // include Contract model

// initialize model with PDO
$model = new Contract($pdo);

// Decide action from query string
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        echo json_encode($model->getAll());
        break;

    case 'view':
        $id = $_GET['id'] ?? null;
        if ($id) {
            echo json_encode($model->getById($id));
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Contract ID required"]);
        }
        break;

    case 'create':
        $data = json_decode(file_get_contents("php://input"), true);
        if ($model->create($data)) {
            echo json_encode(["message" => "Contract created"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create contract"]);
        }
        break;

    case 'update':
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents("php://input"), true);
        if ($id && $model->update($id, $data)) {
            echo json_encode(["message" => "Contract updated"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update contract"]);
        }
        break;

    case 'delete':
        $id = $_GET['id'] ?? null;
        if ($id && $model->delete($id)) {
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
?>
