<?php
echo "=== Complete Active Directory Login Testing Guide ===\n";
echo "From AD Structure to Application Testing\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

echo "🏗️ STEP 1: ACTIVE DIRECTORY ORGANIZATIONAL STRUCTURE\n";
echo "====================================================\n\n";

echo "📋 Your Current AD Domain: hell.lab\n";
echo "Base DN: DC=hell,DC=lab\n\n";

echo "🗂️ Typical AD Structure for hell.lab:\n";
echo "=====================================\n";
echo "hell.lab (Domain Root)\n";
echo "├── DC=hell,DC=lab\n";
echo "    ├── CN=Builtin\n";
echo "    │   ├── CN=Administrators\n";
echo "    │   ├── CN=Users\n";
echo "    │   └── CN=Guests\n";
echo "    ├── CN=Users (Default Users Container)\n";
echo "    │   ├── CN=Administrator\n";
echo "    │   ├── CN=Guest\n";
echo "    │   └── CN=Your Custom Users\n";
echo "    ├── OU=Groups (Optional - for NWR groups)\n";
echo "    │   ├── CN=NWR-Admins\n";
echo "    │   ├── CN=NWR-Managers\n";
echo "    │   └── CN=NWR-Users\n";
echo "    ├── OU=Users (Optional - for organizational users)\n";
echo "    │   ├── CN=John Doe\n";
echo "    │   ├── CN=Jane Smith\n";
echo "    │   └── CN=Test User\n";
echo "    └── OU=Computers\n\n";

echo "🎭 STEP 2: ROLE-BASED GROUPS SETUP\n";
echo "==================================\n\n";

echo "For proper role assignment, create these groups in your hell.lab AD:\n\n";

$groups = [
    'NWR-Admins' => [
        'dn' => 'CN=NWR-Admins,OU=Groups,DC=hell,DC=lab',
        'description' => 'Full system administrators',
        'permissions' => 'All features, user management, system settings'
    ],
    'NWR-Managers' => [
        'dn' => 'CN=NWR-Managers,OU=Groups,DC=hell,DC=lab', 
        'description' => 'Contract managers',
        'permissions' => 'Contract CRUD, file uploads, dashboard access'
    ],
    'NWR-Users' => [
        'dn' => 'CN=NWR-Users,OU=Groups,DC=hell,DC=lab',
        'description' => 'Regular users',
        'permissions' => 'View contracts, basic dashboard access'
    ]
];

foreach ($groups as $groupName => $details) {
    echo "📁 Group: $groupName\n";
    echo "   DN: {$details['dn']}\n";
    echo "   Description: {$details['description']}\n";
    echo "   Permissions: {$details['permissions']}\n\n";
}

echo "🔧 STEP 3: CREATE AD GROUPS (PowerShell Commands)\n";
echo "================================================\n\n";

echo "Run these PowerShell commands on your Domain Controller:\n\n";

echo "# Create Groups OU (if it doesn't exist)\n";
echo "New-ADOrganizationalUnit -Name 'Groups' -Path 'DC=hell,DC=lab'\n\n";

echo "# Create NWR Security Groups\n";
echo "New-ADGroup -Name 'NWR-Admins' -GroupScope Global -GroupCategory Security -Path 'OU=Groups,DC=hell,DC=lab' -Description 'NWR Contract Registry Administrators'\n";
echo "New-ADGroup -Name 'NWR-Managers' -GroupScope Global -GroupCategory Security -Path 'OU=Groups,DC=hell,DC=lab' -Description 'NWR Contract Registry Managers'\n";
echo "New-ADGroup -Name 'NWR-Users' -GroupScope Global -GroupCategory Security -Path 'OU=Groups,DC=hell,DC=lab' -Description 'NWR Contract Registry Users'\n\n";

echo "👤 STEP 4: CREATE TEST USERS\n";
echo "============================\n\n";

echo "Create test users in Active Directory:\n\n";

$testUsers = [
    'nwr-admin' => [
        'displayName' => 'NWR Administrator',
        'email' => 'nwr-admin@hell.lab',
        'group' => 'NWR-Admins',
        'role' => 'admin'
    ],
    'nwr-manager' => [
        'displayName' => 'NWR Manager',
        'email' => 'nwr-manager@hell.lab', 
        'group' => 'NWR-Managers',
        'role' => 'manager'
    ],
    'nwr-user' => [
        'displayName' => 'NWR User',
        'email' => 'nwr-user@hell.lab',
        'group' => 'NWR-Users', 
        'role' => 'viewer'
    ],
    'nwr-test' => [
        'displayName' => 'NWR Test User',
        'email' => 'nwr-test@hell.lab',
        'group' => 'None',
        'role' => 'viewer (default)'
    ]
];

echo "PowerShell commands to create test users:\n\n";

foreach ($testUsers as $username => $details) {
    echo "# Create user: $username\n";
    echo "\$password = ConvertTo-SecureString 'Test123!' -AsPlainText -Force\n";
    echo "New-ADUser -Name '{$details['displayName']}' -SamAccountName '$username' -UserPrincipalName '$username@hell.lab' -EmailAddress '{$details['email']}' -DisplayName '{$details['displayName']}' -AccountPassword \$password -Enabled \$true -Path 'CN=Users,DC=hell,DC=lab'\n";
    
    if ($details['group'] !== 'None') {
        echo "Add-ADGroupMember -Identity '{$details['group']}' -Members '$username'\n";
    }
    echo "\n";
}

echo "🧪 STEP 5: TESTING PROCEDURE\n";
echo "============================\n\n";

echo "Now test the login system with these steps:\n\n";

echo "5.1. 🌐 OPEN THE APPLICATION\n";
echo "   → Go to: http://localhost/nwrcontractregistry/frontend/index.php\n\n";

echo "5.2. 🔐 TEST EACH USER ACCOUNT\n";
echo "   For each test user created above:\n\n";

$testCounter = 1;
foreach ($testUsers as $username => $details) {
    echo "   Test $testCounter: {$details['displayName']}\n";
    echo "   ┌─────────────────────────────────────────┐\n";
    echo "   │ Username: $username                     │\n";
    echo "   │ Password: Test123!                      │\n";
    echo "   │ Expected Role: {$details['role']}       │\n";
    echo "   │ Login Format: '$username' or '$username@hell.lab' │\n";
    echo "   └─────────────────────────────────────────┘\n\n";
    $testCounter++;
}

echo "5.3. 📊 VERIFY LOGIN RESULTS\n";
echo "   After each successful login, check:\n";
echo "   ✅ User is redirected to dashboard\n";
echo "   ✅ Correct role is displayed\n";
echo "   ✅ Menu items match role permissions\n";
echo "   ✅ User appears in database\n\n";

echo "5.4. 🗃️ CHECK DATABASE AUTO-PROVISIONING\n";
echo "   Run this SQL query to see created users:\n";
echo "   SELECT userid, username, email, role FROM users ORDER BY userid DESC;\n\n";

echo "📋 STEP 6: TESTING CHECKLIST\n";
echo "============================\n\n";

$checklist = [
    "AD Groups created (NWR-Admins, NWR-Managers, NWR-Users)",
    "Test users created with proper group membership",
    "Login with nwr-admin user → Should get 'admin' role",
    "Login with nwr-manager user → Should get 'manager' role", 
    "Login with nwr-user user → Should get 'viewer' role",
    "Login with nwr-test user → Should get 'viewer' role (default)",
    "Users auto-created in local database after AD login",
    "Role-based menu visibility works correctly",
    "Contract management features accessible based on role",
    "Logout functionality works properly"
];

foreach ($checklist as $index => $item) {
    echo "☐ " . ($index + 1) . ". $item\n";
}

echo "\n🔍 STEP 7: TROUBLESHOOTING\n";
echo "==========================\n\n";

echo "If login fails, check:\n\n";

echo "7.1. 🔌 LDAP Connection\n";
echo "   → Test: php test_ldap_auth.php\n";
echo "   → Verify: LDAP server is accessible on localhost:389\n\n";

echo "7.2. 👤 User Account Status\n";
echo "   → Check: User account is enabled in AD\n";
echo "   → Verify: Password is correct\n";
echo "   → Test: Account is not locked out\n\n";

echo "7.3. 🎭 Group Membership\n";
echo "   → Verify: User is member of correct NWR groups\n";
echo "   → Check: Group DNs match configuration\n\n";

echo "7.4. 📝 Application Logs\n";
echo "   → Check: PHP error logs\n";
echo "   → Review: IIS logs\n";
echo "   → Monitor: Windows Event Viewer\n\n";

echo "🎯 EXPECTED OUTCOMES\n";
echo "===================\n\n";

echo "After successful testing, you should see:\n\n";
echo "✅ All 4 test users can login successfully\n";
echo "✅ Correct roles assigned based on AD group membership\n";  
echo "✅ Users auto-created in local database\n";
echo "✅ Role-based access control working\n";
echo "✅ Contract management features accessible per role\n";
echo "✅ Clean logout and re-login functionality\n\n";

echo "🌟 PRODUCTION READY INDICATORS\n";
echo "==============================\n\n";
echo "Your system is production-ready when:\n";
echo "• ✅ All test accounts work correctly\n";
echo "• ✅ Role assignments are accurate\n"; 
echo "• ✅ No authentication errors in logs\n";
echo "• ✅ Auto-provisioning creates users properly\n";
echo "• ✅ Application features work per role\n\n";

echo "🔗 Quick Test URLs:\n";
echo "Application: http://localhost/nwrcontractregistry/frontend/index.php\n";
echo "Test Dashboard: http://localhost/nwrcontractregistry/backend/tests/login_test_dashboard.html\n";

?>