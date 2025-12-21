<?php
/**
 * Valley by Night - World Overview Index
 * Displays all world overview documentation files in an organized view
 * 
 * METADATA CONTRACT:
 * - Reports are discovered dynamically by scanning _summaries/ directory
 * - Files must match pattern: *_XXXX.md where XXXX is 4-digit version code
 * - Metadata headers (YAML frontmatter) are optional but recommended
 * - New report files should appear automatically without code changes
 * - Version grouping is determined by filename pattern, not metadata
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin/storyteller
$user_role = $_SESSION['role'] ?? 'player';
$is_admin = ($user_role === 'admin' || $user_role === 'storyteller');

// Include header
$extra_css = ['css/dashboard.css'];
include '../../includes/header.php';

// Include version management
require_once __DIR__ . '/../../includes/version.php';

// Define file structure
$world_dir = __DIR__;
$overview_file = $world_dir . '/VbN_overview.md';
$summaries_dir = $world_dir . '/_summaries';
$checkpoints_dir = $world_dir . '/_checkpoints';
$canon_dir = $world_dir . '/_canon';

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

// Helper function to get file info
function getFileInfo($filepath) {
    if (!file_exists($filepath)) {
        return null;
    }
    
    return [
        'name' => basename($filepath),
        'path' => $filepath,
        'size' => filesize($filepath),
        'modified' => filemtime($filepath),
        'relative_path' => str_replace(__DIR__ . '/', '', $filepath)
    ];
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

// Get main overview file
$overview_info = getFileInfo($overview_file);

// Get available versions from summary files
$available_versions = getAvailableVersions($summaries_dir);
$most_recent_version = getMostRecentVersion($available_versions) ?? LOTN_VERSION;

// Get selected version from GET parameter (admin only) or use most recent
$selected_version = $most_recent_version;
if ($is_admin && isset($_GET['version'])) {
    $requested_version = $_GET['version'];
    // Convert filename format (0861) to version string (0.8.61)
    if (strlen($requested_version) === 4 && ctype_digit($requested_version)) {
        $requested_version_string = $requested_version[0] . '.' . $requested_version[1] . '.' . substr($requested_version, 2);
        if (in_array($requested_version_string, $available_versions)) {
            $selected_version = $requested_version_string;
        }
    } elseif (in_array($requested_version, $available_versions)) {
        $selected_version = $requested_version;
    }
}

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

// Get checkpoint files
$checkpoints = [];
if (is_dir($checkpoints_dir)) {
    $files = glob($checkpoints_dir . '/*.md');
    foreach ($files as $file) {
        $info = getFileInfo($file);
        if ($info) {
            $checkpoints[] = $info;
        }
    }
    // Sort by filename
    usort($checkpoints, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

// Get canon files
$canon_files = [];
if (is_dir($canon_dir)) {
    $files = glob($canon_dir . '/*.md');
    foreach ($files as $file) {
        $info = getFileInfo($file);
        if ($info) {
            $canon_files[] = $info;
        }
    }
}

// Calculate path prefix for links (2 levels up from reference/world/)
$path_prefix = '../../';
?>

<div class="page-content container py-4">
    <main id="main-content">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Valley by Night: Phoenix World Overview</h1>
                <p class="lead mb-4">
                    Comprehensive reference documentation for the Phoenix, Arizona chronicle setting (1994).
                </p>
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
            </div>
        </div>

        <!-- Main Overview Document -->
        <?php if ($overview_info): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h4 mb-0">Main Overview Document</h2>
                    </div>
                    <div class="card-body">
                        <h3 class="h5">
                            <a href="VbN_overview.md" target="_blank" class="text-decoration-none">
                                <?php echo htmlspecialchars($overview_info['name']); ?>
                            </a>
                        </h3>
                        <p class="mb-2">
                            Comprehensive reference guide integrating all summaries, characters, locations, clans, plot hooks, and historical context.
                        </p>
                        <div class="d-flex gap-3 flex-wrap small opacity-75">
                            <span><strong>Size:</strong> <?php echo formatFileSize($overview_info['size']); ?></span>
                            <span><strong>Last Modified:</strong> <?php echo date('Y-m-d H:i:s', $overview_info['modified']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary Documents -->
        <?php if (!empty($summaries)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0">Summary Documents</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 opacity-75">
                            Intermediate summary documents organized by analysis phase. These were synthesized into the main overview document.
                        </p>
                        <div class="row g-3">
                            <?php foreach ($summaries as $summary): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="_summaries/<?php echo htmlspecialchars($summary['name']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($summary['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small opacity-75 mb-2">
                                            <?php
                                            // Extract phase description from filename
                                            $name = $summary['name'];
                                            if (strpos($name, 'characters') !== false) {
                                                echo 'Character analysis and clan distribution';
                                            } elseif (strpos($name, 'locations') !== false) {
                                                echo 'Location analysis and power centers';
                                            } elseif (strpos($name, 'game_lore') !== false) {
                                                echo 'Core game lore and faction dynamics';
                                            } elseif (strpos($name, 'plot_hooks') !== false) {
                                                echo 'Plot hooks and storylines';
                                            } elseif (strpos($name, 'canon_clan') !== false) {
                                                echo 'Clan-specific Phoenix canon';
                                            } elseif (strpos($name, 'vbn_history') !== false) {
                                                echo 'Chronological historical narrative';
                                            } else {
                                                echo 'Summary document';
                                            }
                                            ?>
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap small opacity-75">
                                            <span><?php echo formatFileSize($summary['size']); ?></span>
                                            <span>•</span>
                                            <span><?php echo date('M d, Y', $summary['modified']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Checkpoint and Progress Files -->
        <?php if (!empty($checkpoints)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0">Progress Tracking</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 opacity-75">
                            Project progress logs, dashboards, and quality gate reports from the overview generation process.
                        </p>
                        <div class="row g-3">
                            <?php foreach ($checkpoints as $checkpoint): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-info">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="_checkpoints/<?php echo htmlspecialchars($checkpoint['name']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($checkpoint['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small opacity-75 mb-2">
                                            <?php
                                            $name = $checkpoint['name'];
                                            if (strpos($name, 'progress_dashboard') !== false) {
                                                echo 'Overall progress dashboard and status';
                                            } elseif (strpos($name, 'progress_log') !== false) {
                                                echo 'Detailed checkpoint log with timestamps';
                                            } elseif (strpos($name, 'quality_gate') !== false) {
                                                echo 'Quality gate verification report';
                                            } else {
                                                echo 'Progress tracking document';
                                            }
                                            ?>
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap small opacity-75">
                                            <span><?php echo formatFileSize($checkpoint['size']); ?></span>
                                            <span>•</span>
                                            <span><?php echo date('M d, Y', $checkpoint['modified']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Canon Registry -->
        <?php if (!empty($canon_files)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0">Canon Registry</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 opacity-75">
                            Official canon registry tracking frozen content and proposed additions.
                        </p>
                        <div class="row g-3">
                            <?php foreach ($canon_files as $canon): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-warning">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="_canon/<?php echo htmlspecialchars($canon['name']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($canon['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small opacity-75 mb-2">
                                            Canon content registry and tracking.
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap small opacity-75">
                                            <span><?php echo formatFileSize($canon['size']); ?></span>
                                            <span>•</span>
                                            <span><?php echo date('M d, Y', $canon['modified']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Documentation Statistics</h3>
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-primary mb-1"><?php echo count($summaries); ?></div>
                                    <div class="small opacity-75">Summary Documents</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-info mb-1"><?php echo count($checkpoints); ?></div>
                                    <div class="small opacity-75">Progress Files</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-warning mb-1"><?php echo count($canon_files); ?></div>
                                    <div class="small opacity-75">Canon Files</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-success mb-1"><?php echo ($overview_info ? 1 : 0) + count($summaries) + count($checkpoints) + count($canon_files); ?></div>
                                    <div class="small opacity-75">Total Files</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if ($is_admin): ?>
<script>
// Version selection handler
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
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>

