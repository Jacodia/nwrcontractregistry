<?php
echo "=== NWR Contract Registry - LDAP Authentication Setup Guide ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: PRODUCTION (LDAP Enabled)\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "📋 CURRENT CONFIGURATION\n";
echo "========================\n";
echo "Environment Mode: " . $_ENV['APP_ENV'] . "\n";
echo "LDAP Server: " . $_ENV['LDAP_HOST'] . ":" . $_ENV['LDAP_PORT'] . "\n";
echo "LDAP Domain: " . $_ENV['LDAP_DOMAIN'] . "\n";
echo "Base DN: " . $_ENV['LDAP_BASE_DN'] . "\n";
echo "TLS Enabled: " . ($_ENV['LDAP_USE_TLS'] === 'true' ? 'Yes' : 'No') . "\n\n";

echo "🔐 AUTHENTICATION METHODS\n";
echo "==========================\n";
echo "✅ LDAP Authentication: ACTIVE (Production Mode)\n";
echo "   → Users authenticate against hell.lab domain\n";
echo "   → Automatic user provisioning in local database\n";
echo "   → Role mapping from LDAP groups\n\n";

echo "❌ Database Authentication: DISABLED (Production Mode)\n";
echo "   → Switch to APP_ENV=development to enable\n\n";

echo "👥 USER AUTHENTICATION FORMATS\n";
echo "===============================\n";
echo "The system accepts these login formats:\n";
echo "   • Full email: user@hell.lab\n";
echo "   • Username only: username (will be expanded to username@hell.lab)\n";
echo "   • UPN format: user@hell.lab\n\n";

echo "🏗️ USER PROVISIONING PROCESS\n";
echo "============================\n";
echo "When a user successfully authenticates via LDAP:\n";
echo "1. ✅ LDAP authentication validates credentials\n";
echo "2. ✅ System retrieves user info from Active Directory\n";
echo "3. ✅ User is automatically created/updated in local database\n";
echo "4. ✅ Role is assigned based on LDAP group membership\n";
echo "5. ✅ Session is created for web application access\n\n";

echo "🎭 ROLE MAPPING\n";
echo "===============\n";
echo "Default role groups (can be customized in .env):\n";
echo "   • Admin Role: CN=NWR-Admins,OU=Groups,DC=hell,DC=lab\n";
echo "   • Manager Role: CN=NWR-Managers,OU=Groups,DC=hell,DC=lab\n";
echo "   • User Role: CN=NWR-Users,OU=Groups,DC=hell,DC=lab\n";
echo "   • Default Role: viewer (if no group membership found)\n\n";

echo "🧪 TESTING INSTRUCTIONS\n";
echo "========================\n";
echo "1. Open the application:\n";
echo "   http://localhost/nwrcontractregistry/frontend/index.php\n\n";

echo "2. Test with actual hell.lab domain users:\n";
echo "   • Use existing domain accounts\n";
echo "   • Try both email and username formats\n";
echo "   • Verify role assignment after login\n\n";

echo "3. Example test scenarios:\n";
echo "   → Login: admin@hell.lab (or just 'admin')\n";
echo "   → Login: testuser@hell.lab (or just 'testuser')\n";
echo "   → Login: manager@hell.lab (or just 'manager')\n\n";

echo "🔧 TROUBLESHOOTING\n";
echo "==================\n";
echo "If authentication fails:\n\n";

echo "1. Verify LDAP connectivity:\n";
echo "   php " . __DIR__ . "/test_ldap_auth.php\n\n";

echo "2. Check user exists in domain:\n";
echo "   • Verify user exists in hell.lab Active Directory\n";
echo "   • Ensure user account is enabled\n";
echo "   • Check account lockout status\n\n";

echo "3. Test network connectivity:\n";
echo "   telnet localhost 389\n\n";

echo "4. Review logs:\n";
echo "   • Check PHP error logs\n";
echo "   • Check IIS logs\n";
echo "   • Check Windows Event Viewer\n\n";

echo "⚙️ CONFIGURATION OPTIONS\n";
echo "=========================\n";
echo "To customize LDAP settings, edit .env file:\n\n";

echo "# Service Account (for advanced LDAP queries)\n";
echo "LDAP_BIND_USER=cn=nwr-service,ou=service-accounts,dc=hell,dc=lab\n";
echo "LDAP_BIND_PASSWORD=ServiceAccountPassword\n\n";

echo "# Custom Group Mappings\n";
echo "LDAP_ADMIN_GROUP=CN=NWR-Admins,OU=Groups,DC=hell,DC=lab\n";
echo "LDAP_MANAGER_GROUP=CN=NWR-Managers,OU=Groups,DC=hell,DC=lab\n";
echo "LDAP_USER_GROUP=CN=NWR-Users,OU=Groups,DC=hell,DC=lab\n";
echo "LDAP_DEFAULT_ROLE=viewer\n\n";

echo "🔄 SWITCHING BACK TO DATABASE AUTH\n";
echo "===================================\n";
echo "If you need to switch back to database authentication:\n";
echo "1. Edit .env file: APP_ENV=development\n";
echo "2. Restart IIS: iisreset\n";
echo "3. Use test accounts from database\n\n";

echo "📊 SYSTEM STATUS\n";
echo "================\n";

// Quick connectivity test
$ldapStatus = "❌ Not Connected";
try {
    $ldapconn = ldap_connect($_ENV['LDAP_HOST'], $_ENV['LDAP_PORT']);
    if ($ldapconn) {
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        
        if (@ldap_bind($ldapconn)) {
            $ldapStatus = "✅ Connected";
        }
        ldap_close($ldapconn);
    }
} catch (Exception $e) {
    // Connection failed
}

$phpLdap = extension_loaded('ldap') ? "✅ Loaded" : "❌ Not Loaded";

echo "LDAP Connection: $ldapStatus\n";
echo "PHP LDAP Extension: $phpLdap\n";
echo "Environment: " . $_ENV['APP_ENV'] . "\n";
echo "Application: ✅ Ready for LDAP Testing\n\n";

echo "🎯 NEXT STEPS\n";
echo "=============\n";
echo "1. Test login with actual hell.lab domain users\n";
echo "2. Verify role assignments are correct\n";
echo "3. Test contract management features with different roles\n";
echo "4. Configure LDAP group mappings if needed\n";
echo "5. Set up service account for production use\n\n";

echo "🌐 Application URL: http://localhost/nwrcontractregistry/frontend/index.php\n";

?>