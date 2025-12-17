# Valley by Night - Local Development Setup Verification Script
# This script checks if your local development environment is configured correctly

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VbN Local Development Setup Verification" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$errors = 0
$warnings = 0

# Check 1: Verify .env file exists
Write-Host "1. Checking .env file..." -ForegroundColor Yellow
if (Test-Path ".env") {
    Write-Host "   ✓ .env file found" -ForegroundColor Green
    
    # Check if DB_PASSWORD is set
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "DB_PASSWORD\s*=\s*[^\s]+" -and $envContent -notmatch "DB_PASSWORD\s*=\s*your_password_here") {
        Write-Host "   ✓ DB_PASSWORD appears to be configured" -ForegroundColor Green
    } else {
        Write-Host "   ⚠ DB_PASSWORD may not be set or is using placeholder value" -ForegroundColor Yellow
        $warnings++
    }
} else {
    Write-Host "   ✗ .env file not found - copy .env.example to .env and configure it" -ForegroundColor Red
    $errors++
}
Write-Host ""

# Check 2: Verify PHP is available
Write-Host "2. Checking PHP installation..." -ForegroundColor Yellow
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpPath) {
    $phpVersion = php -v 2>&1 | Select-Object -First 1
    Write-Host "   ✓ PHP found: $phpVersion" -ForegroundColor Green
    
    # Check PHP version
    $versionMatch = $phpVersion -match "PHP (\d+)\.(\d+)"
    if ($versionMatch) {
        $major = [int]$matches[1]
        $minor = [int]$matches[2]
        if ($major -gt 7 -or ($major -eq 7 -and $minor -ge 4)) {
            Write-Host "   ✓ PHP version is 7.4+ (compatible)" -ForegroundColor Green
        } else {
            Write-Host "   ⚠ PHP version may be too old (7.4+ recommended)" -ForegroundColor Yellow
            $warnings++
        }
    }
} else {
    Write-Host "   ✗ PHP not found in PATH - ensure PHP is installed and in your PATH" -ForegroundColor Red
    $errors++
}
Write-Host ""

# Check 3: Verify required PHP extensions
Write-Host "3. Checking PHP extensions..." -ForegroundColor Yellow
if ($phpPath) {
    $extensions = @("mysqli", "session", "mbstring")
    foreach ($ext in $extensions) {
        $check = php -m 2>&1 | Select-String $ext
        if ($check) {
            Write-Host "   ✓ $ext extension is loaded" -ForegroundColor Green
        } else {
            Write-Host "   ✗ $ext extension not found - may cause issues" -ForegroundColor Red
            $errors++
        }
    }
}
Write-Host ""

# Check 4: Check if Apache is running (XAMPP)
Write-Host "4. Checking Apache/XAMPP status..." -ForegroundColor Yellow
$apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apacheProcess) {
    Write-Host "   ✓ Apache process found (may be running)" -ForegroundColor Green
} else {
    Write-Host "   ⚠ Apache process not found - ensure XAMPP Apache is started" -ForegroundColor Yellow
    $warnings++
}

# Check if port 80 is in use
$port80 = Get-NetTCPConnection -LocalPort 80 -ErrorAction SilentlyContinue
if ($port80) {
    Write-Host "   ✓ Port 80 appears to be in use (Apache may be running)" -ForegroundColor Green
} else {
    Write-Host "   ⚠ Port 80 is not in use - Apache may not be running on default port" -ForegroundColor Yellow
    $warnings++
}
Write-Host ""

# Check 5: Verify project structure
Write-Host "5. Checking project structure..." -ForegroundColor Yellow
$requiredFiles = @("index.php", "includes/connect.php", ".env.example")
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "   ✓ $file found" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $file not found - project may be incomplete" -ForegroundColor Red
        $errors++
    }
}
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Verification Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

if ($errors -eq 0 -and $warnings -eq 0) {
    Write-Host "✓ All checks passed! Your local development environment looks good." -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Ensure Apache is running in XAMPP"
    Write-Host "  2. Access the application in your browser (e.g., http://localhost/vbn/)"
    Write-Host "  3. Check the application loads without errors"
} elseif ($errors -eq 0) {
    Write-Host "⚠ Setup mostly complete, but some warnings were found." -ForegroundColor Yellow
    Write-Host "  Review the warnings above and address them if needed." -ForegroundColor Yellow
} else {
    Write-Host "✗ Found $errors error(s) that need to be fixed." -ForegroundColor Red
    Write-Host "  Review the errors above and fix them before proceeding." -ForegroundColor Red
}

if ($warnings -gt 0) {
    Write-Host "  (Also found $warnings warning(s))" -ForegroundColor Yellow
}

Write-Host ""

