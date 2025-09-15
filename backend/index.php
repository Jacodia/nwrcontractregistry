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
            if (!empty($_FILES['contractFile']['name'])) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = time() . "_" . basename($_FILES['contractFile']['name']);
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $targetPath)) {
                    $data = $_POST;
                    $data['filepath'] = 'uploads/' . $filename; // relative path
                    if ($controller->create($data)) {
                        echo json_encode(["message" => "Contract created"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Failed to create contract"]);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "File upload failed"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "File is required"]);
            }
            break;

        case 'update':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = $_POST;

                if (!empty($_FILES['contractFile']['name'])) {
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filename = time() . "_" . basename($_FILES['contractFile']['name']);
                    $targetPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $targetPath)) {
                        $data['filepath'] = 'uploads/' . $filename;
                    }
                }

                if ($controller->update($id, $data)) {
                    echo json_encode(["message" => "Contract updated"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to update contract"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Contract ID required"]);
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
