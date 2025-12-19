<?php
declare(strict_types=1);

/**
 * Integration Tests for DisciplineAgent
 * 
 * Tests the Discipline Agent with real database interactions.
 * Verifies that paths and rituals are excluded from all operations.
 * 
 * IMPORTANT: This file should only be run from command line, not via web browser.
 */

// Check if running from CLI first (before any includes)
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied. This test must be run from command line.\n");
}

require_once __DIR__ . '/../src/DisciplineAgent.php';

echo "DisciplineAgent Integration Tests\n";
echo "=================================\n\n";

// Get database connection
$connectPath = __DIR__ . '/../../../includes/connect.php';
if (!file_exists($connectPath)) {
    die("ERROR: Database connection file not found: {$connectPath}\n");
}

require_once $connectPath;

if (!isset($conn) || !$conn instanceof mysqli) {
    die("ERROR: Database connection not available\n");
}

try {
    $agent = new DisciplineAgent($conn);
    echo "✓ DisciplineAgent instantiated successfully\n\n";
} catch (Exception $e) {
    die("ERROR: Failed to create DisciplineAgent: {$e->getMessage()}\n");
}

// Test 1: Verify agent can list disciplines
echo "Test 1: List Character Disciplines\n";
echo "-----------------------------------\n";
try {
    // Get a test character ID (use first character in database)
    $charResult = db_fetch_one($conn, "SELECT id FROM characters LIMIT 1");
    
    if ($charResult === null) {
        echo "⚠ No characters found in database, skipping list test\n\n";
    } else {
        $characterId = (int)$charResult['id'];
        $result = $agent->listCharacterDisciplines($characterId);
        
        echo "Character ID: {$characterId}\n";
        echo "Disciplines found: " . count($result['disciplines']) . "\n";
        
        // Verify no paths/rituals
        $hasPath = false;
        foreach ($result['disciplines'] as $discipline) {
            $name = $discipline['discipline_name'] ?? '';
            if (stripos($name, 'Path of') === 0) {
                $hasPath = true;
                echo "✗ ERROR: Found path in disciplines: {$name}\n";
            }
        }
        
        if (!$hasPath) {
            echo "✓ No paths found in discipline list\n";
        }
        
        echo "\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n\n";
}

// Test 2: Verify dot validation
echo "Test 2: Dot Range Validation\n";
echo "-----------------------------\n";
try {
    $updates = [
        'Celerity' => 3,
        'Potence' => 5,
        'Presence' => 0
    ];
    
    $result = $agent->validateDisciplineDots(1, $updates);
    
    if ($result['isValid']) {
        echo "✓ Valid dot ranges passed validation\n";
    } else {
        echo "✗ Valid dot ranges failed validation\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error['message']}\n";
        }
    }
    
    // Test invalid ranges
    $invalidUpdates = [
        'Celerity' => 6,
        'Potence' => -1
    ];
    
    $invalidResult = $agent->validateDisciplineDots(1, $invalidUpdates);
    
    if (!$invalidResult['isValid']) {
        echo "✓ Invalid dot ranges correctly rejected\n";
    } else {
        echo "✗ Invalid dot ranges incorrectly accepted\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n\n";
}

// Test 3: Verify path exclusion
echo "Test 3: Path/Ritual Exclusion\n";
echo "------------------------------\n";
try {
    // Try to validate a path
    $pathUpdates = [
        'Path of Blood' => 3
    ];
    
    $result = $agent->validateDisciplineDots(1, $pathUpdates);
    
    $hasPathError = false;
    foreach ($result['errors'] as $error) {
        if (($error['code'] ?? '') === 'INVALID_DISCIPLINE_TYPE') {
            $hasPathError = true;
            echo "✓ Path correctly rejected with INVALID_DISCIPLINE_TYPE error\n";
            break;
        }
    }
    
    if (!$hasPathError) {
        echo "✗ Path was not rejected\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n\n";
}

// Test 4: Verify clan access validation
echo "Test 4: Clan Access Validation\n";
echo "-------------------------------\n";
try {
    $charResult = db_fetch_one($conn, "SELECT id, clan FROM characters LIMIT 1");
    
    if ($charResult === null) {
        echo "⚠ No characters found, skipping clan access test\n\n";
    } else {
        $characterId = (int)$charResult['id'];
        $clan = $charResult['clan'] ?? 'Unknown';
        
        echo "Character ID: {$characterId}, Clan: {$clan}\n";
        
        // Test with a common discipline
        $result = $agent->validateClanDisciplineAccess($characterId, 'Celerity');
        
        echo "Has Access: " . ($result['hasAccess'] ? 'Yes' : 'No') . "\n";
        echo "Is In-Clan: " . ($result['isInClan'] ? 'Yes' : 'No') . "\n";
        echo "Restrictions: " . count($result['restrictions']) . "\n";
        
        echo "✓ Clan access validation completed\n\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n\n";
}

echo "Integration tests completed.\n";

