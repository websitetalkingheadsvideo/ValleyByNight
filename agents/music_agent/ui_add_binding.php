<?php
/**
 * Music Registry Admin - Add Binding
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
        
        $binding_id = trim($_POST['binding_id'] ?? '');
        $binding_type = $_POST['binding_type'] ?? '';
        $play_cue_ref = trim($_POST['play_cue_ref'] ?? '');
        $priority = !empty($_POST['priority']) ? (int)$_POST['priority'] : 0;
        $target_ref_type = $_POST['target_ref_type'] ?? '';
        $target_ref_id = trim($_POST['target_ref_id'] ?? '');
        $event_key = trim($_POST['event_key'] ?? '');
        $payload_filters_json = trim($_POST['payload_filters_json'] ?? '');
        $conditions_json = trim($_POST['conditions_json'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate
        if (empty($binding_id)) {
            throw new Exception("binding_id is required");
        }
        if (empty($binding_type) || !in_array($binding_type, $registry['enums']['binding_type'])) {
            throw new Exception("Invalid binding_type");
        }
        if (empty($play_cue_ref)) {
            throw new Exception("play_cue_ref is required");
        }
        
        // Check unique ID
        if (!ensure_unique_id($registry, 'binding', $binding_id)) {
            throw new Exception("Binding ID '{$binding_id}' already exists");
        }
        
        // Verify cue exists
        $cue_exists = false;
        foreach ($registry['cues'] ?? [] as $cue) {
            if (isset($cue['cue_id']) && $cue['cue_id'] === $play_cue_ref) {
                $cue_exists = true;
                break;
            }
        }
        if (!$cue_exists) {
            throw new Exception("Cue '{$play_cue_ref}' does not exist");
        }
        
        // Build binding object
        $binding = [
            'binding_id' => $binding_id,
            'binding_type' => $binding_type,
            'play_cue_ref' => $play_cue_ref,
            'priority' => $priority,
            'notes' => $notes
        ];
        
        // Handle binding type specific fields
        if ($binding_type === 'on_location_enter') {
            if (empty($target_ref_id)) {
                throw new Exception("target_ref.id is required for on_location_enter");
            }
            $binding['target_ref'] = [
                'type' => 'location',
                'id' => $target_ref_id
            ];
        } elseif ($binding_type === 'on_focus_acquired') {
            if (empty($target_ref_type) || empty($target_ref_id)) {
                throw new Exception("target_ref.type and target_ref.id are required for on_focus_acquired");
            }
            $binding['target_ref'] = [
                'type' => $target_ref_type,
                'id' => $target_ref_id
            ];
        } elseif ($binding_type === 'on_event') {
            if (empty($event_key)) {
                throw new Exception("event_key is required for on_event");
            }
            $binding['event'] = [
                'event_key' => $event_key
            ];
            if (!empty($payload_filters_json)) {
                $payload_filters = json_decode($payload_filters_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid payload_filters JSON: " . json_last_error_msg());
                }
                $binding['event']['payload_filters'] = $payload_filters;
            }
        }
        
        // Parse conditions
        if (!empty($conditions_json)) {
            $conditions = json_decode($conditions_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid conditions JSON: " . json_last_error_msg());
            }
            $binding['conditions'] = $conditions;
        } else {
            $binding['conditions'] = [];
        }
        
        // Add to registry
        if (!isset($registry['bindings'])) {
            $registry['bindings'] = [];
        }
        $registry['bindings'][] = $binding;
        
        // Save
        save_registry($registry);
        
        $success = true;
        $created_id = $binding_id;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $registry = load_registry();
    $cue_options = get_cue_options($registry);
} catch (Exception $e) {
    $error = $error ?: $e->getMessage();
    $registry = null;
    $cue_options = [];
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">🔗 Add Binding</h1>
            
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">← Back to Index</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Binding created with ID: <code><?php echo h($created_id); ?></code>
                <div class="mt-2">
                    <a href="ui_add_binding.php" class="btn btn-sm btn-primary">Add Another</a>
                    <a href="ui_browse.php" class="btn btn-sm btn-secondary">Browse Bindings</a>
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
                            <label for="binding_id" class="form-label">Binding ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="binding_id" name="binding_id" 
                                   value="<?php echo h($_POST['binding_id'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="binding_type" class="form-label">Binding Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="binding_type" name="binding_type" required>
                                <option value="">Select...</option>
                                <?php foreach ($registry['enums']['binding_type'] as $type): ?>
                                <option value="<?php echo h($type); ?>" 
                                        <?php echo (($_POST['binding_type'] ?? '') === $type) ? 'selected' : ''; ?>>
                                    <?php echo h($type); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="play_cue_ref" class="form-label">Play Cue Reference <span class="text-danger">*</span></label>
                            <select class="form-select" id="play_cue_ref" name="play_cue_ref" required>
                                <option value="">Select a cue...</option>
                                <?php foreach ($cue_options as $id => $label): ?>
                                <option value="<?php echo h($id); ?>" 
                                        <?php echo (($_POST['play_cue_ref'] ?? '') === $id) ? 'selected' : ''; ?>>
                                    <?php echo h($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" class="form-control" id="priority" name="priority" 
                                   value="<?php echo h($_POST['priority'] ?? '0'); ?>" min="0">
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">Higher numbers = higher priority</div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3" id="target_ref_card" style="display: none;">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Target Reference</h2>
                    </div>
                    <div class="card-body">
                        <div id="location_enter_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="target_ref_id_location" class="form-label">Location ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="target_ref_id_location" name="target_ref_id" 
                                       value="<?php echo h($_POST['target_ref_id'] ?? ''); ?>" 
                                       placeholder="e.g., hawthorn_estate">
                            </div>
                        </div>
                        
                        <div id="focus_acquired_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="target_ref_type" class="form-label">Target Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="target_ref_type" name="target_ref_type">
                                    <option value="">Select...</option>
                                    <option value="character" <?php echo (($_POST['target_ref_type'] ?? '') === 'character') ? 'selected' : ''; ?>>Character</option>
                                    <option value="creature" <?php echo (($_POST['target_ref_type'] ?? '') === 'creature') ? 'selected' : ''; ?>>Creature</option>
                                    <option value="object" <?php echo (($_POST['target_ref_type'] ?? '') === 'object') ? 'selected' : ''; ?>>Object</option>
                                    <option value="location" <?php echo (($_POST['target_ref_type'] ?? '') === 'location') ? 'selected' : ''; ?>>Location</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="target_ref_id_focus" class="form-label">Target ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="target_ref_id_focus" name="target_ref_id" 
                                       value="<?php echo h($_POST['target_ref_id'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3" id="event_card" style="display: none;">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Event Configuration</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="event_key" class="form-label">Event Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="event_key" name="event_key" 
                                   value="<?php echo h($_POST['event_key'] ?? ''); ?>" 
                                   placeholder="e.g., combat_start">
                        </div>
                        
                        <div class="mb-3">
                            <label for="payload_filters_json" class="form-label">Payload Filters (JSON, Optional)</label>
                            <textarea class="form-control font-monospace" id="payload_filters_json" name="payload_filters_json" rows="4"><?php echo h($_POST['payload_filters_json'] ?? '{}'); ?></textarea>
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">JSON object for filtering event payloads</div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Conditions (Optional)</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="conditions_json" class="form-label">Conditions (JSON)</label>
                            <textarea class="form-control font-monospace" id="conditions_json" name="conditions_json" rows="4"><?php echo h($_POST['conditions_json'] ?? '{}'); ?></textarea>
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">JSON object for conditional logic</div>
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
                    <button type="submit" class="btn btn-primary">Create Binding</button>
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
// Show/hide fields based on binding type
document.getElementById('binding_type')?.addEventListener('change', function() {
    const type = this.value;
    const targetCard = document.getElementById('target_ref_card');
    const eventCard = document.getElementById('event_card');
    const locationFields = document.getElementById('location_enter_fields');
    const focusFields = document.getElementById('focus_acquired_fields');
    
    targetCard.style.display = 'none';
    eventCard.style.display = 'none';
    locationFields.style.display = 'none';
    focusFields.style.display = 'none';
    
    if (type === 'on_location_enter') {
        targetCard.style.display = 'block';
        locationFields.style.display = 'block';
    } else if (type === 'on_focus_acquired') {
        targetCard.style.display = 'block';
        focusFields.style.display = 'block';
    } else if (type === 'on_event') {
        eventCard.style.display = 'block';
    }
});

// Update JSON preview
function updatePreview() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const formData = new FormData(form);
    const binding = {
        binding_id: formData.get('binding_id') || '',
        binding_type: formData.get('binding_type') || '',
        play_cue_ref: formData.get('play_cue_ref') || '',
        priority: parseInt(formData.get('priority') || '0'),
        notes: formData.get('notes') || ''
    };
    
    const bindingType = formData.get('binding_type');
    if (bindingType === 'on_location_enter') {
        const targetId = formData.get('target_ref_id');
        if (targetId) {
            binding.target_ref = { type: 'location', id: targetId };
        }
    } else if (bindingType === 'on_focus_acquired') {
        const targetType = formData.get('target_ref_type');
        const targetId = formData.get('target_ref_id'); // Both location and focus use same name, but only one is visible
        if (targetType && targetId) {
            binding.target_ref = { type: targetType, id: targetId };
        }
    } else if (bindingType === 'on_event') {
        const eventKey = formData.get('event_key');
        if (eventKey) {
            binding.event = { event_key: eventKey };
            const payloadFilters = formData.get('payload_filters_json');
            if (payloadFilters) {
                try {
                    binding.event.payload_filters = JSON.parse(payloadFilters);
                } catch (e) {}
            }
        }
    }
    
    const conditions = formData.get('conditions_json');
    if (conditions) {
        try {
            binding.conditions = JSON.parse(conditions);
        } catch (e) {
            binding.conditions = {};
        }
    } else {
        binding.conditions = {};
    }
    
    const codeEl = document.getElementById('json_preview').querySelector('code');
    codeEl.textContent = JSON.stringify(binding, null, 2);
    codeEl.style.color = '#d4c4b0';
}

document.querySelector('form')?.addEventListener('input', updatePreview);
document.querySelector('form')?.addEventListener('change', updatePreview);
updatePreview();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
