# PowerShell Deployment Script for NWR Contract Registry
# File: deploy-production.ps1

param(
    [string]$ServerName = "WEB-NWR-01",
    [string]$SiteName = "NWR-Contracts",
    [string]$AppPath = "D:\WebApps\NWRContracts"
)

Write-Host "Starting NWR Contract Registry Production Deployment..." -ForegroundColor Green

# 1. Create application directory
if (!(Test-Path $AppPath)) {
    New-Item -ItemType Directory -Path $AppPath -Force
    Write-Host "Created application directory: $AppPath" -ForegroundColor Yellow
}

# 2. Set permissions
$AppPoolIdentity = "IIS_IUSRS"
icacls $AppPath /grant "${AppPoolIdentity}:(OI)(CI)M" /T

# 3. Create IIS Application Pool
$PoolName = "NWR-Contracts-Pool"
if (!(Get-IISAppPool -Name $PoolName -ErrorAction SilentlyContinue)) {
    New-IISAppPool -Name $PoolName
    Set-IISAppPool -Name $PoolName -ProcessModel.IdentityType ApplicationPoolIdentity
    Set-IISAppPool -Name $PoolName -Recycling.PeriodicRestart.Time "00:00:00"
    Write-Host "Created application pool: $PoolName" -ForegroundColor Yellow
}

# 4. Create IIS Website
if (!(Get-IISSite -Name $SiteName -ErrorAction SilentlyContinue)) {
    New-IISSite -Name $SiteName -PhysicalPath $AppPath -Port 80 -ApplicationPool $PoolName
    Write-Host "Created IIS site: $SiteName" -ForegroundColor Yellow
}

# 5. Configure HTTPS binding
$Cert = Get-ChildItem -Path Cert:\LocalMachine\My | Where-Object { $_.Subject -like "*nwr.local*" }
if ($Cert) {
    New-IISSiteBinding -Name $SiteName -Protocol https -Port 443 -CertificateThumbPrint $Cert.Thumbprint
    Write-Host "Configured HTTPS binding" -ForegroundColor Yellow
}

# 6. Install PHP dependencies
Set-Location $AppPath
if (Test-Path "composer.json") {
    composer install --no-dev --optimize-autoloader
    Write-Host "Installed Composer dependencies" -ForegroundColor Yellow
}

# 7. Set up upload directories
$UploadDir = Join-Path $AppPath "backend\uploads"
$LogDir = Join-Path $AppPath "backend\logs"

if (!(Test-Path $UploadDir)) { New-Item -ItemType Directory -Path $UploadDir -Force }
if (!(Test-Path $LogDir)) { New-Item -ItemType Directory -Path $LogDir -Force }

# Set permissions for upload and log directories
icacls $UploadDir /grant "${AppPoolIdentity}:(OI)(CI)F" /T
icacls $LogDir /grant "${AppPoolIdentity}:(OI)(CI)F" /T

# 8. Configure scheduled tasks for email notifications
$TaskName = "NWR-Contract-Notifications"
$TaskAction = New-ScheduledTaskAction -Execute "php.exe" -Argument "$AppPath\backend\cron_notify.php"
$TaskTrigger = New-ScheduledTaskTrigger -Daily -At "09:00AM"
$TaskSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $TaskName -Action $TaskAction -Trigger $TaskTrigger -Settings $TaskSettings -User "NT AUTHORITY\SYSTEM"

Write-Host "Deployment completed successfully!" -ForegroundColor Green
Write-Host "Site URL: https://$ServerName" -ForegroundColor Cyan
Write-Host "Remember to:" -ForegroundColor Yellow
Write-Host "1. Update .env file with production settings" -ForegroundColor Yellow
Write-Host "2. Import SSL certificate" -ForegroundColor Yellow
Write-Host "3. Configure firewall rules" -ForegroundColor Yellow
Write-Host "4. Test LDAP connectivity" -ForegroundColor Yellow