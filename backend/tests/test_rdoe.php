<?php
echo "Testing RDoe@hell.lab authentication\n";
echo "====================================\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$username = 'RDoe';
$password = 'pass.123';
$domain = $_ENV['LDAP_DOMAIN'];
$host = $_ENV['LDAP_HOST'];
$port = $_ENV['LDAP_PORT'];
$base_dn = $_ENV['LDAP_BASE_DN'];

echo "Configuration:\n";
echo "Host: $host:$port\n";
echo "Domain: $domain\n";
echo "Base DN: $base_dn\n";
echo "Username: $username\n\n";

echo "Step 1: Connecting to LDAP server...\n";

try {
    $ldapconn = ldap_connect($host, $port);
    if (!$ldapconn) {
        throw new Exception("Could not connect to LDAP server");
    }
    echo "✅ Connected to LDAP server\n\n";

    echo "Step 2: Setting LDAP options...\n";
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
    echo "✅ LDAP options set\n\n";

    echo "Step 3: Attempting authentication...\n";
    $userDN = $username . '@' . $domain;
    echo "Full DN: $userDN\n";

    $bind_result = @ldap_bind($ldapconn, $userDN, $password);
    
    if ($bind_result) {
        echo "✅ Authentication successful!\n\n";
        
        echo "Step 4: Searching for user details...\n";
        $search_filter = "(&(objectClass=user)(sAMAccountName=$username))";
        $search_result = @ldap_search($ldapconn, $base_dn, $search_filter, [
            'sAMAccountName', 'mail', 'displayName', 'memberOf', 'cn'
        ]);
        
        if ($search_result) {
            $entries = ldap_get_entries($ldapconn, $search_result);
            echo "Search results: " . $entries['count'] . " user(s) found\n";
            
            if ($entries['count'] > 0) {
                $user = $entries[0];
                echo "✅ User details retrieved:\n";
                echo "  - Account Name: " . ($user['samaccountname'][0] ?? 'N/A') . "\n";
                echo "  - Display Name: " . ($user['displayname'][0] ?? 'N/A') . "\n";
                echo "  - Email: " . ($user['mail'][0] ?? 'N/A') . "\n";
                echo "  - Common Name: " . ($user['cn'][0] ?? 'N/A') . "\n";
                
                if (isset($user['memberof'])) {
                    $groups = $user['memberof'];
                    $group_count = is_array($groups) ? count($groups) - 1 : 0;
                    echo "  - Group Memberships: $group_count group(s)\n";
                    
                    if ($group_count > 0) {
                        echo "    Groups:\n";
                        for ($i = 0; $i < min(5, $group_count); $i++) {
                            echo "    • " . $groups[$i] . "\n";
                        }
                        if ($group_count > 5) {
                            echo "    ... and " . ($group_count - 5) . " more\n";
                        }
                    }
                }
                
                echo "\n🎉 SUCCESS: RDoe can authenticate with hell.lab domain!\n";
                echo "User is ready for application login.\n";
                
            } else {
                echo "⚠️ Authentication worked but user details not found in directory\n";
            }
        } else {
            echo "⚠️ Authentication worked but search failed: " . ldap_error($ldapconn) . "\n";
        }
        
    } else {
        echo "❌ Authentication failed\n";
        echo "LDAP Error: " . ldap_error($ldapconn) . "\n";
        echo "Error Number: " . ldap_errno($ldapconn) . "\n\n";
        
        echo "Possible reasons:\n";
        echo "• Username 'RDoe' doesn't exist in hell.lab domain\n";
        echo "• Password 'pass.123' is incorrect\n";
        echo "• Account is disabled or locked\n";
        echo "• Account requires password change\n";
    }
    
    ldap_close($ldapconn);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🔍 Next steps:\n";
echo "1. If successful: Test login at http://localhost/nwrcontractregistry/frontend/\n";
echo "2. If failed: Check if RDoe user exists in Active Directory\n";
echo "3. Verify password is correct\n";
echo "4. Check if account is enabled in AD\n";