# Local Development Setup Guide

This guide explains how to run the Valley by Night (VbN) project locally for development.

## Prerequisites

- **PHP 7.4+** (PHP 8.x recommended)
- **Apache web server** (via XAMPP or similar)
- **Database access credentials** for the remote database at `vdb5.pit.pair.com`
- **Git** (for cloning the repository)

## Quick Start

1. **Clone the repository** (if not already done)
   ```bash
   git clone <repository-url>
   cd VbN
   ```

2. **Configure environment variables**
   - Copy `.env.example` to `.env`
   - Edit `.env` and set your database password:
     ```
     DB_PASSWORD=your_actual_password_here
     ```

3. **Configure your web server**
   - See "Web Server Configuration" section below

4. **Access the application**
   - Open your browser and navigate to your configured local URL
   - Default: `http://localhost/vbn/` or `http://localhost:8080/vbn/`

## Web Server Configuration

### Option 1: XAMPP Virtual Host (Recommended)

Configure Apache to serve the project from its current location (`G:\VbN`):

1. **Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`**
   ```apache
   <VirtualHost *:80>
       ServerName vbn.local
       DocumentRoot "G:/VbN"
       <Directory "G:/VbN">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. **Edit `C:\Windows\System32\drivers\etc\hosts`** (Run as Administrator)
   ```
   127.0.0.1    vbn.local
   ```

3. **Restart Apache** in XAMPP Control Panel

4. **Access the application**: `http://vbn.local/`

### Option 2: XAMPP htdocs Subdirectory

1. **Create a symlink or junction** (if you want to keep files in `G:\VbN`):
   ```powershell
   # Run PowerShell as Administrator
   New-Item -ItemType Junction -Path "C:\xampp\htdocs\vbn" -Target "G:\VbN"
   ```

2. **Access the application**: `http://localhost/vbn/`

### Option 3: XAMPP htdocs Root (Simple)

1. **Copy or move the project** to `C:\xampp\htdocs\vbn\`

2. **Access the application**: `http://localhost/vbn/`

## Environment Variables

The application uses environment variables for database configuration. These can be set in two ways:

### Method 1: .env File (Recommended for Development)

1. Copy `.env.example` to `.env`
2. Edit `.env` and set your database password:
   ```
   DB_PASSWORD=your_actual_password_here
   ```

The `.env` file is automatically loaded by `includes/connect.php` and takes priority over system environment variables.

### Method 2: System Environment Variables

Set these in your Windows environment:

```powershell
# PowerShell (Run as Administrator)
[System.Environment]::SetEnvironmentVariable('DB_HOST', 'vdb5.pit.pair.com', 'User')
[System.Environment]::SetEnvironmentVariable('DB_USER', 'working_64', 'User')
[System.Environment]::SetEnvironmentVariable('DB_PASSWORD', 'your_password', 'User')
[System.Environment]::SetEnvironmentVariable('DB_NAME', 'working_vbn', 'User')
```

**Note:** You may need to restart your terminal/IDE and Apache after setting environment variables.

## Database Connection

- **Host**: `vdb5.pit.pair.com` (remote Pair Networks database)
- **Database**: `working_vbn`
- **User**: `working_64`
- **Password**: Set via `.env` file or environment variable

The application connects to the remote production database. **Do not create a local database** - the project is designed to use the remote database only.

## Verification Steps

1. **Check PHP is running**
   - Create `test.php` in your document root:
     ```php
     <?php phpinfo(); ?>
     ```
   - Access it in your browser to verify PHP is working

2. **Verify database connection**
   - Load any page that uses the database (e.g., `index.php`)
   - Check for database connection errors in the browser or Apache error logs

3. **Check .env file is loaded**
   - If you see "Database configuration error" message, the `.env` file may not be found
   - Ensure `.env` exists in the project root (same directory as `index.php`)

## Troubleshooting

### "Database configuration error" Message

**Problem**: Database password not configured.

**Solution**:
1. Ensure `.env` file exists in project root
2. Verify `DB_PASSWORD` is set in `.env`
3. Check that `.env` file has correct format: `DB_PASSWORD=your_password` (no spaces around `=`)
4. Restart Apache after creating/modifying `.env`

### Port Already in Use

**Problem**: Port 80 is already in use.

**Solutions**:
- Use a different port (e.g., 8080) in your Apache configuration
- Or stop the service using port 80
- Access the application at `http://localhost:8080/vbn/`

### PHP Version Issues

**Problem**: Application errors related to PHP version.

**Solution**:
- Verify PHP version: `php -v` (should be 7.4+)
- Check XAMPP includes the correct PHP version
- Update XAMPP if needed

### Session Issues

**Problem**: Sessions not working, constant login redirects.

**Solution**:
- Check `session.save_path` in `php.ini` is writable
- Verify Apache has write permissions to the session directory
- Clear browser cookies and try again

### Path Issues (CSS/JS not loading)

**Problem**: Assets (CSS, JavaScript, images) not loading.

**Solution**:
- Verify the application is accessed from the correct URL path
- Check browser console for 404 errors
- Ensure relative paths are correct for your setup
- The application uses relative paths, so it should work from any subdirectory

## Development Notes

- **Localhost URLs**: This setup uses `localhost` for local development only. Production code should never reference localhost.
- **Database**: Uses remote database - no local MySQL setup required.
- **Hot Reload**: PHP files are executed on each request, so changes take effect immediately after saving. Refresh your browser to see changes.
- **Error Reporting**: Check Apache error logs and PHP error logs for debugging information.

## File Structure

```
G:\VbN\
├── .env                    # Your local environment config (not in git)
├── .env.example            # Template for .env (committed to git)
├── index.php               # Main entry point
├── includes/
│   ├── connect.php         # Database connection (loads .env)
│   └── ...
├── admin/                  # Admin panel
├── css/                    # Stylesheets
├── js/                     # JavaScript files
└── ...
```

## Next Steps

After setting up the local server:

1. Test the login page: `http://your-local-url/login.php`
2. Verify database queries work
3. Check admin panel access: `http://your-local-url/admin/`
4. Review any error logs if issues occur

## Security Reminders

- **Never commit `.env` files** - they contain sensitive credentials
- `.env` is already in `.gitignore`
- Keep your database password secure
- This is a development setup - production uses different configuration

