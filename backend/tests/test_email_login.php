<?php
echo "Testing Email-Based Login: RDoe@hell.lab\n";
echo "========================================\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize authentication system
Auth::init($pdo);

$email = 'RDoe@hell.lab';
$password = 'pass.123';

echo "🔐 FULL EMAIL LOGIN TEST\n";
echo "========================\n";
echo "Email: $email\n";
echo "Password: [PROVIDED]\n";
echo "Environment: {$_ENV['APP_ENV']}\n\n";

echo "Step 1: Testing login method...\n";

try {
    // Use the actual Auth::login method which handles email format
    $result = Auth::login($email, $password);
    
    if ($result['success']) {
        echo "✅ LOGIN SUCCESSFUL!\n\n";
        echo "Authentication Details:\n";
        echo "  Method: {$result['method']}\n";
        echo "  User ID: {$result['user']['id']}\n";
        echo "  Username: {$result['user']['username']}\n";
        echo "  Email: {$result['user']['email']}\n";
        echo "  Role: {$result['user']['role']}\n\n";
        
        echo "🎉 SUCCESS: User can now access the web application!\n";
        echo "Session variables have been set for web login.\n\n";
        
        echo "Session Information:\n";
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo "  Session ID: " . session_id() . "\n";
        echo "  User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
        echo "  Email: " . ($_SESSION['user_email'] ?? 'Not set') . "\n";
        echo "  Role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
        echo "  Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
        
    } else {
        echo "❌ LOGIN FAILED\n";
        echo "Error: {$result['error']}\n\n";
        
        echo "Troubleshooting:\n";
        echo "• Verify RDoe@hell.lab exists in Active Directory\n";
        echo "• Check password is correct: pass.123\n";
        echo "• Ensure account is enabled in AD\n";
        echo "• Check LDAP connectivity\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n📋 NEXT STEPS:\n";
echo "==============\n";
if (isset($result) && $result['success']) {
    echo "1. ✅ User is authenticated and provisioned\n";
    echo "2. 🌐 Test web login at: http://localhost/nwrcontractregistry/frontend/\n";
    echo "3. 📝 Use these credentials:\n";
    echo "   Email: RDoe@hell.lab\n";
    echo "   Password: pass.123\n";
    echo "4. ✅ Expected behavior: Immediate login without re-authentication\n";
} else {
    echo "1. 🔧 Fix authentication issues\n";
    echo "2. 🔍 Check Active Directory user account\n";
    echo "3. 🧪 Re-test after corrections\n";
}

echo "\n💡 TECHNICAL NOTES:\n";
echo "===================\n";
echo "• System expects FULL EMAIL as username (not just 'RDoe')\n";
echo "• Authentication normalizes email format automatically\n";
echo "• LDAP bind uses: RDoe@hell.lab format\n";
echo "• Auto-provisioning creates local database user\n";
echo "• Role mapping from AD group: ContractManagers\n";