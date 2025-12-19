<?php
declare(strict_types=1);

/**
 * Unit Tests for DisciplineAgent
 * 
 * Tests the core functionality of the Discipline Agent including:
 * - Discipline listing (innate only, excludes paths/rituals)
 * - Dot range validation (0-5)
 * - Clan access validation
 * - Power eligibility validation
 * - Path/ritual exclusion (CRITICAL)
 * 
 * IMPORTANT: This file should only be run from command line, not via web browser.
 */

// Check if running from CLI first (before any includes)
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied. This test must be run from command line.\n");
}

require_once __DIR__ . '/../src/DisciplineAgent.php';
require_once __DIR__ . '/../src/DisciplineRepository.php';
require_once __DIR__ . '/../src/DisciplinePowersRepository.php';
require_once __DIR__ . '/../src/ClanAccessRepository.php';
require_once __DIR__ . '/../src/DisciplineValidator.php';

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
            if ($condition) {
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
        
        protected function assertCount(int $expected, array $array, string $message = ''): void
        {
            $actual = count($array);
            if ($actual !== $expected) {
                throw new Exception("Assertion failed: " . ($message ?: "Expected count {$expected}, got {$actual}"));
            }
        }
    }
}

class DisciplineAgentTest extends TestCase
{
    /**
     * @var mysqli|null
     */
    protected $db;
    
    /**
     * @var DisciplineAgent|null
     */
    protected $agent;
    
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        // Try to get database connection
        $connectPath = __DIR__ . '/../../../includes/connect.php';
        if (file_exists($connectPath)) {
            require_once $connectPath;
            if (isset($conn) && $conn instanceof mysqli) {
                $this->db = $conn;
                $this->agent = new DisciplineAgent($this->db);
            }
        }
        
        // Skip tests if no database connection
        if ($this->db === null || $this->agent === null) {
            $this->markTestSkipped('Database connection not available');
        }
    }
    
    /**
     * Test that listCharacterDisciplines returns only innate disciplines
     */
    public function testListCharacterDisciplinesExcludesPaths(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        // This test assumes a test character exists
        // In a real scenario, you'd create a test character first
        $result = $this->agent->listCharacterDisciplines(1);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('disciplines', $result);
        $this->assertArrayHasKey('summary', $result);
        
        // Verify no paths are returned
        foreach ($result['disciplines'] as $discipline) {
            $name = $discipline['discipline_name'] ?? '';
            $this->assertFalse(
                stripos($name, 'Path of') === 0,
                "Discipline '{$name}' appears to be a path and should be excluded"
            );
        }
    }
    
    /**
     * Test dot range validation - valid ranges
     */
    public function testValidateDisciplineDotsValidRanges(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        $updates = [
            'Celerity' => 0,
            'Potence' => 1,
            'Presence' => 3,
            'Fortitude' => 5
        ];
        
        $result = $this->agent->validateDisciplineDots(1, $updates);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isValid', $result);
        $this->assertTrue($result['isValid'], 'Valid dot ranges should pass validation');
        $this->assertIsArray($result['errors']);
        $this->assertCount(0, $result['errors'], 'No errors should be present for valid ranges');
    }
    
    /**
     * Test dot range validation - invalid ranges (too high)
     */
    public function testValidateDisciplineDotsTooHigh(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        $updates = [
            'Celerity' => 6,
            'Potence' => 10
        ];
        
        $result = $this->agent->validateDisciplineDots(1, $updates);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isValid', $result);
        $this->assertFalse($result['isValid'], 'Invalid dot ranges should fail validation');
        $this->assertIsArray($result['errors']);
        $this->assertGreaterThan(0, count($result['errors']), 'Errors should be present for invalid ranges');
    }
    
    /**
     * Test dot range validation - invalid types
     */
    public function testValidateDisciplineDotsInvalidType(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        $updates = [
            'Celerity' => '5', // String instead of int
            'Potence' => 3.5,  // Float instead of int
            'Presence' => null
        ];
        
        $result = $this->agent->validateDisciplineDots(1, $updates);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isValid', $result);
        // String "5" should be accepted (ctype_digit check), but float and null should fail
        $this->assertIsArray($result['errors']);
    }
    
    /**
     * Test clan access validation - in-clan discipline
     */
    public function testValidateClanDisciplineAccessInClan(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        // Test with a Brujah character (Celerity, Potence, Presence are in-clan)
        $result = $this->agent->validateClanDisciplineAccess(1, 'Celerity');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('hasAccess', $result);
        $this->assertArrayHasKey('isInClan', $result);
        $this->assertArrayHasKey('restrictions', $result);
        
        // Note: Actual result depends on character's clan in database
        // This test verifies the structure, not the specific values
    }
    
    /**
     * Test path/ritual exclusion - paths are rejected
     */
    public function testPathExclusion(): void
    {
        if ($this->agent === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        // Try to validate a path as a discipline
        $updates = [
            'Path of Blood' => 3
        ];
        
        $result = $this->agent->validateDisciplineDots(1, $updates);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        
        // Should have error about invalid discipline type
        $hasPathError = false;
        foreach ($result['errors'] as $error) {
            if (($error['code'] ?? '') === 'INVALID_DISCIPLINE_TYPE') {
                $hasPathError = true;
                break;
            }
        }
        
        $this->assertTrue($hasPathError, 'Path should be rejected with INVALID_DISCIPLINE_TYPE error');
    }
    
    /**
     * Test that isInnateDiscipline correctly identifies paths
     */
    public function testIsInnateDisciplineRejectsPaths(): void
    {
        if ($this->agent === null || $this->db === null) {
            $this->markTestSkipped('Agent not available');
            return;
        }
        
        // Load config from file
        $configPath = __DIR__ . '/../config/settings.json';
        $config = [];
        if (file_exists($configPath)) {
            $configContent = file_get_contents($configPath);
            $config = json_decode($configContent, true) ?: [];
        }
        
        $repository = new DisciplineRepository($this->db, $config);
        
        // These should be rejected (paths)
        $this->assertFalse($repository->isInnateDiscipline('Path of Blood'));
        $this->assertFalse($repository->isInnateDiscipline('Path of Geomancy'));
        
        // These should be accepted (innate disciplines)
        $this->assertTrue($repository->isInnateDiscipline('Celerity'));
        $this->assertTrue($repository->isInnateDiscipline('Potence'));
        $this->assertTrue($repository->isInnateDiscipline('Thaumaturgy')); // Blood sorcery discipline, not a path
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new DisciplineAgentTest();
    $test->setUp();
    
    $methods = get_class_methods($test);
    $testMethods = array_filter($methods, function($method) {
        return strpos($method, 'test') === 0;
    });
    
    $passed = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($testMethods as $method) {
        try {
            $test->setUp();
            $test->$method();
            echo "✓ {$method}\n";
            $passed++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Skipped') !== false) {
                echo "- {$method} (skipped)\n";
                $skipped++;
            } else {
                echo "✗ {$method}: {$e->getMessage()}\n";
                $failed++;
            }
        }
    }
    
    echo "\n";
    echo "Results: {$passed} passed, {$failed} failed, {$skipped} skipped\n";
    exit($failed > 0 ? 1 : 0);
}

