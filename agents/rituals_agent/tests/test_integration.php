<?php
require_once __DIR__ . '/../src/RitualsAgent.php';

try {
    $agent = new RitualsAgent();
    echo "Agent initialized successfully\n";
    
    // Test basic fetch
    $ritual = $agent->getRitualById(1, false);
    if ($ritual) {
        echo "Test fetch by ID: OK\n";
        echo "  Ritual: {$ritual['name']} ({$ritual['type']} {$ritual['level']})\n";
    } else {
        echo "Test fetch by ID: No ritual found (table may be empty)\n";
    }
    
    // Test listing
    $rituals = $agent->listRituals(null, null, false, 5, 0);
    echo "Test listing: Found " . count($rituals) . " rituals\n";
    
    echo "\nAll basic tests passed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

