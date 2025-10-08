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
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
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
            $ldap = ldap_connect($_ENV['LDAP_HOST'], $_ENV['LDAP_PORT']);
            if (!$ldap) {
                return ['success' => false, 'error' => 'Could not connect to LDAP server'];
            }

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            if ($_ENV['LDAP_USE_TLS'] === 'true') {
                ldap_start_tls($ldap);
            }

            // 1. Bind with service account
            $bind = @ldap_bind($ldap, $_ENV['LDAP_BIND_USER'], $_ENV['LDAP_BIND_PASSWORD']);
            if (!$bind) {
                ldap_close($ldap);
                return ['success' => false, 'error' => 'LDAP service account bind failed'];
            }

            // 2. Search for user
            $filter = "(userPrincipalName=$email)";
            $search = ldap_search($ldap, $_ENV['LDAP_BASE_DN'], $filter, ['dn', 'memberof', 'sAMAccountName', 'displayName', 'mail']);
            $entries = ldap_get_entries($ldap, $search);

            if ($entries['count'] == 0) {
                ldap_close($ldap);
                return ['success' => false, 'error' => 'User not found'];
            }

            $userDn = $entries[0]['dn'];
            $groups = isset($entries[0]['memberof']) ? $entries[0]['memberof'] : [];
            $isContractUser = false;

            // Check group membership
            if (is_array($groups)) {
                for ($i = 0; $i < $groups['count']; $i++) {
                    if (stripos($groups[$i], $_ENV['LDAP_GROUP']) !== false) {
                        $isContractUser = true;
                        break;
                    }
                }
            }

            if (!$isContractUser) {
                ldap_close($ldap);
                return ['success' => false, 'error' => 'Access denied – not a Contracts_User member.'];
            }

            // 3. Verify user password
            if (@ldap_bind($ldap, $userDn, $password)) {
                // Get user info
                $username = $entries[0]['samaccountname'][0] ?? explode('@', $email)[0];
                $displayName = $entries[0]['displayname'][0] ?? '';
                $role = 'viewer'; // Default, or you can map based on group membership

                // Optionally, map role based on group membership
                if (is_array($groups)) {
                    for ($i = 0; $i < $groups['count']; $i++) {
                        if (stripos($groups[$i], 'NWR-Admins') !== false) {
                            $role = 'admin';
                            break;
                        } elseif (stripos($groups[$i], 'NWR-Managers') !== false) {
                            $role = 'manager';
                        }
                    }
                }

                // Create or update local user
                $userId = self::createOrUpdateLocalUser($username, $email, $role);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                $_SESSION['username'] = $username;

                ldap_close($ldap);

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
                ldap_close($ldap);
                return ['success' => false, 'error' => 'Invalid password'];
            }
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