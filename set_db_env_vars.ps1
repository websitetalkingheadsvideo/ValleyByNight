# Script to set database environment variables for MCP server
# Run this script as Administrator to set environment variables permanently

[System.Environment]::SetEnvironmentVariable("DB_HOST", "vdb5.pit.pair.com", "User")
[System.Environment]::SetEnvironmentVariable("DB_USER", "working_64", "User")
[System.Environment]::SetEnvironmentVariable("DB_PASS", "KevinHenry09!", "User")
[System.Environment]::SetEnvironmentVariable("DB_NAME", "working_vbn", "User")

Write-Host "Environment variables set successfully!" -ForegroundColor Green
Write-Host "Please restart Cursor for the changes to take effect." -ForegroundColor Yellow
