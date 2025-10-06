<?php
echo "=== Active Directory User Discovery for hell.lab ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "🔍 FINDING USERS IN hell.lab ACTIVE DIRECTORY\n";
echo "==============================================\n\n";

function searchADUsers() {
    try {
        $ldap_host = $_ENV['LDAP_HOST'];
        $ldap_port = $_ENV['LDAP_PORT'];
        $ldap_base_dn = $_ENV['LDAP_BASE_DN'];
        $use_tls = $_ENV['LDAP_USE_TLS'] === 'true';
        
        echo "Connecting to: $ldap_host:$ldap_port\n";
        echo "Base DN: $ldap_base_dn\n\n";
        
        // Connect to LDAP
        $ldapconn = ldap_connect($ldap_host, $ldap_port);
        if (!$ldapconn) {
            echo "❌ Could not connect to LDAP server\n";
            return false;
        }
        
        // Set options
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 10);
        
        if ($use_tls && !ldap_start_tls($ldapconn)) {
            echo "⚠️ Could not start TLS, continuing without\n";
        }
        
        // Try anonymous bind first
        if (!@ldap_bind($ldapconn)) {
            echo "⚠️ Anonymous bind failed, trying without authentication\n";
        }
        
        echo "🔍 Searching for users in hell.lab domain...\n\n";
        
        // Search for user accounts
        $search_filter = "(&(objectClass=user)(!(objectClass=computer))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))";
        $attributes = ['sAMAccountName', 'mail', 'displayName', 'userPrincipalName', 'description', 'memberOf'];
        
        $search_result = @ldap_search($ldapconn, $ldap_base_dn, $search_filter, $attributes);
        
        if (!$search_result) {
            echo "❌ Search failed: " . ldap_error($ldapconn) . "\n";
            echo "This might be due to:\n";
            echo "• Anonymous search not allowed\n";
            echo "• Need service account credentials\n";
            echo "• Firewall or network issues\n\n";
            
            ldap_close($ldapconn);
            return false;
        }
        
        $entries = ldap_get_entries($ldapconn, $search_result);
        ldap_close($ldapconn);
        
        if ($entries['count'] == 0) {
            echo "⚠️ No users found. This could mean:\n";
            echo "• Anonymous search restrictions\n";
            echo "• Need proper search permissions\n";
            echo "• Wrong base DN\n\n";
            return false;
        }
        
        echo "✅ Found {$entries['count']} user(s) in hell.lab:\n\n";
        
        $userList = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            
            $username = $entry['samaccountname'][0] ?? 'Unknown';
            $email = $entry['mail'][0] ?? $username . '@hell.lab';
            $displayName = $entry['displayname'][0] ?? $username;
            $upn = $entry['userprincipalname'][0] ?? $email;
            
            $userList[] = [
                'username' => $username,
                'email' => $email,
                'displayName' => $displayName,
                'upn' => $upn
            ];
            
            echo "👤 User " . ($i + 1) . ":\n";
            echo "   Username: $username\n";
            echo "   Display Name: $displayName\n";
            echo "   Email: $email\n";
            echo "   UPN: $upn\n";
            echo "   Login formats: '$username' or '$email'\n\n";
        }
        
        return $userList;
        
    } catch (Exception $e) {
        echo "❌ Error searching AD: " . $e->getMessage() . "\n";
        return false;
    }
}

// Try to discover users
$users = searchADUsers();

if (!$users) {
    echo "🔧 ALTERNATIVE METHODS TO FIND AD USERS:\n";
    echo "========================================\n\n";
    
    echo "1. 💻 PowerShell (run on domain controller or domain-joined machine):\n";
    echo "   Get-ADUser -Filter * | Select Name,SamAccountName,EmailAddress | Format-Table\n\n";
    
    echo "2. 🖥️ Active Directory Users and Computers:\n";
    echo "   • Open ADUC on domain controller\n";
    echo "   • Navigate to Users container\n";
    echo "   • Look for enabled user accounts\n\n";
    
    echo "3. 📋 Common default accounts to try:\n";
    echo "   • administrator (usually exists)\n";
    echo "   • guest (often disabled)\n";
    echo "   • Any user accounts you've created\n\n";
    
    echo "4. 🔍 LDAP Browser tools:\n";
    echo "   • Use LDP.exe (Windows LDAP browser)\n";
    echo "   • Connect to localhost:389\n";
    echo "   • Browse DC=hell,DC=lab\n\n";
}

echo "🧪 TESTING RECOMMENDATIONS:\n";
echo "===========================\n\n";

if ($users && count($users) > 0) {
    echo "Based on discovered users, try these login tests:\n\n";
    foreach (array_slice($users, 0, 3) as $i => $user) {
        echo "Test " . ($i + 1) . ": Login with '{$user['username']}'\n";
        echo "   • Go to: http://localhost/nwrcontractregistry/frontend/index.php\n";
        echo "   • Username: {$user['username']}\n";
        echo "   • Password: [actual AD password for this user]\n";
        echo "   • Alternative: {$user['email']}\n\n";
    }
} else {
    echo "Since automatic discovery didn't work, try these steps:\n\n";
    echo "1. 🎯 Test with known accounts:\n";
    echo "   • Use 'administrator' if you know the password\n";
    echo "   • Try any user accounts you've created in hell.lab\n\n";
    
    echo "2. 🔐 Create a test user in AD:\n";
    echo "   • Open Active Directory Users and Computers\n";
    echo "   • Create new user (e.g., 'testuser')\n";
    echo "   • Set password and enable account\n";
    echo "   • Test login with testuser@hell.lab\n\n";
}

echo "📊 WHAT TO EXPECT AFTER SUCCESSFUL LOGIN:\n";
echo "=========================================\n";
echo "1. ✅ User authenticates against hell.lab AD\n";
echo "2. ✅ New record appears in local database\n";
echo "3. ✅ User gets redirected to dashboard\n";
echo "4. ✅ Role assigned based on AD group membership\n\n";

// Show current database users for comparison
echo "📋 CURRENT DATABASE USERS (for reference):\n";
echo "==========================================\n";
try {
    $stmt = $pdo->query("SELECT username, email, role FROM users ORDER BY userid DESC LIMIT 5");
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent users in local database:\n";
    foreach ($dbUsers as $user) {
        echo "• {$user['username']} ({$user['email']}) - Role: " . ($user['role'] ?: 'None') . "\n";
    }
    echo "\nNote: In production mode, these local users CANNOT login.\n";
    echo "Only hell.lab AD users can authenticate.\n\n";
} catch (Exception $e) {
    echo "Could not query database: " . $e->getMessage() . "\n\n";
}

echo "🌐 Ready to test at: http://localhost/nwrcontractregistry/frontend/index.php\n";

?>