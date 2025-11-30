<?php
/**
 * ReportGenerator
 * Generates various reports for the Boon Agent
 */

class ReportGenerator
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
     * ReportGenerator constructor.
     * 
     * @param mysqli $db
     * @param array $config
     */
    public function __construct($db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
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
        
        // Get all boons and validate
        $query = "SELECT 
                    b.id as boon_id,
                    b.creditor_id,
                    b.debtor_id,
                    creditor.character_name as giver_name,
                    debtor.character_name as receiver_name,
                    b.boon_type,
                    b.status,
                    b.description,
                    b.created_date as date_created
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  ORDER BY b.created_date DESC";
        $result = mysqli_query($this->db, $query);
        
        $violations = [];
        $totalBoons = 0;
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $totalBoons++;
                $validation = $validator->validateBoon($row);
                if (!$validation['valid']) {
                    $violations[] = [
                        'boon_id' => $row['boon_id'],
                        'boon' => $row,
                        'errors' => $validation['errors']
                    ];
                }
            }
        }
        
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
        $today = date('Y-m-d');
        
        $query = "SELECT 
                    COUNT(*) as total_boons,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as owed_count,
                    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status IN ('disputed', 'cancelled') THEN 1 ELSE 0 END) as broken_count,
                    SUM(CASE WHEN DATE(created_date) = ? THEN 1 ELSE 0 END) as new_today
                  FROM boons";
        
        $stmt = mysqli_prepare($this->db, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $today);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $summary = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            // Map called_count to owed_count since they're both 'active' status
            if ($summary) {
                $summary['called_count'] = 0; // Could query separately if needed
            }
            
            return $summary ?? [];
        }
        
        return [];
    }
    
    /**
     * Get new boons created today
     * 
     * @return array New boons
     */
    protected function getNewBoonsToday(): array
    {
        $today = date('Y-m-d');
        
        $query = "SELECT 
                    b.id as boon_id,
                    creditor.character_name as giver_name,
                    debtor.character_name as receiver_name,
                    b.boon_type,
                    b.status,
                    b.description,
                    b.created_date as date_created
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  WHERE DATE(b.created_date) = ?
                  ORDER BY b.created_date DESC";
        
        $stmt = mysqli_prepare($this->db, $query);
        if (!$stmt) {
            return [];
        }
        
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $boons = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map boon_type to title case
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            $boons[] = $row;
        }
        mysqli_stmt_close($stmt);
        
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
        $analyzer = new BoonAnalyzer($this->db, $this->config);
        
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
        // First get character ID
        $charQuery = "SELECT id FROM characters WHERE character_name = ? LIMIT 1";
        $charStmt = mysqli_prepare($this->db, $charQuery);
        if (!$charStmt) {
            return [];
        }
        
        mysqli_stmt_bind_param($charStmt, "s", $characterName);
        mysqli_stmt_execute($charStmt);
        $charResult = mysqli_stmt_get_result($charStmt);
        $charRow = mysqli_fetch_assoc($charResult);
        mysqli_stmt_close($charStmt);
        
        if (!$charRow) {
            return [];
        }
        
        $characterId = $charRow['id'];
        $idField = $role === 'giver' ? 'creditor_id' : 'debtor_id';
        
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
                    b.notes,
                    b.registered_with_harpy,
                    b.date_registered
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  WHERE b.{$idField} = ?
                  ORDER BY b.created_date DESC";
        
        $stmt = mysqli_prepare($this->db, $query);
        if (!$stmt) {
            return [];
        }
        
        mysqli_stmt_bind_param($stmt, "i", $characterId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $boons = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map boon_type to title case
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            $boons[] = $row;
        }
        mysqli_stmt_close($stmt);
        
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

