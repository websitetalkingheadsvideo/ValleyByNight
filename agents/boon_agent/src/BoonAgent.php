<?php
/**
 * BoonAgent for Valley by Night
 * 
 * Monitors and validates boons according to Laws of the Night Revised mechanics.
 * Tracks favor-debt, detects violations, integrates with Harpy systems, and analyzes
 * the social economy of prestation.
 * 
 * Integration notes:
 * - Expects a MySQL connection `$conn` created in connect.php (mysqli object).
 * - Uses the `boons` table for boon data.
 * - Uses the `characters` table for character validation.
 * - Designed to be called from an Agent page in the admin panel.
 */

class BoonAgent
{
    /**
     * @var mysqli
     */
    protected $db;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @var BoonValidator
     */
    protected $validator;
    
    /**
     * @var BoonAnalyzer
     */
    protected $analyzer;
    
    /**
     * @var ReportGenerator
     */
    protected $reporter;
    
    /**
     * BoonAgent constructor.
     * 
     * If a DB handle is not passed in, this will include connect.php
     * and expect it to define `$conn` (mysqli).
     * 
     * @param mysqli|null $db
     * @param array|null $config
     * @throws Exception
     */
    public function __construct($db = null, array $config = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            // Use project-standard DB connection
            $connectPath = __DIR__ . '/../../../includes/connect.php';
            if (!file_exists($connectPath)) {
                throw new Exception("Database connection file not found: {$connectPath}");
            }
            require_once $connectPath;
            if (!isset($conn)) {
                throw new Exception('connect.php did not define $conn (mysqli). Check database configuration.');
            }
            if (!$conn instanceof mysqli) {
                throw new Exception('$conn is not a mysqli object. Database connection failed.');
            }
            $this->db = $conn;
        }
        
        // Load configuration
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->loadConfig();
        }
        
        // Initialize components
        require_once __DIR__ . '/BoonValidator.php';
        require_once __DIR__ . '/BoonAnalyzer.php';
        require_once __DIR__ . '/ReportGenerator.php';
        
        $this->validator = new BoonValidator($this->db, $this->config);
        $this->analyzer = new BoonAnalyzer($this->db, $this->config);
        $this->reporter = new ReportGenerator($this->db, $this->config);
    }
    
    /**
     * Load configuration from settings.json
     */
    protected function loadConfig()
    {
        $configPath = __DIR__ . '/../config/settings.json';
        if (file_exists($configPath)) {
            $configContent = file_get_contents($configPath);
            $this->config = json_decode($configContent, true);
        } else {
            $this->config = [];
        }
    }
    
    /**
     * Main entry point to validate all boons
     * 
     * @return array Validation results
     */
    public function validateAllBoons(): array
    {
        if (!($this->config['validation']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Validation is disabled'];
        }
        
        $boons = $this->getAllBoons();
        $results = [];
        
        foreach ($boons as $boon) {
            $validation = $this->validator->validateBoon($boon);
            if (!$validation['valid']) {
                $results[] = [
                    'boon_id' => $boon['boon_id'],
                    'errors' => $validation['errors'],
                    'boon' => $boon
                ];
            }
        }
        
        return [
            'success' => true,
            'total_boons' => count($boons),
            'violations' => count($results),
            'results' => $results
        ];
    }
    
    /**
     * Analyze boon economy and detect issues
     * 
     * @return array Analysis results
     */
    public function analyzeBoonEconomy(): array
    {
        if (!($this->config['analysis']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Analysis is disabled'];
        }
        
        return $this->analyzer->analyzeEconomy();
    }
    
    /**
     * Detect dead debts (boons owed by deceased characters)
     * 
     * @return array Dead debt results
     */
    public function detectDeadDebts(): array
    {
        if (!($this->config['monitoring']['dead_debt_detection']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Dead debt detection is disabled'];
        }
        
        return $this->analyzer->findDeadDebts();
    }
    
    /**
     * Detect unregistered boons
     * 
     * @return array Unregistered boons
     */
    public function detectUnregisteredBoons(): array
    {
        if (!($this->config['monitoring']['unregistered_boon_alerts']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Unregistered boon detection is disabled'];
        }
        
        return $this->analyzer->findUnregisteredBoons();
    }
    
    /**
     * Detect broken boons (scandals)
     * 
     * @return array Broken boons
     */
    public function detectBrokenBoons(): array
    {
        if (!($this->config['monitoring']['scandal_detection']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Scandal detection is disabled'];
        }
        
        return $this->analyzer->findBrokenBoons();
    }
    
    /**
     * Find combination opportunities
     * 
     * @return array Combination opportunities
     */
    public function findCombinationOpportunities(): array
    {
        if (!($this->config['monitoring']['combination_opportunities']['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'Combination detection is disabled'];
        }
        
        return $this->analyzer->findCombinationOpportunities();
    }
    
    /**
     * Handle character death - void owed boons
     * 
     * @param string $characterName
     * @return array Result of voiding boons
     */
    public function handleCharacterDeath(string $characterName): array
    {
        if (!($this->config['monitoring']['dead_debt_detection']['check_on_character_death'] ?? true)) {
            return ['success' => false, 'message' => 'Character death handling is disabled'];
        }
        
        return $this->analyzer->voidBoonsOnDeath($characterName);
    }
    
    /**
     * Generate daily report
     * 
     * @return array Report generation result
     */
    public function generateDailyReport(): array
    {
        if (!($this->config['reporting']['generate_daily_reports'] ?? true)) {
            return ['success' => false, 'message' => 'Daily reports are disabled'];
        }
        
        return $this->reporter->generateDailyReport();
    }
    
    /**
     * Generate character report
     * 
     * @param string $characterName
     * @return array Report generation result
     */
    public function generateCharacterReport(string $characterName): array
    {
        if (!($this->config['reporting']['generate_character_reports'] ?? true)) {
            return ['success' => false, 'message' => 'Character reports are disabled'];
        }
        
        return $this->reporter->generateCharacterReport($characterName);
    }
    
    /**
     * Generate validation report
     * 
     * @return array Report generation result
     */
    public function generateValidationReport(): array
    {
        if (!($this->config['reporting']['generate_validation_reports'] ?? true)) {
            return ['success' => false, 'message' => 'Validation reports are disabled'];
        }
        
        return $this->reporter->generateValidationReport();
    }
    
    /**
     * Generate economy report
     * 
     * @return array Report generation result
     */
    public function generateEconomyReport(): array
    {
        if (!($this->config['reporting']['generate_economy_reports'] ?? true)) {
            return ['success' => false, 'message' => 'Economy reports are disabled'];
        }
        
        return $this->reporter->generateEconomyReport();
    }
    
    /**
     * Get all boons from database
     * 
     * @return array All boons
     */
    protected function getAllBoons(): array
    {
        $query = "SELECT 
                    b.id as boon_id,
                    b.creditor_id,
                    b.debtor_id,
                    creditor.character_name as giver_name,
                    debtor.character_name as receiver_name,
                    b.boon_type,
                    b.status,
                    b.description,
                    b.created_date as date_created,
                    b.fulfilled_date,
                    b.due_date,
                    b.notes,
                    b.registered_with_harpy,
                    b.date_registered,
                    b.harpy_notes
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  ORDER BY b.created_date DESC";
        
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [];
        }
        
        $boons = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map DB status to UI status for compatibility
            $statusMap = [
                'active' => 'Owed',
                'fulfilled' => 'Paid',
                'cancelled' => 'Broken',
                'disputed' => 'Broken'
            ];
            $row['status'] = $statusMap[strtolower($row['status'])] ?? $row['status'];
            
            // Map boon_type to title case
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            
            $boons[] = $row;
        }
        
        return $boons;
    }
    
    /**
     * Get configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

?>

