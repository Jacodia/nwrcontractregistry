# NWR Contract Registry - Active Directory Setup Script
# Run this script on your Domain Controller as Administrator

Write-Host "=== NWR Contract Registry AD Setup ===" -ForegroundColor Green
Write-Host "Setting up users and groups for hell.lab domain" -ForegroundColor Yellow
Write-Host ""

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Import Active Directory module
try {
    Import-Module ActiveDirectory -ErrorAction Stop
    Write-Host "✅ Active Directory module loaded" -ForegroundColor Green
} catch {
    Write-Host "❌ Active Directory module not available" -ForegroundColor Red
    Write-Host "Install RSAT-AD-PowerShell feature first" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "🏗️ STEP 1: Creating Organizational Units" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Create Groups OU if it doesn't exist
try {
    $groupsOU = Get-ADOrganizationalUnit -Filter {Name -eq "Groups"} -SearchBase "DC=hell,DC=lab" -ErrorAction SilentlyContinue
    if (-not $groupsOU) {
        New-ADOrganizationalUnit -Name "Groups" -Path "DC=hell,DC=lab" -Description "NWR Contract Registry Groups"
        Write-Host "✅ Created Groups OU" -ForegroundColor Green
    } else {
        Write-Host "ℹ️ Groups OU already exists" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️ Could not create Groups OU: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "👥 STEP 2: Creating Security Groups" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan

$groups = @(
    @{Name="NWR-Admins"; Description="NWR Contract Registry Administrators"},
    @{Name="NWR-Managers"; Description="NWR Contract Registry Managers"}, 
    @{Name="NWR-Users"; Description="NWR Contract Registry Users"}
)

foreach ($group in $groups) {
    try {
        $existingGroup = Get-ADGroup -Filter {Name -eq $group.Name} -ErrorAction SilentlyContinue
        if (-not $existingGroup) {
            New-ADGroup -Name $group.Name -GroupScope Global -GroupCategory Security -Path "OU=Groups,DC=hell,DC=lab" -Description $group.Description
            Write-Host "✅ Created group: $($group.Name)" -ForegroundColor Green
        } else {
            Write-Host "ℹ️ Group already exists: $($group.Name)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠️ Could not create group $($group.Name): $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "👤 STEP 3: Creating Test Users" -ForegroundColor Cyan
Write-Host "==============================" -ForegroundColor Cyan

$password = ConvertTo-SecureString "Test123!" -AsPlainText -Force

$users = @(
    @{Name="NWR Administrator"; SamAccountName="nwr-admin"; Email="nwr-admin@hell.lab"; Group="NWR-Admins"},
    @{Name="NWR Manager"; SamAccountName="nwr-manager"; Email="nwr-manager@hell.lab"; Group="NWR-Managers"},
    @{Name="NWR User"; SamAccountName="nwr-user"; Email="nwr-user@hell.lab"; Group="NWR-Users"},
    @{Name="NWR Test User"; SamAccountName="nwr-test"; Email="nwr-test@hell.lab"; Group=$null}
)

foreach ($user in $users) {
    try {
        $existingUser = Get-ADUser -Filter {SamAccountName -eq $user.SamAccountName} -ErrorAction SilentlyContinue
        if (-not $existingUser) {
            $userParams = @{
                Name = $user.Name
                SamAccountName = $user.SamAccountName
                UserPrincipalName = "$($user.SamAccountName)@hell.lab"
                EmailAddress = $user.Email
                DisplayName = $user.Name
                AccountPassword = $password
                Enabled = $true
                Path = "CN=Users,DC=hell,DC=lab"
                PasswordNeverExpires = $true
            }
            
            New-ADUser @userParams
            Write-Host "✅ Created user: $($user.SamAccountName)" -ForegroundColor Green
            
            # Add to group if specified
            if ($user.Group) {
                try {
                    Add-ADGroupMember -Identity $user.Group -Members $user.SamAccountName
                    Write-Host "   → Added to group: $($user.Group)" -ForegroundColor Green
                } catch {
                    Write-Host "   ⚠️ Could not add to group $($user.Group): $($_.Exception.Message)" -ForegroundColor Yellow
                }
            }
        } else {
            Write-Host "ℹ️ User already exists: $($user.SamAccountName)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠️ Could not create user $($user.SamAccountName): $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "📋 STEP 4: Setup Summary" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan

Write-Host ""
Write-Host "🎭 Created Groups:" -ForegroundColor White
try {
    $createdGroups = Get-ADGroup -Filter {Name -like "NWR-*"} | Select-Object Name,DistinguishedName
    foreach ($group in $createdGroups) {
        Write-Host "   ✅ $($group.Name)" -ForegroundColor Green
    }
} catch {
    Write-Host "   ⚠️ Could not list groups" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "👥 Created Users:" -ForegroundColor White
try {
    $createdUsers = Get-ADUser -Filter {SamAccountName -like "nwr-*"} | Select-Object Name,SamAccountName,EmailAddress
    foreach ($user in $createdUsers) {
        Write-Host "   ✅ $($user.SamAccountName) ($($user.Name))" -ForegroundColor Green
    }
} catch {
    Write-Host "   ⚠️ Could not list users" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🧪 READY FOR TESTING!" -ForegroundColor Green
Write-Host "=====================" -ForegroundColor Green
Write-Host ""
Write-Host "Test these accounts in the NWR Contract Registry:" -ForegroundColor White
Write-Host ""
Write-Host "🔑 Login Credentials:" -ForegroundColor Yellow
Write-Host "   Username: nwr-admin    | Password: Test123!  | Expected Role: admin" -ForegroundColor White
Write-Host "   Username: nwr-manager  | Password: Test123!  | Expected Role: manager" -ForegroundColor White  
Write-Host "   Username: nwr-user     | Password: Test123!  | Expected Role: viewer" -ForegroundColor White
Write-Host "   Username: nwr-test     | Password: Test123!  | Expected Role: viewer (default)" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Application URL:" -ForegroundColor Yellow
Write-Host "   http://localhost/nwrcontractregistry/frontend/index.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Login Tips:" -ForegroundColor Yellow
Write-Host "   • Use username only (e.g., 'nwr-admin') or full email (e.g., 'nwr-admin@hell.lab')" -ForegroundColor White
Write-Host "   • Users will be auto-created in application database after successful AD login" -ForegroundColor White
Write-Host "   • Roles are assigned based on AD group membership" -ForegroundColor White
Write-Host ""
Write-Host "✅ Active Directory setup complete!" -ForegroundColor Green