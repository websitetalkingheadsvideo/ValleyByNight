<?php
/**
 * Generate World Summaries - Execution Page
 * 
 * Executes database/generate_world_summaries.php and displays output
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin/storyteller
$user_role = $_SESSION['role'] ?? 'player';
$is_admin = ($user_role === 'admin' || $user_role === 'storyteller');

if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Admin or Storyteller role required.');
}

// Include header
$extra_css = ['css/dashboard.css'];
include '../../includes/header.php';

$project_root = dirname(__DIR__, 2);
$script_path = $project_root . '/database/generate_world_summaries.php';

// Check if script exists
if (!file_exists($script_path)) {
    die("ERROR: Generation script not found at: {$script_path}");
}

// Execute the script and capture output
ob_start();
$output = '';
$return_var = 0;

// Change to project root directory for script execution
$original_dir = getcwd();
chdir($project_root);

// Execute the PHP script
exec("php database/generate_world_summaries.php 2>&1", $output_lines, $return_var);

// Restore original directory
chdir($original_dir);

$output = implode("\n", $output_lines);

// Get current version for display
require_once $project_root . '/includes/version.php';
$current_version = defined('LOTN_VERSION') ? LOTN_VERSION : 'Unknown';
?>

<div class="page-content container py-4">
    <main id="main-content">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Generate World Summaries</h1>
                <p class="lead mb-4">
                    Execute the world summary generation script to create new versioned summary documents.
                </p>
                
                <div class="mb-3">
                    <a href="index.php" class="btn btn-secondary">
                        ← Back to World Overview
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Generation Results</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Script:</strong> <code>database/generate_world_summaries.php</code><br>
                            <strong>Current Version:</strong> <code><?php echo htmlspecialchars($current_version); ?></code><br>
                            <strong>Exit Code:</strong> 
                            <?php if ($return_var === 0): ?>
                                <span class="badge bg-success">Success (0)</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Error (<?php echo $return_var; ?>)</span>
                            <?php endif; ?>
                        </div>

                        <div class="border rounded p-3 bg-dark">
                            <pre class="text-light mb-0" style="white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 0.9em;"><?php echo htmlspecialchars($output); ?></pre>
                        </div>

                        <?php if ($return_var === 0): ?>
                        <div class="alert alert-success mt-3">
                            <strong>✓ Generation Complete!</strong> New summary files have been created in <code>reference/world/_summaries/</code>
                        </div>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary">
                                View Updated Summaries
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            <strong>✗ Generation Failed</strong> Please check the output above for error details.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>

