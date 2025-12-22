<?php
/**
 * Update Character Abilities
 * 
 * Generic script to update or add abilities for any character.
 * 
 * Usage:
 *   php update_character_abilities.php --character-id=125 --abilities="Finance:5,Leadership:5,Subterfuge:5,Etiquette:2"
 *   php update_character_abilities.php --character-id=125 --abilities="Finance:5" --categories="Mental"
 *   php update_character_abilities.php --character-id=125 --abilities="Etiquette:2" --categories="Social" --dry-run
 * 
 * Format:
 *   --character-id=<id>     Character ID (required)
 *   --abilities=<list>       Comma-separated list of "AbilityName:Level" (required)
 *   --categories=<list>     Comma-separated list of categories matching abilities (optional, will try to auto-detect)
 *   --dry-run               Show what would be updated without making changes
 *   --help                  Show this help message
 */
require_once dirname(__DIR__, 2) . '/includes/connect.php';

// Parse command line arguments
$options = getopt('', ['character-id:', 'abilities:', 'categories:', 'dry-run', 'help']);

if (isset($options['help']) || !isset($options['character-id']) || !isset($options['abilities'])) {
    echo "Update Character Abilities\n";
    echo "==========================\n\n";
    echo "Usage:\n";
    echo "  php update_character_abilities.php --character-id=<id> --abilities=\"Ability1:Level1,Ability2:Level2\"\n\n";
    echo "Options:\n";
    echo "  --character-id=<id>     Character ID (required)\n";
    echo "  --abilities=<list>      Comma-separated list of \"AbilityName:Level\" (required)\n";
    echo "  --categories=<list>     Comma-separated list of categories matching abilities (optional)\n";
    echo "  --dry-run               Show what would be updated without making changes\n";
    echo "  --help                  Show this help message\n\n";
    echo "Examples:\n";
    echo "  php update_character_abilities.php --character-id=125 --abilities=\"Finance:5,Leadership:5,Subterfuge:5,Etiquette:2\"\n";
    echo "  php update_character_abilities.php --character-id=125 --abilities=\"Finance:5\" --categories=\"Mental\"\n";
    echo "  php update_character_abilities.php --character-id=125 --abilities=\"Etiquette:2\" --categories=\"Social\" --dry-run\n\n";
    exit(0);
}

$character_id = intval($options['character-id']);
$dry_run = isset($options['dry-run']);

if ($character_id <= 0) {
    echo "✗ Error: Invalid character ID\n";
    exit(1);
}

// Parse abilities
$abilities_input = $options['abilities'];
$abilities_list = [];
foreach (explode(',', $abilities_input) as $ability_str) {
    $ability_str = trim($ability_str);
    if (empty($ability_str)) continue;
    
    if (strpos($ability_str, ':') === false) {
        echo "✗ Error: Invalid ability format: '$ability_str'. Expected format: 'AbilityName:Level'\n";
        exit(1);
    }
    
    list($name, $level) = explode(':', $ability_str, 2);
    $name = trim($name);
    $level = intval(trim($level));
    
    if (empty($name) || $level < 1 || $level > 5) {
        echo "✗ Error: Invalid ability '$name' with level $level. Level must be 1-5.\n";
        exit(1);
    }
    
    $abilities_list[] = ['name' => $name, 'level' => $level];
}

// Parse categories if provided
$categories_input = isset($options['categories']) ? $options['categories'] : '';
$categories_list = [];
if (!empty($categories_input)) {
    $categories_list = array_map('trim', explode(',', $categories_input));
    if (count($categories_list) !== count($abilities_list)) {
        echo "⚠ Warning: Number of categories doesn't match number of abilities. Will auto-detect missing categories.\n";
    }
}

// Get character name
$char = db_fetch_one($conn, "SELECT character_name FROM characters WHERE id = ?", 'i', [$character_id]);
if (!$char) {
    echo "✗ Error: Character with ID $character_id not found\n";
    exit(1);
}

$character_name = $char['character_name'];

echo "Updating abilities for: $character_name (ID: $character_id)\n";
if ($dry_run) {
    echo "*** DRY RUN MODE - No changes will be made ***\n";
}
echo "\n";

// Check if ability_category column exists
$check_column_sql = "SHOW COLUMNS FROM character_abilities LIKE 'ability_category'";
$column_check = mysqli_query($conn, $check_column_sql);
$has_category_column = ($column_check && mysqli_num_rows($column_check) > 0);
if ($column_check) {
    mysqli_free_result($column_check);
}

// Get current abilities to determine categories and existing levels
$current_abilities = db_fetch_all($conn,
    "SELECT 
        ca.ability_name,
        COALESCE(ca.ability_category, a.category) as ability_category,
        ca.level
     FROM character_abilities ca
     LEFT JOIN abilities a ON ca.ability_name COLLATE utf8mb4_unicode_ci = a.name COLLATE utf8mb4_unicode_ci
     WHERE ca.character_id = ?",
    'i', [$character_id]
);

$current_abilities_map = [];
foreach ($current_abilities as $ca) {
    $current_abilities_map[$ca['ability_name']] = [
        'category' => $ca['ability_category'],
        'level' => $ca['level']
    ];
}

// Process each ability
foreach ($abilities_list as $index => $ability) {
    $ability_name = $ability['name'];
    $new_level = $ability['level'];
    
    // Determine category
    $category = null;
    if (isset($categories_list[$index]) && !empty($categories_list[$index])) {
        $category = $categories_list[$index];
    } elseif (isset($current_abilities_map[$ability_name])) {
        $category = $current_abilities_map[$ability_name]['category'];
    } else {
        // Try to get category from abilities table
        $ability_info = db_fetch_one($conn,
            "SELECT category FROM abilities WHERE name = ? LIMIT 1",
            's', [$ability_name]
        );
        if ($ability_info) {
            $category = $ability_info['category'];
        }
    }
    
    if (!$category) {
        echo "⚠ Warning: Could not determine category for '$ability_name'. Skipping.\n";
        continue;
    }
    
    $exists = isset($current_abilities_map[$ability_name]);
    $old_level = $exists ? $current_abilities_map[$ability_name]['level'] : 0;
    
    if ($exists && $old_level == $new_level) {
        echo "○ $ability_name is already at level $new_level. Skipping.\n";
        continue;
    }
    
    if ($dry_run) {
        if ($exists) {
            echo "[DRY RUN] Would update $ability_name from level $old_level to $new_level ($category)\n";
        } else {
            echo "[DRY RUN] Would add $ability_name at level $new_level ($category)\n";
        }
        continue;
    }
    
    // Update or insert
    if ($exists) {
        // Update existing ability
        if ($has_category_column) {
            $sql = "UPDATE character_abilities 
                    SET level = ? 
                    WHERE character_id = ? AND ability_name = ? AND ability_category = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'iiss', $new_level, $character_id, $ability_name, $category);
                if (mysqli_stmt_execute($stmt)) {
                    $affected = mysqli_stmt_affected_rows($stmt);
                    if ($affected > 0) {
                        echo "✓ Updated $ability_name from level $old_level to $new_level ($category)\n";
                    } else {
                        echo "⚠ No rows updated for $ability_name (may not match category filter)\n";
                    }
                } else {
                    echo "✗ Failed to update $ability_name: " . mysqli_stmt_error($stmt) . "\n";
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "✗ Failed to prepare statement for $ability_name: " . mysqli_error($conn) . "\n";
            }
        } else {
            $sql = "UPDATE character_abilities 
                    SET level = ? 
                    WHERE character_id = ? AND ability_name = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'iis', $new_level, $character_id, $ability_name);
                if (mysqli_stmt_execute($stmt)) {
                    $affected = mysqli_stmt_affected_rows($stmt);
                    if ($affected > 0) {
                        echo "✓ Updated $ability_name from level $old_level to $new_level\n";
                    } else {
                        echo "⚠ No rows updated for $ability_name\n";
                    }
                } else {
                    echo "✗ Failed to update $ability_name: " . mysqli_stmt_error($stmt) . "\n";
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "✗ Failed to prepare statement for $ability_name: " . mysqli_error($conn) . "\n";
            }
        }
    } else {
        // Insert new ability
        if ($has_category_column) {
            $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, ability_category, level, specialization) 
                           VALUES (?, ?, ?, ?, NULL)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            if ($insert_stmt) {
                mysqli_stmt_bind_param($insert_stmt, 'issi', $character_id, $ability_name, $category, $new_level);
                if (mysqli_stmt_execute($insert_stmt)) {
                    echo "✓ Added $ability_name at level $new_level ($category)\n";
                } else {
                    echo "✗ Failed to add $ability_name: " . mysqli_stmt_error($insert_stmt) . "\n";
                }
                mysqli_stmt_close($insert_stmt);
            } else {
                echo "✗ Failed to prepare insert statement for $ability_name: " . mysqli_error($conn) . "\n";
            }
        } else {
            $insert_sql = "INSERT INTO character_abilities (character_id, ability_name, level, specialization) 
                           VALUES (?, ?, ?, NULL)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            if ($insert_stmt) {
                mysqli_stmt_bind_param($insert_stmt, 'isi', $character_id, $ability_name, $new_level);
                if (mysqli_stmt_execute($insert_stmt)) {
                    echo "✓ Added $ability_name at level $new_level\n";
                } else {
                    echo "✗ Failed to add $ability_name: " . mysqli_stmt_error($insert_stmt) . "\n";
                }
                mysqli_stmt_close($insert_stmt);
            } else {
                echo "✗ Failed to prepare insert statement for $ability_name: " . mysqli_error($conn) . "\n";
            }
        }
    }
}

if (!$dry_run) {
    echo "\n=== Final Abilities ===\n";
    $abilities = db_fetch_all($conn,
        "SELECT 
            ca.ability_name,
            COALESCE(ca.ability_category, a.category) as ability_category,
            ca.level,
            ca.specialization
         FROM character_abilities ca
         LEFT JOIN abilities a ON ca.ability_name COLLATE utf8mb4_unicode_ci = a.name COLLATE utf8mb4_unicode_ci
         WHERE ca.character_id = ?
         ORDER BY COALESCE(ca.ability_category, a.category), ca.ability_name",
        'i', [$character_id]
    );
    
    $by_category = [];
    foreach ($abilities as $a) {
        $cat = $a['ability_category'] ?? 'Unknown';
        if (!isset($by_category[$cat])) {
            $by_category[$cat] = [];
        }
        $by_category[$cat][] = $a;
    }
    
    foreach ($by_category as $category => $abilities_list) {
        echo "\n$category:\n";
        foreach ($abilities_list as $a) {
            $spec = $a['specialization'] ? ' (' . $a['specialization'] . ')' : '';
            echo "  - {$a['ability_name']} x{$a['level']}$spec\n";
        }
    }
    
    echo "\n✓ Update complete!\n";
} else {
    echo "\n[DRY RUN] No changes were made.\n";
}

