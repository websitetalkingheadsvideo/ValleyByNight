<?php
/**
 * BoonValidator
 * Validates boons according to Laws of the Night Revised mechanics
 */

class BoonValidator
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * Valid boon types (database enum values - lowercase)
     */
    protected $validBoonTypes = ['trivial', 'minor', 'major', 'life'];
    
    /**
     * Valid status values (database enum values - lowercase)
     */
    protected $validStatuses = ['active', 'fulfilled', 'cancelled', 'disputed'];
    
    /**
     * BoonValidator constructor.
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
     * Validate a boon
     * 
     * @param array $boon
     * @return array Validation result with 'valid' bool and 'errors' array
     */
    public function validateBoon(array $boon): array
    {
        $errors = [];
        
        // Validate boon type
        if ($this->shouldValidate('validate_boon_types')) {
            $typeErrors = $this->validateBoonType($boon);
            $errors = array_merge($errors, $typeErrors);
        }
        
        // Validate character existence (if enabled)
        if ($this->shouldValidate('validate_character_existence')) {
            $charErrors = $this->validateCharacterExistence($boon);
            $errors = array_merge($errors, $charErrors);
        }
        
        // Validate status transitions (if tracking previous status)
        if ($this->shouldValidate('validate_status_transitions')) {
            $statusErrors = $this->validateStatus($boon);
            $errors = array_merge($errors, $statusErrors);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate boon type
     * 
     * @param array $boon
     * @return array Errors
     */
    protected function validateBoonType(array $boon): array
    {
        $errors = [];
        
        $boonType = strtolower($boon['boon_type'] ?? '');
        
        if (empty($boonType)) {
            $errors[] = 'Boon type is required';
        } elseif (!in_array($boonType, $this->validBoonTypes)) {
            $errors[] = "Invalid boon type: {$boonType}. Must be one of: " . implode(', ', $this->validBoonTypes);
        }
        
        return $errors;
    }
    
    /**
     * Validate character existence
     * 
     * @param array $boon
     * @return array Errors
     */
    protected function validateCharacterExistence(array $boon): array
    {
        $errors = [];
        $allowCustom = $this->config['validation']['validate_character_existence']['allow_custom_names'] ?? true;
        
        // Check if using IDs (preferred) or names
        if (isset($boon['creditor_id']) || isset($boon['debtor_id'])) {
            // Using IDs - validate they exist
            if (isset($boon['creditor_id']) && !$this->characterExistsById($boon['creditor_id'])) {
                $errors[] = "Creditor ID '{$boon['creditor_id']}' does not exist in characters table";
            }
            
            if (isset($boon['debtor_id']) && !$this->characterExistsById($boon['debtor_id'])) {
                $errors[] = "Debtor ID '{$boon['debtor_id']}' does not exist in characters table";
            }
        } elseif (!$allowCustom) {
            // Using names - validate they exist
            $giverName = $boon['giver_name'] ?? '';
            $receiverName = $boon['receiver_name'] ?? '';
            
            if (!empty($giverName) && !$this->characterExists($giverName)) {
                $errors[] = "Giver '{$giverName}' does not exist in characters table";
            }
            
            if (!empty($receiverName) && !$this->characterExists($receiverName)) {
                $errors[] = "Receiver '{$receiverName}' does not exist in characters table";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate status value
     * 
     * @param array $boon
     * @return array Errors
     */
    protected function validateStatus(array $boon): array
    {
        $errors = [];
        
        $status = strtolower($boon['status'] ?? '');
        
        if (empty($status)) {
            // Status can be nullable in DB, so not always required
        } elseif (!in_array($status, $this->validStatuses)) {
            $errors[] = "Invalid status: {$status}. Must be one of: " . implode(', ', $this->validStatuses);
        }
        
        return $errors;
    }
    
    /**
     * Check if character exists in database by name (Supabase)
     */
    protected function characterExists(string $characterName): bool
    {
        require_once __DIR__ . '/../../../includes/supabase_client.php';
        $rows = supabase_table_get('characters', ['select' => 'id', 'character_name' => 'eq.' . $characterName, 'limit' => '1']);
        return !empty($rows);
    }

    /**
     * Check if character exists in database by ID (Supabase)
     */
    protected function characterExistsById(int $characterId): bool
    {
        require_once __DIR__ . '/../../../includes/supabase_client.php';
        $rows = supabase_table_get('characters', ['select' => 'id', 'id' => 'eq.' . $characterId, 'limit' => '1']);
        return !empty($rows);
    }
    
    /**
     * Check if validation rule should be applied
     * 
     * @param string $rule
     * @return bool
     */
    protected function shouldValidate(string $rule): bool
    {
        return $this->config['validation'][$rule]['enabled'] ?? false;
    }
    
    /**
     * Validate non-transferability (boons cannot be transferred)
     * This is more of a conceptual rule - would need additional tracking to enforce
     * 
     * @param array $boon
     * @return array Errors
     */
    public function validateNonTransferability(array $boon): array
    {
        $errors = [];
        
        // This would need additional tracking to detect transfers
        // For now, we just note it's a rule that should be followed
        
        return $errors;
    }
    
    /**
     * Validate combination rules (same two Kindred can combine boons)
     * 
     * @param array $boon1
     * @param array $boon2
     * @return bool Can combine
     */
    public function canCombineBoons(array $boon1, array $boon2): bool
    {
        if (!$this->config['validation']['validate_combination_rules']['allow_combining'] ?? true) {
            return false;
        }
        
        // Check IDs first (more reliable)
        $id1 = $boon1['id'] ?? $boon1['boon_id'] ?? 0;
        $id2 = $boon2['id'] ?? $boon2['boon_id'] ?? 0;
        
        if ($id1 === $id2) {
            return false; // Can't combine a boon with itself
        }
        
        // Check if same creditor/debtor pair using IDs
        $samePairById = (
            (($boon1['creditor_id'] ?? null) === ($boon2['creditor_id'] ?? null) && 
             ($boon1['debtor_id'] ?? null) === ($boon2['debtor_id'] ?? null)) ||
            (($boon1['creditor_id'] ?? null) === ($boon2['debtor_id'] ?? null) && 
             ($boon1['debtor_id'] ?? null) === ($boon2['creditor_id'] ?? null))
        );
        
        // Or check by names if IDs not available
        $samePairByName = false;
        if (!$samePairById) {
            $samePairByName = (
                (($boon1['giver_name'] ?? '') === ($boon2['giver_name'] ?? '') && 
                 ($boon1['receiver_name'] ?? '') === ($boon2['receiver_name'] ?? '')) ||
                (($boon1['giver_name'] ?? '') === ($boon2['receiver_name'] ?? '') && 
                 ($boon1['receiver_name'] ?? '') === ($boon2['giver_name'] ?? ''))
            );
        }
        
        $samePair = $samePairById || $samePairByName;
        
        // Both must be active (equivalent to "Owed")
        $status1 = strtolower($boon1['status'] ?? '');
        $status2 = strtolower($boon2['status'] ?? '');
        $bothActive = ($status1 === 'active' && $status2 === 'active');
        
        return $samePair && $bothActive;
    }
}

?>

