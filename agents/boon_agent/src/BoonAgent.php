<?php
/**
 * BoonAgent for Valley by Night
 * 
 * Monitors and validates boons according to Laws of the Night Revised mechanics.
 * Tracks favor-debt, detects violations, integrates with Harpy systems, and analyzes
 * the social economy of prestation.
 * 
 * Integration notes:
 * - Uses Supabase (includes/supabase_client.php). $db param ignored.
 * - Uses the `boons` and `characters` tables.
 * - Designed to be called from an Agent page in the admin panel.
 */

class BoonAgent
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
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
     * $db is ignored; Supabase is used for all queries.
     *
     * @param mixed|null $db Ignored
     * @param array|null $config
     * @throws Exception
     */
    public function __construct($db = null, array $config = null)
    {
        $this->db = null;
        require_once __DIR__ . '/../../../includes/supabase_client.php';
        
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
     * Get all boons from database (Supabase)
     *
     * @return array All boons
     */
    protected function getAllBoons(): array
    {
        require_once __DIR__ . '/../../../includes/supabase_client.php';
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date,fulfilled_date,due_date,notes,registered_with_harpy,date_registered,harpy_notes',
            'order' => 'created_date.desc'
        ]);
        if (empty($rows)) {
            return [];
        }
        $charIds = [];
        foreach ($rows as $r) {
            if (!empty($r['creditor_id'])) $charIds[(int)$r['creditor_id']] = true;
            if (!empty($r['debtor_id'])) $charIds[(int)$r['debtor_id']] = true;
        }
        $charIds = array_keys($charIds);
        $nameMap = [];
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', $charIds) . ')']);
            foreach ($chars as $c) {
                $nameMap[(int)$c['id']] = $c['character_name'] ?? '';
            }
        }
        $statusMap = ['active' => 'Owed', 'fulfilled' => 'Paid', 'cancelled' => 'Broken', 'disputed' => 'Broken'];
        $boons = [];
        foreach ($rows as $row) {
            $row['boon_id'] = $row['id'];
            $row['giver_name'] = $nameMap[(int)($row['creditor_id'] ?? 0)] ?? '';
            $row['receiver_name'] = $nameMap[(int)($row['debtor_id'] ?? 0)] ?? '';
            $row['date_created'] = $row['created_date'] ?? null;
            $row['status'] = $statusMap[strtolower((string)($row['status'] ?? ''))] ?? $row['status'];
            $row['boon_type'] = ucfirst(strtolower((string)($row['boon_type'] ?? '')));
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

