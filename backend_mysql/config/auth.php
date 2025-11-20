<?php
// Simple session-based auth helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_json_request() {
    if ($_SERVER['CONTENT_TYPE'] ?? '' !== 'application/json') {
        // allow form posts too
        return;
    }
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function login_user($user_id) {
    $_SESSION['user_id'] = $user_id;
    // regenerate session id to reduce fixation risk
    session_regenerate_id(true);
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

?>
