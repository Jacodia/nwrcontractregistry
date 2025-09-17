<?php
// Role-based authentication 
class Auth {
    private static $pdo;
    
    public static function init($database) {
        self::$pdo = $database;
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }
    
    // Get current user data
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        
        try {
            $stmt = self::$pdo->prepare("SELECT userid, username, email, role FROM users WHERE userid = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Check if user has required role
    public static function hasRole($requiredRole) {
        $user = self::getCurrentUser();
        if (!$user) return false;
        
        $roleHierarchy = [
            'user' => 1,
            'manager' => 2,
            'admin' => 3
        ];
        
        $userLevel = $roleHierarchy[$user['role']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    // Require authentication (redirect if not logged in)
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: /nwrcontractregistry/frontend/index.php");
            exit();
        }
    }
    
    // Require specific role
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Insufficient privileges.']);
            exit();
        }
    }
    
    // Login user
    public static function login($email, $password) {
        try {
            $stmt = self::$pdo->prepare("SELECT userid, username, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['userid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ];
            } else {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    // Register new user
    public static function register($username, $email, $password, $role = 'user') {
        try {
            // Check if user already exists
            $stmt = self::$pdo->prepare("SELECT userid FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'User with this email or username already exists'];
            }
            
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = self::$pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            $userId = self::$pdo->lastInsertId();
            
            // Auto-login the new user
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $_SESSION['username'] = $username;
            
            return [
                'success' => true,
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'role' => $role
                ]
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    // Logout user
    public static function logout() {
        session_destroy();
        return ['success' => true];
    }
}

?>