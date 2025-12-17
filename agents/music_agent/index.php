<?php
/**
 * Music Registry Admin - Index Page
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/music_registry_io.php';
require_once __DIR__ . '/helpers.php';

$path_prefix = '../../';
$extra_css = [];
include __DIR__ . '/../../includes/header.php';

try {
    $registry = load_registry();
    $metadata = get_registry_metadata();
} catch (Exception $e) {
    $error = $e->getMessage();
    $registry = null;
    $metadata = null;
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">🎵 Music Registry Admin</h1>
            
            <?php if ($metadata): ?>
            <div class="alert alert-info mb-4">
                <strong>Registry File:</strong> <?php echo h($metadata['path']); ?><br>
                <strong>Last Modified:</strong> <?php echo h($metadata['modified_formatted']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($registry): ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo count($registry['assets'] ?? []); ?></h3>
                            <p class="text-muted mb-0">Assets</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo count($registry['cues'] ?? []); ?></h3>
                            <p class="text-muted mb-0">Cues</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo count($registry['bindings'] ?? []); ?></h3>
                            <p class="text-muted mb-0">Bindings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo count($registry['mix_profiles'] ?? []); ?></h3>
                            <p class="text-muted mb-0">Mix Profiles</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Navigation</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <a href="ui_add_asset.php" class="btn btn-primary w-100">
                                ➕ Add Asset
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="ui_add_cue.php" class="btn btn-primary w-100">
                                🎬 Add Cue
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="ui_add_binding.php" class="btn btn-primary w-100">
                                🔗 Add Binding
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="ui_browse.php" class="btn btn-secondary w-100">
                                🔍 Browse / Search
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="ui_settings.php" class="btn btn-secondary w-100">
                                ⚙️ Settings (Mix Profiles)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
