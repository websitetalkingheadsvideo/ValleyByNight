<?php
/**
 * Music Registry Admin - Add Asset
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
        
        // Get form data
        $asset_id = trim($_POST['asset_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $source_type = $_POST['source_type'] ?? '';
        $envato_item_id = trim($_POST['envato_item_id'] ?? '');
        $envato_license_ref = trim($_POST['envato_license_ref'] ?? '');
        $generator = trim($_POST['generator'] ?? '');
        $prompt = trim($_POST['prompt'] ?? '');
        $generation_version = trim($_POST['generation_version'] ?? '');
        $seed_or_job_id = trim($_POST['seed_or_job_id'] ?? '');
        $files_json = trim($_POST['files_json'] ?? '');
        $tags_input = trim($_POST['tags'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate required fields
        if (empty($asset_id)) {
            throw new Exception("asset_id is required");
        }
        if (empty($title)) {
            throw new Exception("title is required");
        }
        if (empty($source_type) || !in_array($source_type, $registry['enums']['asset_source_type'])) {
            throw new Exception("Invalid source type");
        }
        
        // Check unique ID
        if (!ensure_unique_id($registry, 'asset', $asset_id)) {
            throw new Exception("Asset ID '{$asset_id}' already exists");
        }
        
        // Build source object
        $source = ['type' => $source_type];
        if ($source_type === 'envato_elements') {
            if (empty($envato_item_id)) {
                throw new Exception("envato_item_id is required for envato_elements");
            }
            $source['envato_item_id'] = $envato_item_id;
            if (!empty($envato_license_ref)) {
                $source['license_ref'] = $envato_license_ref;
            }
        } elseif ($source_type === 'envato_generative') {
            if (empty($generator)) {
                throw new Exception("generator is required for envato_generative");
            }
            if (empty($prompt)) {
                throw new Exception("prompt is required for envato_generative");
            }
            $source['generator'] = $generator;
            $source['prompt'] = $prompt;
            if (!empty($generation_version)) {
                $source['generation_version'] = $generation_version;
            }
            if (!empty($seed_or_job_id)) {
                $source['seed_or_job_id'] = $seed_or_job_id;
            }
        }
        
        // Parse files
        $files = [];
        if (!empty($files_json)) {
            $files_data = json_decode($files_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid files JSON: " . json_last_error_msg());
            }
            if (!is_array($files_data)) {
                throw new Exception("files must be an array");
            }
            foreach ($files_data as $file) {
                if (!isset($file['path'])) {
                    throw new Exception("Each file must have a 'path'");
                }
                if (!validate_file_path($file['path'])) {
                    throw new Exception("Invalid file path: " . $file['path']);
                }
                $files[] = [
                    'path' => $file['path'],
                    'format' => $file['format'] ?? 'mp3',
                    'loopable' => isset($file['loopable']) ? (bool)$file['loopable'] : false
                ];
            }
        }
        if (empty($files)) {
            throw new Exception("At least one file is required");
        }
        
        // Build asset object
        $asset = [
            'asset_id' => $asset_id,
            'title' => $title,
            'source' => $source,
            'files' => $files,
            'tags' => parse_tags($tags_input),
            'notes' => $notes
        ];
        
        // Add to registry
        if (!isset($registry['assets'])) {
            $registry['assets'] = [];
        }
        $registry['assets'][] = $asset;
        
        // Save
        save_registry($registry);
        
        $success = true;
        $created_id = $asset_id;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $registry = load_registry();
    $metadata = get_registry_metadata();
} catch (Exception $e) {
    $error = $error ?: $e->getMessage();
    $registry = null;
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">➕ Add Asset</h1>
            
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">← Back to Index</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Asset created with ID: <code><?php echo h($created_id); ?></code>
                <div class="mt-2">
                    <a href="ui_add_asset.php" class="btn btn-sm btn-primary">Add Another</a>
                    <a href="ui_browse.php" class="btn btn-sm btn-secondary">Browse Assets</a>
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
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo h($_POST['title'] ?? ''); ?>" required>
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">Asset ID will be auto-generated below</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="asset_id" class="form-label">Asset ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="asset_id" name="asset_id" 
                                   value="<?php echo h($_POST['asset_id'] ?? ''); ?>" required readonly 
                                   style="background-color: #2a1515; cursor: not-allowed;">
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">Auto-generated from title</div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Source</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="source_type" class="form-label">Source Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="source_type" name="source_type" required>
                                <option value="">Select...</option>
                                <?php foreach ($registry['enums']['asset_source_type'] as $type): ?>
                                <option value="<?php echo h($type); ?>" 
                                        <?php echo (($_POST['source_type'] ?? '') === $type) ? 'selected' : ''; ?>>
                                    <?php echo h($type); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="envato_elements_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="envato_item_id" class="form-label">Envato Item ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="envato_item_id" name="envato_item_id" 
                                       value="<?php echo h($_POST['envato_item_id'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="envato_license_ref" class="form-label">License Ref (Optional)</label>
                                <input type="text" class="form-control" id="envato_license_ref" name="envato_license_ref" 
                                       value="<?php echo h($_POST['envato_license_ref'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div id="envato_generative_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="generator" class="form-label">Generator <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="generator" name="generator" 
                                       value="<?php echo h($_POST['generator'] ?? ''); ?>" 
                                       placeholder="e.g., Envato Generative Music">
                            </div>
                            <div class="mb-3">
                                <label for="prompt" class="form-label">Prompt <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="prompt" name="prompt" rows="3"><?php echo h($_POST['prompt'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="generation_version" class="form-label">Generation Version (Optional)</label>
                                <input type="text" class="form-control" id="generation_version" name="generation_version" 
                                       value="<?php echo h($_POST['generation_version'] ?? ''); ?>" 
                                       placeholder="e.g., v3">
                            </div>
                            <div class="mb-3">
                                <label for="seed_or_job_id" class="form-label">Seed/Job ID (Optional)</label>
                                <input type="text" class="form-control" id="seed_or_job_id" name="seed_or_job_id" 
                                       value="<?php echo h($_POST['seed_or_job_id'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Files</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="files_json" class="form-label">Files (JSON) <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace" id="files_json" name="files_json" rows="6" required><?php echo h($_POST['files_json'] ?? '[{"path": "assets/audio/music/example.mp3", "format": "mp3", "loopable": true}]'); ?></textarea>
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">
                                JSON array of file objects. Example:<br>
                                <code style="background: rgba(139, 0, 0, 0.2); padding: 2px 6px; border-radius: 3px; color: #f5e6d3;">[{"path": "assets/audio/music/track.mp3", "format": "mp3", "loopable": true}]</code>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Metadata</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   value="<?php echo h($_POST['tags'] ?? ''); ?>" 
                                   placeholder="camarilla, elegant, noir">
                            <div class="form-text" style="color: #b8a090; font-size: 0.9em; margin-top: 0.25rem;">Comma-separated tags</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo h($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create Asset</button>
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
// Auto-generate ID from title
function updateAssetId() {
    const titleField = document.getElementById('title');
    const assetIdField = document.getElementById('asset_id');
    if (titleField && assetIdField) {
        const title = titleField.value.trim();
        assetIdField.value = generateIdFromTitle(title, 'asset');
    }
}

document.getElementById('title')?.addEventListener('input', updateAssetId);
// Also update on page load if title has value
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateAssetId);
} else {
    updateAssetId();
}

// Show/hide source type fields
document.getElementById('source_type')?.addEventListener('change', function() {
    const type = this.value;
    document.getElementById('envato_elements_fields').style.display = 
        (type === 'envato_elements') ? 'block' : 'none';
    document.getElementById('envato_generative_fields').style.display = 
        (type === 'envato_generative') ? 'block' : 'none';
});

// Update JSON preview
function updatePreview() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const formData = new FormData(form);
    const asset = {
        asset_id: formData.get('asset_id') || '',
        title: formData.get('title') || '',
        source: {
            type: formData.get('source_type') || ''
        },
        files: [],
        tags: [],
        notes: formData.get('notes') || ''
    };
    
    // Build source
    const sourceType = formData.get('source_type');
    if (sourceType === 'envato_elements') {
        asset.source.envato_item_id = formData.get('envato_item_id') || '';
        const licenseRef = formData.get('envato_license_ref');
        if (licenseRef) asset.source.license_ref = licenseRef;
    } else if (sourceType === 'envato_generative') {
        asset.source.generator = formData.get('generator') || '';
        asset.source.prompt = formData.get('prompt') || '';
        const version = formData.get('generation_version');
        if (version) asset.source.generation_version = version;
        const seed = formData.get('seed_or_job_id');
        if (seed) asset.source.seed_or_job_id = seed;
    }
    
    // Parse files
    try {
        const filesJson = formData.get('files_json');
        if (filesJson) {
            asset.files = JSON.parse(filesJson);
        }
    } catch (e) {}
    
    // Parse tags
    const tags = formData.get('tags');
    if (tags) {
        asset.tags = tags.split(',').map(t => t.trim()).filter(t => t);
    }
    
    const codeEl = document.getElementById('json_preview').querySelector('code');
    codeEl.textContent = JSON.stringify(asset, null, 2);
    codeEl.style.color = '#d4c4b0';
}

document.querySelector('form')?.addEventListener('input', updatePreview);
document.querySelector('form')?.addEventListener('change', updatePreview);
updatePreview();

function generateIdFromTitle(title, prefix) {
    let slug = title.toLowerCase().trim();
    slug = slug.replace(/[^a-z0-9\s-]/g, '');
    slug = slug.replace(/\s+/g, '_');
    slug = slug.replace(/_+/g, '_');
    slug = slug.trim('_');
    return prefix ? prefix + '_' + slug : slug || 'untitled';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
