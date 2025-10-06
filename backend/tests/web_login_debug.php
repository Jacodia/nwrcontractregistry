<?php
// Start session to capture any errors
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 Web Login Debug Test</h2>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if this is a POST request (login attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h3>📝 Login Attempt Details:</h3>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    echo "<p><strong>Password:</strong> [PROVIDED]</p>";
    
    // Load authentication system
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/../config/db.php';
    
    // Load environment
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
    
    echo "<h3>⚙️ Environment Check:</h3>";
    echo "<p><strong>APP_ENV:</strong> " . ($_ENV['APP_ENV'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>LDAP_HOST:</strong> " . ($_ENV['LDAP_HOST'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>LDAP_PORT:</strong> " . ($_ENV['LDAP_PORT'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>LDAP_DOMAIN:</strong> " . ($_ENV['LDAP_DOMAIN'] ?? 'NOT SET') . "</p>";
    
    // Initialize auth system
    Auth::init($pdo);
    
    echo "<h3>🔐 Authentication Test:</h3>";
    
    try {
        $result = Auth::login($email, $password);
        
        if ($result['success']) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #f0fff0;'>";
            echo "<strong>✅ LOGIN SUCCESSFUL!</strong><br>";
            echo "Method: " . $result['method'] . "<br>";
            echo "User ID: " . $result['user']['id'] . "<br>";
            echo "Username: " . $result['user']['username'] . "<br>";
            echo "Email: " . $result['user']['email'] . "<br>";
            echo "Role: " . $result['user']['role'] . "<br>";
            echo "</div>";
            
            echo "<h3>📱 Session Information:</h3>";
            echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
            echo "<p><strong>User ID in Session:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>Email in Session:</strong> " . ($_SESSION['user_email'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>Role in Session:</strong> " . ($_SESSION['user_role'] ?? 'NOT SET') . "</p>";
            
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0;'>";
            echo "<strong>❌ LOGIN FAILED!</strong><br>";
            echo "Error: " . ($result['error'] ?? 'Unknown error') . "<br>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0;'>";
        echo "<strong>💥 EXCEPTION OCCURRED!</strong><br>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")<br>";
        echo "</div>";
    }
    
} else {
    // Show login form for testing
    echo "<h3>🧪 Test Login Form:</h3>";
    echo "<p>Use this form to test login with debug output:</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Web Login Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { 
            width: 300px; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        button { 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<form method="POST" action="">
    <div class="form-group">
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="RDoe@hell.lab" required>
    </div>
    
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" value="pass.123" required>
    </div>
    
    <button type="submit">🔐 Test Login</button>
</form>

<hr>

<h3>📋 Quick Tests:</h3>
<ul>
    <li><a href="?test=env">Check Environment Variables</a></li>
    <li><a href="?test=ldap">Test LDAP Connection</a></li>
    <li><a href="?test=db">Test Database Connection</a></li>
</ul>

<?php
if (isset($_GET['test'])) {
    echo "<h3>🔧 Quick Test Results:</h3>";
    
    if ($_GET['test'] === 'env') {
        echo "<pre>";
        echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'NOT SET') . "\n";
        echo "LDAP_HOST: " . ($_ENV['LDAP_HOST'] ?? 'NOT SET') . "\n";
        echo "LDAP_PORT: " . ($_ENV['LDAP_PORT'] ?? 'NOT SET') . "\n";
        echo "LDAP_DOMAIN: " . ($_ENV['LDAP_DOMAIN'] ?? 'NOT SET') . "\n";
        echo "LDAP_BASE_DN: " . ($_ENV['LDAP_BASE_DN'] ?? 'NOT SET') . "\n";
        echo "LDAP_USE_TLS: " . ($_ENV['LDAP_USE_TLS'] ?? 'NOT SET') . "\n";
        echo "</pre>";
    }
    
    if ($_GET['test'] === 'ldap') {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
            
            $ldapconn = ldap_connect($_ENV['LDAP_HOST'], $_ENV['LDAP_PORT']);
            if ($ldapconn) {
                echo "<p style='color: green;'>✅ LDAP Connection: SUCCESS</p>";
                ldap_close($ldapconn);
            } else {
                echo "<p style='color: red;'>❌ LDAP Connection: FAILED</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ LDAP Error: " . $e->getMessage() . "</p>";
        }
    }
    
    if ($_GET['test'] === 'db') {
        try {
            require_once __DIR__ . '/../config/db.php';
            echo "<p style='color: green;'>✅ Database Connection: SUCCESS</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

</body>
</html>