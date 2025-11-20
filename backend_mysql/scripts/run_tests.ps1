<#
PowerShell test runner for backend_mysql.

What it does:
- Attempts to import the SQL schema via `mysql` CLI if available.
- Calls the backend endpoints on localhost (login, get_contracts, add_contract) using a persistent web session.

Usage:
Open PowerShell and run:
  cd "c:\Users\JGaseb\xampp\htdocs\nwrcontractregistry\backend_mysql\scripts"
  .\run_tests.ps1

#>

$base = "http://localhost/nwrcontractregistry/backend_mysql"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$schema = Join-Path -Path $scriptDir -ChildPath "schema_mysql.sql"

Write-Host "Schema file: $schema" -ForegroundColor Cyan

# Try to detect mysql CLI
$mysqlExists = $false
try {
    $ver = & mysql --version 2>$null
    if ($LASTEXITCODE -eq 0) { $mysqlExists = $true }
} catch {
    $mysqlExists = $false
}

if ($mysqlExists) {
    Write-Host "Importing schema via mysql CLI..." -ForegroundColor Yellow
    # Import into default database name inside SQL file
    & mysql -h 127.0.0.1 -u root < "$schema"
    if ($LASTEXITCODE -ne 0) { Write-Host "mysql import returned code $LASTEXITCODE" -ForegroundColor Red }
    else { Write-Host "Import completed." -ForegroundColor Green }
} else {
    Write-Host "mysql CLI not found in PATH. Please import the SQL file into XAMPP phpMyAdmin or run the equivalent command:" -ForegroundColor Yellow
    Write-Host "mysql -h 127.0.0.1 -u root < \"$schema\""
}

# Start HTTP tests
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Show-Json($obj) {
    $obj | ConvertTo-Json -Depth 10 | Write-Host
}

Write-Host "`nTesting GET /get_contracts.php" -ForegroundColor Cyan
try {
    $resp = Invoke-RestMethod -Uri "$base/get_contracts.php" -Method GET -WebSession $session -ErrorAction Stop
    Show-Json $resp
} catch {
    Write-Host "GET failed:`n$_" -ForegroundColor Red
}

Write-Host "`nTesting POST /login.php" -ForegroundColor Cyan
$loginBody = @{ username = 'admin'; password = 'secret' } | ConvertTo-Json
try {
    $resp = Invoke-RestMethod -Uri "$base/login.php" -Method Post -Body $loginBody -ContentType 'application/json' -WebSession $session -ErrorAction Stop
    Show-Json $resp
} catch {
    Write-Host "Login failed:`n$_" -ForegroundColor Red
}

Write-Host "`nTesting POST /add_contract.php" -ForegroundColor Cyan
$contractBody = @{ title='PowerShell Test Contract'; party='Test Party'; start_date='2025-11-10'; end_date='2026-11-10'; notes='Created by PS test' } | ConvertTo-Json
try {
    $resp = Invoke-RestMethod -Uri "$base/add_contract.php" -Method Post -Body $contractBody -ContentType 'application/json' -WebSession $session -ErrorAction Stop
    Show-Json $resp
} catch {
    Write-Host "Add contract failed:`n$_" -ForegroundColor Red
}

Write-Host "`nGET /get_contracts.php (after add)" -ForegroundColor Cyan
try {
    $resp = Invoke-RestMethod -Uri "$base/get_contracts.php" -Method GET -WebSession $session -ErrorAction Stop
    Show-Json $resp
} catch {
    Write-Host "GET after add failed:`n$_" -ForegroundColor Red
}

Write-Host "`nTests completed." -ForegroundColor Green
$repo = 'C:\Users\JGaseb\xampp\htdocs\nwrcontractregistry'
Set-Location $repo

Write-Output "Running bootstrap (creates tables and seeds test user)..."
php .\backend_mysql\scripts\bootstrap.php

Write-Output "\nStarting endpoint tests..."

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Write-Output "Logging in as testuser/secret..."
try {
    $login = Invoke-RestMethod -Uri 'http://localhost/nwrcontractregistry/backend_mysql/login.php' -Method Post -ContentType 'application/json' -Body ('{"username":"testuser","password":"secret"}') -WebSession $session -ErrorAction Stop
    Write-Output "Login response:"; $login | ConvertTo-Json -Depth 5
} catch {
    Write-Output "Login failed: $_"
    exit 1
}

Write-Output "\nFetching contracts (before add)..."
try {
    $contracts = Invoke-RestMethod -Uri 'http://localhost/nwrcontractregistry/backend_mysql/get_contracts.php' -Method Get -WebSession $session -ErrorAction Stop
    $contracts | ConvertTo-Json -Depth 10
} catch {
    Write-Output "Get contracts failed: $_"
    exit 1
}

Write-Output "\nAdding a new contract..."
try {
    $body = '{"title":"Test Contract PS","party":"Acme Inc","start_date":"2025-10-01","end_date":"2026-10-01","notes":"added by run_tests"}'
    $add = Invoke-RestMethod -Uri 'http://localhost/nwrcontractregistry/backend_mysql/add_contract.php' -Method Post -ContentType 'application/json' -Body $body -WebSession $session -ErrorAction Stop
    Write-Output "Add response:"; $add | ConvertTo-Json -Depth 5
} catch {
    Write-Output "Add contract failed: $_"
    exit 1
}

Write-Output "\nFetching contracts (after add)..."
try {
    $contracts2 = Invoke-RestMethod -Uri 'http://localhost/nwrcontractregistry/backend_mysql/get_contracts.php' -Method Get -WebSession $session -ErrorAction Stop
    $contracts2 | ConvertTo-Json -Depth 10
} catch {
    Write-Output "Get contracts failed: $_"
    exit 1
}

Write-Output "\nTests completed"
