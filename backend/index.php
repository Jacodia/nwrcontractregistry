<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/controllers/ContractController.php";
require_once __DIR__ . "/controllers/UserController.php";
require_once __DIR__ . "/controllers/ContractTypeController.php";

Auth::init($pdo);

$controller = new ContractController($pdo);
$userController = new UserController($pdo);
$contractTypeController = new ContractTypeController($pdo);

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
        // --------------------Contract Management--------------------
        // Contract actions - require authentication
        case 'list':
            Auth::requireLogin(); // All logged in users can view dashboard
            echo json_encode($controller->list());
            break;

        case 'view':
            Auth::requireLogin();
            $id = $_GET['id'] ?? null;
            if ($id) {
                echo json_encode($controller->view($id));
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Contract ID required"]);
            }
            break;

        case 'create':
            Auth::requireRole('manager'); // Only managers and admins can create contracts
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
            Auth::requireRole('manager'); // Only managers and admins can edit contracts
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
            Auth::requireRole('manager'); // Only managers and admins can delete contracts
            $id = $_GET['id'] ?? null;
            if ($id && $controller->delete($id)) {
                echo json_encode(["message" => "Contract deleted"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete contract"]);
            }
            break;

        // --------------------User Management--------------------

        // User management actions - admin only
        case 'users':
            Auth::requireRole('admin'); // Only admins can manage users
            echo json_encode($userController->getAllUsers());
            break;

        case 'update_user_role':
            Auth::requireRole('admin'); // Only admins can change user roles
            $userId = $_POST['user_id'] ?? null;
            $newRole = $_POST['role'] ?? null;

            if (!$userId || !$newRole) {
                http_response_code(400);
                echo json_encode(["error" => "User ID and role required"]);
                break;
            }

            if (!in_array($newRole, ['user', 'manager', 'admin'])) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid role"]);
                break;
            }

            if ($userController->updateUserRole($userId, $newRole)) {
                echo json_encode(["message" => "User role updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update user role"]);
            }
            break;

        case 'delete_user':
            Auth::requireRole('admin'); // Only admins can delete users
            $userId = $_POST['user_id'] ?? null;

            if (!$userId) {
                http_response_code(400);
                echo json_encode(["error" => "User ID required"]);
                break;
            }

            // Prevent admin from deleting themselves
            if ($userId == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(["error" => "Cannot delete your own account"]);
                break;
            }

            if ($userController->deleteUser($userId)) {
                echo json_encode(["message" => "User deleted successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete user"]);
            }
            break;

        case 'user_stats':
            Auth::requireRole('admin'); // Only admins can view user stats
            echo json_encode($userController->getUserStats());
            break;

        // --------------------Contract Type Management--------------------
        case 'list_types':
            $controller = new ContractTypeController($pdo);
            echo json_encode($controller->getAll());
            break;

        case 'add_type':
            $controller = new ContractTypeController($pdo);
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode($controller->create($data['name'], $data['userId']));
            break;


        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
