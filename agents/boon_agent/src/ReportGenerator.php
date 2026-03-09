<?php
/**
 * ReportGenerator
 * Generates various reports for the Boon Agent (Supabase)
 */

class ReportGenerator
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;

    /** @var array */
    protected $config;

    public function __construct($db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    protected function supabase(): void
    {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
    }
    
    /**
     * Generate daily report
     * 
     * @return array Report generation result
     */
    public function generateDailyReport(): array
    {
        $reportDir = __DIR__ . '/../reports/daily';
        $this->ensureDirectory($reportDir);
        
        $timestamp = date('Y-m-d_H-i-s');
        $reportFile = $reportDir . '/daily_report_' . $timestamp . '.json';
        
        require_once __DIR__ . '/BoonAnalyzer.php';
        $analyzer = new BoonAnalyzer($this->db, $this->config);
        
        // Gather report data
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d'),
            'summary' => $this->getDailySummary(),
            'new_boons' => $this->getNewBoonsToday(),
            'status_changes' => $this->getStatusChangesToday(),
            'violations' => $this->getViolationsToday(),
            'dead_debts' => $analyzer->findDeadDebts(),
            'broken_boons' => $analyzer->findBrokenBoons(),
            'unregistered_boons' => $analyzer->findUnregisteredBoons()
        ];
        
        // Save report
        $json = json_encode($report, JSON_PRETTY_PRINT);
        if (file_put_contents($reportFile, $json) === false) {
            return [
                'success' => false,
                'message' => 'Failed to write report file'
            ];
        }
        
        return [
            'success' => true,
            'report_file' => basename($reportFile),
            'report_path' => $reportFile,
            'report' => $report
        ];
    }
    
    /**
     * Generate character report
     * 
     * @param string $characterName
     * @return array Report generation result
     */
    public function generateCharacterReport(string $characterName): array
    {
        $reportDir = __DIR__ . '/../reports/character';
        $this->ensureDirectory($reportDir);
        
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $characterName);
        $timestamp = date('Y-m-d_H-i-s');
        $reportFile = $reportDir . '/character_' . $safeFilename . '_' . $timestamp . '.json';
        
        // Get boons for character
        $boonsOwed = $this->getBoonsForCharacter($characterName, 'receiver');
        $boonsHeld = $this->getBoonsForCharacter($characterName, 'giver');
        
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'character_name' => $characterName,
            'boons_owed' => [
                'count' => count($boonsOwed),
                'boons' => $boonsOwed
            ],
            'boons_held' => [
                'count' => count($boonsHeld),
                'boons' => $boonsHeld
            ],
            'summary' => [
                'total_active_owed' => count(array_filter($boonsOwed, function($b) { 
                    return strtolower($b['status']) === 'active'; 
                })),
                'total_active_held' => count(array_filter($boonsHeld, function($b) { 
                    return strtolower($b['status']) === 'active'; 
                }))
            ]
        ];
        
        // Save report
        $json = json_encode($report, JSON_PRETTY_PRINT);
        if (file_put_contents($reportFile, $json) === false) {
            return [
                'success' => false,
                'message' => 'Failed to write report file'
            ];
        }
        
        return [
            'success' => true,
            'report_file' => basename($reportFile),
            'report_path' => $reportFile,
            'report' => $report
        ];
    }
    
    /**
     * Generate validation report
     * 
     * @return array Report generation result
     */
    public function generateValidationReport(): array
    {
        $reportDir = __DIR__ . '/../reports/validation';
        $this->ensureDirectory($reportDir);
        
        $timestamp = date('Y-m-d_H-i-s');
        $reportFile = $reportDir . '/validation_report_' . $timestamp . '.json';
        
        require_once __DIR__ . '/BoonValidator.php';
        $validator = new BoonValidator($this->db, $this->config);
        
        $this->supabase();
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date',
            'order' => 'created_date.desc'
        ]);
        $charIds = [];
        foreach ($rows as $r) {
            if (!empty($r['creditor_id'])) $charIds[(int)$r['creditor_id']] = true;
            if (!empty($r['debtor_id'])) $charIds[(int)$r['debtor_id']] = true;
        }
        $nameMap = [];
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', array_keys($charIds)) . ')']);
            foreach ($chars as $c) {
                $nameMap[(int)$c['id']] = $c['character_name'] ?? '';
            }
        }
        $violations = [];
        foreach ($rows as $row) {
            $row['boon_id'] = $row['id'];
            $row['giver_name'] = $nameMap[(int)($row['creditor_id'] ?? 0)] ?? '';
            $row['receiver_name'] = $nameMap[(int)($row['debtor_id'] ?? 0)] ?? '';
            $row['date_created'] = $row['created_date'] ?? null;
            $validation = $validator->validateBoon($row);
            if (!$validation['valid']) {
                $violations[] = [
                    'boon_id' => $row['boon_id'],
                    'boon' => $row,
                    'errors' => $validation['errors']
                ];
            }
        }
        $totalBoons = count($rows);
        
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_boons' => $totalBoons,
            'violations_found' => count($violations),
            'violations' => $violations,
            'validation_passed' => count($violations) === 0
        ];
        
        // Save report
        $json = json_encode($report, JSON_PRETTY_PRINT);
        if (file_put_contents($reportFile, $json) === false) {
            return [
                'success' => false,
                'message' => 'Failed to write report file'
            ];
        }
        
        return [
            'success' => true,
            'report_file' => basename($reportFile),
            'report_path' => $reportFile,
            'report' => $report
        ];
    }
    
    /**
     * Generate economy report
     * 
     * @return array Report generation result
     */
    public function generateEconomyReport(): array
    {
        $reportDir = __DIR__ . '/../reports/daily';
        $this->ensureDirectory($reportDir);
        
        $timestamp = date('Y-m-d_H-i-s');
        $reportFile = $reportDir . '/economy_report_' . $timestamp . '.json';
        
        require_once __DIR__ . '/BoonAnalyzer.php';
        $analyzer = new BoonAnalyzer($this->db, $this->config);
        
        $economyAnalysis = $analyzer->analyzeEconomy();
        $combinationOpportunities = $analyzer->findCombinationOpportunities();
        
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'economy_analysis' => $economyAnalysis,
            'combination_opportunities' => $combinationOpportunities
        ];
        
        // Save report
        $json = json_encode($report, JSON_PRETTY_PRINT);
        if (file_put_contents($reportFile, $json) === false) {
            return [
                'success' => false,
                'message' => 'Failed to write report file'
            ];
        }
        
        return [
            'success' => true,
            'report_file' => basename($reportFile),
            'report_path' => $reportFile,
            'report' => $report
        ];
    }
    
    /**
     * Get daily summary
     * 
     * @return array Summary data
     */
    protected function getDailySummary(): array
    {
        $this->supabase();
        $today = date('Y-m-d');
        $rows = supabase_table_get('boons', ['select' => 'status,created_date']);
        $summary = [
            'total_boons' => count($rows),
            'owed_count' => 0,
            'paid_count' => 0,
            'broken_count' => 0,
            'new_today' => 0,
            'called_count' => 0
        ];
        foreach ($rows as $r) {
            $st = strtolower((string)($r['status'] ?? ''));
            if ($st === 'active') $summary['owed_count']++;
            elseif ($st === 'fulfilled') $summary['paid_count']++;
            elseif (in_array($st, ['disputed', 'cancelled'], true)) $summary['broken_count']++;
            $cd = isset($r['created_date']) ? substr((string)$r['created_date'], 0, 10) : '';
            if ($cd === $today) $summary['new_today']++;
        }
        return $summary;
    }
    
    /**
     * Get new boons created today
     * 
     * @return array New boons
     */
    protected function getNewBoonsToday(): array
    {
        $this->supabase();
        $today = date('Y-m-d');
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date',
            'order' => 'created_date.desc'
        ]);
        $boons = [];
        foreach ($rows as $r) {
            $cd = isset($r['created_date']) ? substr((string)$r['created_date'], 0, 10) : '';
            if ($cd !== $today) continue;
            $r['boon_id'] = $r['id'];
            $r['date_created'] = $r['created_date'];
            $r['boon_type'] = ucfirst(strtolower((string)($r['boon_type'] ?? '')));
            $boons[] = $r;
        }
        $charIds = [];
        foreach ($boons as $b) {
            if (!empty($b['creditor_id'])) $charIds[(int)$b['creditor_id']] = true;
            if (!empty($b['debtor_id'])) $charIds[(int)$b['debtor_id']] = true;
        }
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', array_keys($charIds)) . ')']);
            $nameMap = [];
            foreach ($chars as $c) {
                $nameMap[(int)$c['id']] = $c['character_name'] ?? '';
            }
            foreach ($boons as &$b) {
                $b['giver_name'] = $nameMap[(int)($b['creditor_id'] ?? 0)] ?? '';
                $b['receiver_name'] = $nameMap[(int)($b['debtor_id'] ?? 0)] ?? '';
            }
            unset($b);
        }
        return $boons;
    }
    
    /**
     * Get status changes today (would need change tracking)
     * 
     * @return array Status changes
     */
    protected function getStatusChangesToday(): array
    {
        // This would require a status_change_log table to track changes
        // For now, return empty array
        return [];
    }
    
    /**
     * Get violations today
     * 
     * @return array Violations
     */
    protected function getViolationsToday(): array
    {
        require_once __DIR__ . '/BoonAnalyzer.php';
        $analyzer = new BoonAnalyzer(null, $this->config);
        
        $deadDebts = $analyzer->findDeadDebts();
        $brokenBoons = $analyzer->findBrokenBoons();
        $unregistered = $analyzer->findUnregisteredBoons();
        
        return [
            'dead_debts_count' => $deadDebts['count'] ?? 0,
            'broken_boons_count' => $brokenBoons['count'] ?? 0,
            'unregistered_count' => $unregistered['count'] ?? 0
        ];
    }
    
    /**
     * Get boons for a specific character
     * 
     * @param string $characterName
     * @param string $role 'giver' (creditor) or 'receiver' (debtor)
     * @return array Boons
     */
    protected function getBoonsForCharacter(string $characterName, string $role): array
    {
        $this->supabase();
        $chars = supabase_table_get('characters', ['select' => 'id', 'character_name' => 'eq.' . $characterName, 'limit' => '1']);
        if (empty($chars)) {
            return [];
        }
        $characterId = (int)$chars[0]['id'];
        $idField = $role === 'giver' ? 'creditor_id' : 'debtor_id';
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date,fulfilled_date,notes,registered_with_harpy,date_registered',
            $idField => 'eq.' . $characterId,
            'order' => 'created_date.desc'
        ]);
        $charIds = [];
        foreach ($rows as $r) {
            if (!empty($r['creditor_id'])) $charIds[(int)$r['creditor_id']] = true;
            if (!empty($r['debtor_id'])) $charIds[(int)$r['debtor_id']] = true;
        }
        $nameMap = [];
        if (!empty($charIds)) {
            $cs = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', array_keys($charIds)) . ')']);
            foreach ($cs as $c) {
                $nameMap[(int)$c['id']] = $c['character_name'] ?? '';
            }
        }
        $boons = [];
        foreach ($rows as $r) {
            $r['boon_id'] = $r['id'];
            $r['giver_name'] = $nameMap[(int)($r['creditor_id'] ?? 0)] ?? '';
            $r['receiver_name'] = $nameMap[(int)($r['debtor_id'] ?? 0)] ?? '';
            $r['date_created'] = $r['created_date'] ?? null;
            $r['boon_type'] = ucfirst(strtolower((string)($r['boon_type'] ?? '')));
            $boons[] = $r;
        }
        return $boons;
    }
    
    /**
     * Ensure directory exists
     * 
     * @param string $dir
     */
    protected function ensureDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

?>

