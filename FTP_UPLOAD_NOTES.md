# FTP Upload Notes - Case Sensitivity Issue

## Problem
- **Local folder**: `agents/style_agent/` (lowercase)
- **Remote folder**: `Agent/style_agent/` (capitalized)
- FTP client tries to upload to `agents/` but server expects `Agent/`

## Solution Options

### Option 1: Manual FTP Upload (Recommended)
1. Connect via FTP client (FileZilla, WinSCP, etc.)
2. Navigate to server root
3. **Manually create** `Agent/style_agent/docs/` if it doesn't exist
4. Upload files directly to `Agent/style_agent/docs/` (bypass the local `agents/` structure)

### Option 2: Use FTP Client Path Mapping
Some FTP clients support path mapping:
- Map local `agents/` → remote `Agent/`
- Check your FTP client settings for "Path Mapping" or "Directory Mapping"

### Option 3: Temporary Workaround
1. Create a temporary local folder: `Agent/style_agent/docs/`
2. Copy files from `agents/style_agent/docs/` to `Agent/style_agent/docs/`
3. Upload from `Agent/` folder
4. Delete temporary folder after upload

## Database Path
The database has been updated to use `Agent/style_agent` to match the remote server structure.

## Verification
After uploading, verify files are in the correct location:
```
https://vbn.talkingheads.video/verify_mcp_structure.php
```

This will check if files are accessible at `Agent/style_agent/docs/` on the remote server.

