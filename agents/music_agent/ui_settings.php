<?php
/**
 * Music Registry Admin - Settings (Mix Profiles)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $registry = load_registry();
        
        $mix_profiles_json = trim($_POST['mix_profiles_json'] ?? '');
        
        if (empty($mix_profiles_json)) {
            throw new Exception("mix_profiles JSON is required");
        }
        
        $mix_profiles = json_decode($mix_profiles_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }
        
        if (!is_array($mix_profiles)) {
            throw new Exception("mix_profiles must be an array");
        }
        
        // Validate each profile
        foreach ($mix_profiles as $idx => $profile) {
            if (!isset($profile['mix_profile_id'])) {
                throw new Exception("Profile at index {$idx} missing mix_profile_id");
            }
            
            // Validate gains (0.0-1.0)
            if (isset($profile['gains']) && is_array($profile['gains'])) {
                foreach ($profile['gains'] as $key => $value) {
                    if (!is_numeric($value) || $value < 0.0 || $value > 1.0) {
                        throw new Exception("Profile {$profile['mix_profile_id']}: gains.{$key} must be between 0.0 and 1.0");
                    }
                }
            }
            
            // Validate ducking (0.0-1.0 for ratios, >= 0 for ms)
            if (isset($profile['ducking']) && is_array($profile['ducking'])) {
                if (isset($profile['ducking']['duck_beds_on_stinger'])) {
                    $val = $profile['ducking']['duck_beds_on_stinger'];
                    if (!is_numeric($val) || $val < 0.0 || $val > 1.0) {
                        throw new Exception("Profile {$profile['mix_profile_id']}: ducking.duck_beds_on_stinger must be between 0.0 and 1.0");
                    }
                }
                if (isset($profile['ducking']['duck_attack_ms']) && 
                    (!is_numeric($profile['ducking']['duck_attack_ms']) || $profile['ducking']['duck_attack_ms'] < 0)) {
                    throw new Exception("Profile {$profile['mix_profile_id']}: ducking.duck_attack_ms must be >= 0");
                }
                if (isset($profile['ducking']['duck_release_ms']) && 
                    (!is_numeric($profile['ducking']['duck_release_ms']) || $profile['ducking']['duck_release_ms'] < 0)) {
                    throw new Exception("Profile {$profile['mix_profile_id']}: ducking.duck_release_ms must be >= 0");
                }
            }
            
            // Validate fades (>= 0)
            if (isset($profile['default_fades_ms']) && is_array($profile['default_fades_ms'])) {
                foreach ($profile['default_fades_ms'] as $key => $value) {
                    if (!is_numeric($value) || $value < 0) {
                        throw new Exception("Profile {$profile['mix_profile_id']}: default_fades_ms.{$key} must be >= 0");
                    }
                }
            }
            
            // Validate underbed behavior
            if (isset($profile['underbed_behavior']) && is_array($profile['underbed_behavior'])) {
                foreach (['enter_fade_ms', 'exit_fade_ms', 'raise_fade_ms'] as $key) {
                    if (isset($profile['underbed_behavior'][$key]) && 
                        (!is_numeric($profile['underbed_behavior'][$key]) || $profile['underbed_behavior'][$key] < 0)) {
                        throw new Exception("Profile {$profile['mix_profile_id']}: underbed_behavior.{$key} must be >= 0");
                    }
                }
                if (isset($profile['underbed_behavior']['raise_when_no_focus_gain'])) {
                    $val = $profile['underbed_behavior']['raise_when_no_focus_gain'];
                    if (!is_numeric($val) || $val < 0.0 || $val > 1.0) {
                        throw new Exception("Profile {$profile['mix_profile_id']}: underbed_behavior.raise_when_no_focus_gain must be between 0.0 and 1.0");
                    }
                }
            }
            
            // Validate exclusive defaults
            if (isset($profile['exclusive_defaults']) && is_array($profile['exclusive_defaults'])) {
                if (isset($profile['exclusive_defaults']['stop_fade_ms']) && 
                    (!is_numeric($profile['exclusive_defaults']['stop_fade_ms']) || $profile['exclusive_defaults']['stop_fade_ms'] < 0)) {
                    throw new Exception("Profile {$profile['mix_profile_id']}: exclusive_defaults.stop_fade_ms must be >= 0");
                }
            }
        }
        
        // Update registry
        $registry['mix_profiles'] = $mix_profiles;
        
        // Save
        save_registry($registry);
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $registry = load_registry();
    $mix_profiles = $registry['mix_profiles'] ?? [];
} catch (Exception $e) {
    $error = $error ?: $e->getMessage();
    $registry = null;
    $mix_profiles = [];
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">⚙️ Settings - Mix Profiles</h1>
            
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">← Back to Index</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Mix profiles updated successfully.
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($registry): ?>
            <div class="alert alert-info">
                <strong>Validation Rules:</strong>
                <ul class="mb-0">
                    <li>Gains and ducking ratios: 0.0 to 1.0</li>
                    <li>Fade times (ms): >= 0</li>
                    <li>All numeric values must be valid numbers</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Mix Profiles (JSON)</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="mix_profiles_json" class="form-label">Mix Profiles <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace" id="mix_profiles_json" name="mix_profiles_json" rows="30" required><?php echo h(json_preview($mix_profiles)); ?></textarea>
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">
                                Edit the mix_profiles array. Changes are validated before saving.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Save Mix Profiles</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" class="btn btn-outline-info" onclick="formatJSON()">Format JSON</button>
                </div>
            </form>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Current Mix Profiles</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($mix_profiles)): ?>
                    <p class="text-muted">No mix profiles configured.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Profile ID</th>
                                    <th>Profile Name</th>
                                    <th>Gains</th>
                                    <th>Ducking</th>
                                    <th>Default Fades</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mix_profiles as $profile): ?>
                                <tr>
                                    <td><code><?php echo h($profile['mix_profile_id'] ?? ''); ?></code></td>
                                    <td><?php echo h($profile['profile_name'] ?? ''); ?></td>
                                    <td>
                                        <?php if (isset($profile['gains'])): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($profile['gains'] as $key => $value): ?>
                                            <li><small><?php echo h($key); ?>: <?php echo h($value); ?></small></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($profile['ducking'])): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($profile['ducking'] as $key => $value): ?>
                                            <li><small><?php echo h($key); ?>: <?php echo h($value); ?></small></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($profile['default_fades_ms'])): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($profile['default_fades_ms'] as $key => $value): ?>
                                            <li><small><?php echo h($key); ?>: <?php echo h($value); ?>ms</small></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function formatJSON() {
    const textarea = document.getElementById('mix_profiles_json');
    if (!textarea) return;
    
    try {
        const json = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(json, null, 2);
    } catch (e) {
        alert('Invalid JSON: ' + e.message);
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
