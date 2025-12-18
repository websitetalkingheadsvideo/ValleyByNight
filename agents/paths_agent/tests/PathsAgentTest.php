<?php
declare(strict_types=1);

/**
 * Unit Tests for PathsAgent
 * 
 * Tests the core functionality of the Paths Agent including:
 * - Fetching paths by ID and type
 * - Listing paths with filters
 * - Getting path powers
 * - Getting character paths with ratings
 * - Rating gate evaluation (canUsePathPower)
 * - Challenge metadata in all responses
 * - Verifying read-only behavior (only reads from allowed tables)
 */

require_once __DIR__ . '/../src/PathsAgent.php';
require_once __DIR__ . '/../src/PathRepository.php';
require_once __DIR__ . '/../src/PathPowersRepository.php';
require_once __DIR__ . '/../src/CharacterPathsRepository.php';

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
        
        protected function assertContains($needle, $haystack, string $message = ''): void
        {
            if (is_array($haystack)) {
                if (!in_array($needle, $haystack)) {
                    throw new Exception("Assertion failed: " . ($message ?: "Array does not contain {$needle}"));
                }
            } else {
                if (strpos($haystack, $needle) === false) {
                    throw new Exception("Assertion failed: " . ($message ?: "String does not contain {$needle}"));
                }
            }
        }
    }
}

class PathsAgentTest extends TestCase
{
    /**
     * @var mysqli|null
     */
    protected $db;
    
    /**
     * @var PathsAgent|null
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
                $this->agent = new PathsAgent($this->db);
            } else {
                throw new Exception('Database connection not available');
            }
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }
    }
    
    /**
     * Test that agent can be instantiated
     */
    public function testAgentInstantiation(): void
    {
        $this->assertNotNull($this->agent, 'Agent should be instantiated');
    }
    
    /**
     * Test listing paths by type
     */
    public function testListPathsByType(): void
    {
        $result = $this->agent->listPathsByType('Necromancy', 10, 0);
        
        $this->assertIsArray($result, 'Result should be an array');
        $this->assertArrayHasKey('paths', $result, 'Result should have paths key');
        $this->assertArrayHasKey('metadata', $result, 'Result should have metadata key');
        $this->assertIsArray($result['paths'], 'Paths should be an array');
        $this->assertIsArray($result['metadata'], 'Metadata should be an array');
        
        // Verify challenge metadata
        $metadata = $result['metadata'];
        $this->assertArrayHasKey('challenge', $metadata);
        $this->assertEquals('TM-03', $metadata['challenge']['code']);
        $this->assertArrayHasKey('sourcesRead', $metadata);
        $this->assertContains('paths_master', $metadata['sourcesRead']);
        $this->assertArrayHasKey('gating', $metadata);
        $this->assertEquals('rating-only', $metadata['gating']['type']);
        $this->assertFalse($metadata['gating']['ritualLogicIncluded']);
    }
    
    /**
     * Test listing all paths (no type filter)
     */
    public function testListAllPaths(): void
    {
        $result = $this->agent->listPathsByType(null, 10, 0);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertArrayHasKey('metadata', $result);
    }
    
    /**
     * Test getting path powers
     */
    public function testGetPathPowers(): void
    {
        // First, get a path ID
        $pathsResult = $this->agent->listPathsByType('Necromancy', 1, 0);
        
        if (empty($pathsResult['paths'])) {
            $this->markTestSkipped('No paths found in database');
            return;
        }
        
        $pathId = (int)$pathsResult['paths'][0]['id'];
        
        $result = $this->agent->getPathPowers($pathId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('powers', $result);
        $this->assertArrayHasKey('path_id', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals($pathId, $result['path_id']);
        $this->assertIsArray($result['powers']);
        
        // Verify metadata includes both sources
        $metadata = $result['metadata'];
        $this->assertContains('paths_master', $metadata['sourcesRead']);
        $this->assertContains('path_powers', $metadata['sourcesRead']);
    }
    
    /**
     * Test getting character paths
     */
    public function testGetCharacterPaths(): void
    {
        // Get a character ID from the database
        $charQuery = "SELECT id FROM characters LIMIT 1";
        $charResult = mysqli_query($this->db, $charQuery);
        
        if (!$charResult || mysqli_num_rows($charResult) === 0) {
            $this->markTestSkipped('No characters found in database');
            return;
        }
        
        $charRow = mysqli_fetch_assoc($charResult);
        $characterId = (int)$charRow['id'];
        mysqli_free_result($charResult);
        
        $result = $this->agent->getCharacterPaths($characterId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('character_id', $result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals($characterId, $result['character_id']);
        $this->assertIsArray($result['paths']);
        
        // Verify metadata
        $metadata = $result['metadata'];
        $this->assertContains('paths_master', $metadata['sourcesRead']);
        $this->assertContains('character_paths', $metadata['sourcesRead']);
    }
    
    /**
     * Test canUsePathPower - rating gate logic
     */
    public function testCanUsePathPower(): void
    {
        // Get a power ID
        $pathsResult = $this->agent->listPathsByType('Necromancy', 1, 0);
        
        if (empty($pathsResult['paths'])) {
            $this->markTestSkipped('No paths found in database');
            return;
        }
        
        $pathId = (int)$pathsResult['paths'][0]['id'];
        $powersResult = $this->agent->getPathPowers($pathId);
        
        if (empty($powersResult['powers'])) {
            $this->markTestSkipped('No powers found for path');
            return;
        }
        
        $powerId = (int)$powersResult['powers'][0]['id'];
        $requiredRating = (int)$powersResult['powers'][0]['level'];
        
        // Get a character ID
        $charQuery = "SELECT id FROM characters LIMIT 1";
        $charResult = mysqli_query($this->db, $charQuery);
        
        if (!$charResult || mysqli_num_rows($charResult) === 0) {
            $this->markTestSkipped('No characters found in database');
            return;
        }
        
        $charRow = mysqli_fetch_assoc($charResult);
        $characterId = (int)$charRow['id'];
        mysqli_free_result($charResult);
        
        $result = $this->agent->canUsePathPower($characterId, $powerId);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('canUse', $result);
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertArrayHasKey('requiredRating', $result);
        $this->assertArrayHasKey('characterRating', $result);
        $this->assertArrayHasKey('powerId', $result);
        $this->assertArrayHasKey('pathId', $result);
        $this->assertArrayHasKey('metadata', $result);
        
        $this->assertIsBool($result['canUse']);
        $this->assertEquals($powerId, $result['powerId']);
        $this->assertEquals($requiredRating, $result['requiredRating']);
        
        // Verify metadata includes additional fields
        $metadata = $result['metadata'];
        $this->assertArrayHasKey('requiredRating', $metadata);
        $this->assertArrayHasKey('characterRating', $metadata);
        $this->assertArrayHasKey('powerId', $metadata);
        $this->assertArrayHasKey('pathId', $metadata);
    }
    
    /**
     * Test canUsePathPower with non-existent power
     */
    public function testCanUsePathPowerNotFound(): void
    {
        $charQuery = "SELECT id FROM characters LIMIT 1";
        $charResult = mysqli_query($this->db, $charQuery);
        
        if (!$charResult || mysqli_num_rows($charResult) === 0) {
            $this->markTestSkipped('No characters found in database');
            return;
        }
        
        $charRow = mysqli_fetch_assoc($charResult);
        $characterId = (int)$charRow['id'];
        mysqli_free_result($charResult);
        
        // Use a very large power ID that shouldn't exist
        $result = $this->agent->canUsePathPower($characterId, 999999);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['canUse']);
        $this->assertNotNull($result['reasoning']);
        $this->assertNull($result['requiredRating']);
        $this->assertNull($result['characterRating']);
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new PathsAgentTest();
    $test->setUp();
    
    $tests = [
        'testAgentInstantiation',
        'testListPathsByType',
        'testListAllPaths',
        'testGetPathPowers',
        'testGetCharacterPaths',
        'testCanUsePathPower',
        'testCanUsePathPowerNotFound'
    ];
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $testMethod) {
        try {
            $test->$testMethod();
            echo "✓ {$testMethod}\n";
            $passed++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Skipped') !== false) {
                echo "⊘ {$testMethod} (skipped: " . $e->getMessage() . ")\n";
            } else {
                echo "✗ {$testMethod}: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
    }
    
    echo "\nResults: {$passed} passed, {$failed} failed\n";
    exit($failed > 0 ? 1 : 0);
}

