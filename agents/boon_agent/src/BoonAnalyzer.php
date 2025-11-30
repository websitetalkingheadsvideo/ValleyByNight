<?php
/**
 * BoonAnalyzer
 * Monitors boons and detects various issues and patterns
 */

class BoonAnalyzer
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
     * BoonAnalyzer constructor.
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
     * Find dead debts (boons owed by deceased characters)
     * 
     * @return array Dead debt results
     */
    public function findDeadDebts(): array
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
                    debtor.status as character_status
                  FROM boons b
                  INNER JOIN characters debtor ON b.debtor_id = debtor.id
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  WHERE b.status = 'active'
                    AND LOWER(debtor.status) = 'dead'";
        
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'dead_debts' => []
            ];
        }
        
        $deadDebts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map boon_type to title case for display
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            $deadDebts[] = $row;
        }
        
        return [
            'success' => true,
            'count' => count($deadDebts),
            'dead_debts' => $deadDebts
        ];
    }
    
    /**
     * Find unregistered boons (not linked to Harpies)
     * Note: This assumes a future field for harpy registration
     * 
     * @return array Unregistered boons
     */
    public function findUnregisteredBoons(): array
    {
        // Find active boons that are not registered with a Harpy
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
                    b.registered_with_harpy
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  WHERE b.status = 'active'
                    AND (b.registered_with_harpy IS NULL OR b.registered_with_harpy = '')
                  ORDER BY b.created_date DESC";
        
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'unregistered' => []
            ];
        }
        
        $unregistered = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map boon_type to title case for display
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            $unregistered[] = $row;
        }
        
        return [
            'success' => true,
            'count' => count($unregistered),
            'unregistered' => $unregistered
        ];
    }
    
    /**
     * Find broken boons (scandals)
     * 
     * @return array Broken boons
     */
    public function findBrokenBoons(): array
    {
        // Broken boons are those with status 'disputed' or 'cancelled'
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
                    b.harpy_notes
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id
                  WHERE b.status IN ('disputed', 'cancelled')
                  ORDER BY b.created_date DESC";
        
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'broken' => []
            ];
        }
        
        $broken = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Map boon_type to title case for display
            $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
            $broken[] = $row;
        }
        
        return [
            'success' => true,
            'count' => count($broken),
            'broken' => $broken
        ];
    }
    
    /**
     * Find combination opportunities (multiple boons between same characters)
     * 
     * @return array Combination opportunities
     */
    public function findCombinationOpportunities(): array
    {
        $query = "SELECT 
                    b1.creditor_id,
                    b1.debtor_id,
                    creditor.character_name as giver_name,
                    debtor.character_name as receiver_name,
                    COUNT(*) as boon_count,
                    GROUP_CONCAT(b1.id ORDER BY b1.id) as boon_ids,
                    GROUP_CONCAT(b1.boon_type ORDER BY b1.id) as boon_types
                  FROM boons b1
                  LEFT JOIN characters creditor ON b1.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b1.debtor_id = debtor.id
                  WHERE b1.status = 'active'
                  GROUP BY b1.creditor_id, b1.debtor_id
                  HAVING boon_count > 1
                  ORDER BY boon_count DESC";
        
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'opportunities' => []
            ];
        }
        
        $opportunities = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $boonTypes = explode(',', $row['boon_types']);
            // Map to title case
            $boonTypes = array_map(function($type) {
                return ucfirst(strtolower($type));
            }, $boonTypes);
            
            $opportunities[] = [
                'creditor_id' => $row['creditor_id'],
                'debtor_id' => $row['debtor_id'],
                'giver_name' => $row['giver_name'],
                'receiver_name' => $row['receiver_name'],
                'boon_count' => $row['boon_count'],
                'boon_ids' => explode(',', $row['boon_ids']),
                'boon_types' => $boonTypes
            ];
        }
        
        return [
            'success' => true,
            'count' => count($opportunities),
            'opportunities' => $opportunities
        ];
    }
    
    /**
     * Analyze boon economy - track patterns and power dynamics
     * 
     * @return array Economy analysis
     */
    public function analyzeEconomy(): array
    {
        $analysis = [
            'total_boons' => 0,
            'by_status' => [],
            'by_type' => [],
            'top_creditors' => [],
            'top_debtors' => [],
            'by_clan' => []
        ];
        
        // Get all boons with character names
        $query = "SELECT 
                    b.id as boon_id,
                    b.creditor_id,
                    b.debtor_id,
                    creditor.character_name as giver_name,
                    debtor.character_name as receiver_name,
                    creditor.clan as creditor_clan,
                    debtor.clan as debtor_clan,
                    b.boon_type,
                    b.status
                  FROM boons b
                  LEFT JOIN characters creditor ON b.creditor_id = creditor.id
                  LEFT JOIN characters debtor ON b.debtor_id = debtor.id";
        $result = mysqli_query($this->db, $query);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db)
            ];
        }
        
        $boons = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $boons[] = $row;
        }
        
        $analysis['total_boons'] = count($boons);
        
        // Count by status - map DB status to readable names
        $statusMap = [
            'active' => 'Active/Owed',
            'fulfilled' => 'Fulfilled/Paid',
            'cancelled' => 'Cancelled',
            'disputed' => 'Disputed/Broken'
        ];
        foreach ($boons as $boon) {
            $status = strtolower($boon['status'] ?? 'unknown');
            $statusLabel = $statusMap[$status] ?? ucfirst($status);
            $analysis['by_status'][$statusLabel] = ($analysis['by_status'][$statusLabel] ?? 0) + 1;
        }
        
        // Count by type - map to title case
        foreach ($boons as $boon) {
            $type = ucfirst(strtolower($boon['boon_type'] ?? 'unknown'));
            $analysis['by_type'][$type] = ($analysis['by_type'][$type] ?? 0) + 1;
        }
        
        // Count creditors (givers) - only active boons
        $creditors = [];
        foreach ($boons as $boon) {
            if (strtolower($boon['status']) === 'active' && !empty($boon['giver_name'])) {
                $giver = $boon['giver_name'];
                $creditors[$giver] = ($creditors[$giver] ?? 0) + 1;
            }
        }
        arsort($creditors);
        $analysis['top_creditors'] = array_slice($creditors, 0, 10, true);
        
        // Count debtors (receivers) - only active boons
        $debtors = [];
        foreach ($boons as $boon) {
            if (strtolower($boon['status']) === 'active' && !empty($boon['receiver_name'])) {
                $receiver = $boon['receiver_name'];
                $debtors[$receiver] = ($debtors[$receiver] ?? 0) + 1;
            }
        }
        arsort($debtors);
        $analysis['top_debtors'] = array_slice($debtors, 0, 10, true);
        
        // Analyze by clan
        $clanAnalysis = $this->analyzeByClan($boons);
        if (!empty($clanAnalysis)) {
            $analysis['by_clan'] = $clanAnalysis;
        }
        
        return [
            'success' => true,
            'analysis' => $analysis
        ];
    }
    
    /**
     * Analyze boons by clan
     * 
     * @param array $boons Boons array with clan information already included
     * @return array Clan analysis
     */
    protected function analyzeByClan(array $boons): array
    {
        $clanCounts = [];
        
        foreach ($boons as $boon) {
            $giverClan = $boon['creditor_clan'] ?? 'Unknown';
            $receiverClan = $boon['debtor_clan'] ?? 'Unknown';
            
            if (!isset($clanCounts[$giverClan])) {
                $clanCounts[$giverClan] = ['given' => 0, 'received' => 0];
            }
            if (!isset($clanCounts[$receiverClan])) {
                $clanCounts[$receiverClan] = ['given' => 0, 'received' => 0];
            }
            
            $clanCounts[$giverClan]['given']++;
            $clanCounts[$receiverClan]['received']++;
        }
        
        return $clanCounts;
    }
    
    /**
     * Void boons on character death
     * 
     * @param string $characterName
     * @return array Result
     */
    public function voidBoonsOnDeath(string $characterName): array
    {
        // First, get character ID by name
        $charQuery = "SELECT id FROM characters WHERE character_name = ? LIMIT 1";
        $charStmt = mysqli_prepare($this->db, $charQuery);
        
        if (!$charStmt) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'voided_count' => 0
            ];
        }
        
        mysqli_stmt_bind_param($charStmt, "s", $characterName);
        mysqli_stmt_execute($charStmt);
        $charResult = mysqli_stmt_get_result($charStmt);
        $charRow = mysqli_fetch_assoc($charResult);
        mysqli_stmt_close($charStmt);
        
        if (!$charRow) {
            return [
                'success' => false,
                'message' => "Character '{$characterName}' not found",
                'voided_count' => 0
            ];
        }
        
        $characterId = $charRow['id'];
        
        // Find all active boons where the receiver (debtor) has died
        $query = "SELECT b.id FROM boons b
                  WHERE b.debtor_id = ? AND b.status = 'active'";
        $stmt = mysqli_prepare($this->db, $query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($this->db),
                'voided_count' => 0
            ];
        }
        
        mysqli_stmt_bind_param($stmt, "i", $characterId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $voidedIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $voidedIds[] = $row['id'];
        }
        mysqli_stmt_close($stmt);
        
        // Mark boons as cancelled (voided due to death)
        // Note: Per rules, boons are "lost" when debtor dies
        $voidedCount = 0;
        if (!empty($voidedIds)) {
            foreach ($voidedIds as $boonId) {
                // Update status to cancelled and add note
                $updateQuery = "UPDATE boons 
                               SET status = 'cancelled',
                                   notes = CONCAT(COALESCE(notes, ''), 
                                       IF(notes IS NULL OR notes = '', '', ' | '), 
                                       '[Voided: Debtor deceased on ', NOW(), ']'),
                                   updated_at = NOW()
                               WHERE id = ?";
                $updateStmt = mysqli_prepare($this->db, $updateQuery);
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "i", $boonId);
                    if (mysqli_stmt_execute($updateStmt)) {
                        $voidedCount++;
                    }
                    mysqli_stmt_close($updateStmt);
                }
            }
        }
        
        return [
            'success' => true,
            'voided_count' => $voidedCount,
            'boon_ids' => $voidedIds,
            'character' => $characterName,
            'character_id' => $characterId,
            'message' => "Voided {$voidedCount} boon(s) owed by deceased character {$characterName}"
        ];
    }
}

?>

