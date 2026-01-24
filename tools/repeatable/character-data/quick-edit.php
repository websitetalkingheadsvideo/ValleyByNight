<?php
/**
 * Quick Character Editor
 * 
 * Fast interface for editing common character fields.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

require_once __DIR__ . '/../../../includes/connect.php';

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

$message = null;
$message_type = null;
$character = null;
$character_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_character'])) {
    $character_id = isset($_POST['character_id']) ? (int)$_POST['character_id'] : 0;
    
    if ($character_id > 0) {
        // Handle image upload
        $image_filename = null;
        if (isset($_FILES['character_image']) && $_FILES['character_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../../uploads/characters/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['character_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $image_filename = 'character_' . $character_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $image_filename;
                
                if (move_uploaded_file($_FILES['character_image']['tmp_name'], $upload_path)) {
                    // Image uploaded successfully
                } else {
                    $message = "Error uploading image file.";
                    $message_type = 'danger';
                }
            } else {
                $message = "Invalid image file type. Allowed: " . implode(', ', $allowed_extensions);
                $message_type = 'danger';
            }
        }
        
        // Build update query
        $update_fields = [];
        $update_values = [];
        $types = '';
        
        $fields_to_update = [
            'character_name' => 's',
            'concept' => 's',
            'nature' => 's',
            'demeanor' => 's',
            'biography' => 's',
            'appearance' => 's'
        ];
        
        foreach ($fields_to_update as $field => $type) {
            if (isset($_POST[$field])) {
                $update_fields[] = "{$field} = ?";
                $update_values[] = trim($_POST[$field]);
                $types .= $type;
            }
        }
        
        // Add image if uploaded
        if ($image_filename) {
            $update_fields[] = "character_image = ?";
            $update_values[] = $image_filename;
            $types .= 's';
        }
        
        if (!empty($update_fields)) {
            $update_values[] = $character_id;
            $types .= 'i';
            
            $update_sql = "UPDATE characters SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$update_values);
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Character updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating character: " . mysqli_stmt_error($stmt);
                    $message_type = 'danger';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Error preparing update statement: " . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Get character ID from GET or POST
if (isset($_GET['id'])) {
    $character_id = (int)$_GET['id'];
} elseif (isset($_POST['character_id']) && !isset($_POST['update_character'])) {
    $character_id = (int)$_POST['character_id'];
}

// Load character data
if ($character_id > 0) {
    $select_fields = ['id', 'character_name', 'concept', 'nature', 'demeanor', 'biography', 'appearance', 'character_image'];
    $select_sql = "SELECT " . implode(', ', $select_fields) . " FROM characters WHERE id = ?";
    $stmt = mysqli_prepare($conn, $select_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $character_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $character = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Determine which fields are missing
        $missing_fields = [];
        
        // Check text fields
        $text_fields = [
            'character_name' => 'Character Name',
            'concept' => 'Concept',
            'nature' => 'Nature',
            'demeanor' => 'Demeanor',
            'biography' => 'Biography',
            'appearance' => 'Appearance'
        ];
        
        foreach ($text_fields as $field => $label) {
            $value = $character[$field] ?? null;
            if (empty($value) || trim($value) === '') {
                $missing_fields[$field] = $label;
            }
        }
        
        // Check character image
        $image_value = $character['character_image'] ?? null;
        $image_missing = false;
        if (empty($image_value) || trim($image_value) === '') {
            $image_missing = true;
        } else {
            // Check if image file actually exists
            $image_path = __DIR__ . '/../../../uploads/characters/' . $image_value;
            if (!file_exists($image_path)) {
                $image_missing = true;
            }
        }
        
        if ($image_missing) {
            $missing_fields['character_image'] = 'Character Image';
        }
        
        // Store missing fields for use in form
        $character['_missing_fields'] = $missing_fields;
    }
}

// Get list of characters for dropdown
$characters_list = db_fetch_all($conn, "SELECT id, character_name FROM characters ORDER BY character_name ASC");

// Page-specific CSS
$extra_css = ['css/admin_panel.css'];
include __DIR__ . '/../../../includes/header.php';
?>

<div class="page-content container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">Quick Character Editor</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Character Selector -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="character_select" class="form-label">Select Character</label>
                                <select name="id" id="character_select" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Choose a character --</option>
                                    <?php foreach ($characters_list as $char): ?>
                                        <option value="<?php echo $char['id']; ?>" <?php echo ($character_id == $char['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($char['character_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Edit Form -->
                    <?php if ($character): ?>
                        <?php 
                        $missing_fields = $character['_missing_fields'] ?? [];
                        if (empty($missing_fields)): ?>
                            <div class="alert alert-success">
                                <strong>All fields are complete!</strong> This character has all required data filled in.
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="character_id" value="<?php echo $character['id']; ?>">
                                
                                <div class="alert alert-info mb-3">
                                    <strong>Missing Fields:</strong> Only fields that are missing from the database are shown below.
                                </div>
                                
                                <div class="row g-3">
                                    <?php if (isset($missing_fields['character_name'])): ?>
                                        <div class="col-md-6">
                                            <label for="character_name" class="form-label">Character Name</label>
                                            <input type="text" class="form-control" id="character_name" name="character_name" 
                                                   value="<?php echo htmlspecialchars($character['character_name'] ?? ''); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['concept'])): ?>
                                        <div class="col-md-6">
                                            <label for="concept" class="form-label">Concept</label>
                                            <input type="text" class="form-control" id="concept" name="concept" 
                                                   value="<?php echo htmlspecialchars($character['concept'] ?? ''); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['nature'])): ?>
                                        <div class="col-md-6">
                                            <label for="nature" class="form-label">Nature</label>
                                            <input type="text" class="form-control" id="nature" name="nature" 
                                                   value="<?php echo htmlspecialchars($character['nature'] ?? ''); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['demeanor'])): ?>
                                        <div class="col-md-6">
                                            <label for="demeanor" class="form-label">Demeanor</label>
                                            <input type="text" class="form-control" id="demeanor" name="demeanor" 
                                                   value="<?php echo htmlspecialchars($character['demeanor'] ?? ''); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['biography'])): ?>
                                        <div class="col-md-12">
                                            <label for="biography" class="form-label">Biography</label>
                                            <textarea class="form-control" id="biography" name="biography" rows="6"><?php echo htmlspecialchars($character['biography'] ?? ''); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['appearance'])): ?>
                                        <div class="col-md-12">
                                            <label for="appearance" class="form-label">Appearance</label>
                                            <textarea class="form-control" id="appearance" name="appearance" rows="6"><?php echo htmlspecialchars($character['appearance'] ?? ''); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($missing_fields['character_image'])): ?>
                                        <div class="col-md-12">
                                            <label for="character_image" class="form-label">Character Image</label>
                                            <input type="file" class="form-control" id="character_image" name="character_image" 
                                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                            <div class="mt-1">Accepted formats: JPG, PNG, GIF, WebP</div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-12">
                                        <button type="submit" name="update_character" class="btn btn-primary">Update Character</button>
                                        <a href="quick-edit.php" class="btn btn-secondary">Clear</a>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Select a character from the dropdown above to begin editing.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../../includes/footer.php';
?>
