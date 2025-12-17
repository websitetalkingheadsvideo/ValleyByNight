# Add vbn.local to hosts file
# Run this script as Administrator

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$hostsEntry = "127.0.0.1    vbn.local"

# Check if already exists
$hostsContent = Get-Content $hostsPath -ErrorAction SilentlyContinue
if ($hostsContent -contains $hostsEntry) {
    Write-Host "Entry already exists in hosts file" -ForegroundColor Green
} else {
    try {
        Add-Content -Path $hostsPath -Value $hostsEntry
        Write-Host "✓ Added $hostsEntry to hosts file" -ForegroundColor Green
    } catch {
        Write-Host "✗ Failed: $_" -ForegroundColor Red
        exit 1
    }
}

# Flush DNS
ipconfig /flushdns | Out-Null
Write-Host "✓ DNS cache flushed" -ForegroundColor Green
Write-Host "`nDone! Now restart Apache and try http://vbn.local/" -ForegroundColor Cyan

