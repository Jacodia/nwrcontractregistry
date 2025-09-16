<?php
header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/controllers/ContractController.php";

$controller = new ContractController($pdo);

// Function to handle file upload
function handleFileUpload($file, $partiesName)
{
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Sanitize parties name
    $safeParties = preg_replace('/[\/\\\\:*?"<>|]/', '_', $partiesName ?: 'contract');

    // Get original file extension
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Create new filename
    $newFileName = $safeParties . "_" . time() . "." . $extension;

    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $newFileName; // relative path
    }

    return false; // upload failed
}

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
            $data = $_POST;

            if (!empty($_FILES['contractFile']['name'])) {
                $filepath = handleFileUpload($_FILES['contractFile'], $data['parties'] ?? '');
                if ($filepath) {
                    $data['filepath'] = $filepath;
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "File upload failed"]);
                    exit;
                }
            }

            if ($controller->create($data)) {
                echo json_encode(["message" => "Contract created"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to create contract"]);
            }
            break;


        case 'update':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = $_POST;

                if (!empty($_FILES['contractFile']['name'])) {
                    $filepath = handleFileUpload($_FILES['contractFile'], $data['parties'] ?? '');
                    if ($filepath) {
                        $data['filepath'] = $filepath;
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "File upload failed"]);
                        exit;
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
