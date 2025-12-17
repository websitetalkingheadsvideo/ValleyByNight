<?php
/**
 * Music Registry Admin - Add Cue
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

$success = false;
$error = null;
$created_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $registry = load_registry();
        
        $cue_id = trim($_POST['cue_id'] ?? '');
        $asset_ref = trim($_POST['asset_ref'] ?? '');
        $role = $_POST['role'] ?? '';
        $loop = isset($_POST['loop']) && $_POST['loop'] === '1';
        $fade_in_ms = !empty($_POST['fade_in_ms']) ? (int)$_POST['fade_in_ms'] : null;
        $fade_out_ms = !empty($_POST['fade_out_ms']) ? (int)$_POST['fade_out_ms'] : null;
        $override_mode = $_POST['override_mode'] ?? 'none';
        $exclusive_stop_mode = $_POST['exclusive_stop_mode'] ?? null;
        $exclusive_stop_fade_ms = !empty($_POST['exclusive_stop_fade_ms']) ? (int)$_POST['exclusive_stop_fade_ms'] : null;
        $exclusive_resume_mode = $_POST['exclusive_resume_mode'] ?? null;
        $handoff_after_play = trim($_POST['handoff_after_play'] ?? '');
        $handoff_combat_bed_ref = trim($_POST['handoff_combat_bed_ref'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate
        if (empty($cue_id)) {
            throw new Exception("cue_id is required");
        }
        if (empty($asset_ref)) {
            throw new Exception("asset_ref is required");
        }
        if (empty($role) || !in_array($role, $registry['enums']['cue_role'])) {
            throw new Exception("Invalid role");
        }
        
        // Check unique ID
        if (!ensure_unique_id($registry, 'cue', $cue_id)) {
            throw new Exception("Cue ID '{$cue_id}' already exists");
        }
        
        // Verify asset exists
        $asset_exists = false;
        foreach ($registry['assets'] ?? [] as $asset) {
            if (isset($asset['asset_id']) && $asset['asset_id'] === $asset_ref) {
                $asset_exists = true;
                break;
            }
        }
        if (!$asset_exists) {
            throw new Exception("Asset '{$asset_ref}' does not exist");
        }
        
        // Build cue object
        $cue = [
            'cue_id' => $cue_id,
            'asset_ref' => $asset_ref,
            'role' => $role,
            'loop' => $loop,
            'mix_overrides' => [
                'gain' => null,
                'duck_beds' => null
            ],
            'override' => [
                'mode' => $override_mode
            ],
            'notes' => $notes
        ];
        
        if ($fade_in_ms !== null) {
            $cue['fade_in_ms'] = $fade_in_ms;
        }
        if ($fade_out_ms !== null) {
            $cue['fade_out_ms'] = $fade_out_ms;
        }
        
        if ($override_mode === 'exclusive') {
            if ($exclusive_stop_mode && in_array($exclusive_stop_mode, $registry['enums']['exclusive_stop_mode'] ?? [])) {
                $cue['override']['exclusive_stop_mode'] = $exclusive_stop_mode;
            }
            if ($exclusive_stop_fade_ms !== null) {
                $cue['override']['exclusive_stop_fade_ms'] = $exclusive_stop_fade_ms;
            }
            if ($exclusive_resume_mode && in_array($exclusive_resume_mode, $registry['enums']['exclusive_resume_mode'] ?? [])) {
                $cue['override']['exclusive_resume_mode'] = $exclusive_resume_mode;
            }
        }
        
        if (!empty($handoff_after_play) || !empty($handoff_combat_bed_ref)) {
            $cue['handoff'] = [];
            if (!empty($handoff_after_play)) {
                $cue['handoff']['after_play'] = $handoff_after_play;
            }
            if (!empty($handoff_combat_bed_ref)) {
                $cue['handoff']['combat_bed_ref'] = $handoff_combat_bed_ref;
            }
        }
        
        // Add to registry
        if (!isset($registry['cues'])) {
            $registry['cues'] = [];
        }
        $registry['cues'][] = $cue;
        
        // Save
        save_registry($registry);
        
        $success = true;
        $created_id = $cue_id;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $registry = load_registry();
    $asset_options = get_asset_options($registry);
} catch (Exception $e) {
    $error = $error ?: $e->getMessage();
    $registry = null;
    $asset_options = [];
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">🎬 Add Cue</h1>
            
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">← Back to Index</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Cue created with ID: <code><?php echo h($created_id); ?></code>
                <div class="mt-2">
                    <a href="ui_add_cue.php" class="btn btn-sm btn-primary">Add Another</a>
                    <a href="ui_browse.php" class="btn btn-sm btn-secondary">Browse Cues</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($registry): ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Basic Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="cue_id" class="form-label">Cue ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cue_id" name="cue_id" 
                                   value="<?php echo h($_POST['cue_id'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="asset_ref" class="form-label">Asset Reference <span class="text-danger">*</span></label>
                            <select class="form-select" id="asset_ref" name="asset_ref" required>
                                <option value="">Select an asset...</option>
                                <?php foreach ($asset_options as $id => $title): ?>
                                <option value="<?php echo h($id); ?>" 
                                        <?php echo (($_POST['asset_ref'] ?? '') === $id) ? 'selected' : ''; ?>>
                                    <?php echo h($id); ?> - <?php echo h($title); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select...</option>
                                <?php foreach ($registry['enums']['cue_role'] as $role): ?>
                                <option value="<?php echo h($role); ?>" 
                                        <?php echo (($_POST['role'] ?? '') === $role) ? 'selected' : ''; ?>>
                                    <?php echo h($role); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="loop" name="loop" value="1" 
                                       <?php echo (isset($_POST['loop']) && $_POST['loop'] === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="loop">
                                    Loop
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Fade Settings</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fade_in_ms" class="form-label">Fade In (ms)</label>
                                    <input type="number" class="form-control" id="fade_in_ms" name="fade_in_ms" 
                                           value="<?php echo h($_POST['fade_in_ms'] ?? ''); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fade_out_ms" class="form-label">Fade Out (ms)</label>
                                    <input type="number" class="form-control" id="fade_out_ms" name="fade_out_ms" 
                                           value="<?php echo h($_POST['fade_out_ms'] ?? ''); ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Override Settings</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="override_mode" class="form-label">Override Mode</label>
                            <select class="form-select" id="override_mode" name="override_mode">
                                <?php foreach ($registry['enums']['override_mode'] as $mode): ?>
                                <option value="<?php echo h($mode); ?>" 
                                        <?php echo (($_POST['override_mode'] ?? 'none') === $mode) ? 'selected' : ''; ?>>
                                    <?php echo h($mode); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="exclusive_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="exclusive_stop_mode" class="form-label">Exclusive Stop Mode</label>
                                <select class="form-select" id="exclusive_stop_mode" name="exclusive_stop_mode">
                                    <option value="">None</option>
                                    <?php foreach ($registry['enums']['exclusive_stop_mode'] ?? [] as $mode): ?>
                                    <option value="<?php echo h($mode); ?>" 
                                            <?php echo (($_POST['exclusive_stop_mode'] ?? '') === $mode) ? 'selected' : ''; ?>>
                                        <?php echo h($mode); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="exclusive_stop_fade_ms" class="form-label">Exclusive Stop Fade (ms)</label>
                                <input type="number" class="form-control" id="exclusive_stop_fade_ms" name="exclusive_stop_fade_ms" 
                                       value="<?php echo h($_POST['exclusive_stop_fade_ms'] ?? ''); ?>" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="exclusive_resume_mode" class="form-label">Exclusive Resume Mode</label>
                                <select class="form-select" id="exclusive_resume_mode" name="exclusive_resume_mode">
                                    <option value="">None</option>
                                    <?php foreach ($registry['enums']['exclusive_resume_mode'] ?? [] as $mode): ?>
                                    <option value="<?php echo h($mode); ?>" 
                                            <?php echo (($_POST['exclusive_resume_mode'] ?? '') === $mode) ? 'selected' : ''; ?>>
                                        <?php echo h($mode); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Handoff (Optional)</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="handoff_after_play" class="form-label">After Play</label>
                            <input type="text" class="form-control" id="handoff_after_play" name="handoff_after_play" 
                                   value="<?php echo h($_POST['handoff_after_play'] ?? ''); ?>" 
                                   placeholder="e.g., enter_combat_state">
                        </div>
                        
                        <div class="mb-3">
                            <label for="handoff_combat_bed_ref" class="form-label">Combat Bed Reference</label>
                            <input type="text" class="form-control" id="handoff_combat_bed_ref" name="handoff_combat_bed_ref" 
                                   value="<?php echo h($_POST['handoff_combat_bed_ref'] ?? ''); ?>" 
                                   placeholder="e.g., cue_combat_bed_default">
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Notes</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo h($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create Cue</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">JSON Preview</h2>
                </div>
                <div class="card-body">
                    <pre id="json_preview" style="background: #1a0f0f; border: 1px solid #8B0000; border-radius: 4px; padding: 1rem; color: #d4c4b0; font-family: 'Source Code Pro', monospace; font-size: 0.9em; line-height: 1.5; overflow-x: auto; max-height: 400px; overflow-y: auto;"><code style="color: #d4c4b0;"></code></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Show/hide exclusive fields
document.getElementById('override_mode')?.addEventListener('change', function() {
    document.getElementById('exclusive_fields').style.display = 
        (this.value === 'exclusive') ? 'block' : 'none';
});

// Update JSON preview
function updatePreview() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const formData = new FormData(form);
    const cue = {
        cue_id: formData.get('cue_id') || '',
        asset_ref: formData.get('asset_ref') || '',
        role: formData.get('role') || '',
        loop: formData.get('loop') === '1',
        mix_overrides: { gain: null, duck_beds: null },
        override: { mode: formData.get('override_mode') || 'none' },
        notes: formData.get('notes') || ''
    };
    
    const fadeIn = formData.get('fade_in_ms');
    if (fadeIn) cue.fade_in_ms = parseInt(fadeIn);
    
    const fadeOut = formData.get('fade_out_ms');
    if (fadeOut) cue.fade_out_ms = parseInt(fadeOut);
    
    const overrideMode = formData.get('override_mode');
    if (overrideMode === 'exclusive') {
        const stopMode = formData.get('exclusive_stop_mode');
        if (stopMode) cue.override.exclusive_stop_mode = stopMode;
        
        const stopFade = formData.get('exclusive_stop_fade_ms');
        if (stopFade) cue.override.exclusive_stop_fade_ms = parseInt(stopFade);
        
        const resumeMode = formData.get('exclusive_resume_mode');
        if (resumeMode) cue.override.exclusive_resume_mode = resumeMode;
    }
    
    const afterPlay = formData.get('handoff_after_play');
    const combatBed = formData.get('handoff_combat_bed_ref');
    if (afterPlay || combatBed) {
        cue.handoff = {};
        if (afterPlay) cue.handoff.after_play = afterPlay;
        if (combatBed) cue.handoff.combat_bed_ref = combatBed;
    }
    
    const codeEl = document.getElementById('json_preview').querySelector('code');
    codeEl.textContent = JSON.stringify(cue, null, 2);
    codeEl.style.color = '#d4c4b0';
}

document.querySelector('form')?.addEventListener('input', updatePreview);
document.querySelector('form')?.addEventListener('change', updatePreview);
updatePreview();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
