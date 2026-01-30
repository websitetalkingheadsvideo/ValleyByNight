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

// Load Nature/Demeanor options from lookup table for dropdowns
$nature_demeanor_options = [];
$nd_result = mysqli_query($conn, "SELECT name FROM Nature_Demeanor ORDER BY display_order");
if ($nd_result && mysqli_num_rows($nd_result) > 0) {
    while ($row = mysqli_fetch_assoc($nd_result)) {
        $nature_demeanor_options[] = $row['name'];
    }
    mysqli_free_result($nd_result);
}

$message = null;
$message_type = null;
$character = null;
$character_id = null;
$show_missing_only = false;

// Must match index.php
$critical_fields = [
    'biography' => 'Biography',
    'appearance' => 'Appearance',
    'concept' => 'Concept',
    'nature' => 'Nature',
    'demeanor' => 'Demeanor'
];

// Handle search for missing data
if (isset($_GET['search_missing'])) {
    $show_missing_only = true;
}

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

        // Save abilities (same logic as save_character.php)
        if (isset($_POST['abilities_json']) && $_POST['abilities_json'] !== '') {
            $abilities = json_decode($_POST['abilities_json'], true);
            if (is_array($abilities)) {
                $col_check = db_fetch_all($conn, "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'");
                $has_cat = !empty($col_check);
                db_execute($conn, "DELETE FROM character_abilities WHERE character_id = ?", 'i', [$character_id]);
                foreach ($abilities as $category => $abilityNames) {
                    if (!is_array($abilityNames)) {
                        continue;
                    }
                    $counts = [];
                    foreach ($abilityNames as $name) {
                        $clean = trim($name);
                        if (strpos($clean, ' (') !== false) {
                            $clean = substr($clean, 0, strpos($clean, ' ('));
                        }
                        $counts[$clean] = ($counts[$clean] ?? 0) + 1;
                    }
                    foreach ($counts as $abilityName => $level) {
                        $level = max(1, min(5, (int)$level));
                        $spec = null;
                        foreach ($abilityNames as $orig) {
                            if (strpos($orig, $abilityName . ' (') === 0) {
                                $s = strpos($orig, ' (') + 2;
                                $e = strrpos($orig, ')');
                                if ($e > $s) {
                                    $spec = substr($orig, $s, $e - $s);
                                }
                                break;
                            }
                        }
                        if ($has_cat) {
                            db_execute($conn,
                                "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) VALUES (?, ?, ?, ?, ?)",
                                'issis',
                                [$character_id, $abilityName, $category, $level, $spec ?? '']
                            );
                        } else {
                            db_execute($conn,
                                "INSERT INTO character_abilities (character_id, ability_name, level, specialization) VALUES (?, ?, ?, ?)",
                                'isis',
                                [$character_id, $abilityName, $level, $spec ?? '']
                            );
                        }
                    }
                }
                if (empty($message)) {
                    $message = "Character updated successfully!";
                    $message_type = 'success';
                }
            }
        }

        // Save backgrounds (form array backgrounds[Name] = level)
        if (isset($_POST['backgrounds']) && is_array($_POST['backgrounds'])) {
            db_execute($conn, "DELETE FROM character_backgrounds WHERE character_id = ?", 'i', [$character_id]);
            foreach ($_POST['backgrounds'] as $name => $level) {
                $cleanName = trim((string)$name);
                if ($cleanName === '') {
                    continue;
                }
                $level = max(0, min(5, (int)$level));
                if ($level > 0) {
                    db_execute($conn,
                        "INSERT INTO character_backgrounds (character_id, background_name, level) VALUES (?, ?, ?)",
                        'isi',
                        [$character_id, $cleanName, $level]
                    );
                }
            }
            if (empty($message)) {
                $message = "Character updated successfully!";
                $message_type = 'success';
            }
        }

        // Redirect after successful save so URL has id (and search_missing) and refresh shows correct state
        if ($message_type === 'success') {
            $redirect_search = !empty($_POST['redirect_search_missing']);
            header('Location: quick-edit.php?id=' . (int)$character_id . ($redirect_search ? '&search_missing=1' : ''));
            exit;
        }
    }
}

// Get character ID from GET or POST
if (isset($_GET['id'])) {
    $character_id = (int)$_GET['id'];
} elseif (isset($_POST['character_id'])) {
    $character_id = (int)$_POST['character_id'];
}

// Load character data
if ($character_id > 0) {
    $select_fields = ['id', 'character_name', 'concept', 'nature', 'demeanor', 'biography', 'appearance', 'character_image'];
    $select_sql = "SELECT " . implode(', ', $select_fields) . ",
                    (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) as abilities_count,
                    (SELECT COUNT(*) FROM character_disciplines WHERE character_id = c.id) as disciplines_count,
                    (SELECT COUNT(*) FROM character_backgrounds WHERE character_id = c.id) as backgrounds_count
                  FROM characters c WHERE c.id = ?";
    $stmt = mysqli_prepare($conn, $select_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $character_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $character = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($character) {
            $missing_fields = [];
            foreach ($critical_fields as $field => $label) {
                $value = $character[$field] ?? null;
                if ($value === null || $value === '' || trim($value) === '') {
                    $missing_fields[$field] = $label;
                }
            }
            if ((int)($character['abilities_count'] ?? 0) === 0) {
                $missing_fields['abilities'] = 'Abilities';
            }
            if ((int)($character['disciplines_count'] ?? 0) === 0) {
                $missing_fields['disciplines'] = 'Disciplines';
            }
            if ((int)($character['backgrounds_count'] ?? 0) === 0) {
                $missing_fields['backgrounds'] = 'Backgrounds';
            }
            $image_value = $character['character_image'] ?? null;
            $image_missing = empty($image_value) || trim($image_value) === '';
            if (!$image_missing) {
                $image_missing = !file_exists(__DIR__ . '/../../../uploads/characters/' . $image_value);
            }
            if ($image_missing) {
                $missing_fields['character_image'] = 'Character Image';
            }
            $character['_missing_fields'] = $missing_fields;
            // Load existing abilities for editor (same format as save: category => [names repeated by level])
            $char_abilities = db_fetch_all($conn,
                "SELECT ability_name, ability_category, level FROM character_abilities WHERE character_id = ? ORDER BY ability_category, ability_name",
                'i', [$character_id]
            );
            $existing_abilities = ['Physical' => [], 'Social' => [], 'Mental' => [], 'Optional' => []];
            foreach ($char_abilities as $a) {
                $cat = $a['ability_category'] ?? 'Optional';
                if (!isset($existing_abilities[$cat])) {
                    $existing_abilities[$cat] = [];
                }
                $name = trim($a['ability_name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $lvl = max(1, min(5, (int)($a['level'] ?? 1)));
                for ($i = 0; $i < $lvl; $i++) {
                    $existing_abilities[$cat][] = $name;
                }
            }
            $character['_abilities'] = $existing_abilities;
            // Load existing backgrounds (name => level)
            $char_backgrounds = db_fetch_all($conn,
                "SELECT background_name, level FROM character_backgrounds WHERE character_id = ? ORDER BY background_name",
                'i', [$character_id]
            );
            $existing_backgrounds = [];
            foreach ($char_backgrounds as $bg) {
                $n = trim($bg['background_name'] ?? '');
                if ($n !== '') {
                    $existing_backgrounds[$n] = max(0, min(5, (int)($bg['level'] ?? 0)));
                }
            }
            $character['_backgrounds'] = $existing_backgrounds;
        }
    }
}

// Background names for quick-edit (from backgrounds_master or fallback)
$background_names = [];
$bm = @db_fetch_all($conn, "SELECT name FROM backgrounds_master ORDER BY display_order ASC");
if (!empty($bm)) {
    foreach ($bm as $r) {
        $background_names[] = $r['name'];
    }
}
if (empty($background_names)) {
    $background_names = ['Allies', 'Contacts', 'Influence', 'Mentor', 'Resources', 'Retainers', 'Status'];
}

// Get list of characters for dropdown
if ($show_missing_only) {
    // Use same missing-data definition as index.php (query + logic)
    $select_fields = ['c.id', 'c.character_name', 'c.biography', 'c.appearance', 'c.concept', 'c.nature', 'c.demeanor', 'c.character_image'];
    $query = "SELECT " . implode(', ', $select_fields) . ",
                (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) as abilities_count,
                (SELECT COUNT(*) FROM character_disciplines WHERE character_id = c.id) as disciplines_count,
                (SELECT COUNT(*) FROM character_backgrounds WHERE character_id = c.id) as backgrounds_count
              FROM characters c
              ORDER BY c.id";
    $all_chars = db_fetch_all($conn, $query);
    $image_dir = __DIR__ . '/../../../uploads/characters/';
    $characters_list = [];

    foreach ($all_chars as $char) {
        $missing_fields = [];
        foreach ($critical_fields as $field => $label) {
            if (!array_key_exists($field, $char)) {
                continue;
            }
            $value = $char[$field] ?? null;
            if ($value === null || $value === '' || trim($value) === '') {
                $missing_fields[] = $label;
            }
        }
        if ((int)($char['abilities_count'] ?? 0) === 0) {
            $missing_fields[] = 'Abilities';
        }
        if ((int)($char['disciplines_count'] ?? 0) === 0) {
            $missing_fields[] = 'Disciplines';
        }
        if ((int)($char['backgrounds_count'] ?? 0) === 0) {
            $missing_fields[] = 'Backgrounds';
        }
        $image_value = $char['character_image'] ?? null;
        $image_missing = empty($image_value) || trim($image_value) === '';
        if (!$image_missing) {
            $image_missing = !file_exists($image_dir . $image_value);
        }
        if ($image_missing) {
            $missing_fields[] = 'Image';
        }
        if (!empty($missing_fields)) {
            $characters_list[] = [
                'id' => (int)$char['id'],
                'character_name' => $char['character_name'] ?? 'Unknown'
            ];
        }
    }

    usort($characters_list, function ($a, $b) {
        return strcmp($a['character_name'] ?? '', $b['character_name'] ?? '');
    });
} else {
    $characters_list = db_fetch_all($conn, "SELECT id, character_name FROM characters ORDER BY character_name ASC");
}

$extra_css = ['css/admin_panel.css'];
$extra_js = ['js/quick-edit-abilities.js'];
include __DIR__ . '/../../../includes/header.php';

// Abilities from DB (same source as get_abilities_api / character editor)
$abilities_by_category = ['Physical' => [], 'Social' => [], 'Mental' => [], 'Optional' => []];
$abilities_rows = db_fetch_all($conn, "SELECT name, category FROM abilities ORDER BY category, display_order ASC");
foreach ($abilities_rows as $r) {
    $cat = $r['category'] ?? '';
    if (isset($abilities_by_category[$cat])) {
        $abilities_by_category[$cat][] = $r['name'];
    }
}
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
                    
                    <!-- Search Button -->
                    <div class="mb-3">
                        <form method="GET" class="d-inline">
                            <?php if (isset($_GET['id'])): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                            <?php endif; ?>
                            <button type="submit" name="search_missing" value="1" class="btn btn-primary">
                                Search for Missing Data
                            </button>
                        </form>
                        <?php if ($show_missing_only): ?>
                            <a href="quick-edit.php" class="btn btn-secondary ms-2">Show All Characters</a>
                            <span class="ms-2 text-white">Showing <?php echo count($characters_list); ?> character(s) with missing data</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Character Selector -->
                    <form method="GET" class="mb-4">
                        <?php if ($show_missing_only): ?>
                            <input type="hidden" name="search_missing" value="1">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="character_select" class="form-label">Select Character</label>
                                <select name="id" id="character_select" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Choose a character --</option>
                                    <?php foreach ($characters_list as $char): ?>
                                        <option value="<?php echo $char['id']; ?>" <?php echo ($character_id == $char['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($char['character_name'] ?? 'Unnamed Character'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (empty($characters_list)): ?>
                                        <option value="" disabled>No characters found</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Edit Form -->
                    <?php if ($character): ?>
                        <?php
                        $missing_fields = $character['_missing_fields'] ?? [];
                        $editable_keys = ['character_name', 'concept', 'nature', 'demeanor', 'biography', 'appearance', 'character_image'];
                        $missing_editable = array_intersect_key($missing_fields, array_flip($editable_keys));
                        $existing_abilities = $character['_abilities'] ?? ['Physical' => [], 'Social' => [], 'Mental' => [], 'Optional' => []];
                        ?>
                        <form method="POST" enctype="multipart/form-data" id="quick-edit-form">
                            <input type="hidden" name="character_id" value="<?php echo (int)$character['id']; ?>">
                            <?php if ($show_missing_only): ?>
                            <input type="hidden" name="redirect_search_missing" value="1">
                            <?php endif; ?>
                            <?php if (isset($missing_fields['abilities'])): ?>
                            <input type="hidden" name="abilities_json" id="abilities_json" value="<?php echo htmlspecialchars(json_encode($existing_abilities), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endif; ?>
                            <?php if (empty($missing_fields)): ?>
                                <div class="alert alert-success mb-3">All fields complete. No missing data for this character.</div>
                            <?php elseif (!empty($missing_editable)): ?>
                                <div class="alert alert-info mb-3">
                                    <strong>Missing:</strong> <?php echo htmlspecialchars(implode(', ', array_values($missing_editable))); ?>.
                                    <br><strong>Character:</strong> <?php echo htmlspecialchars($character['character_name'] ?? 'Unknown'); ?>
                                </div>
                            <?php else:
                                $missing_other = array_intersect_key($missing_fields, array_flip(['abilities', 'disciplines', 'backgrounds']));
                            ?>
                                <div class="alert alert-info mb-3">
                                    <strong>Missing:</strong> <?php echo htmlspecialchars(implode(', ', array_values($missing_other)), ENT_QUOTES, 'UTF-8'); ?>.
                                    <br><strong>Character:</strong> <?php echo htmlspecialchars($character['character_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($missing_editable)): ?>
                                <div class="row g-3 mb-4">
                                    <?php if (isset($missing_fields['character_name'])): ?>
                                        <div class="col-md-6">
                                            <label for="character_name" class="form-label">Character Name</label>
                                            <input type="text" class="form-control" id="character_name" name="character_name" value="<?php echo htmlspecialchars($character['character_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['concept'])): ?>
                                        <div class="col-md-6">
                                            <label for="concept" class="form-label">Concept</label>
                                            <input type="text" class="form-control" id="concept" name="concept" value="<?php echo htmlspecialchars($character['concept'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['nature'])): ?>
                                        <div class="col-md-6">
                                            <label for="nature" class="form-label">Nature</label>
                                            <select class="form-select" id="nature" name="nature">
                                                <option value="">-- Select Nature --</option>
                                                <?php foreach ($nature_demeanor_options as $opt): ?>
                                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($character['nature']) && trim($character['nature']) === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['demeanor'])): ?>
                                        <div class="col-md-6">
                                            <label for="demeanor" class="form-label">Demeanor</label>
                                            <select class="form-select" id="demeanor" name="demeanor">
                                                <option value="">-- Select Demeanor --</option>
                                                <?php foreach ($nature_demeanor_options as $opt): ?>
                                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($character['demeanor']) && trim($character['demeanor']) === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['biography'])): ?>
                                        <div class="col-md-12">
                                            <label for="biography" class="form-label">Biography</label>
                                            <textarea class="form-control" id="biography" name="biography" rows="6"><?php echo htmlspecialchars($character['biography'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['appearance'])): ?>
                                        <div class="col-md-12">
                                            <label for="appearance" class="form-label">Appearance</label>
                                            <textarea class="form-control" id="appearance" name="appearance" rows="6"><?php echo htmlspecialchars($character['appearance'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($missing_fields['character_image'])): ?>
                                        <div class="col-md-12">
                                            <label for="character_image" class="form-label">Character Image</label>
                                            <input type="file" class="form-control" id="character_image" name="character_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                            <div class="mt-1">Accepted formats: JPG, PNG, GIF, WebP</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($missing_fields['abilities'])): ?>
                            <h5 class="mb-3">Abilities</h5>
                            <?php
                            $cats = ['Physical' => 'Physical', 'Social' => 'Social', 'Mental' => 'Mental', 'Optional' => 'Optional'];
                            foreach ($cats as $catKey => $catLabel):
                                $opts = $abilities_by_category[$catKey] ?? [];
                            ?>
                            <div class="ability-section mb-4">
                                <h6 class="mb-2"><?php echo htmlspecialchars($catLabel); ?></h6>
                                <div class="ability-options d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($opts as $ab): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ability-option-btn" data-category="<?php echo htmlspecialchars($catKey, ENT_QUOTES, 'UTF-8'); ?>" data-ability="<?php echo htmlspecialchars($ab, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ab); ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="ability-list border rounded p-2 bg-dark" id="<?php echo htmlspecialchars(strtolower($catKey)); ?>AbilitiesList" data-category="<?php echo htmlspecialchars($catKey, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="text-white">None selected</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (isset($missing_fields['backgrounds'])): ?>
                            <h5 class="mb-3">Backgrounds</h5>
                            <?php
                            $existing_backgrounds = $character['_backgrounds'] ?? [];
                            foreach ($background_names as $bgName):
                                $currentLevel = (int)($existing_backgrounds[$bgName] ?? 0);
                            ?>
                            <div class="row align-items-center mb-2">
                                <div class="col-md-4">
                                    <label for="bg_<?php echo htmlspecialchars(preg_replace('/[^a-z0-9]/i', '_', $bgName), ENT_QUOTES, 'UTF-8'); ?>" class="form-label"><?php echo htmlspecialchars($bgName); ?></label>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" name="backgrounds[<?php echo htmlspecialchars($bgName, ENT_QUOTES, 'UTF-8'); ?>]" id="bg_<?php echo htmlspecialchars(preg_replace('/[^a-z0-9]/i', '_', $bgName), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php for ($l = 0; $l <= 5; $l++): ?>
                                        <option value="<?php echo $l; ?>" <?php echo $currentLevel === $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" name="update_character" class="btn btn-primary">Update Character</button>
                                <a href="quick-edit.php<?php echo $show_missing_only ? '?search_missing=1' : ''; ?>" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>
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
