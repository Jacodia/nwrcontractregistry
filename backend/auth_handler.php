<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

Auth::init($pdo);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Email and password required']);
                exit;
            }
            
            $result = Auth::login($email, $password);
            echo json_encode($result);
            break;
            
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Username, email, and password required']);
                exit;
            }
            
            // Validate password strength
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                exit;
            }
            
            $result = Auth::register($username, $email, $password, 'viewer'); // Default to viewer role
            echo json_encode($result);
            break;
            
        case 'logout':
            $result = Auth::logout();
            echo json_encode($result);
            break;
            
        case 'check':
            if (Auth::isLoggedIn()) {
                $user = Auth::getCurrentUser();
                echo json_encode(['loggedIn' => true, 'user' => $user]);
            } else {
                echo json_encode(['loggedIn' => false]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>