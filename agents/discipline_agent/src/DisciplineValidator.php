<?php
declare(strict_types=1);

/**
 * DisciplineValidator
 * 
 * Validates discipline dots, clan access, and power eligibility.
 * CRITICAL: Only validates innate disciplines - paths/rituals are excluded.
 */

class DisciplineValidator
{
    /**
     * @var DisciplineRepository
     */
    protected $disciplineRepository;
    
    /**
     * @var DisciplinePowersRepository
     */
    protected $powersRepository;
    
    /**
     * @var ClanAccessRepository
     */
    protected $clanRepository;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @param DisciplineRepository $disciplineRepository
     * @param DisciplinePowersRepository $powersRepository
     * @param ClanAccessRepository $clanRepository
     * @param array $config
     */
    public function __construct(
        DisciplineRepository $disciplineRepository,
        DisciplinePowersRepository $powersRepository,
        ClanAccessRepository $clanRepository,
        array $config = []
    ) {
        $this->disciplineRepository = $disciplineRepository;
        $this->powersRepository = $powersRepository;
        $this->clanRepository = $clanRepository;
        $this->config = $config;
    }
    
    /**
     * Validate discipline dot ranges
     * 
     * @param array $updates {discipline_name: level, ...}
     * @return array {isValid: bool, errors: array, warnings: array}
     */
    public function validateDisciplineDots(array $updates): array
    {
        $errors = [];
        $warnings = [];
        
        $minDots = $this->config['validation']['min_dots'] ?? 0;
        $maxDots = $this->config['validation']['max_dots'] ?? 5;
        
        foreach ($updates as $disciplineName => $level) {
            // Check if this is a path/ritual - reject it
            if (!$this->disciplineRepository->isInnateDiscipline($disciplineName)) {
                $errors[] = [
                    'code' => 'INVALID_DISCIPLINE_TYPE',
                    'message' => "Discipline '{$disciplineName}' is a path or ritual, not an innate discipline",
                    'discipline' => $disciplineName
                ];
                continue;
            }
            
            // Validate type
            if (!is_int($level) && !ctype_digit((string)$level)) {
                $errors[] = [
                    'code' => 'INVALID_DOT_TYPE',
                    'message' => "Discipline dots must be an integer, got: " . gettype($level),
                    'discipline' => $disciplineName,
                    'value' => $level
                ];
                continue;
            }
            
            $level = (int)$level;
            
            // Validate range
            if ($level < $minDots) {
                $errors[] = [
                    'code' => 'DOTS_BELOW_MINIMUM',
                    'message' => "Discipline '{$disciplineName}' has dots below minimum ({$minDots})",
                    'discipline' => $disciplineName,
                    'level' => $level,
                    'minimum' => $minDots
                ];
            }
            
            if ($level > $maxDots) {
                $errors[] = [
                    'code' => 'DOTS_ABOVE_MAXIMUM',
                    'message' => "Discipline '{$disciplineName}' has dots above maximum ({$maxDots})",
                    'discipline' => $disciplineName,
                    'level' => $level,
                    'maximum' => $maxDots
                ];
            }
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Validate clan access for a discipline
     * 
     * @param int $characterId
     * @param string $disciplineName
     * @return array {hasAccess: bool, isInClan: bool, restrictions: array}
     */
    public function validateClanDisciplineAccess(int $characterId, string $disciplineName): array
    {
        // First check if this is a path/ritual - reject it
        if (!$this->disciplineRepository->isInnateDiscipline($disciplineName)) {
            return [
                'hasAccess' => false,
                'isInClan' => false,
                'restrictions' => [
                    [
                        'code' => 'INVALID_DISCIPLINE_TYPE',
                        'message' => "Discipline '{$disciplineName}' is a path or ritual, not an innate discipline"
                    ]
                ]
            ];
        }
        
        $clanName = $this->clanRepository->getClanName($characterId);
        
        if ($clanName === null) {
            return [
                'hasAccess' => false,
                'isInClan' => false,
                'restrictions' => [
                    [
                        'code' => 'CHARACTER_NOT_FOUND',
                        'message' => "Character not found"
                    ]
                ]
            ];
        }
        
        $isInClan = $this->clanRepository->isInClanDiscipline($clanName, $disciplineName);
        $allowOutOfClan = $this->config['validation']['allow_out_of_clan'] ?? true;
        
        $restrictions = [];
        if (!$isInClan && !$allowOutOfClan) {
            $restrictions[] = [
                'code' => 'OUT_OF_CLAN_RESTRICTED',
                'message' => "Discipline '{$disciplineName}' is out-of-clan for {$clanName} and out-of-clan disciplines are restricted"
            ];
        } elseif (!$isInClan) {
            $restrictions[] = [
                'code' => 'OUT_OF_CLAN_WARNING',
                'message' => "Discipline '{$disciplineName}' is out-of-clan for {$clanName} (may have additional XP costs or restrictions)"
            ];
        }
        
        return [
            'hasAccess' => $allowOutOfClan || $isInClan,
            'isInClan' => $isInClan,
            'restrictions' => $restrictions
        ];
    }
    
    /**
     * Validate discipline power eligibility
     * 
     * @param int $characterId
     * @param array $requestedPowers {discipline_name: [power_names], ...}
     * @return array {isValid: bool, errors: array, eligiblePowers: array}
     */
    public function validateDisciplinePowers(int $characterId, array $requestedPowers): array
    {
        $errors = [];
        $eligiblePowers = [];
        
        foreach ($requestedPowers as $disciplineName => $powerNames) {
            // Check if this is a path/ritual - reject it
            if (!$this->disciplineRepository->isInnateDiscipline($disciplineName)) {
                $errors[] = [
                    'code' => 'INVALID_DISCIPLINE_TYPE',
                    'message' => "Discipline '{$disciplineName}' is a path or ritual, not an innate discipline",
                    'discipline' => $disciplineName
                ];
                continue;
            }
            
            // Get character's discipline level
            $disciplineLevel = $this->disciplineRepository->getDisciplineLevel($characterId, $disciplineName);
            
            if ($disciplineLevel === null || $disciplineLevel === 0) {
                $errors[] = [
                    'code' => 'NO_DISCIPLINE_DOTS',
                    'message' => "Character has no dots in '{$disciplineName}'",
                    'discipline' => $disciplineName
                ];
                continue;
            }
            
            // Validate each requested power
            if (!is_array($powerNames)) {
                $powerNames = [$powerNames];
            }
            
            foreach ($powerNames as $powerName) {
                // Check if power exists
                if (!$this->powersRepository->powerExists($disciplineName, $powerName)) {
                    $errors[] = [
                        'code' => 'POWER_NOT_FOUND',
                        'message' => "Power '{$powerName}' does not exist for discipline '{$disciplineName}'",
                        'discipline' => $disciplineName,
                        'power' => $powerName
                    ];
                    continue;
                }
                
                // Check if power level is within discipline level
                $powerLevel = $this->powersRepository->getPowerLevel($disciplineName, $powerName);
                
                if ($powerLevel === null) {
                    $errors[] = [
                        'code' => 'POWER_LEVEL_UNKNOWN',
                        'message' => "Could not determine level requirement for power '{$powerName}'",
                        'discipline' => $disciplineName,
                        'power' => $powerName
                    ];
                    continue;
                }
                
                if ($powerLevel > $disciplineLevel) {
                    $errors[] = [
                        'code' => 'POWER_LEVEL_TOO_HIGH',
                        'message' => "Power '{$powerName}' requires level {$powerLevel} but character only has {$disciplineLevel} dots in '{$disciplineName}'",
                        'discipline' => $disciplineName,
                        'power' => $powerName,
                        'required_level' => $powerLevel,
                        'character_level' => $disciplineLevel
                    ];
                    continue;
                }
                
                // Power is eligible
                $eligiblePowers[] = [
                    'discipline' => $disciplineName,
                    'power' => $powerName,
                    'level' => $powerLevel
                ];
            }
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'eligiblePowers' => $eligiblePowers
        ];
    }
}

