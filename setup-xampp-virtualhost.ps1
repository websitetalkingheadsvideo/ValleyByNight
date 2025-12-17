# Setup XAMPP Virtual Host for G:\VbN
# Run this script as Administrator to configure Apache

Write-Host "XAMPP Virtual Host Setup for VbN" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

$apacheConfPath = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
$hostsFilePath = "$env:SystemRoot\System32\drivers\etc\hosts"

# Check if running as admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Step 1: Backup existing vhosts config
Write-Host "1. Backing up existing vhosts config..." -ForegroundColor Yellow
if (Test-Path $apacheConfPath) {
    $backupPath = "$apacheConfPath.backup.$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Copy-Item $apacheConfPath $backupPath
    Write-Host "   ✓ Backup created: $backupPath" -ForegroundColor Green
} else {
    Write-Host "   ⚠ vhosts.conf not found, will create new file" -ForegroundColor Yellow
}

# Step 2: Add virtual host entry
Write-Host "`n2. Adding virtual host entry..." -ForegroundColor Yellow

$projectPath = "G:\VbN"
$vhostConfig = @"

# Valley by Night - Local Development
<VirtualHost *:80>
    ServerName vbn.local
    DocumentRoot "$projectPath"
    <Directory "$projectPath">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/vbn.local-error.log"
    CustomLog "logs/vbn.local-access.log" common
</VirtualHost>

"@

# Check if entry already exists
$existingContent = Get-Content $apacheConfPath -Raw -ErrorAction SilentlyContinue
if ($existingContent -match "vbn\.local") {
    Write-Host "   ⚠ Virtual host for vbn.local already exists" -ForegroundColor Yellow
    Write-Host "   Review $apacheConfPath manually if needed" -ForegroundColor Yellow
} else {
    # Append to file (or create new)
    Add-Content -Path $apacheConfPath -Value $vhostConfig
    Write-Host "   ✓ Virtual host entry added" -ForegroundColor Green
}

# Step 3: Check if httpd-vhosts.conf is included in main httpd.conf
Write-Host "`n3. Checking main Apache config..." -ForegroundColor Yellow
$mainConfPath = "C:\xampp\apache\conf\httpd.conf"
if (Test-Path $mainConfPath) {
    $mainConfContent = Get-Content $mainConfPath -Raw
    if ($mainConfContent -match "#Include.*conf/extra/httpd-vhosts.conf") {
        Write-Host "   ⚠ Virtual hosts are COMMENTED OUT in httpd.conf" -ForegroundColor Red
        Write-Host "   You need to uncomment this line in httpd.conf:" -ForegroundColor Yellow
        Write-Host "   Change: #Include conf/extra/httpd-vhosts.conf" -ForegroundColor White
        Write-Host "   To:     Include conf/extra/httpd-vhosts.conf" -ForegroundColor Green
    } elseif ($mainConfContent -match "Include.*conf/extra/httpd-vhosts.conf") {
        Write-Host "   ✓ Virtual hosts are enabled in httpd.conf" -ForegroundColor Green
    } else {
        Write-Host "   ⚠ Could not find Include directive for vhosts" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ✗ Could not find main Apache config" -ForegroundColor Red
}

# Step 4: Add entry to hosts file
Write-Host "`n4. Adding entry to Windows hosts file..." -ForegroundColor Yellow
$hostsEntry = "127.0.0.1    vbn.local"

$hostsContent = Get-Content $hostsFilePath -ErrorAction SilentlyContinue
if ($hostsContent -contains $hostsEntry) {
    Write-Host "   ✓ Hosts entry already exists" -ForegroundColor Green
} else {
    try {
        Add-Content -Path $hostsFilePath -Value $hostsEntry
        Write-Host "   ✓ Hosts entry added: $hostsEntry" -ForegroundColor Green
    } catch {
        Write-Host "   ✗ Failed to add hosts entry: $_" -ForegroundColor Red
        Write-Host "   Manually add this line to hosts file:" -ForegroundColor Yellow
        Write-Host "   $hostsEntry" -ForegroundColor White
    }
}

Write-Host "`n=================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Restart Apache in XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Open browser and go to: http://vbn.local/" -ForegroundColor White
Write-Host "3. If you see 'ServerName takes precedence', uncomment the Include line in httpd.conf" -ForegroundColor White
Write-Host ""
Write-Host "Your project will be served directly from: $projectPath" -ForegroundColor Cyan

