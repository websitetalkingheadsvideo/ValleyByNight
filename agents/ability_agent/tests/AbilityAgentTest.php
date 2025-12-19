<?php
declare(strict_types=1);

/**
 * Unit Tests for AbilityAgent
 * 
 * Tests the core functionality of the Ability Agent including:
 * - Validation of ability names and categories
 * - Alias mapping
 * - Deprecated ability handling
 * - Category derivation
 * - Fuzzy matching
 * 
 * IMPORTANT: This file should only be run from command line, not via web browser.
 */

// Check if running from CLI first (before any includes)
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die("Access denied. This test must be run from command line.\n");
}

require_once __DIR__ . '/../src/AbilityAgent.php';
require_once __DIR__ . '/../src/AbilityRepository.php';
require_once __DIR__ . '/../src/AbilityValidator.php';
require_once __DIR__ . '/../src/AbilityMapper.php';

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
    }
}

class AbilityAgentTest extends TestCase
{
    /**
     * @var mysqli|null
     */
    protected $db;
    
    /**
     * @var AbilityAgent|null
     */
    protected $agent;
    
    /**
     * Setup test environment
     */
    public function setUp(): void
    {
        // Use project-standard DB connection
        $connectPath = __DIR__ . '/../../../includes/connect.php';
        if (file_exists($connectPath)) {
            require_once $connectPath;
            if (isset($conn) && $conn instanceof mysqli) {
                $this->db = $conn;
            }
        }
        
        if ($this->db === null) {
            $this->markTestSkipped("Database connection not available");
            return;
        }
        
        // Create agent with test config
        $testConfig = [
            'enabled' => true,
            'validation' => [
                'strict_mode' => false,
                'allow_unknown' => true,
                'auto_replace_deprecated' => false,
                'fuzzy_threshold' => 0.8
            ],
            'aliases' => [
                'Alertness' => 'Awareness',
                'Gunplay' => 'Firearms'
            ],
            'deprecations' => [
                'Old Ability' => [
                    'replacement' => 'New Ability',
                    'category' => 'Physical',
                    'reason' => 'Renamed in V5'
                ]
            ],
            'normalization' => [
                'case_sensitive' => false,
                'trim_whitespace' => true,
                'fuzzy_matching' => true
            ],
            'logging' => [
                'enabled' => false,
                'level' => 'info'
            ]
        ];
        
        $this->agent = new AbilityAgent($this->db, $testConfig);
    }
    
    /**
     * Test valid ability name + valid category
     */
    public function testValidAbilityNameAndCategory(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->validate([
            'name' => 'Athletics',
            'category' => 'Physical',
            'level' => 3
        ]);
        
        $this->assertTrue($result['isValid'], "Should validate valid ability");
        $this->assertEquals('Athletics', $result['normalizedAbility']['name']);
        $this->assertEquals('Physical', $result['normalizedAbility']['category']);
        $this->assertEquals(3, $result['normalizedAbility']['level']);
        $this->assertIsArray($result['issues']);
    }
    
    /**
     * Test valid ability name + wrong category (flag mismatch)
     */
    public function testValidAbilityNameWrongCategory(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->validate([
            'name' => 'Athletics',
            'category' => 'Social', // Wrong category
            'level' => 3
        ]);
        
        // Should still be valid but with warning
        $this->assertTrue($result['isValid'], "Should validate even with category mismatch");
        $this->assertEquals('Athletics', $result['normalizedAbility']['name']);
        $this->assertEquals('Physical', $result['normalizedAbility']['category']); // Corrected
        
        // Check for category mismatch issue
        $hasCategoryMismatch = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['code'] === 'CATEGORY_MISMATCH') {
                $hasCategoryMismatch = true;
                break;
            }
        }
        $this->assertTrue($hasCategoryMismatch, "Should flag category mismatch");
    }
    
    /**
     * Test deprecated ability (flag + replacement suggested/applied)
     */
    public function testDeprecatedAbility(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->map([
            'name' => 'Old Ability',
            'category' => 'Physical',
            'level' => 2
        ]);
        
        // Should map with deprecation warning
        $this->assertNotNull($result['canonicalAbility'], "Should map deprecated ability");
        
        // Check for deprecation issue
        $hasDeprecation = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['code'] === 'DEPRECATED_ABILITY') {
                $hasDeprecation = true;
                $this->assertEquals('Old Ability', $issue['metadata']['source_name']);
                $this->assertEquals('New Ability', $issue['metadata']['replacement']);
                break;
            }
        }
        $this->assertTrue($hasDeprecation, "Should flag deprecated ability");
    }
    
    /**
     * Test unknown ability (flag invalid)
     */
    public function testUnknownAbility(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->validate([
            'name' => 'Nonexistent Ability XYZ',
            'category' => 'Physical',
            'level' => 1
        ]);
        
        // Should be invalid or valid with warning depending on allow_unknown
        $hasUnknown = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['code'] === 'UNKNOWN_ABILITY') {
                $hasUnknown = true;
                break;
            }
        }
        $this->assertTrue($hasUnknown, "Should flag unknown ability");
    }
    
    /**
     * Test alias mapping (maps to canonical)
     */
    public function testAliasMapping(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->map([
            'name' => 'Alertness',
            'level' => 2
        ]);
        
        $this->assertNotNull($result['canonicalAbility'], "Should map alias");
        
        // Check for alias issue
        $hasAlias = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['code'] === 'ALIAS_MAPPED') {
                $hasAlias = true;
                $this->assertEquals('Alertness', $issue['metadata']['source_name']);
                $this->assertEquals('Awareness', $issue['metadata']['canonical_name']);
                break;
            }
        }
        $this->assertTrue($hasAlias, "Should flag alias mapping");
        
        // Check canonical name is used
        $this->assertEquals('Awareness', $result['canonicalAbility']['name']);
    }
    
    /**
     * Test case normalization
     */
    public function testCaseNormalization(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->validate([
            'name' => 'athletics', // lowercase
            'category' => 'physical', // lowercase
            'level' => 2
        ]);
        
        $this->assertTrue($result['isValid'], "Should normalize case");
        $this->assertEquals('Athletics', $result['normalizedAbility']['name']);
        $this->assertEquals('Physical', $result['normalizedAbility']['category']);
    }
    
    /**
     * Test whitespace normalization
     */
    public function testWhitespaceNormalization(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->validate([
            'name' => '  Athletics  ',
            'category' => '  Physical  ',
            'level' => 2
        ]);
        
        $this->assertTrue($result['isValid'], "Should normalize whitespace");
        $this->assertEquals('Athletics', $result['normalizedAbility']['name']);
        $this->assertEquals('Physical', $result['normalizedAbility']['category']);
    }
    
    /**
     * Test category derivation
     */
    public function testCategoryDerivation(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $result = $this->agent->map([
            'name' => 'Athletics',
            // No category provided
            'level' => 2
        ]);
        
        $this->assertNotNull($result['canonicalAbility'], "Should derive category");
        $this->assertEquals('Physical', $result['canonicalAbility']['category']);
    }
    
    /**
     * Test processAbilities with multiple abilities
     */
    public function testProcessAbilities(): void
    {
        $this->assertNotNull($this->agent, "Agent not initialized");
        
        $sourceAbilities = [
            ['name' => 'Athletics', 'category' => 'Physical', 'level' => 3],
            ['name' => 'Alertness', 'level' => 2], // Alias
            ['name' => 'Nonexistent Ability', 'category' => 'Physical', 'level' => 1] // Unknown
        ];
        
        $result = $this->agent->processAbilities($sourceAbilities);
        
        $this->assertIsArray($result['mappedAbilities']);
        $this->assertIsArray($result['allIssues']);
        $this->assertArrayHasKey('summary', $result);
        
        $this->assertEquals(3, $result['summary']['total']);
        $this->assertGreaterThan(0, $result['summary']['mapped']);
    }
    
    /**
     * Mark test as skipped
     */
    protected function markTestSkipped(string $message): void
    {
        echo "SKIP: {$message}\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new AbilityAgentTest();
    $test->setUp();
    
    $methods = get_class_methods($test);
    $testMethods = array_filter($methods, function($m) {
        return strpos($m, 'test') === 0;
    });
    
    echo "Running " . count($testMethods) . " tests...\n\n";
    
    $passed = 0;
    $failed = 0;
    
    foreach ($testMethods as $method) {
        try {
            $test->setUp(); // Reset for each test
            $test->$method();
            echo "✓ {$method}\n";
            $passed++;
        } catch (Exception $e) {
            echo "✗ {$method}: {$e->getMessage()}\n";
            $failed++;
        }
    }
    
    echo "\n";
    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n";
}

