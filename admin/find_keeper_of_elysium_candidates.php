<?php
/**
 * Find Keeper of Elysium Candidates
 * Examines all characters without Camarilla positions and nominates 3 candidates
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

// Get default night
$default_night = CAMARILLA_DEFAULT_NIGHT;

// Get all characters who currently have positions
$characters_with_positions_query = "
    SELECT DISTINCT c.id
    FROM characters c
    INNER JOIN camarilla_position_assignments cpa ON (
        UPPER(REPLACE(c.character_name, ' ', '_')) = cpa.character_id
        OR UPPER(REPLACE(REPLACE(c.character_name, ' ', '_'), '-', '_')) = cpa.character_id
        OR UPPER(c.character_name) = cpa.character_id
    )
    WHERE cpa.start_night <= ?
    AND (cpa.end_night IS NULL OR cpa.end_night >= ?)
";

$characters_with_positions = db_fetch_all($conn, $characters_with_positions_query, "ss", [$default_night, $default_night]);
$position_holder_ids = array_column($characters_with_positions, 'id');

// Get all characters WITHOUT positions
$exclude_ids = !empty($position_holder_ids) ? implode(',', array_map('intval', $position_holder_ids)) : '0';
$characters_without_positions_query = "
    SELECT 
        c.id,
        c.character_name,
        c.clan,
        c.generation,
        c.concept,
        c.biography,
        c.nature,
        c.demeanor,
        c.pc
    FROM characters c
    WHERE c.id NOT IN ($exclude_ids)
    AND c.pc = 0
    ORDER BY c.character_name
";

$candidates = db_fetch_all($conn, $characters_without_positions_query);

// Score each candidate for Keeper of Elysium suitability
$scored_candidates = [];

foreach ($candidates as $char) {
    $score = 0;
    $reasons = [];
    
    // Get traits
    $traits_query = "SELECT trait_name, trait_category, trait_type 
                     FROM character_traits 
                     WHERE character_id = ? AND (trait_type IS NULL OR trait_type = 'positive')";
    $traits = db_fetch_all($conn, $traits_query, "i", [$char['id']]);
    
    // Get abilities
    $abilities_query = "SELECT ability_name, ability_category, level 
                        FROM character_abilities 
                        WHERE character_id = ?";
    $abilities = db_fetch_all($conn, $abilities_query, "i", [$char['id']]);
    
    // Get backgrounds
    $backgrounds_query = "SELECT background_name, level 
                          FROM character_backgrounds 
                          WHERE character_id = ?";
    $backgrounds = db_fetch_all($conn, $backgrounds_query, "i", [$char['id']]);
    
    // Scoring criteria for Keeper of Elysium
    
    // 1. Social traits (very important)
    $social_traits = ['Dignified', 'Commanding', 'Empathetic', 'Charismatic', 'Diplomatic', 
                      'Respectful', 'Calm', 'Composed', 'Gracious', 'Elegant', 'Refined'];
    foreach ($traits as $trait) {
        if ($trait['trait_category'] === 'Social') {
            if (in_array($trait['trait_name'], $social_traits)) {
                $score += 3;
                $reasons[] = "Has social trait: " . $trait['trait_name'];
            }
        }
    }
    
    // 2. Mental traits (important for maintaining order)
    $mental_traits = ['Observant', 'Disciplined', 'Alert', 'Cunning', 'Knowledgeable', 
                      'Perceptive', 'Focused', 'Analytical'];
    foreach ($traits as $trait) {
        if ($trait['trait_category'] === 'Mental') {
            if (in_array($trait['trait_name'], $mental_traits)) {
                $score += 2;
                $reasons[] = "Has mental trait: " . $trait['trait_name'];
            }
        }
    }
    
    // 3. Key abilities for Elysium management
    $key_abilities = [
        'Etiquette' => 5,
        'Leadership' => 4,
        'Security' => 3,
        'Investigation' => 2,
        'Subterfuge' => 2,
        'Intimidation' => 1
    ];
    
    foreach ($abilities as $ability) {
        $ability_name = $ability['ability_name'];
        if (isset($key_abilities[$ability_name])) {
            $ability_score = $key_abilities[$ability_name] * ($ability['level'] ?? 1);
            $score += $ability_score;
            $reasons[] = "Has " . $ability_name . " x" . ($ability['level'] ?? 1) . " (+" . $ability_score . " points)";
        }
    }
    
    // 4. Status and Influence backgrounds (very important)
    foreach ($backgrounds as $bg) {
        if ($bg['background_name'] === 'Status' && $bg['level'] >= 2) {
            $score += 5;
            $reasons[] = "Has Status " . $bg['level'];
        }
        if ($bg['background_name'] === 'Influence' && $bg['level'] >= 2) {
            $score += 4;
            $reasons[] = "Has Influence " . $bg['level'];
        }
        if ($bg['background_name'] === 'Resources' && $bg['level'] >= 3) {
            $score += 2;
            $reasons[] = "Has Resources " . $bg['level'] . " (useful for maintaining Elysium)";
        }
    }
    
    // 5. Clan suitability (some clans are better suited)
    $clan_scores = [
        'Toreador' => 3,  // Social, artistic, appreciate beauty
        'Ventrue' => 3,   // Leadership, authority
        'Tremere' => 2,   // Order, discipline
        'Malkavian' => 0,  // Unpredictable, not ideal
        'Brujah' => 0,    // Too volatile
        'Nosferatu' => 1, // Can work but not ideal
        'Gangrel' => 0    // Too wild
    ];
    if (isset($clan_scores[$char['clan']])) {
        $score += $clan_scores[$char['clan']];
        if ($clan_scores[$char['clan']] > 0) {
            $reasons[] = "Clan " . $char['clan'] . " is well-suited for this role";
        }
    }
    
    // 6. Generation (lower is better, but not too low)
    if ($char['generation'] >= 8 && $char['generation'] <= 11) {
        $score += 2;
        $reasons[] = "Generation " . $char['generation'] . " (appropriate authority level)";
    }
    
    // 7. Nature/Demeanor suitability
    $suitable_natures = ['Architect', 'Autocrat', 'Caregiver', 'Confidant', 'Director', 
                        'Judge', 'Martyr', 'Pedagogue', 'Perfectionist', 'Traditionalist'];
    if (in_array($char['nature'], $suitable_natures)) {
        $score += 2;
        $reasons[] = "Nature: " . $char['nature'];
    }
    if (in_array($char['demeanor'], $suitable_natures)) {
        $score += 1;
        $reasons[] = "Demeanor: " . $char['demeanor'];
    }
    
    // 8. Biography keywords
    $biography = strtolower($char['biography'] ?? '');
    $biography_keywords = ['elysium', 'court', 'diplomatic', 'neutral', 'respected', 
                           'maintain', 'order', 'peace', 'sanctuary', 'safe', 'security'];
    $keyword_count = 0;
    foreach ($biography_keywords as $keyword) {
        if (strpos($biography, $keyword) !== false) {
            $keyword_count++;
        }
    }
    if ($keyword_count > 0) {
        $score += $keyword_count;
        $reasons[] = "Biography mentions relevant concepts (" . $keyword_count . " keywords)";
    }
    
    // Store candidate with score
    $scored_candidates[] = [
        'character' => $char,
        'score' => $score,
        'reasons' => $reasons,
        'traits' => $traits,
        'abilities' => $abilities,
        'backgrounds' => $backgrounds
    ];
}

// Sort by score (highest first)
usort($scored_candidates, function($a, $b) {
    return $b['score'] - $a['score'];
});

// Get top 3 candidates
$top_candidates = array_slice($scored_candidates, 0, 3);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keeper of Elysium Candidates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #1a0f0f; color: #fff; padding: 20px; }
        .candidate-card { background: #2a1f1f; border: 1px solid #dc3545; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .score-badge { font-size: 1.5em; font-weight: bold; color: #28a745; }
        .reason-item { margin: 5px 0; padding-left: 10px; }
        .trait-badge { display: inline-block; margin: 2px; padding: 4px 8px; background: #3a2f2f; border-radius: 4px; font-size: 0.9em; }
        .ability-item { margin: 3px 0; }
        .background-item { margin: 3px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🏛️ Keeper of Elysium - Candidate Analysis</h1>
        
        <div class="alert alert-info mb-4">
            <strong>Analysis Summary:</strong> Examined <?php echo count($candidates); ?> characters without Camarilla positions.
            Showing top 3 candidates based on suitability scoring.
        </div>
        
        <?php if (empty($top_candidates)): ?>
            <div class="alert alert-warning">
                No suitable candidates found. All characters may already have positions, or none meet the criteria.
            </div>
        <?php else: ?>
            <?php foreach ($top_candidates as $index => $candidate): 
                $char = $candidate['character'];
                $score = $candidate['score'];
                $reasons = $candidate['reasons'];
                $traits = $candidate['traits'];
                $abilities = $candidate['abilities'];
                $backgrounds = $candidate['backgrounds'];
            ?>
                <div class="candidate-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-1">
                                #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($char['character_name']); ?>
                            </h2>
                            <p class="opacity-75 mb-0">
                                <?php echo htmlspecialchars($char['clan']); ?> • Generation <?php echo htmlspecialchars($char['generation']); ?>
                                <?php if ($char['concept']): ?>
                                    • <?php echo htmlspecialchars($char['concept']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="score-badge">
                            Score: <?php echo $score; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Why This Candidate:</h4>
                            <ul class="list-unstyled">
                                <?php foreach ($reasons as $reason): ?>
                                    <li class="reason-item">✓ <?php echo htmlspecialchars($reason); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($char['biography']): ?>
                                <h5 class="mt-3">Biography Excerpt:</h5>
                                <p class="opacity-75" style="max-height: 150px; overflow-y: auto;">
                                    <?php echo htmlspecialchars(substr($char['biography'], 0, 300)); ?>
                                    <?php if (strlen($char['biography']) > 300): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if (!empty($traits)): ?>
                                <h5>Traits:</h5>
                                <?php 
                                $traits_by_category = ['Physical' => [], 'Social' => [], 'Mental' => []];
                                foreach ($traits as $trait) {
                                    $traits_by_category[$trait['trait_category']][] = $trait['trait_name'];
                                }
                                foreach ($traits_by_category as $category => $trait_names):
                                    if (!empty($trait_names)):
                                ?>
                                    <div class="mb-2">
                                        <strong><?php echo $category; ?>:</strong>
                                        <?php foreach ($trait_names as $trait_name): ?>
                                            <span class="trait-badge"><?php echo htmlspecialchars($trait_name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($abilities)): ?>
                                <h5 class="mt-3">Relevant Abilities:</h5>
                                <?php 
                                $relevant_abilities = array_filter($abilities, function($a) {
                                    $key = ['Etiquette', 'Leadership', 'Security', 'Investigation', 'Subterfuge', 'Intimidation'];
                                    return in_array($a['ability_name'], $key);
                                });
                                if (!empty($relevant_abilities)):
                                    foreach ($relevant_abilities as $ability):
                                ?>
                                    <div class="ability-item">
                                        <strong><?php echo htmlspecialchars($ability['ability_name']); ?>:</strong> 
                                        x<?php echo htmlspecialchars($ability['level'] ?? 1); ?>
                                    </div>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <p class="opacity-75">No relevant abilities found</p>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($backgrounds)): ?>
                                <h5 class="mt-3">Backgrounds:</h5>
                                <?php foreach ($backgrounds as $bg): ?>
                                    <div class="background-item">
                                        <strong><?php echo htmlspecialchars($bg['background_name']); ?>:</strong> 
                                        <?php echo htmlspecialchars($bg['level']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="../lotn_char_create.php?id=<?php echo $char['id']; ?>" 
                           class="btn btn-primary" target="_blank">View Full Character</a>
                        <a href="camarilla_positions.php" class="btn btn-secondary">Back to Positions</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="mt-4">
            <h3>Scoring Criteria:</h3>
            <ul>
                <li><strong>Social Traits:</strong> +3 points each (Dignified, Commanding, Empathetic, etc.)</li>
                <li><strong>Mental Traits:</strong> +2 points each (Observant, Disciplined, Alert, etc.)</li>
                <li><strong>Key Abilities:</strong> Etiquette (x5), Leadership (x4), Security (x3), Investigation/Subterfuge (x2), Intimidation (x1) - multiplied by level</li>
                <li><strong>Status Background:</strong> +5 points (level 2+)</li>
                <li><strong>Influence Background:</strong> +4 points (level 2+)</li>
                <li><strong>Resources Background:</strong> +2 points (level 3+)</li>
                <li><strong>Clan Suitability:</strong> Toreador/Ventrue (+3), Tremere (+2), others vary</li>
                <li><strong>Generation:</strong> +2 points (8-11 range)</li>
                <li><strong>Nature/Demeanor:</strong> +2/+1 points for suitable archetypes</li>
                <li><strong>Biography Keywords:</strong> +1 point per relevant keyword</li>
            </ul>
        </div>
    </div>
</body>
</html>

