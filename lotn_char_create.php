<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>lotn_char_create</title>
</head>

<body>
	<?php
// LOTN Character Creator - Version 0.2.1
define('LOTN_VERSION', '0.2.1');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once __DIR__ . '/includes/supabase_client.php';

// Fetch Nature/Demeanor options from database
$nature_demeanor_options = [];
$fallback_options = [
    'Architect', 'Autist', 'Bon Vivant', 'Bravo', 'Caregiver', 'Capitalist',
    'Competitor', 'Conformist', 'Conniver', 'Curmudgeon', 'Deviant', 'Director',
    'Fanatic', 'Gallant', 'Judge', 'Loner', 'Martyr', 'Masochist', 'Monster',
    'Pedagogue', 'Penitent', 'Perfectionist', 'Rebel', 'Rogue', 'Survivor',
    'Thrill-Seeker', 'Traditionalist', 'Visionary'
];

try {
    $natureDemeanorRows = supabase_table_get('Nature_Demeanor', [
        'select' => 'name',
        'order' => 'display_order.asc'
    ]);
    if (!empty($natureDemeanorRows)) {
        foreach ($natureDemeanorRows as $row) {
            if (!empty($row['name'])) {
                $nature_demeanor_options[] = $row['name'];
            }
        }
    }
    if (empty($nature_demeanor_options)) {
        $nature_demeanor_options = $fallback_options;
    }
} catch (Throwable $e) {
    error_log('lotn_char_create Nature_Demeanor query failed: ' . $e->getMessage());
    $nature_demeanor_options = $fallback_options;
}

// Fetch derangements from database
$derangements_data = [];

try {
    $derangementRows = supabase_table_get('derangements', [
        'select' => 'name,description',
        'order' => 'display_order.asc'
    ]);
    if (!empty($derangementRows)) {
        foreach ($derangementRows as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $derangements_data[] = [
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? '')
            ];
        }
    }
} catch (Throwable $e) {
    error_log('lotn_char_create derangements query failed: ' . $e->getMessage());
}

// Fetch traits from database
$traits_data = [
    'Physical' => ['positive' => [], 'negative' => []],
    'Social' => ['positive' => [], 'negative' => []],
    'Mental' => ['positive' => [], 'negative' => []]
];

try {
    $traitRows = supabase_table_get('traits', [
        'select' => 'trait_name,trait_category,is_negative',
        'order' => 'trait_category.asc,is_negative.asc,trait_name.asc'
    ]);
    if (!empty($traitRows)) {
        foreach ($traitRows as $row) {
            $category = (string) ($row['trait_category'] ?? '');
            $is_negative = (int) ($row['is_negative'] ?? 0);
            $type = $is_negative ? 'negative' : 'positive';
            
            if (isset($traits_data[$category])) {
                $traits_data[$category][$type][] = (string) ($row['trait_name'] ?? '');
            }
        }
    }
} catch (Throwable $e) {
    error_log('lotn_char_create traits query failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Character - Laws of the Night</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚜ Laws of the Night: Character Creation ⚜</h1>
            <div class="version-info">
                <span class="version">v<?php echo LOTN_VERSION; ?></span>
            </div>
            <div class="xp-tracker">
                <div class="label">Available XP</div>
                <div class="xp-display" id="xpDisplay">30</div>
                <div class="xp-label">Experience Points</div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab btn btn-outline-secondary active" onclick="showTab(0)">Basic Info</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(1)">Traits</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(2)">Abilities</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(3)">Disciplines</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(4)">Backgrounds</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(5)">Morality</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(6)">Merits & Flaws</button>
            <button class="tab btn btn-outline-secondary" onclick="showTab(7)">Final Details</button>
        </div>
        
        <form id="characterForm">
            <!-- Tab 1: Basic Info -->
            <div class="tab-content active" id="tab0">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h2 style="color: #8b0000; margin: 0;">Basic Information</h2>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="characterName">Character Name *</label>
                        <input type="text" id="characterName" name="characterName" required>
                    </div>
                    
                    <div class="form-group">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <label for="playerName" style="margin: 0; white-space: nowrap;">Player Name <span id="playerNameRequired" class="required-asterisk">*</span></label>
                            <input type="text" id="playerName" name="playerName" required style="flex: 0 1 auto;">
                            <div style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                <input type="checkbox" id="npc" name="npc">
                                <label for="npc" style="margin: 0;">NPC</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="chronicle">Chronicle</label>
                    <input type="text" id="chronicle" name="chronicle" value="Valley by Night">
                    <div class="helper-text">Name of the campaign/game</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nature">Nature *</label>
                        <select id="nature" name="nature" required>
                            <option value="">Select Nature...</option>
                            <?php foreach ($nature_demeanor_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="helper-text">True personality</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="demeanor">Demeanor *</label>
                        <select id="demeanor" name="demeanor" required>
                            <option value="">Select Demeanor...</option>
                            <?php foreach ($nature_demeanor_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="helper-text">Public personality</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="derangement">Derangement</label>
                    <select id="derangement" name="derangement" class="derangement-select">
                        <option value="">Select Derangement...</option>
                        <?php foreach ($derangements_data as $derangement): ?>
                            <option value="<?php echo htmlspecialchars($derangement['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                    data-description="<?php echo htmlspecialchars($derangement['description'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($derangement['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="helper-text">Mental or psychological disorder (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="concept">Concept *</label>
                    <input type="text" id="concept" name="concept" required>
                    <div class="helper-text">Brief description of character concept (e.g., "Street Gang Leader", "Tortured Artist")</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="clan">Clan *</label>
                        <select id="clan" name="clan" required>
                            <option value="">Select Clan...</option>
                            <option value="Assamite">Assamite</option>
                            <option value="Brujah">Brujah</option>
                            <option value="Followers of Set">Followers of Set</option>
                            <option value="Gangrel">Gangrel</option>
                            <option value="Giovanni">Giovanni</option>
                            <option value="Lasombra">Lasombra</option>
                            <option value="Malkavian">Malkavian</option>
                            <option value="Nosferatu">Nosferatu</option>
                            <option value="Ravnos">Ravnos</option>
                            <option value="Toreador">Toreador</option>
                            <option value="Tremere">Tremere</option>
                            <option value="Tzimisce">Tzimisce</option>
                            <option value="Ventrue">Ventrue</option>
                            <option value="Caitiff">Caitiff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="generation">Generation *</label>
                        <select id="generation" name="generation" required>
                            <option value="">Select Generation...</option>
                            <option value="13" selected>13th Generation</option>
                            <option value="12">12th Generation</option>
                            <option value="11">11th Generation</option>
                            <option value="10">10th Generation</option>
                            <option value="9">9th Generation</option>
                            <option value="8">8th Generation</option>
                            <option value="7">7th Generation</option>
                        </select>
                        <div class="helper-text">Distance from Caine</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sire">Sire</label>
                    <input type="text" id="sire" name="sire">
                    <div class="helper-text">Name of vampire who embraced this character</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="pc" name="pc" checked>
                        <label for="pc" style="margin: 0;">Player Character (PC)</label>
                    </div>
                    <div class="helper-text">Uncheck if this is an NPC</div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" disabled>← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(1)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 2: Traits -->
            <div class="tab-content" id="tab1">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Traits</h2>
                
                <div class="info-box">
                    <strong>Trait Selection:</strong> Choose your traits from the lists below.
                    <ul>
                        <li><strong>First 7 traits</strong> in each category are <strong>FREE</strong></li>
                        <li>Traits 8-10 cost <strong>4 XP each</strong></li>
                        <li>Maximum 10 traits per category at character creation</li>
                        <li><strong>You can select the same trait multiple times</strong> - click the same trait button repeatedly</li>
                        <li><strong>Remove traits anytime</strong> - click the × button on any selected trait to remove it</li>
                        <li><strong>Negative traits give +4 XP each</strong> - select from the red negative trait sections below</li>
                        <li>Each selection counts toward your trait total and XP cost</li>
                    </ul>
                </div>
                
                <!-- Physical Traits -->
                <div class="trait-section">
                    <div class="trait-header">
                        <h3>Physical Traits</h3>
                        <div class="trait-progress">
                            <div class="trait-progress-label">
                                <span><span id="physicalCountDisplay">0</span> selected</span>
                                <span>7 required | 10 maximum</span>
                            </div>
                            <div class="trait-progress-bar">
                                <div class="trait-progress-fill incomplete" id="physicalProgressFill" style="width: 0%;">
                                    <div class="trait-progress-marker"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trait-options" id="physicalOptions">
                        <?php foreach ($traits_data['Physical']['positive'] as $trait): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="selectTrait('Physical', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="trait-list" id="physicalTraitList">
                    </div>
                    
                    <!-- Physical Negative Traits -->
                    <div class="negative-traits-section">
                        <h4>Physical Negative Traits (+4 XP each)</h4>
                        <div class="trait-options" id="physicalNegativeOptions">
                            <?php foreach ($traits_data['Physical']['negative'] as $trait): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger negative" onclick="selectNegativeTrait('Physical', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="trait-list" id="physicalNegativeTraitList">
                        </div>
                    </div>
                </div>
                
                <!-- Social Traits -->
                <div class="trait-section">
                    <div class="trait-header">
                        <h3>Social Traits</h3>
                        <div class="trait-progress">
                            <div class="trait-progress-label">
                                <span><span id="socialCountDisplay">0</span> selected</span>
                                <span>7 required | 10 maximum</span>
                            </div>
                            <div class="trait-progress-bar">
                                <div class="trait-progress-fill incomplete" id="socialProgressFill" style="width: 0%;">
                                    <div class="trait-progress-marker"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trait-options" id="socialOptions">
                        <?php foreach ($traits_data['Social']['positive'] as $trait): ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectTrait('Social', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="trait-list" id="socialTraitList">
                    </div>
                    
                    <!-- Social Negative Traits -->
                    <div class="negative-traits-section">
                        <h4>Social Negative Traits (+4 XP each)</h4>
                        <div class="trait-options" id="socialNegativeOptions">
                            <?php foreach ($traits_data['Social']['negative'] as $trait): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger negative" onclick="selectNegativeTrait('Social', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="trait-list" id="socialNegativeTraitList">
                        </div>
                    </div>
                </div>
                
                <!-- Mental Traits -->
                <div class="trait-section">
                    <div class="trait-header">
                        <h3>Mental Traits</h3>
                        <div class="trait-progress">
                            <div class="trait-progress-label">
                                <span><span id="mentalCountDisplay">0</span> selected</span>
                                <span>7 required | 10 maximum</span>
                            </div>
                            <div class="trait-progress-bar">
                                <div class="trait-progress-fill incomplete" id="mentalProgressFill" style="width: 0%;">
                                    <div class="trait-progress-marker"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trait-options" id="mentalOptions">
                        <?php foreach ($traits_data['Mental']['positive'] as $trait): ?>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="selectTrait('Mental', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="trait-list" id="mentalTraitList">
                    </div>
                    
                    <!-- Mental Negative Traits -->
                    <div class="negative-traits-section">
                        <h4>Mental Negative Traits (+4 XP each)</h4>
                        <div class="trait-options" id="mentalNegativeOptions">
                            <?php foreach ($traits_data['Mental']['negative'] as $trait): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger negative" onclick="selectNegativeTrait('Mental', '<?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($trait, ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="trait-list" id="mentalNegativeTraitList">
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(0)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(2)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 3: Abilities -->
            <div class="tab-content" id="tab2">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Abilities</h2>
                <p>Abilities section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(1)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(3)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 4: Disciplines -->
            <div class="tab-content" id="tab3">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Disciplines</h2>
                <p>Disciplines section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(2)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(4)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 5: Backgrounds -->
            <div class="tab-content" id="tab4">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Backgrounds</h2>
                <p>Backgrounds section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(3)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(5)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 6: Morality -->
            <div class="tab-content" id="tab5">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Morality</h2>
                <p>Morality & Stats section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(4)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(6)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 7: Merits & Flaws -->
            <div class="tab-content" id="tab6">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Merits & Flaws</h2>
                <p>Merits & Flaws section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(5)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" onclick="showTab(7)">Next →</button>
                </div>
            </div>
            
            <!-- Tab 8: Final Details -->
            <div class="tab-content" id="tab7">
                <h2 style="color: #8b0000; margin-bottom: 25px;">Final Details</h2>
                <p>Final Details section - Coming soon!</p>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="showTab(6)">← Previous</button>
                    <button type="button" class="save-btn btn btn-primary">💾 Save Character</button>
                    <button type="button" class="btn btn-secondary" disabled>Next →</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Load module system -->
    <script src="js/modules/core/StateManager.js"></script>
    <script src="js/modules/core/UIManager.js"></script>
    <script src="js/modules/core/EventManager.js"></script>
    <script src="js/modules/core/DataManager.js"></script>
    <script src="js/modules/core/NotificationManager.js"></script>
    <script src="js/modules/core/ValidationManager.js"></script>
    <script src="js/modules/ui/TabManager.js"></script>
    <script src="js/modules/ui/PreviewManager.js"></script>
    <script src="js/modules/systems/TraitSystem.js"></script>
    <script src="js/modules/systems/AbilitySystem.js"></script>
    <script src="js/modules/systems/DisciplineSystem.js"></script>
    <script src="js/modules/systems/BackgroundSystem.js"></script>
    <script src="js/modules/systems/MoralitySystem.js"></script>
    <script src="js/modules/systems/MeritsFlawsSystem.js"></script>
    <script src="js/modules/systems/HealthWillpowerSystem.js"></script>
    <script src="js/modules/systems/CashSystem.js"></script>
    <script src="js/modules/main.js"></script>
    <script src="js/lotn_char_create.js"></script>
    
    <script>
    // Initialize Bootstrap popovers for derangement dropdown
    document.addEventListener('DOMContentLoaded', function() {
        const derangementSelect = document.getElementById('derangement');
        if (!derangementSelect) return;
        
        // Store derangement descriptions
        const derangementDescriptions = {};
        <?php foreach ($derangements_data as $derangement): ?>
        derangementDescriptions['<?php echo htmlspecialchars($derangement['name'], ENT_QUOTES, 'UTF-8'); ?>'] = <?php echo json_encode($derangement['description']); ?>;
        <?php endforeach; ?>
        
        // Create description display element
        const descriptionDiv = document.createElement('div');
        descriptionDiv.className = 'derangement-description mt-2 p-2 bg-light border rounded';
        descriptionDiv.style.display = 'none';
        descriptionDiv.style.fontSize = '0.9em';
        descriptionDiv.style.color = '#666';
        derangementSelect.parentNode.appendChild(descriptionDiv);
        
        // Update description when selection changes
        function updateDescription() {
            const selectedValue = derangementSelect.value;
            if (selectedValue && derangementDescriptions[selectedValue]) {
                descriptionDiv.innerHTML = '<strong>' + escapeHtml(selectedValue) + ':</strong> ' + escapeHtml(derangementDescriptions[selectedValue]);
                descriptionDiv.style.display = 'block';
            } else {
                descriptionDiv.style.display = 'none';
            }
        }
        
        derangementSelect.addEventListener('change', updateDescription);
        
        // Initialize Bootstrap popover for hover on select element
        if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
            const popover = new bootstrap.Popover(derangementSelect, {
                trigger: 'hover focus',
                placement: 'right',
                html: true,
                content: function() {
                    const selectedValue = derangementSelect.value;
                    if (selectedValue && derangementDescriptions[selectedValue]) {
                        return '<div class="derangement-popover"><strong>' + 
                               escapeHtml(selectedValue) + '</strong><br><br>' + 
                               escapeHtml(derangementDescriptions[selectedValue]) + '</div>';
                    }
                    return '<div class="derangement-popover">Hover over or select a derangement to see its description</div>';
                }
            });
            
            // Update popover when selection changes
            derangementSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                if (selectedValue && derangementDescriptions[selectedValue]) {
                    popover.setContent({
                        '.popover-body': '<div class="derangement-popover"><strong>' + 
                                        escapeHtml(selectedValue) + '</strong><br><br>' + 
                                        escapeHtml(derangementDescriptions[selectedValue]) + '</div>'
                    });
                } else {
                    popover.setContent({
                        '.popover-body': '<div class="derangement-popover">Hover over or select a derangement to see its description</div>'
                    });
                }
            });
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
    </script>
</body>
</html>