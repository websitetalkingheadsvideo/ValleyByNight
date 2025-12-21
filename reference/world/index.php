<?php
/**
 * Valley by Night - World Overview Index
 * Displays all world overview documentation files in an organized view
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header
$extra_css = ['css/dashboard.css'];
include '../../includes/header.php';

// Define file structure
$world_dir = __DIR__;
$overview_file = $world_dir . '/VbN_overview.md';
$summaries_dir = $world_dir . '/_summaries';
$checkpoints_dir = $world_dir . '/_checkpoints';
$canon_dir = $world_dir . '/_canon';

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

// Get summary files
$summaries = [];
if (is_dir($summaries_dir)) {
    $files = glob($summaries_dir . '/*.md');
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

<?php include '../../includes/footer.php'; ?>

