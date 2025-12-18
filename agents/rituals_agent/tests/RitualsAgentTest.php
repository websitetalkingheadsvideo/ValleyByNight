<?php
declare(strict_types=1);

/**
 * Unit Tests for RitualsAgent
 * 
 * Tests the core functionality of the Rituals Agent including:
 * - Fetching rituals by ID and composite key
 * - Listing rituals with filters
 * - Getting character-known rituals
 * - Rule attachment (Necromancy and Thaumaturgy)
 * - Verifying no rules are duplicated into ritual records
 * - Verifying read-only behavior (no writes to character tables)
 */

require_once __DIR__ . '/../src/RitualsAgent.php';
require_once __DIR__ . '/../src/RitualRepository.php';
require_once __DIR__ . '/../src/CharacterRitualsRepository.php';
require_once __DIR__ . '/../src/RulesRepository.php';
require_once __DIR__ . '/../src/RitualRulesAttacher.php';

// Simple test framework if PHPUnit is not available
if (!class_exists('PHPUnit\Framework\TestCase')) {
    class TestCase
    {
        protected function assertTrue($condition, string $message = ''): void
        {
            if (!$condition) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected true"));
            }
        }
        
        protected function assertFalse($condition, string $message = ''): void
        {
            if (!$condition) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected false"));
            }
        }
        
        protected function assertEquals($expected, $actual, string $message = ''): void
        {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected {$expected}, got {$actual}"));
            }
        }
        
        protected function assertNotNull($value, string $message = ''): void
        {
            if ($value === null) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected not null"));
            }
        }
        
        protected function assertNull($value, string $message = ''): void
        {
            if ($value !== null) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected null"));
            }
        }
        
        protected function assertArrayHasKey($key, array $array, string $message = ''): void
        {
            if (!array_key_exists($key, $array)) {
                throw new Exception("Assertion failed: " . ($message ?: "Array does not have key {$key}"));
            }
        }
        
        protected function assertIsArray($value, string $message = ''): void
        {
            if (!is_array($value)) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected array"));
            }
        }
    }
}

class RitualsAgentTest extends TestCase
{
    /**
     * @var mysqli|null
     */
    protected $db;
    
    /**
     * @var RitualsAgent|null
     */
    protected $agent;
    
    /**
     * Set up test fixtures
     */
    public function setUp(): void
    {
        // Use real database connection for integration tests
        // In a full test suite, you'd use a test database or mocks
        try {
            require_once __DIR__ . '/../../../includes/connect.php';
            if (isset($conn) && $conn instanceof mysqli) {
                $this->db = $conn;
                $this->agent = new RitualsAgent($this->db);
            }
        } catch (Exception $e) {
            // Skip tests if DB connection not available
            $this->markTestSkipped("Database connection not available: " . $e->getMessage());
        }
    }
    
    /**
     * Test fetching ritual by ID
     */
    public function testGetRitualById(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Get first ritual from database
        $result = db_select($this->db, "SELECT id FROM rituals_master LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $ritualId = (int)$row['id'];
            
            $ritual = $this->agent->getRitualById($ritualId, false);
            
            $this->assertNotNull($ritual, "Ritual should be found");
            $this->assertArrayHasKey('id', $ritual);
            $this->assertArrayHasKey('name', $ritual);
            $this->assertArrayHasKey('type', $ritual);
            $this->assertArrayHasKey('level', $ritual);
            $this->assertEquals($ritualId, $ritual['id']);
        } else {
            $this->markTestSkipped("No rituals in database for testing");
        }
    }
    
    /**
     * Test fetching ritual by composite key (type, level, name)
     */
    public function testGetRitualByTypeLevelName(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Get first ritual from database
        $result = db_select($this->db, "SELECT type, level, name FROM rituals_master LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $type = $row['type'];
            $level = (int)$row['level'];
            $name = $row['name'];
            
            $ritual = $this->agent->getRitual($type, $level, $name, false);
            
            $this->assertNotNull($ritual, "Ritual should be found");
            $this->assertEquals($type, $ritual['type']);
            $this->assertEquals($level, $ritual['level']);
            $this->assertEquals($name, $ritual['name']);
        } else {
            $this->markTestSkipped("No rituals in database for testing");
        }
    }
    
    /**
     * Test listing rituals with filters
     */
    public function testListRitualsWithFilters(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Test listing all rituals
        $allRituals = $this->agent->listRituals(null, null, false, 10, 0);
        $this->assertIsArray($allRituals);
        
        // Test filtering by type
        $thaumaturgyRituals = $this->agent->listRituals('Thaumaturgy', null, false, 10, 0);
        $this->assertIsArray($thaumaturgyRituals);
        foreach ($thaumaturgyRituals as $ritual) {
            $this->assertEquals('Thaumaturgy', $ritual['type']);
        }
        
        // Test filtering by level
        $level1Rituals = $this->agent->listRituals(null, 1, false, 10, 0);
        $this->assertIsArray($level1Rituals);
        foreach ($level1Rituals as $ritual) {
            $this->assertEquals(1, $ritual['level']);
        }
        
        // Test filtering by both type and level
        $filteredRituals = $this->agent->listRituals('Thaumaturgy', 1, false, 10, 0);
        $this->assertIsArray($filteredRituals);
        foreach ($filteredRituals as $ritual) {
            $this->assertEquals('Thaumaturgy', $ritual['type']);
            $this->assertEquals(1, $ritual['level']);
        }
    }
    
    /**
     * Test getting known rituals for a character
     */
    public function testGetKnownRitualsForCharacter(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Check if character_rituals table has any data
        $result = db_select($this->db, "SELECT character_id FROM character_rituals LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $characterId = (int)$row['character_id'];
            
            $rituals = $this->agent->getKnownRitualsForCharacter($characterId, false);
            
            $this->assertIsArray($rituals);
            // Each ritual should have basic fields
            foreach ($rituals as $ritual) {
                $this->assertArrayHasKey('name', $ritual);
                $this->assertArrayHasKey('type', $ritual);
            }
        } else {
            // Test with non-existent character (should return empty array)
            $rituals = $this->agent->getKnownRitualsForCharacter(99999, false);
            $this->assertIsArray($rituals);
            $this->assertEquals(0, count($rituals));
        }
    }
    
    /**
     * Test rule attachment for Necromancy ritual
     */
    public function testAttachRulesNecromancy(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Find a Necromancy ritual
        $result = db_select($this->db, "SELECT id FROM rituals_master WHERE type = 'Necromancy' LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $ritualId = (int)$row['id'];
            
            $ritual = $this->agent->getRitualById($ritualId, true);
            
            $this->assertNotNull($ritual);
            $this->assertArrayHasKey('rules', $ritual);
            $this->assertIsArray($ritual['rules']);
            $this->assertArrayHasKey('global', $ritual['rules']);
            $this->assertArrayHasKey('tradition', $ritual['rules']);
            $this->assertIsArray($ritual['rules']['global']);
            $this->assertIsArray($ritual['rules']['tradition']);
            
            // Verify original ritual fields are unchanged
            $this->assertArrayHasKey('id', $ritual);
            $this->assertArrayHasKey('name', $ritual);
            $this->assertArrayHasKey('type', $ritual);
            $this->assertEquals('Necromancy', $ritual['type']);
        } else {
            $this->markTestSkipped("No Necromancy rituals in database for testing");
        }
    }
    
    /**
     * Test rule attachment for Thaumaturgy ritual
     */
    public function testAttachRulesThaumaturgy(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Find a Thaumaturgy ritual
        $result = db_select($this->db, "SELECT id FROM rituals_master WHERE type = 'Thaumaturgy' LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $ritualId = (int)$row['id'];
            
            $ritual = $this->agent->getRitualById($ritualId, true);
            
            $this->assertNotNull($ritual);
            $this->assertArrayHasKey('rules', $ritual);
            $this->assertIsArray($ritual['rules']);
            $this->assertArrayHasKey('global', $ritual['rules']);
            $this->assertArrayHasKey('tradition', $ritual['rules']);
            $this->assertIsArray($ritual['rules']['global']);
            $this->assertIsArray($ritual['rules']['tradition']);
            
            // Verify original ritual fields are unchanged
            $this->assertArrayHasKey('id', $ritual);
            $this->assertArrayHasKey('name', $ritual);
            $this->assertArrayHasKey('type', $ritual);
            $this->assertEquals('Thaumaturgy', $ritual['type']);
        } else {
            $this->markTestSkipped("No Thaumaturgy rituals in database for testing");
        }
    }
    
    /**
     * Test that rules are not duplicated into ritual records
     */
    public function testNoRulesDuplicated(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Get ritual without rules
        $result = db_select($this->db, "SELECT id FROM rituals_master LIMIT 1", '', []);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $ritualId = (int)$row['id'];
            
            $ritualWithoutRules = $this->agent->getRitualById($ritualId, false);
            $ritualWithRules = $this->agent->getRitualById($ritualId, true);
            
            // Verify ritual definition fields are identical
            $this->assertEquals($ritualWithoutRules['id'], $ritualWithRules['id']);
            $this->assertEquals($ritualWithoutRules['name'], $ritualWithRules['name']);
            $this->assertEquals($ritualWithoutRules['type'], $ritualWithRules['type']);
            $this->assertEquals($ritualWithoutRules['level'], $ritualWithRules['level']);
            $this->assertEquals($ritualWithoutRules['description'], $ritualWithRules['description']);
            
            // Verify rules are only in the version with rules
            $this->assertFalse(array_key_exists('rules', $ritualWithoutRules));
            $this->assertTrue(array_key_exists('rules', $ritualWithRules));
        } else {
            $this->markTestSkipped("No rituals in database for testing");
        }
    }
    
    /**
     * Test that no writes occur to character tables
     * This is a read-only verification test
     */
    public function testNoWritesToCharacterTables(): void
    {
        if (!$this->agent) {
            $this->markTestSkipped("Agent not initialized");
        }
        
        // Get count before operations
        $result = db_select($this->db, "SELECT COUNT(*) as count FROM character_rituals", '', []);
        $beforeCount = 0;
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $beforeCount = (int)$row['count'];
        }
        
        // Perform various read operations
        $this->agent->getKnownRitualsForCharacter(1, false);
        $this->agent->listRituals(null, null, false, 10, 0);
        $this->agent->getRitualRules('Thaumaturgy');
        
        // Get count after operations
        $result = db_select($this->db, "SELECT COUNT(*) as count FROM character_rituals", '', []);
        $afterCount = 0;
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $afterCount = (int)$row['count'];
        }
        
        // Count should be unchanged (read-only)
        $this->assertEquals($beforeCount, $afterCount, "Character rituals table should not be modified");
    }
    
    /**
     * Helper method to mark test as skipped
     */
    protected function markTestSkipped(string $message): void
    {
        echo "SKIPPED: {$message}\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new RitualsAgentTest();
    $test->setUp();
    
    $tests = [
        'testGetRitualById',
        'testGetRitualByTypeLevelName',
        'testListRitualsWithFilters',
        'testGetKnownRitualsForCharacter',
        'testAttachRulesNecromancy',
        'testAttachRulesThaumaturgy',
        'testNoRulesDuplicated',
        'testNoWritesToCharacterTables'
    ];
    
    $passed = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($tests as $testMethod) {
        try {
            $test->$testMethod();
            echo "PASS: {$testMethod}\n";
            $passed++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'SKIPPED') === 0) {
                echo "{$e->getMessage()}\n";
                $skipped++;
            } else {
                echo "FAIL: {$testMethod} - {$e->getMessage()}\n";
                $failed++;
            }
        }
    }
    
    echo "\nResults: {$passed} passed, {$failed} failed, {$skipped} skipped\n";
    exit($failed > 0 ? 1 : 0);
}

