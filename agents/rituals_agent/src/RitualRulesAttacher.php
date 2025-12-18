<?php
declare(strict_types=1);

/**
 * RitualRulesAttacher
 * 
 * Composes ritual rules at runtime without modifying the original ritual record.
 * Attaches rules as a separate 'rules' field in the returned array.
 */

class RitualRulesAttacher
{
    /**
     * @var RulesRepository
     */
    protected $rulesRepository;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @param RulesRepository $rulesRepository
     * @param array $config
     */
    public function __construct(RulesRepository $rulesRepository, array $config = [])
    {
        $this->rulesRepository = $rulesRepository;
        $this->config = $config;
    }
    
    /**
     * Attach rules to a ritual array
     * 
     * Adds a 'rules' field containing global and/or tradition-specific rules.
     * The original ritual fields remain unchanged.
     * 
     * @param array $ritual Ritual data array
     * @param bool $includeGlobal Whether to include global rules
     * @param bool $includeTradition Whether to include tradition-specific rules
     * @return array Ritual array with 'rules' field added
     */
    public function attachRules(array $ritual, bool $includeGlobal = true, bool $includeTradition = true): array
    {
        // Create a copy to avoid modifying the original
        $ritualWithRules = $ritual;
        
        $rules = [
            'global' => [],
            'tradition' => []
        ];
        
        // Attach global rules if requested
        if ($includeGlobal) {
            $limit = $this->config['rules']['default_limit'] ?? 10;
            $rules['global'] = $this->rulesRepository->getGlobalRitualRules($limit);
        }
        
        // Attach tradition-specific rules if requested and ritual has a type
        if ($includeTradition && !empty($ritual['type'])) {
            $tradition = $ritual['type'];
            $limit = $this->config['rules']['default_limit'] ?? 10;
            $rules['tradition'] = $this->rulesRepository->getTraditionRules($tradition, $limit);
        }
        
        // Add rules as a separate field - original ritual fields unchanged
        $ritualWithRules['rules'] = $rules;
        
        return $ritualWithRules;
    }
}

