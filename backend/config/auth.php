<?php
// Load environment configuration
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Environment-based error handling
if ($_ENV['APP_ENV'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

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
    
    // Login user with environment-based authentication
    public static function login($email, $password) {
        if ($_ENV['APP_ENV'] === 'development') {
            // ✅ Database-based authentication for development
            return self::authenticateDatabase($email, $password);
        } else {
            // ✅ LDAP authentication for production
            return self::authenticateLDAP($email, $password);
        }
    }
    
    // Database authentication method
    private static function authenticateDatabase($email, $password) {
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
                    'method' => 'database',
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
    
    // LDAP authentication method
    private static function authenticateLDAP($email, $password) {
        try {
            $ldap_host = $_ENV['LDAP_HOST'];
            $ldap_port = $_ENV['LDAP_PORT'];
            $ldap_base_dn = $_ENV['LDAP_BASE_DN'];
            $ldap_domain = $_ENV['LDAP_DOMAIN'];
            $use_tls = $_ENV['LDAP_USE_TLS'] === 'true';
            
            // Connect to LDAP server
            $ldapconn = ldap_connect($ldap_host, $ldap_port);
            if (!$ldapconn) {
                return ['success' => false, 'error' => 'Could not connect to LDAP server'];
            }
            
            // Set LDAP options
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
            
            if ($use_tls) {
                if (!ldap_start_tls($ldapconn)) {
                    return ['success' => false, 'error' => 'Could not start TLS'];
                }
            }
            
            // Try to bind with user credentials
            $userdn = $email; // or construct: "cn=" . explode('@', $email)[0] . "," . $ldap_base_dn
            if (@ldap_bind($ldapconn, $userdn, $password)) {
                // Successful authentication
                $username = explode('@', $email)[0];
                
                // Get user details from LDAP (optional)
                $search_filter = "(&(objectClass=user)(mail=$email))";
                $search_result = ldap_search($ldapconn, $ldap_base_dn, $search_filter);
                $entries = ldap_get_entries($ldapconn, $search_result);
                
                // Determine role based on group membership (simplified)
                $role = self::getLDAPUserRole($ldapconn, $ldap_base_dn, $email);
                
                // Create or update user in local database for session management
                $userId = self::createOrUpdateLocalUser($username, $email, $role);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                $_SESSION['username'] = $username;
                
                ldap_close($ldapconn);
                
                return [
                    'success' => true,
                    'method' => 'ldap',
                    'user' => [
                        'id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'role' => $role
                    ]
                ];
            } else {
                ldap_close($ldapconn);
                return ['success' => false, 'error' => 'Invalid LDAP credentials'];
            }
        } catch (Exception $e) {
            error_log('LDAP Authentication Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'LDAP authentication failed'];
        }
    }
    
    // Get user role from LDAP group membership
    private static function getLDAPUserRole($ldapconn, $base_dn, $email) {
        // Define group mappings
        $roleGroups = [
            'admin' => 'CN=NWR-Admins,OU=Groups,' . $base_dn,
            'manager' => 'CN=NWR-Managers,OU=Groups,' . $base_dn,
            'user' => 'CN=NWR-Users,OU=Groups,' . $base_dn
        ];
        
        // Search for user's group memberships
        $search_filter = "(&(objectClass=user)(mail=$email))";
        $search_result = ldap_search($ldapconn, $base_dn, $search_filter, ['memberOf']);
        $entries = ldap_get_entries($ldapconn, $search_result);
        
        if ($entries['count'] > 0 && isset($entries[0]['memberof'])) {
            $memberOf = $entries[0]['memberof'];
            
            // Check for admin role first (highest priority)
            if (in_array($roleGroups['admin'], $memberOf)) {
                return 'admin';
            }
            // Check for manager role
            if (in_array($roleGroups['manager'], $memberOf)) {
                return 'manager';
            }
            // Default to user role
            if (in_array($roleGroups['user'], $memberOf)) {
                return 'user';
            }
        }
        
        // Default role if no groups found
        return 'user';
    }
    
    // Create or update local user record for LDAP users
    private static function createOrUpdateLocalUser($username, $email, $role) {
        try {
            // Check if user exists
            $stmt = self::$pdo->prepare("SELECT userid FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Update existing user
                $stmt = self::$pdo->prepare("UPDATE users SET username = ?, role = ? WHERE email = ?");
                $stmt->execute([$username, $role, $email]);
                return $existingUser['userid'];
            } else {
                // Create new user (LDAP users don't need local password)
                $stmt = self::$pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, 'LDAP_AUTH', $role]);
                return self::$pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log('Local user creation error: ' . $e->getMessage());
            return null;
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