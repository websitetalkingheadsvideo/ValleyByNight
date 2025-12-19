<?php
declare(strict_types=1);

/**
 * Integration Test for AbilityAgent with Character Import Workflow
 * 
 * Tests the full integration: character JSON import → AbilityAgent → database storage
 * 
 * IMPORTANT: This file should only be run from command line, not via web browser.
 */

// Check if running from CLI first (before any includes)
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied. This test must be run from command line.\n");
}

// Fix path: from agents/ability_agent/tests/ we need to go up 3 levels to project root
require_once __DIR__ . '/../../../database/import_characters.php';
require_once __DIR__ . '/../src/AbilityAgent.php';

echo "AbilityAgent Integration Test\n";
echo "============================\n\n";

// Test database connection
require_once __DIR__ . '/../../../includes/connect.php';

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error() . "\n");
}

echo "✓ Database connection established\n\n";

// Test 1: Sample character JSON with various ability formats
echo "Test 1: Import character with mixed ability formats\n";
echo "----------------------------------------------------\n";

$testCharacter = [
    'character_name' => 'Test Character Integration',
    'player_name' => 'Test',
    'chronicle' => 'Test Chronicle',
    'abilities' => [
        // Format 1: Array of objects
        ['name' => 'Athletics', 'category' => 'Physical', 'level' => 3],
        ['name' => 'Alertness', 'level' => 2], // Will be aliased to Awareness
        ['name' => 'Firearms', 'category' => 'Physical', 'level' => 4],
        // This will test validation and mapping
        ['name' => '  Occult  ', 'category' => '  Mental  ', 'level' => 2], // Whitespace test
    ],
    'specializations' => [
        'Athletics' => 'Running',
        'Firearms' => 'Pistols'
    ]
];

// Create temporary JSON file
$tempFile = sys_get_temp_dir() . '/test_character_integration.json';
file_put_contents($tempFile, json_encode($testCharacter, JSON_PRETTY_PRINT));

try {
    $stats = [
        'processed' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'import_issues' => []
    ];
    
    // Import the test character
    $result = importCharacterFile($conn, $tempFile, $stats);
    
    if ($result) {
        echo "✓ Character imported successfully\n";
        echo "  - Processed: {$stats['processed']}\n";
        echo "  - Inserted: {$stats['inserted']}\n";
    } else {
        echo "✗ Character import failed\n";
        if (!empty($stats['errors'])) {
            foreach ($stats['errors'] as $error) {
                echo "  Error: {$error}\n";
            }
        }
    }
    
    // Check import issues
    if (!empty($stats['import_issues'])) {
        echo "\n  Import Issues:\n";
        foreach ($stats['import_issues'] as $item) {
            $issue = $item['issue'];
            echo "    [{$issue['severity']}] {$issue['message']}\n";
            if (isset($issue['metadata']['source_name'])) {
                echo "      Source: {$issue['metadata']['source_name']}";
                if (isset($issue['metadata']['canonical_name']) && $issue['metadata']['canonical_name'] !== $issue['metadata']['source_name']) {
                    echo " → {$issue['metadata']['canonical_name']}";
                }
                echo "\n";
            }
        }
    } else {
        echo "  ✓ No import issues (all abilities validated)\n";
    }
    
    // Verify abilities were stored correctly
    $characterId = db_fetch_one($conn, 
        "SELECT id FROM characters WHERE character_name = ? LIMIT 1",
        's',
        ['Test Character Integration']
    );
    
    if ($characterId) {
        $storedAbilities = db_fetch_all($conn,
            "SELECT ability_name, ability_category, level, specialization 
             FROM character_abilities 
             WHERE character_id = ? 
             ORDER BY ability_name",
            'i',
            [$characterId['id']]
        );
        
        echo "\n  Stored Abilities:\n";
        foreach ($storedAbilities as $ability) {
            echo "    - {$ability['ability_name']} ({$ability['ability_category']}) x{$ability['level']}";
            if (!empty($ability['specialization'])) {
                echo " [{$ability['specialization']}]";
            }
            echo "\n";
        }
        
        // Verify canonical names were used
        $expectedCanonical = ['Athletics', 'Awareness', 'Firearms', 'Occult'];
        $actualNames = array_column($storedAbilities, 'ability_name');
        
        $allMatch = true;
        foreach ($expectedCanonical as $expected) {
            if (!in_array($expected, $actualNames, true)) {
                echo "    ✗ Expected ability '{$expected}' not found\n";
                $allMatch = false;
            }
        }
        
        if ($allMatch) {
            echo "\n  ✓ All abilities stored with canonical names\n";
            echo "  ✓ Alertness was correctly aliased to Awareness\n";
        }
        
        // Cleanup: Delete test character
        db_execute($conn, "DELETE FROM character_abilities WHERE character_id = ?", 'i', [$characterId['id']]);
        db_execute($conn, "DELETE FROM characters WHERE id = ?", 'i', [$characterId['id']]);
        echo "\n  ✓ Test character cleaned up\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: {$e->getMessage()}\n";
} finally {
    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

// Test 2: Test with unknown/deprecated abilities
echo "\n\nTest 2: Import character with unknown/deprecated abilities\n";
echo "------------------------------------------------------------\n";

$testCharacter2 = [
    'character_name' => 'Test Character Issues',
    'player_name' => 'Test',
    'chronicle' => 'Test Chronicle',
    'abilities' => [
        ['name' => 'Valid Ability', 'category' => 'Physical', 'level' => 2],
        ['name' => 'Unknown Ability XYZ', 'category' => 'Physical', 'level' => 1], // Unknown
        ['name' => 'Old Ability', 'category' => 'Physical', 'level' => 2], // Deprecated (if configured)
    ]
];

$tempFile2 = sys_get_temp_dir() . '/test_character_issues.json';
file_put_contents($tempFile2, json_encode($testCharacter2, JSON_PRETTY_PRINT));

try {
    $stats2 = [
        'processed' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'import_issues' => []
    ];
    
    $result2 = importCharacterFile($conn, $tempFile2, $stats2);
    
    if ($result2) {
        echo "✓ Character imported (with expected issues)\n";
        
        if (!empty($stats2['import_issues'])) {
            echo "\n  Import Issues Detected:\n";
            $issueCounts = ['error' => 0, 'warning' => 0, 'info' => 0];
            foreach ($stats2['import_issues'] as $item) {
                $issue = $item['issue'];
                $severity = $issue['severity'] ?? 'info';
                $issueCounts[$severity] = ($issueCounts[$severity] ?? 0) + 1;
            }
            echo "    - Errors: {$issueCounts['error']}\n";
            echo "    - Warnings: {$issueCounts['warning']}\n";
            echo "    - Info: {$issueCounts['info']}\n";
            
            // Verify we detected unknown ability
            $hasUnknown = false;
            foreach ($stats2['import_issues'] as $item) {
                if ($item['issue']['code'] === 'UNKNOWN_ABILITY') {
                    $hasUnknown = true;
                    echo "\n  ✓ Unknown ability correctly flagged\n";
                    break;
                }
            }
            
            if (!$hasUnknown) {
                echo "\n  ⚠ Unknown ability issue not detected (may be allowed by config)\n";
            }
        }
        
        // Cleanup
        $characterId2 = db_fetch_one($conn, 
            "SELECT id FROM characters WHERE character_name = ? LIMIT 1",
            's',
            ['Test Character Issues']
        );
        
        if ($characterId2) {
            db_execute($conn, "DELETE FROM character_abilities WHERE character_id = ?", 'i', [$characterId2['id']]);
            db_execute($conn, "DELETE FROM characters WHERE id = ?", 'i', [$characterId2['id']]);
        }
    }
    
} catch (Exception $e) {
    echo "✗ Exception: {$e->getMessage()}\n";
} finally {
    if (file_exists($tempFile2)) {
        unlink($tempFile2);
    }
}

echo "\n";
echo "=== Integration Test Complete ===\n";
mysqli_close($conn);

