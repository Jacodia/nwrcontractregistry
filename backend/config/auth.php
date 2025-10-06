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
    ini_set('display_errors', 1);
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
            'viewer' => 1,
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
            $ldap_bind_user = $_ENV['LDAP_BIND_USER'] ?? null;
            $ldap_bind_password = $_ENV['LDAP_BIND_PASSWORD'] ?? null;
            $use_tls = $_ENV['LDAP_USE_TLS'] === 'true';
            
            // Connect to LDAP server
            $ldapconn = ldap_connect($ldap_host, $ldap_port);
            if (!$ldapconn) {
                return ['success' => false, 'error' => 'Could not connect to LDAP server'];
            }
            
            // Set LDAP options
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 10);
            
            if ($use_tls) {
                if (!ldap_start_tls($ldapconn)) {
                    return ['success' => false, 'error' => 'Could not start TLS'];
                }
            }
            
            // If service account is configured, bind with it first to search for user
            if ($ldap_bind_user && $ldap_bind_password) {
                if (!ldap_bind($ldapconn, $ldap_bind_user, $ldap_bind_password)) {
                    return ['success' => false, 'error' => 'Service account bind failed'];
                }
                
                // Search for user DN
                $userDN = self::findUserDN($ldapconn, $ldap_base_dn, $email);
                if (!$userDN) {
                    ldap_close($ldapconn);
                    return ['success' => false, 'error' => 'User not found'];
                }
            } else {
                // Direct bind attempt (simpler setup)
                $userDN = self::normalizeUsername($email, $ldap_domain);
            }
            
            // Authenticate user with their credentials
            if (!@ldap_bind($ldapconn, $userDN, $password)) {
                ldap_close($ldapconn);
                return ['success' => false, 'error' => 'Invalid LDAP credentials'];
            }
            
            // Get user details and role
            $userInfo = self::getUserLDAPInfo($ldapconn, $ldap_base_dn, $email);
            $role = self::getLDAPUserRole($ldapconn, $ldap_base_dn, $email);
            $username = $userInfo['username'] ?? explode('@', $email)[0];
            
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
            
        } catch (Exception $e) {
            error_log('LDAP Authentication Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'LDAP authentication failed'];
        }
    }
    
    // Helper method to normalize username for LDAP
    private static function normalizeUsername($username, $domain) {
        // Handle various input formats
        if (strpos($username, '@') === false) {
            return $username . '@' . $domain;
        }
        return $username;
    }
    
    // Helper method to find user DN in LDAP
    private static function findUserDN($ldapconn, $base_dn, $email) {
        $search_filter = "(&(objectClass=user)(|(mail=$email)(userPrincipalName=$email)(sAMAccountName=" . explode('@', $email)[0] . ")))";
        $search_result = ldap_search($ldapconn, $base_dn, $search_filter);
        
        if (!$search_result) {
            return false;
        }
        
        $entries = ldap_get_entries($ldapconn, $search_result);
        
        if ($entries['count'] > 0) {
            return $entries[0]['dn'];
        }
        
        return false;
    }
    
    // Helper method to get user info from LDAP
    private static function getUserLDAPInfo($ldapconn, $base_dn, $email) {
        $search_filter = "(&(objectClass=user)(|(mail=$email)(userPrincipalName=$email)))";
        $search_result = ldap_search($ldapconn, $base_dn, $search_filter, [
            'sAMAccountName', 'mail', 'displayName', 'department'
        ]);
        
        if (!$search_result) {
            return ['username' => explode('@', $email)[0]];
        }
        
        $entries = ldap_get_entries($ldapconn, $search_result);
        
        if ($entries['count'] > 0) {
            return [
                'username' => $entries[0]['samaccountname'][0] ?? explode('@', $email)[0],
                'displayName' => $entries[0]['displayname'][0] ?? '',
                'department' => $entries[0]['department'][0] ?? ''
            ];
        }
        
        return ['username' => explode('@', $email)[0]];
    }
    
    // Get user role from LDAP group membership
    private static function getLDAPUserRole($ldapconn, $base_dn, $email) {
        // Define group mappings - can be configured via environment variables
        $roleGroups = [
            'admin' => $_ENV['LDAP_ADMIN_GROUP'] ?? 'CN=NWR-Admins,OU=Groups,' . $base_dn,
            'manager' => $_ENV['LDAP_MANAGER_GROUP'] ?? 'CN=NWR-Managers,OU=Groups,' . $base_dn,
            'viewer' => $_ENV['LDAP_USER_GROUP'] ?? 'CN=NWR-Users,OU=Groups,' . $base_dn
        ];
        
        // Search for user's group memberships
        $search_filter = "(&(objectClass=user)(|(mail=$email)(userPrincipalName=$email)))";
        $search_result = ldap_search($ldapconn, $base_dn, $search_filter, ['memberOf']);
        
        if (!$search_result) {
            return 'viewer'; // Default role
        }
        
        $entries = ldap_get_entries($ldapconn, $search_result);
        
        if ($entries['count'] > 0 && isset($entries[0]['memberof'])) {
            $memberOf = [];
            
            // Handle both single and multiple group memberships
            if (is_array($entries[0]['memberof'])) {
                for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                    $memberOf[] = $entries[0]['memberof'][$i];
                }
            }
            
            // Check for admin role first (highest priority)
            foreach ($memberOf as $group) {
                if (stripos($group, 'NWR-Admins') !== false || 
                    stripos($group, $roleGroups['admin']) !== false) {
                    return 'admin';
                }
            }
            
            // Check for manager role
            foreach ($memberOf as $group) {
                if (stripos($group, 'NWR-Managers') !== false || 
                    stripos($group, $roleGroups['manager']) !== false) {
                    return 'manager';
                }
            }
            
            // Check for viewer role
            foreach ($memberOf as $group) {
                if (stripos($group, 'NWR-Users') !== false || 
                    stripos($group, $roleGroups['viewer']) !== false) {
                    return 'viewer';
                }
            }
        }
        
        // Default role if no groups found or if groups don't match
        return $_ENV['LDAP_DEFAULT_ROLE'] ?? 'viewer';
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
    public static function register($username, $email, $password, $role = 'viewer') {
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