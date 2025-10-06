# PowerShell script to find Active Directory users in hell.lab domain
Write-Host "=== Finding Active Directory Users in hell.lab ===" -ForegroundColor Green
Write-Host "Date: $(Get-Date)" -ForegroundColor Gray
Write-Host ""

# Check if AD module is available
if (Get-Module -ListAvailable -Name ActiveDirectory) {
    Write-Host "✅ Active Directory PowerShell module found" -ForegroundColor Green
    
    try {
        Import-Module ActiveDirectory
        
        Write-Host "🔍 Searching for enabled user accounts in hell.lab..." -ForegroundColor Yellow
        Write-Host ""
        
        # Get all enabled users
        $users = Get-ADUser -Filter {Enabled -eq $true} -Properties DisplayName,EmailAddress | Select-Object Name,SamAccountName,EmailAddress,DisplayName
        
        if ($users.Count -gt 0) {
            Write-Host "✅ Found $($users.Count) enabled user(s):" -ForegroundColor Green
            Write-Host ""
            
            $counter = 1
            foreach ($user in $users) {
                Write-Host "👤 User $counter" -ForegroundColor Cyan
                Write-Host "   Username: $($user.SamAccountName)" -ForegroundColor White
                Write-Host "   Display Name: $($user.DisplayName)" -ForegroundColor White
                Write-Host "   Email: $($user.EmailAddress)" -ForegroundColor White
                Write-Host "   Login formats: '$($user.SamAccountName)' or '$($user.SamAccountName)@hell.lab'" -ForegroundColor Yellow
                Write-Host ""
                $counter++
            }
        } else {
            Write-Host "⚠️ No enabled users found in domain" -ForegroundColor Yellow
        }
        
    } catch {
        Write-Host "❌ Error querying Active Directory: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "   Make sure you're running this on a domain-joined machine" -ForegroundColor Yellow
    }
    
} else {
    Write-Host "⚠️ Active Directory PowerShell module not available" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Alternative methods:" -ForegroundColor White
    Write-Host "1. Open Active Directory Users and Computers (dsa.msc)" -ForegroundColor White
    Write-Host "2. Browse to Users container" -ForegroundColor White
    Write-Host "3. Look for enabled user accounts" -ForegroundColor White
    Write-Host ""
}

Write-Host "📋 COMMON TEST ACCOUNTS TO TRY:" -ForegroundColor Green
Write-Host "• administrator (if you know the password)" -ForegroundColor White
Write-Host "• Any user accounts you've created" -ForegroundColor White
Write-Host ""

Write-Host "🎯 TESTING INSTRUCTIONS:" -ForegroundColor Green
Write-Host "1. Open: http://localhost/nwrcontractregistry/frontend/index.php" -ForegroundColor White
Write-Host "2. Use format: 'username' or 'username@hell.lab'" -ForegroundColor White
Write-Host "3. Enter the actual domain password" -ForegroundColor White
Write-Host "4. System will auto-create user in database on successful login" -ForegroundColor White