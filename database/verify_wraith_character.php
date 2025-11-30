<?php
/**
 * Verify Wraith Character JSON against Database Schema
 * 
 * This script validates a Wraith character JSON file against the database schema
 * to ensure all required fields are present and properly formatted.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Get character file from command line or query string
$character_file = $argv[1] ?? $_GET['file'] ?? '';

if (empty($character_file)) {
    die("Usage: php verify_wraith_character.php <path_to_json_file>\n");
}

// Resolve file path
if (!file_exists($character_file)) {
    // Try relative to script directory
    $character_file = __DIR__ . '/../' . $character_file;
    if (!file_exists($character_file)) {
        die("Error: File not found: $character_file\n");
    }
}

// Read and parse JSON
$json_content = file_get_contents($character_file);
$character = json_decode($json_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Invalid JSON - " . json_last_error_msg() . "\n");
}

echo "Verifying: " . basename($character_file) . "\n";
echo str_repeat("=", 60) . "\n\n";

// Database schema fields (from create_wraith_characters_table.php)
$required_fields = [
    'id' => 'integer',
    'user_id' => 'integer',
    'character_name' => 'string',
    'shadow_name' => 'string',
    'player_name' => 'string',
    'chronicle' => 'string',
    'nature' => 'string',
    'demeanor' => 'string',
    'concept' => 'string',
    'circle' => 'string',
    'guild' => 'string',
    'legion_at_death' => 'string',
    'date_of_death' => 'date_string',
    'cause_of_death' => 'string',
    'pc' => 'integer',
    'appearance' => 'string',
    'ghostly_appearance' => 'string',
    'biography' => 'string',
    'notes' => 'string',
    'equipment' => 'string',
    'character_image' => 'string',
    'status' => 'string',
    'timeline' => 'json_object',
    'personality' => 'json_object',
    'traits' => 'json_object',
    'negativeTraits' => 'json_object',
    'abilities' => 'json_array',
    'specializations' => 'json_object',
    'fetters' => 'json_array',
    'passions' => 'json_array',
    'arcanoi' => 'json_array',
    'backgrounds' => 'json_object',
    'backgroundDetails' => 'json_object',
    'willpower_permanent' => 'integer',
    'willpower_current' => 'integer',
    'pathos_corpus' => 'json_object',
    'shadow' => 'json_object',
    'harrowing' => 'json_object',
    'merits_flaws' => 'json_array',
    'status_details' => 'json_object',
    'relationships' => 'json_array',
    'artifacts' => 'json_array',
    'custom_data' => 'any',
    'actingNotes' => 'string',
    'agentNotes' => 'string',
    'health_status' => 'string',
    'experience_total' => 'integer',
    'spent_xp' => 'integer',
    'experience_unspent' => 'integer',
    'shadow_xp_total' => 'integer',
    'shadow_xp_spent' => 'integer',
    'shadow_xp_available' => 'integer',
];

$optional_fields = [
    'created_at' => 'timestamp',
    'updated_at' => 'timestamp',
];

$errors = [];
$warnings = [];
$info = [];

// Check required fields
foreach ($required_fields as $field => $type) {
    if (!array_key_exists($field, $character)) {
        $errors[] = "Missing required field: $field";
        continue;
    }
    
    $value = $character[$field];
    
    // Type validation
    switch ($type) {
        case 'integer':
            if (!is_int($value) && $value !== null && $value !== '') {
                $warnings[] = "Field '$field' should be integer, got: " . gettype($value);
            }
            break;
            
        case 'string':
            if (!is_string($value) && $value !== null && $value !== '') {
                $warnings[] = "Field '$field' should be string, got: " . gettype($value);
            }
            break;
            
        case 'date_string':
            if ($value !== null && $value !== '') {
                if (!is_string($value)) {
                    $warnings[] = "Field '$field' should be date string (YYYY-MM-DD), got: " . gettype($value);
                } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $warnings[] = "Field '$field' should be in YYYY-MM-DD format, got: '$value'";
                }
            }
            break;
            
        case 'json_object':
            if ($value !== null && !is_array($value)) {
                $warnings[] = "Field '$field' should be JSON object/array, got: " . gettype($value);
            }
            break;
            
        case 'json_array':
            if ($value !== null && !is_array($value)) {
                $warnings[] = "Field '$field' should be JSON array, got: " . gettype($value);
            } elseif (is_array($value) && !array_is_list($value)) {
                $warnings[] = "Field '$field' should be a list array, but appears to be an associative array";
            }
            break;
    }
}

// Validate status value
if (isset($character['status'])) {
    $valid_statuses = ['active', 'inactive', 'archived', 'dead', 'missing'];
    if (!in_array(strtolower($character['status']), $valid_statuses)) {
        $warnings[] = "Field 'status' has unusual value: '{$character['status']}' (expected: " . implode(', ', $valid_statuses) . ")";
    }
}

// Validate pc field
if (isset($character['pc'])) {
    if (!in_array($character['pc'], [0, 1], true)) {
        $warnings[] = "Field 'pc' should be 0 or 1, got: {$character['pc']}";
    }
}

// Validate JSON structure for complex fields
$json_structure_checks = [
    'abilities' => function($val) {
        if (!is_array($val)) return false;
        foreach ($val as $ability) {
            if (!is_array($ability) || !isset($ability['name']) || !isset($ability['category']) || !isset($ability['level'])) {
                return false;
            }
        }
        return true;
    },
    'fetters' => function($val) {
        if (!is_array($val)) return false;
        foreach ($val as $fetter) {
            if (!is_array($fetter) || !isset($fetter['name']) || !isset($fetter['rating'])) {
                return false;
            }
        }
        return true;
    },
    'passions' => function($val) {
        if (!is_array($val)) return false;
        foreach ($val as $passion) {
            if (!is_array($passion) || !isset($passion['passion']) || !isset($passion['rating'])) {
                return false;
            }
        }
        return true;
    },
    'arcanoi' => function($val) {
        if (!is_array($val)) return false;
        foreach ($val as $arcanoi) {
            if (!is_array($arcanoi) || !isset($arcanoi['name']) || !isset($arcanoi['rating']) || !isset($arcanoi['arts'])) {
                return false;
            }
        }
        return true;
    },
    'traits' => function($val) {
        if (!is_array($val)) return false;
        $required_categories = ['Physical', 'Social', 'Mental'];
        foreach ($required_categories as $cat) {
            if (!isset($val[$cat]) || !is_array($val[$cat])) {
                return false;
            }
        }
        return true;
    },
    'negativeTraits' => function($val) {
        if (!is_array($val)) return false;
        $required_categories = ['Physical', 'Social', 'Mental'];
        foreach ($required_categories as $cat) {
            if (!isset($val[$cat]) || !is_array($val[$cat])) {
                return false;
            }
        }
        return true;
    },
];

foreach ($json_structure_checks as $field => $check) {
    if (isset($character[$field]) && $character[$field] !== null) {
        if (!$check($character[$field])) {
            $errors[] = "Field '$field' has invalid structure";
        }
    }
}

// Check XP calculations
if (isset($character['experience_total']) && isset($character['spent_xp']) && isset($character['experience_unspent'])) {
    $calculated = $character['experience_total'] - $character['spent_xp'];
    if ($character['experience_unspent'] !== $calculated) {
        $warnings[] = "XP calculation mismatch: experience_unspent ({$character['experience_unspent']}) should equal experience_total ({$character['experience_total']}) - spent_xp ({$character['spent_xp']}) = $calculated";
    }
}

if (isset($character['shadow_xp_total']) && isset($character['shadow_xp_spent']) && isset($character['shadow_xp_available'])) {
    $calculated = $character['shadow_xp_total'] - $character['shadow_xp_spent'];
    if ($character['shadow_xp_available'] !== $calculated) {
        $warnings[] = "Shadow XP calculation mismatch: shadow_xp_available ({$character['shadow_xp_available']}) should equal shadow_xp_total ({$character['shadow_xp_total']}) - shadow_xp_spent ({$character['shadow_xp_spent']}) = $calculated";
    }
}

// Report results
if (empty($errors) && empty($warnings)) {
    echo "✅ VALIDATION PASSED\n\n";
    echo "All required fields are present and properly formatted.\n";
    echo "Character: " . ($character['character_name'] ?? 'Unknown') . "\n";
    echo "Status: " . ($character['status'] ?? 'Unknown') . "\n";
    echo "PC: " . ($character['pc'] == 1 ? 'Yes' : 'No') . "\n";
    exit(0);
}

if (!empty($errors)) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ VALIDATION FAILED\n";
    exit(1);
} else {
    echo "⚠️  VALIDATION PASSED WITH WARNINGS\n";
    exit(0);
}
?>

