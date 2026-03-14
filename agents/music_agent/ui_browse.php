<?php
/**
 * Music Registry Admin - Browse / Search
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
$extra_css = ['css/rituals-display.css'];
include __DIR__ . '/../../includes/header.php';

$error = null;
$success = null;
$deleted_id = null;

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $registry = load_registry();
        $delete_type = $_POST['delete_type'] ?? '';
        $delete_id = $_POST['delete_id'] ?? '';
        
        if (empty($delete_type) || empty($delete_id)) {
            throw new Exception("Invalid delete request");
        }
        
        if ($delete_type === 'asset') {
            // Check if asset is used by any cues
            $used_by = find_cues_using_asset($registry, $delete_id);
            if (!empty($used_by)) {
                throw new Exception("Cannot delete asset '{$delete_id}': used by cues: " . implode(', ', $used_by));
            }
            
            // Remove asset
            $registry['assets'] = array_filter($registry['assets'] ?? [], function($asset) use ($delete_id) {
                return ($asset['asset_id'] ?? '') !== $delete_id;
            });
            $registry['assets'] = array_values($registry['assets']);
            
        } elseif ($delete_type === 'cue') {
            // Check if cue is used by any bindings
            $used_by = find_bindings_using_cue($registry, $delete_id);
            if (!empty($used_by)) {
                throw new Exception("Cannot delete cue '{$delete_id}': used by bindings: " . implode(', ', $used_by));
            }
            
            // Remove cue
            $registry['cues'] = array_filter($registry['cues'] ?? [], function($cue) use ($delete_id) {
                return ($cue['cue_id'] ?? '') !== $delete_id;
            });
            $registry['cues'] = array_values($registry['cues']);
            
        } elseif ($delete_type === 'binding') {
            // Bindings can always be deleted
            $registry['bindings'] = array_filter($registry['bindings'] ?? [], function($binding) use ($delete_id) {
                return ($binding['binding_id'] ?? '') !== $delete_id;
            });
            $registry['bindings'] = array_values($registry['bindings']);
        } else {
            throw new Exception("Invalid delete type");
        }
        
        save_registry($registry);
        $success = "Deleted {$delete_type} '{$delete_id}'";
        $deleted_id = $delete_id;
        
    } catch (Exception $e) {
        error_log('ui_browse: save failed: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

try {
    $registry = load_registry();
    
    // Get search/filter params
    $search = trim($_GET['search'] ?? '');
    $filter_role = $_GET['filter_role'] ?? '';
    
    // Filter assets
    $assets = $registry['assets'] ?? [];
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $assets = array_filter($assets, function($asset) use ($search_lower) {
            return strpos(strtolower($asset['asset_id'] ?? ''), $search_lower) !== false ||
                   strpos(strtolower($asset['title'] ?? ''), $search_lower) !== false ||
                   in_array($search_lower, array_map('strtolower', $asset['tags'] ?? []));
        });
    }
    
    // Filter cues
    $cues = $registry['cues'] ?? [];
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $cues = array_filter($cues, function($cue) use ($search_lower) {
            return strpos(strtolower($cue['cue_id'] ?? ''), $search_lower) !== false ||
                   strpos(strtolower($cue['asset_ref'] ?? ''), $search_lower) !== false ||
                   strpos(strtolower($cue['role'] ?? ''), $search_lower) !== false;
        });
    }
    if (!empty($filter_role)) {
        $cues = array_filter($cues, function($cue) use ($filter_role) {
            return ($cue['role'] ?? '') === $filter_role;
        });
    }
    
    // Filter bindings
    $bindings = $registry['bindings'] ?? [];
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $bindings = array_filter($bindings, function($binding) use ($search_lower) {
            return strpos(strtolower($binding['binding_id'] ?? ''), $search_lower) !== false ||
                   strpos(strtolower($binding['play_cue_ref'] ?? ''), $search_lower) !== false ||
                   strpos(strtolower($binding['binding_type'] ?? ''), $search_lower) !== false;
        });
    }
    
} catch (Exception $e) {
    error_log('ui_browse: load_registry failed: ' . $e->getMessage());
    $error = $error ?: $e->getMessage();
    $registry = null;
    $assets = [];
    $cues = [];
    $bindings = [];
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">🔍 Browse / Search</h1>
            
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">← Back to Index</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo h($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($registry): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo h($search); ?>" 
                                   placeholder="Search by ID, title, tags...">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_role" class="form-label">Filter by Cue Role</label>
                            <select class="form-select" id="filter_role" name="filter_role">
                                <option value="">All Roles</option>
                                <?php foreach ($registry['enums']['cue_role'] as $role): ?>
                                <option value="<?php echo h($role); ?>" 
                                        <?php echo ($filter_role === $role) ? 'selected' : ''; ?>>
                                    <?php echo h($role); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Assets -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Assets (<?php echo count($assets); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($assets)): ?>
                    <p class="text-light">No assets found.</p>
                    <?php else: ?>
                    <div class="table-responsive rounded-3">
                        <table class="table table-dark table-sm rituals-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Source</th>
                                    <th>Files</th>
                                    <th>Tags</th>
                                    <th>Used By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assets as $asset): 
                                    $used_by = find_cues_using_asset($registry, $asset['asset_id']);
                                ?>
                                <tr>
                                    <td><code><?php echo h($asset['asset_id']); ?></code></td>
                                    <td><?php echo h($asset['title'] ?? ''); ?></td>
                                    <td><?php echo h($asset['source']['type'] ?? ''); ?></td>
                                    <td><?php echo count($asset['files'] ?? []); ?> file(s)</td>
                                    <td><?php echo h(format_tags($asset['tags'] ?? [])); ?></td>
                                    <td>
                                        <?php if (!empty($used_by)): ?>
                                        <span class="badge bg-info"><?php echo count($used_by); ?> cue(s)</span>
                                        <?php else: ?>
                                        <span class="text-light">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($used_by)): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete asset <?php echo h($asset['asset_id']); ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_type" value="asset">
                                            <input type="hidden" name="delete_id" value="<?php echo h($asset['asset_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-light">In use</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Cues -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Cues (<?php echo count($cues); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($cues)): ?>
                    <p class="text-light">No cues found.</p>
                    <?php else: ?>
                    <div class="table-responsive rounded-3">
                        <table class="table table-dark table-sm rituals-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Asset Ref</th>
                                    <th>Role</th>
                                    <th>Loop</th>
                                    <th>Override</th>
                                    <th>Used By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cues as $cue): 
                                    $used_by = find_bindings_using_cue($registry, $cue['cue_id']);
                                ?>
                                <tr>
                                    <td><code><?php echo h($cue['cue_id']); ?></code></td>
                                    <td><code><?php echo h($cue['asset_ref'] ?? ''); ?></code></td>
                                    <td><?php echo h($cue['role'] ?? ''); ?></td>
                                    <td><?php echo ($cue['loop'] ?? false) ? '✓' : '—'; ?></td>
                                    <td><?php echo h($cue['override']['mode'] ?? 'none'); ?></td>
                                    <td>
                                        <?php if (!empty($used_by)): ?>
                                        <span class="badge bg-info"><?php echo count($used_by); ?> binding(s)</span>
                                        <?php else: ?>
                                        <span class="text-light">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($used_by)): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete cue <?php echo h($cue['cue_id']); ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_type" value="cue">
                                            <input type="hidden" name="delete_id" value="<?php echo h($cue['cue_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-light">In use</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bindings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Bindings (<?php echo count($bindings); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($bindings)): ?>
                    <p class="text-light">No bindings found.</p>
                    <?php else: ?>
                    <div class="table-responsive rounded-3">
                        <table class="table table-dark table-sm rituals-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Cue Ref</th>
                                    <th>Priority</th>
                                    <th>Target/Event</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bindings as $binding): ?>
                                <tr>
                                    <td><code><?php echo h($binding['binding_id']); ?></code></td>
                                    <td><?php echo h($binding['binding_type'] ?? ''); ?></td>
                                    <td><code><?php echo h($binding['play_cue_ref'] ?? ''); ?></code></td>
                                    <td><?php echo h($binding['priority'] ?? 0); ?></td>
                                    <td>
                                        <?php if (isset($binding['target_ref'])): ?>
                                        <?php echo h($binding['target_ref']['type'] ?? ''); ?>: <?php echo h($binding['target_ref']['id'] ?? ''); ?>
                                        <?php elseif (isset($binding['event'])): ?>
                                        Event: <?php echo h($binding['event']['event_key'] ?? ''); ?>
                                        <?php else: ?>
                                        —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete binding <?php echo h($binding['binding_id']); ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_type" value="binding">
                                            <input type="hidden" name="delete_id" value="<?php echo h($binding['binding_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
