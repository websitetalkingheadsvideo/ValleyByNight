# How to Update Database Password

The database password is now stored in environment variables for security. Here are the ways to update it:

## Option 1: Windows System Environment Variables (Recommended for Development)

### Via PowerShell (Run as Administrator):
```powershell
# Set the database password
[System.Environment]::SetEnvironmentVariable('DB_PASSWORD', 'your_new_password_here', 'User')

# Verify it's set
[System.Environment]::GetEnvironmentVariable('DB_PASSWORD', 'User')
```

### Via Command Prompt (Run as Administrator):
```cmd
setx DB_PASSWORD "your_new_password_here" /M
```

**Note:** After setting, you may need to:
- Restart your terminal/IDE
- Restart your web server (Apache/Nginx)
- Or restart your computer for system-wide changes

## Option 2: Web Server Configuration

### Apache (.htaccess or httpd.conf):
Add to your `.htaccess` file in the project root:
```apache
SetEnv DB_HOST "vdb5.pit.pair.com"
SetEnv DB_USER "working_64"
SetEnv DB_PASSWORD "your_new_password_here"
SetEnv DB_NAME "working_vbn"
```

### Nginx (nginx.conf):
Add to your server block:
```nginx
fastcgi_param DB_HOST "vdb5.pit.pair.com";
fastcgi_param DB_USER "working_64";
fastcgi_param DB_PASSWORD "6>52S{23Jwex";
fastcgi_param DB_NAME "working_vbn";
```

## Option 3: Simple .env File Loader (Easiest for Development)

If you prefer using a `.env` file, we can add a simple loader. Would you like me to add this?

## Option 4: Pair Networks Hosting Panel

If you're using Pair Networks hosting:
1. Log into your Pair Networks control panel
2. Navigate to "Environment Variables" or "Application Settings"
3. Add/update `DB_PASSWORD` with your new password
4. Restart your application

## Verification

After setting the password, test the connection by:
1. Loading any page that uses the database
2. Check PHP error logs if connection fails
3. The error message will indicate if the password is missing

## Important Notes

- **Never commit `.env` files or passwords to version control**
- The `.env` file is already in `.gitignore`
- Restart your web server after changing environment variables
- On Windows, you may need to restart your IDE/terminal for changes to take effect

