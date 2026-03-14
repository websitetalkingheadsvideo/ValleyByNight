<?php
/**
 * Character Agent - View Reports
 * Display available reports (daily, continuity, etc.)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../includes/version.php';
$extra_css = ['css/admin-agents.css'];
require_once __DIR__ . '/../../includes/header.php';

$reports_dir = __DIR__ . '/reports';
$daily_dir = $reports_dir . '/daily';
$continuity_dir = $reports_dir . '/continuity';
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">📊 Character Agent Reports</h1>
            <p class="lead fst-italic mb-0">View generated daily and continuity reports</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">
            ← Back to Agents
        </a>
    </div>

    <div class="row g-4">
        <!-- Daily Reports -->
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-header bg-danger text-light">
                    <h3 class="mb-0">📅 Daily Reports</h3>
                </div>
                <div class="card-body">
                    <?php
                    $daily_reports = [];
                    if (is_dir($daily_dir)) {
                        $files = scandir($daily_dir);
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && $file !== '.gitkeep' && $file !== 'index.php' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                                $daily_reports[] = [
                                    'filename' => $file,
                                    'path' => 'reports/daily/' . $file,
                                    'modified' => filemtime($daily_dir . '/' . $file),
                                    'size' => filesize($daily_dir . '/' . $file)
                                ];
                            }
                        }
                        usort($daily_reports, function($a, $b) {
                            return $b['modified'] - $a['modified'];
                        });
                    }
                    ?>
                    <?php if (empty($daily_reports)): ?>
                        <p class="text-light mb-0">No daily reports generated yet.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($daily_reports as $report): ?>
                                <li class="mb-2">
                                    <a href="api_get_report.php?type=daily&file=<?= urlencode($report['filename']); ?>" 
                                       target="_blank"
                                       class="text-light text-decoration-none">
                                        <?= htmlspecialchars($report['filename']); ?>
                                    </a>
                                    <small class="text-light d-block">
                                        <?= date('Y-m-d H:i:s', $report['modified']); ?> 
                                        (<?= number_format($report['size']) ?> bytes)
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Continuity Reports -->
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-danger h-100">
                <div class="card-header bg-danger text-light">
                    <h3 class="mb-0">🔗 Continuity Reports</h3>
                </div>
                <div class="card-body">
                    <?php
                    $continuity_reports = [];
                    if (is_dir($continuity_dir)) {
                        $files = scandir($continuity_dir);
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && $file !== '.gitkeep' && $file !== 'index.php' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                                $continuity_reports[] = [
                                    'filename' => $file,
                                    'path' => 'reports/continuity/' . $file,
                                    'modified' => filemtime($continuity_dir . '/' . $file),
                                    'size' => filesize($continuity_dir . '/' . $file)
                                ];
                            }
                        }
                        usort($continuity_reports, function($a, $b) {
                            return $b['modified'] - $a['modified'];
                        });
                    }
                    ?>
                    <?php if (empty($continuity_reports)): ?>
                        <p class="text-light mb-0">No continuity reports generated yet.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($continuity_reports as $report): ?>
                                <li class="mb-2">
                                    <a href="api_get_report.php?type=continuity&file=<?= urlencode($report['filename']); ?>" 
                                       target="_blank"
                                       class="text-light text-decoration-none">
                                        <?= htmlspecialchars($report['filename']); ?>
                                    </a>
                                    <small class="text-light d-block">
                                        <?= date('Y-m-d H:i:s', $report['modified']); ?> 
                                        (<?= number_format($report['size']) ?> bytes)
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

