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

// Simplified authentication class for AD auth + MySQL roles
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
        if (!self::isLoggedIn()) {
            return null;
        }
        
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
        if (!$user) {
            return false;
        }
        
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
            // Database-based authentication for development
            return self::authenticateDatabase($email, $password);
        } else {
            // AD authentication + MySQL roles for production
            return self::authenticateAD($email, $password);
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
    
    // AD authentication method - authenticate against AD, roles from MySQL
    private static function authenticateAD($email, $password) {
        try {
            // Step 1: Connect to LDAP
            $ldapHost = rtrim($_ENV['LDAP_HOST'], '/');
            $ldap = @ldap_connect($ldapHost . ':' . $_ENV['LDAP_PORT']);
            if (!$ldap) {
                return ['success' => false, 'error' => 'Could not connect to LDAP server'];
            }

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            if (($_ENV['LDAP_USE_TLS'] ?? 'false') === 'true') {
                ldap_start_tls($ldap);
            }

            $username = explode('@', $email)[0];
            $baseDN = $_ENV['LDAP_BASE_DN'] ?? 'DC=hell,DC=lab';
            $requiredGroup = $_ENV['LDAP_GROUP'] ?? 'Contracts_User';

            // Try Method 1: Direct user authentication
            $userFormats = [
                $email,  // RDoe@hell.lab
                "$username@" . ($_ENV['LDAP_DOMAIN'] ?? 'hell.lab'), // RDoe@hell.lab
                "CN=$username,CN=Users,$baseDN", // CN=RDoe,CN=Users,DC=hell,DC=lab
                "uid=$username,$baseDN", // uid=RDoe,DC=hell,DC=lab
                $username // Just RDoe
            ];

            $authenticatedUserDN = null;
            foreach ($userFormats as $userDN) {
                if (@ldap_bind($ldap, $userDN, $password)) {
                    $authenticatedUserDN = $userDN;
                    break;
                }
            }

            if ($authenticatedUserDN) {
                // User authenticated! Now MANDATORY group membership check
                $bindUser = $_ENV['LDAP_BIND_USER'] ?? '';
                $bindPassword = $_ENV['LDAP_BIND_PASSWORD'] ?? '';
                
                $groupCheckPassed = false;
                
                if (!empty($bindUser) && !empty($bindPassword)) {
                    // Method A: Use service account for group search
                    if (@ldap_bind($ldap, $bindUser, $bindPassword)) {
                        $searchFilter = "(|(userPrincipalName=$email)(sAMAccountName=$username))";
                        $searchResult = @ldap_search($ldap, $baseDN, $searchFilter, ['memberOf']);
                        
                        if ($searchResult) {
                            $entries = ldap_get_entries($ldap, $searchResult);
                            
                            if ($entries['count'] > 0) {
                                $userEntry = $entries[0];
                                
                                // Check group membership - REQUIRED
                                if (isset($userEntry['memberof'])) {
                                    for ($i = 0; $i < $userEntry['memberof']['count']; $i++) {
                                        $group = $userEntry['memberof'][$i];
                                        if (stripos($group, "CN=$requiredGroup,") !== false) {
                                            $groupCheckPassed = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // If service account method failed, try alternative group check
                if (!$groupCheckPassed) {
                    // Method B: Try binding as user and reading their own groups
                    if (@ldap_bind($ldap, $authenticatedUserDN, $password)) {
                        // Some LDAP servers allow users to read their own memberOf
                        $searchFilter = "(|(userPrincipalName=$email)(sAMAccountName=$username))";
                        $searchResult = @ldap_search($ldap, $baseDN, $searchFilter, ['memberOf']);
                        
                        if ($searchResult) {
                            $entries = ldap_get_entries($ldap, $searchResult);
                            
                            if ($entries['count'] > 0) {
                                $userEntry = $entries[0];
                                
                                if (isset($userEntry['memberof'])) {
                                    for ($i = 0; $i < $userEntry['memberof']['count']; $i++) {
                                        $group = $userEntry['memberof'][$i];
                                        if (stripos($group, "CN=$requiredGroup,") !== false) {
                                            $groupCheckPassed = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // ENFORCE GROUP MEMBERSHIP - No bypass allowed
                if (!$groupCheckPassed) {
                    ldap_close($ldap);
                    return ['success' => false, 'error' => "Access denied. User must be a member of '$requiredGroup' group."];
                }

                ldap_close($ldap);

                // User authenticated AND authorized
                $user = self::getOrCreateLocalUser($username, $email);
                if (!$user) {
                    return ['success' => false, 'error' => 'Failed to create user record'];
                }

                // Set session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                return [
                    'success' => true,
                    'method' => 'ad_auth_with_groups',
                    'user' => [
                        'id' => $user['userid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ];
            }

            // User authentication failed
            ldap_close($ldap);
            return ['success' => false, 'error' => 'Invalid username or password'];

        } catch (Exception $e) {
            // Log to file, not HTTP response
            file_put_contents(__DIR__ . '/../logs/auth_error.log', 
                date('Y-m-d H:i:s') . " - AD Auth Error: " . $e->getMessage() . "\n", 
                FILE_APPEND | LOCK_EX);
            return ['success' => false, 'error' => 'Authentication system error'];
        }
    }
    
    // Get existing user or create new one with default role - PRESERVE EXISTING ROLES
    private static function getOrCreateLocalUser($username, $email) {
        try {
            // Check if user exists
            $stmt = self::$pdo->prepare("SELECT userid, username, email, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // User exists - only update username if it's different, PRESERVE ROLE
                if ($existingUser['username'] !== $username) {
                    $stmt = self::$pdo->prepare("UPDATE users SET username = ? WHERE email = ?");
                    $stmt->execute([$username, $email]);
                    // Return updated user data but keep existing role
                    $existingUser['username'] = $username;
                }
                return $existingUser; // Keep existing role and all other data
            } else {
                // Create new user ONLY if they don't exist
                $defaultRole = 'viewer';
                $stmt = self::$pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, 'AD_AUTH', $defaultRole]);
                
                return [
                    'userid' => self::$pdo->lastInsertId(),
                    'username' => $username,
                    'email' => $email,
                    'role' => $defaultRole
                ];
            }
        } catch (PDOException $e) {
            error_log('User creation error: ' . $e->getMessage());
            return null;
        }
    }
    
    // Register new user (for development mode)
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