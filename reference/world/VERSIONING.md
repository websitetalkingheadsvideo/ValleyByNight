# World Reference Version Tracking System

## Overview

The World Reference system supports version tracking for summary documents, allowing administrators to view and switch between different versions of world documentation. This system is designed for maintaining historical versions of world reference materials while keeping the interface simple for regular users.

## Purpose

- **Version History**: Maintain multiple versions of summary documents as the world reference system evolves
- **Admin Control**: Allow administrators to view historical versions through a dropdown interface
- **User Experience**: Non-admin users automatically see the most recent version without interface complexity
- **Documentation Integrity**: Preserve snapshots of world documentation at specific project versions

## System Architecture

### File Naming Convention

Summary files in `reference/world/_summaries/` follow a versioned naming pattern:

```
[prefix]_[name]_[version].md
```

**Format Specification:**
- **Version Format**: `0.8.61` → `0861` (remove dots, 4-digit code)
- **Filename Pattern**: `01_characters_summary_0861.md`
- **Version Code Pattern**: `_XXXX` where XXXX is exactly 4 digits

**Examples:**
- Version `0.8.61` → Filename suffix `_0861`
- Version `0.9.0` → Filename suffix `_0900`
- Version `1.0.0` → Filename suffix `_1000`

### Directory Structure

```
reference/world/
├── _summaries/
│   ├── 01_characters_summary_0861.md    (Version 0.8.61)
│   ├── 02_locations_summary_0861.md     (Version 0.8.61)
│   ├── 03_game_lore_summary_0861.md     (Version 0.8.61)
│   ├── 04_plot_hooks_summary_0861.md    (Version 0.8.61)
│   ├── 05_canon_clan_summary_0861.md    (Version 0.8.61)
│   └── 06_vbn_history_0861.md           (Version 0.8.61)
├── index.php                             (Main interface)
└── VERSIONING.md                         (This document)
```

### Version Code Conversion

The system converts between human-readable version strings and filename version codes:

**Version String → Filename Code:**
- Input: `"0.8.61"`
- Process: Remove all dots → `"0861"`
- Output: `"0861"`

**Filename Code → Version String:**
- Input: `"0861"` from filename `*_0861.md`
- Process: Insert dots → `"0.8.61"` (format: `X.X.XX`)
- Output: `"0.8.61"`

**Note**: The conversion assumes the format `X.X.XX` (major.minor.patch where patch is two digits). For versions like `0.9.0`, the code would be `0900` and converts to `0.9.0`.

## Technical Implementation

### Core Functions

The version tracking system is implemented in `reference/world/index.php` with the following helper functions:

#### `extractVersionFromFilename($filename)`

Extracts version number from a filename pattern.

```php
/**
 * Extract version number from filename
 * Converts _0861 → 0.8.61
 * Format: 0861 = 0.8.61 (first digit.middle digit.last two digits)
 * 
 * @param string $filename
 * @return string|null Version string or null if not found
 */
function extractVersionFromFilename($filename) {
    // Match pattern: _XXXX where XXXX is 4 digits before .md
    if (preg_match('/_(\d{4})\.md$/', $filename, $matches)) {
        $version_code = $matches[1];
        // Convert 0861 to 0.8.61 (format: X.XX.XX where first X, second X, last two XX)
        if (strlen($version_code) === 4) {
            return $version_code[0] . '.' . $version_code[1] . '.' . substr($version_code, 2);
        }
    }
    return null;
}
```

#### `formatVersionForFilename($version)`

Converts version string to filename format.

```php
/**
 * Format version string for filename
 * Converts 0.8.61 → 0861
 * Format: removes dots from version string
 * 
 * @param string $version Version string like "0.8.61"
 * @return string Filename version code like "0861"
 */
function formatVersionForFilename($version) {
    // Remove dots to create filename version code
    return str_replace('.', '', $version);
}
```

#### `getAvailableVersions($summaries_dir)`

Scans directory for all available versions.

```php
/**
 * Get all available versions from summary files
 * 
 * @param string $summaries_dir Directory path
 * @return array Array of version strings
 */
function getAvailableVersions($summaries_dir) {
    $versions = [];
    if (is_dir($summaries_dir)) {
        // Match files with pattern *_XXXX.md where XXXX is 4 digits
        $files = glob($summaries_dir . '/*_[0-9][0-9][0-9][0-9].md');
        foreach ($files as $file) {
            $filename = basename($file);
            $version = extractVersionFromFilename($filename);
            if ($version && !in_array($version, $versions)) {
                $versions[] = $version;
            }
        }
        // Sort versions descending (most recent first)
        usort($versions, function($a, $b) {
            return version_compare($b, $a); // Reverse order
        });
    }
    return $versions;
}
```

#### `getMostRecentVersion($versions)`

Returns the highest version number from array.

```php
/**
 * Get most recent version from array
 * 
 * @param array $versions Array of version strings
 * @return string|null Most recent version or null if empty
 */
function getMostRecentVersion($versions) {
    if (empty($versions)) {
        return null;
    }
    // Versions should already be sorted, but ensure first is most recent
    usort($versions, function($a, $b) {
        return version_compare($b, $a);
    });
    return $versions[0];
}
```

### User Interface

#### Admin Version Dropdown

Administrators see a version dropdown in the page header that:
- Lists all available versions (extracted from filenames)
- Defaults to the most recent version
- Updates the URL with `?version=0861` parameter on selection
- Reloads the page to filter displayed files

**Implementation Location**: `reference/world/index.php` (lines ~214-225)

```php
<?php if ($is_admin && !empty($available_versions)): ?>
<div class="mb-4">
    <label for="version-select" class="form-label fw-bold">Version:</label>
    <select id="version-select" class="form-select d-inline-block" style="width: auto; min-width: 150px;">
        <?php foreach ($available_versions as $version): ?>
        <option value="<?php echo htmlspecialchars(formatVersionForFilename($version)); ?>" <?php echo $version === $selected_version ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($version); ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>
```

#### JavaScript Version Switching

Version selection is handled via JavaScript that updates the URL parameter:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const versionSelect = document.getElementById('version-select');
    if (versionSelect) {
        versionSelect.addEventListener('change', function() {
            const selectedVersion = this.value;
            // Update URL with version parameter
            const url = new URL(window.location.href);
            url.searchParams.set('version', selectedVersion);
            // Reload page with new version parameter
            window.location.href = url.toString();
        });
    }
});
```

### File Filtering Logic

Summary files are filtered based on the selected version:

```php
// Convert selected version to filename format for filtering
$selected_version_code = formatVersionForFilename($selected_version);

// Get summary files matching selected version
$summaries = [];
if (is_dir($summaries_dir)) {
    // Pattern matches: *_XXXX.md (any filename ending with _version.md)
    $pattern = $summaries_dir . '/*_' . $selected_version_code . '.md';
    $files = glob($pattern);
    foreach ($files as $file) {
        $info = getFileInfo($file);
        if ($info) {
            $summaries[] = $info;
        }
    }
    // Sort by filename (which includes numbers for ordering)
    usort($summaries, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}
```

### Authentication

The system checks for admin/storyteller role:

```php
// Check if user is admin/storyteller
$user_role = $_SESSION['role'] ?? 'player';
$is_admin = ($user_role === 'admin' || $user_role === 'storyteller');
```

- **Admin users**: See version dropdown and can switch versions
- **Non-admin users**: Automatically see the most recent version (no dropdown)

## Usage Examples

### Example 1: Adding a New Version (0.9.0)

When creating a new version of summary documents:

1. **Create new versioned files** with `_0900` suffix:
   ```
   01_characters_summary_0900.md
   02_locations_summary_0900.md
   03_game_lore_summary_0900.md
   04_plot_hooks_summary_0900.md
   05_canon_clan_summary_0900.md
   06_vbn_history_0900.md
   ```

2. **The system automatically**:
   - Detects the new version from filenames
   - Adds it to the dropdown
   - Sets it as the most recent version (if it's the highest)
   - Non-admin users see the new version automatically

### Example 2: Version Format Conversions

| Version String | Version Code | Filename Example |
|----------------|--------------|------------------|
| `0.8.61` | `0861` | `01_characters_summary_0861.md` |
| `0.9.0` | `0900` | `01_characters_summary_0900.md` |
| `0.9.10` | `0910` | `01_characters_summary_0910.md` |
| `1.0.0` | `1000` | `01_characters_summary_1000.md` |
| `1.2.34` | `1234` | `01_characters_summary_1234.md` |

### Example 3: URL Parameters

Admin users can directly access specific versions via URL:

```
reference/world/index.php?version=0861  (Version 0.8.61)
reference/world/index.php?version=0900  (Version 0.9.0)
reference/world/index.php               (Most recent version)
```

### Example 4: Version Detection Flow

1. System scans `_summaries/` directory for files matching `*_[0-9][0-9][0-9][0-9].md`
2. Extracts version codes from filenames (e.g., `0861`, `0900`)
3. Converts codes to version strings (e.g., `0.8.61`, `0.9.0`)
4. Sorts versions descending (most recent first)
5. Displays in dropdown with most recent selected by default

## Integration with Project Versioning

The version tracking system integrates with the main project version (`includes/version.php`):

- Default version falls back to `LOTN_VERSION` constant if no files found
- Version naming should align with project releases
- When project version increments, create new summary files with matching version suffix

**Current Project Version**: See `includes/version.php` for `LOTN_VERSION` constant.

## Maintenance and Updates

### When Adding New Versions

1. **Create new versioned files** following the naming pattern
2. **Ensure all 6 summary files exist** for the new version:
   - `01_characters_summary_[VERSION].md`
   - `02_locations_summary_[VERSION].md`
   - `03_game_lore_summary_[VERSION].md`
   - `04_plot_hooks_summary_[VERSION].md`
   - `05_canon_clan_summary_[VERSION].md`
   - `06_vbn_history_[VERSION].md`

3. **Version code calculation**: Remove dots from version string
   - `0.9.0` → `0900`
   - `1.0.0` → `1000`

### When Modifying the System

If changes are made to the version tracking implementation:

1. **Update this document** to reflect:
   - New functions or modified function signatures
   - Changed file patterns or naming conventions
   - Updated UI components or JavaScript behavior
   - Modified authentication or permission logic

2. **Update code comments** in `index.php` to match documentation

3. **Verify examples** still match current implementation

4. **Test version detection** with existing and new version files

### Common Issues and Solutions

**Issue**: Versions not appearing in dropdown
- **Check**: File naming follows `*_XXXX.md` pattern exactly
- **Check**: Version code is exactly 4 digits
- **Check**: Files are in `_summaries/` directory

**Issue**: Wrong version selected by default
- **Check**: Version sorting logic (should be descending)
- **Check**: Most recent version file exists and is readable

**Issue**: Version conversion errors
- **Check**: Version format matches `X.X.XX` pattern (major.minor.patch)
- **Check**: Conversion functions handle edge cases (e.g., `0.9.0` → `0900`)

## Technical Notes

### Version Comparison

The system uses PHP's `version_compare()` function for sorting:
- Sorts versions in descending order (newest first)
- Handles semantic versioning correctly
- Supports versions like `0.8.61`, `0.9.0`, `1.0.0`, etc.

### File Pattern Matching

The glob pattern `*_[0-9][0-9][0-9][0-9].md` ensures:
- Only files with exactly 4-digit version codes are matched
- Prevents matching files without versions
- Works with all summary file prefixes

### Session and Authentication

The system relies on PHP session variables:
- `$_SESSION['role']` must be set (via login system)
- Roles `'admin'` and `'storyteller'` have version switching access
- Non-admin users see filtered view without dropdown

## Future Enhancements

Potential improvements for the version tracking system:

1. **Version metadata**: Store version creation date, author, changelog
2. **Diff view**: Compare content between versions
3. **Auto-versioning**: Automatically create versions on significant changes
4. **Archive old versions**: Move old versions to archive directory
5. **Version validation**: Ensure all 6 files exist for each version
6. **Export functionality**: Download specific version as package

## Related Documentation

- Main project versioning: `VERSION.md`
- Version constants: `includes/version.php`
- World reference index: `reference/world/index.php`

---

**Last Updated**: 2025-01-30  
**Current Implementation Version**: 0.8.61  
**Documentation Version**: 1.0

