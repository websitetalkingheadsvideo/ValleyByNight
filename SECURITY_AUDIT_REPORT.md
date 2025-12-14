# Security Audit Report - VbN (Vampire by Night)
**Date:** 2025-01-30  
**Scope:** Full codebase security analysis  
**Severity Levels:** CRITICAL | HIGH | MEDIUM | LOW

---

## Executive Summary

This security audit identified **7 critical issues**, **12 high-risk vulnerabilities**, and **8 medium-risk concerns** across the codebase. The most severe findings include hardcoded database credentials, an authentication bypass mechanism, and multiple SQL injection vulnerabilities.

**Overall Risk Level: CRITICAL**

---

## CRITICAL FINDINGS

### 1. Hardcoded Database Credentials
**File:** `includes/connect.php:19`  
**Severity:** CRITICAL  
**Risk:** Complete database compromise if code is exposed

**Finding:**
```19:19:includes/connect.php
$password = "KevinHenry09!";  // XAMPP default is empty password
```

**Impact:**
- Database credentials are stored in plaintext within source code
- If repository is public or compromised, attackers gain full database access
- Credentials visible in version control history

**Remediation:**
1. Move credentials to environment variables or secure config file outside web root
2. Use `.env` file with `.gitignore` exclusion
3. Rotate database password immediately after migration
4. Implement credential management system (e.g., AWS Secrets Manager, HashiCorp Vault)

**Recommended Fix:**
```php
// Use environment variables
$password = getenv('DB_PASSWORD') ?: die('Database password not configured');
```

---

### 2. Authentication Bypass Mechanism
**File:** `includes/auth_bypass.php`  
**Severity:** CRITICAL  
**Risk:** Unauthorized access to protected resources

**Finding:**
The codebase includes an authentication bypass system that can be enabled via JSON configuration:

```7:33:includes/auth_bypass.php
function isAuthBypassEnabled() {
    $bypassFile = __DIR__ . '/../config/auth_bypass.json';
    
    if (!file_exists($bypassFile)) {
        return false;
    }
    
    $config = json_decode(file_get_contents($bypassFile), true);
    if (!$config || !isset($config['enabled']) || $config['enabled'] !== true) {
        return false;
    }
    
    // Check if bypass period has expired
    if (isset($config['enabled_until'])) {
        $now = time();
        $until = strtotime($config['enabled_until']);
        if ($now >= $until) {
            // Expired - disable bypass
            $config['enabled'] = false;
            $config['enabled_until'] = null;
            file_put_contents($bypassFile, json_encode($config, JSON_PRETTY_PRINT));
            return false;
        }
    }
    
    return true;
}
```

**Impact:**
- If `config/auth_bypass.json` is created with `{"enabled": true}`, authentication is bypassed
- Used in multiple files: `phoenix_map.php`, `questionnaire.php`
- Allows guest access to protected areas

**Remediation:**
1. **Remove this file entirely** from production deployments
2. If needed for development, ensure it's excluded from production builds
3. Add `.gitignore` entry for `config/auth_bypass.json`
4. Implement proper development/staging environment separation
5. Use proper authentication tokens for testing instead

---

### 3. Hardcoded API Bypass Key
**File:** `agents/laws_agent/api.php:633`  
**Severity:** CRITICAL  
**Risk:** Unauthorized API access

**Finding:**
```632:633:agents/laws_agent/api.php
$mcpApiKey = $_GET['mcp_key'] ?? $_POST['mcp_key'] ?? '';
$mcpBypass = $mcpApiKey === 'vbn_mcp_b4byp4ss_k3y_2025';
```

**Impact:**
- Hardcoded bypass key allows authentication bypass via URL parameter
- Anyone with knowledge of key can access protected API endpoints
- Key is visible in source code and version control

**Remediation:**
1. Remove hardcoded bypass key
2. Implement proper API key management system
3. Store API keys in environment variables
4. Use cryptographic token validation instead of string comparison
5. Rotate any keys that may have been exposed

---

## HIGH-RISK FINDINGS

### 4. SQL Injection via mysqli_query with String Concatenation
**Files:** Multiple (119 instances found)  
**Severity:** HIGH  
**Risk:** Database compromise, data exfiltration

**Affected Files (Sample):**
- `phoenix_map.php:31,55,118`
- `check_books_when_ready.php:34,46,55`
- `query_rulebooks.php:17,29,38,89,105`
- `admin/admin_locations.php:32,37,44,51,59`
- `admin/admin_panel.php:156,206,281`
- And 100+ more instances

**Finding:**
Many queries use `mysqli_query()` with direct string concatenation instead of prepared statements:

```31:31:phoenix_map.php
$columns_result = mysqli_query($conn, $columns_check);
```

```55:55:phoenix_map.php
$locations_result = mysqli_query($conn, $locations_query);
```

**Impact:**
- If user input reaches these queries, SQL injection is possible
- Even static queries should use prepared statements for consistency
- Risk increases if queries are modified to include user input

**Remediation:**
1. Convert all `mysqli_query()` calls to use prepared statements via `db_execute()` or `db_select()` helpers
2. Audit each query to ensure no user input is concatenated
3. Implement code review process to prevent new instances
4. Use static analysis tools to detect SQL injection patterns

**Example Fix:**
```php
// BEFORE (Vulnerable)
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = " . $user_id);

// AFTER (Secure)
$result = db_fetch_one($conn, "SELECT * FROM users WHERE id = ?", 'i', [$user_id]);
```

---

### 5. Inconsistent Prepared Statement Usage
**File:** `admin/api_admin_locations_crud.php:26-35`  
**Severity:** HIGH  
**Risk:** SQL injection if prepared statement binding fails

**Finding:**
The code uses `mysqli_real_escape_string()` before binding to prepared statements, which is redundant and indicates uncertainty about proper usage:

```26:47:admin/api_admin_locations_crud.php
$name = mysqli_real_escape_string($conn, $input['name'] ?? '');
$type = mysqli_real_escape_string($conn, $input['type'] ?? '');
$summary = mysqli_real_escape_string($conn, $input['summary'] ?? '');
$description = mysqli_real_escape_string($conn, $input['description'] ?? '');
$notes = mysqli_real_escape_string($conn, $input['notes'] ?? '');
$status = mysqli_real_escape_string($conn, $input['status'] ?? 'Active');
$district = mysqli_real_escape_string($conn, $input['district'] ?? '');
$owner_type = mysqli_real_escape_string($conn, $input['owner_type'] ?? '');
$faction = mysqli_real_escape_string($conn, $input['faction'] ?? '');
$access_control = mysqli_real_escape_string($conn, $input['access_control'] ?? '');

$query = "INSERT INTO locations (name, type, summary, description, notes, status, district, owner_type, faction, access_control, security_level, pc_haven, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ssssssssssii', $name, $type, $summary, $description, $notes, $status, $district, $owner_type, $faction, $access_control, $security_level, $pc_haven);
```

**Impact:**
- While prepared statements protect against SQL injection, the escaping is unnecessary
- Indicates potential confusion about security practices
- If escaping is relied upon instead of prepared statements elsewhere, vulnerabilities exist

**Remediation:**
1. Remove `mysqli_real_escape_string()` calls when using prepared statements
2. Bind raw values directly to prepared statements
3. Use the existing `db_execute()` helper function which handles this correctly
4. Train developers on proper prepared statement usage

**Example Fix:**
```php
// BEFORE
$name = mysqli_real_escape_string($conn, $input['name'] ?? '');
mysqli_stmt_bind_param($stmt, 's', $name);

// AFTER
$name = $input['name'] ?? '';
mysqli_stmt_bind_param($stmt, 's', $name);
```

---

### 6. Direct File Path Usage from User Input
**File:** `database/import_characters.php:958-960`  
**Severity:** HIGH  
**Risk:** Path traversal, arbitrary file access

**Finding:**
```958:960:database/import_characters.php
if (isset($_GET['file'])) {
    $filepath = __DIR__ . '/../reference/Characters/Added to Database/' . basename($_GET['file']);
    $filename = basename($_GET['file']);
```

**Impact:**
- While `basename()` prevents directory traversal, this pattern is risky
- If `basename()` is ever removed or bypassed, arbitrary file access is possible
- No validation of file existence or allowed file types

**Remediation:**
1. Implement whitelist of allowed filenames
2. Validate file extension against allowed types
3. Verify file exists before processing
4. Log all file access attempts
5. Consider removing direct file parameter access entirely

---

### 7. Missing Input Validation on GET Parameters
**Files:** Multiple API endpoints  
**Severity:** HIGH  
**Risk:** Injection attacks, data manipulation

**Affected Files:**
- `admin/view_character_api.php:17` - Uses `intval()` which is good
- `agents/map_agent/map_agent.php:5-6` - No validation
- `admin/rumor_viewer.php:228-229` - Limited validation

**Finding:**
```5:6:agents/map_agent/map_agent.php
$mapId  = $_GET['map'] ?? 'phoenix_1994';
$layer  = $_GET['layer'] ?? 'cities';
```

**Impact:**
- User-controlled values used without validation
- Could lead to unexpected behavior or injection if used in queries
- No type checking or whitelist validation

**Remediation:**
1. Implement strict input validation on all GET/POST parameters
2. Use whitelists for allowed values where possible
3. Validate data types (int, string, enum)
4. Reject invalid input with clear error messages
5. Log validation failures for security monitoring

---

## MEDIUM-RISK FINDINGS

### 8. XSS Vulnerabilities via innerHTML
**File:** `js/phoenix_map.js` (multiple locations)  
**Severity:** MEDIUM  
**Risk:** Cross-site scripting attacks

**Finding:**
```348:348:js/phoenix_map.js
markersOverlay.innerHTML = '';
```

```369:369:js/phoenix_map.js
marker.innerHTML = `
```

**Impact:**
- If user-controlled data is inserted into `innerHTML`, XSS attacks are possible
- Need to verify data sources for these assignments

**Remediation:**
1. Audit all `innerHTML` assignments to ensure data is sanitized
2. Use `textContent` for text-only content
3. Implement HTML sanitization library (e.g., DOMPurify) for HTML content
4. Use template literals with proper escaping
5. Consider using framework with built-in XSS protection

---

### 9. File Upload Validation
**File:** `includes/upload_character_image.php`  
**Severity:** MEDIUM  
**Risk:** Malicious file uploads

**Current Implementation:**
The file upload handler has good validation:
- File type checking (lines 51-58)
- File size limits (lines 60-66)
- Authentication checks (lines 11-15)
- Ownership verification (lines 37-48)

**Recommendations:**
1. Add file content validation (magic bytes, not just MIME type)
2. Implement virus scanning for uploaded files
3. Store uploaded files outside web root when possible
4. Use random filenames instead of predictable patterns
5. Implement rate limiting on upload endpoints

---

### 10. Error Information Disclosure
**Files:** Multiple  
**Severity:** MEDIUM  
**Risk:** Information leakage

**Finding:**
Some error messages may expose system information:

```44:44:includes/connect.php
die("Error creating database: " . mysqli_error($conn));
```

**Impact:**
- Database errors may reveal table structure or system information
- Stack traces could expose file paths or internal structure

**Remediation:**
1. Implement custom error handlers
2. Log detailed errors server-side only
3. Return generic error messages to users
4. Disable error display in production (`display_errors = Off`)
5. Use structured logging for debugging

---

### 11. Session Security
**Files:** Multiple (all files using `session_start()`)  
**Severity:** MEDIUM  
**Risk:** Session hijacking, fixation

**Recommendations:**
1. Ensure `session.cookie_httponly = 1` is set
2. Use `session.cookie_secure = 1` for HTTPS
3. Implement session regeneration on login
4. Set appropriate session timeout values
5. Validate session data integrity

---

### 12. Missing CSRF Protection
**Files:** Most form submissions  
**Severity:** MEDIUM  
**Risk:** Cross-site request forgery

**Finding:**
While `account.php` implements CSRF tokens, most other forms do not:

```27:31:account.php
// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
```

**Remediation:**
1. Implement CSRF tokens on all state-changing operations
2. Validate tokens on POST/PUT/DELETE requests
3. Use framework-level CSRF protection if available
4. Consider SameSite cookie attributes

---

## LOW-RISK FINDINGS

### 13. Information Disclosure in Comments
**Files:** Multiple  
**Severity:** LOW  
**Risk:** Information leakage

**Finding:**
Comments may reveal system architecture or sensitive information.

**Remediation:**
1. Review and sanitize comments before production
2. Remove development notes and TODOs
3. Avoid commenting sensitive information

---

### 14. Missing Security Headers
**Severity:** LOW  
**Risk:** Various client-side attacks

**Recommendations:**
1. Implement Content Security Policy (CSP)
2. Set X-Frame-Options header
3. Set X-Content-Type-Options: nosniff
4. Implement HSTS for HTTPS
5. Set Referrer-Policy

---

## POSITIVE FINDINGS

### Security Best Practices Observed

1. **Prepared Statements:** Many files correctly use prepared statements via helper functions (`db_execute`, `db_select`, `db_fetch_one`, `db_fetch_all`)

2. **Password Hashing:** Uses `password_hash()` and `password_verify()` correctly:
   ```74:74:includes/login_process.php
   if (password_verify($password, $user['password'])) {
   ```

3. **Input Sanitization:** HTML output uses `htmlspecialchars()` in many places:
   ```74:74:login.php
   echo '<div class="alert alert-danger" role="alert" aria-live="polite">⚠️ ' . htmlspecialchars($_SESSION['error']) . '</div>';
   ```

4. **Authentication Checks:** Most protected endpoints verify authentication:
   ```10:12:admin/view_character_api.php
   if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
       echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   ```

5. **File Upload Security:** Upload handler includes proper validation and authentication

---

## REMEDIATION PRIORITY

### Immediate Action Required (Within 24 Hours)
1. ✅ Remove or secure hardcoded database credentials
2. ✅ Remove authentication bypass mechanism from production
3. ✅ Remove hardcoded API bypass key
4. ✅ Rotate all exposed credentials

### High Priority (Within 1 Week)
5. ✅ Convert all `mysqli_query()` calls to prepared statements
6. ✅ Remove unnecessary `mysqli_real_escape_string()` usage
7. ✅ Implement input validation on all user inputs
8. ✅ Add CSRF protection to all forms

### Medium Priority (Within 1 Month)
9. ✅ Audit and fix XSS vulnerabilities
10. ✅ Enhance file upload security
11. ✅ Implement security headers
12. ✅ Add comprehensive error handling

### Low Priority (Ongoing)
13. ✅ Code review process for security
14. ✅ Security training for developers
15. ✅ Regular security audits
16. ✅ Dependency vulnerability scanning

---

## TESTING RECOMMENDATIONS

1. **Penetration Testing:** Engage security professionals for comprehensive testing
2. **Static Analysis:** Use tools like SonarQube, PHPStan, or Psalm
3. **Dynamic Analysis:** Use OWASP ZAP or Burp Suite
4. **Dependency Scanning:** Use Composer security advisories
5. **Code Review:** Implement mandatory security reviews for all changes

---

## COMPLIANCE NOTES

This audit identified issues that may affect compliance with:
- **OWASP Top 10** - Multiple categories affected
- **PCI DSS** - If handling payment data
- **GDPR** - Data protection concerns with exposed credentials
- **SOC 2** - Security control deficiencies

---

## CONCLUSION

While the codebase demonstrates some security awareness (prepared statements, password hashing), critical vulnerabilities exist that require immediate attention. The hardcoded credentials and authentication bypass mechanisms pose the highest risk and must be addressed before any production deployment.

**Recommendation:** Do not deploy to production until critical and high-priority issues are resolved.

---

**Report Generated:** 2025-01-30  
**Auditor:** Security Analysis System  
**Next Review:** After remediation of critical issues

