<?php
/**
 * Wraith: The Oblivion Character Creator
 * Version: 1.0.0
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/version.php';

session_start();

// Check for authentication bypass
require_once 'includes/auth_bypass.php';

// Check if user is logged in (or bypass is enabled)
if (!isset($_SESSION['user_id']) && !isAuthBypassEnabled()) {
    header("Location: login.php");
    exit();
}

// If bypass is enabled, set up guest session
if (isAuthBypassEnabled() && !isset($_SESSION['user_id'])) {
    setupBypassSession();
}

// Database connection
include 'includes/connect.php';

// Load archetypes from database for nature/demeanor dropdowns
$archetypes = [];
if ($conn) {
    $archetypes_query = "SELECT name FROM archetypes ORDER BY name ASC";
    $archetypes_result = @mysqli_query($conn, $archetypes_query);
    if ($archetypes_result) {
        while ($row = mysqli_fetch_assoc($archetypes_result)) {
            $archetypes[] = $row['name'];
        }
        mysqli_free_result($archetypes_result);
    }
}

$extra_css = [
  'css/style.css',
  'css/character_image.css',
  'css/exit-button.css',
  'css/wraith_char_create.css'
];
include __DIR__ . '/includes/header.php';
?>
<div class="container-xxl">
    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <div class="header">
                <h1 class="brand">⚰ Wraith: The Oblivion - Character Creation ⚰</h1>
                <div class="header-center">
                    <div class="user-info">
                        <span class="user-label">Logged in as:</span>
                        <span class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest User'; ?></span>
                    </div>
                    <div class="version-info">
                        <span class="version">v<?php echo LOTN_VERSION; ?></span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-actions d-flex justify-content-end align-items-center gap-2 mt-2">
                        <button type="button" id="saveHeaderBtn" class="btn btn-success save-btn" data-action="save" aria-label="Save character">Save</button>
                        <button type="button" id="exitEditorBtn" class="exit-inline" title="Exit without saving" aria-label="Exit without saving">Exit</button>
                    </div>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab active tab-btn" data-tab="identity">Page 1: Identity</button>
                <button class="tab tab-btn" data-tab="traits">Page 2: Traits</button>
                <button class="tab tab-btn" data-tab="shadow">Page 3: Shadow</button>
                <button class="tab tab-btn" data-tab="pathos">Page 4: Pathos/Corpus</button>
                <button class="tab tab-btn" data-tab="metadata">Page 5: Metadata</button>
            </div>
            
            <div class="tab-progress">
                <div class="tab-progress-bar" id="tabProgressBar"></div>
            </div>
            
            <form id="wraithCharacterForm">
                <input type="hidden" id="characterId" name="characterId" value="">
                <input type="hidden" id="imagePath" name="imagePath" value="">
                
                <!-- Page 1: Identity & Background -->
                <div class="tab-content active" id="identityTab">
                    <div class="tab-card">
                        <div class="card-header">
                            <h2 class="card-title">Identity & Background</h2>
                            <p class="card-subtitle">Core character information and death details</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="pc" name="pc" class="form-check-input" checked>
                                <label for="pc" class="form-check-label">Player Character (PC)</label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="characterName">Character Name *</label>
                                <input type="text" id="characterName" name="characterName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="shadowName">Shadow Name *</label>
                                <input type="text" id="shadowName" name="shadowName" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="playerName">Player Name *</label>
                                <input type="text" id="playerName" name="playerName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="chronicle">Chronicle</label>
                                <input type="text" id="chronicle" name="chronicle" class="form-control" value="Valley by Night">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="guild">Guild</label>
                                <select id="guild" name="guild" class="form-select">
                                    <option value="">Select Guild</option>
                                    <option value="Artificers">Artificers</option>
                                    <option value="Chanteurs">Chanteurs</option>
                                    <option value="Harbingers">Harbingers</option>
                                    <option value="Masquers">Masquers</option>
                                    <option value="Monitors">Monitors</option>
                                    <option value="Pardoners">Pardoners</option>
                                    <option value="Proctors">Proctors</option>
                                    <option value="Sandmen">Sandmen</option>
                                    <option value="Spooks">Spooks</option>
                                    <option value="Usurers">Usurers</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="circle">Circle</label>
                                <input type="text" id="circle" name="circle" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="legionAtDeath">Legion at Death</label>
                                <select id="legionAtDeath" name="legionAtDeath" class="form-select">
                                    <option value="">Select Legion</option>
                                    <option value="Grim Legion">Grim Legion</option>
                                    <option value="Legion of Fate">Legion of Fate</option>
                                    <option value="Legion of Paupers">Legion of Paupers</option>
                                    <option value="Legion of Scribes">Legion of Scribes</option>
                                    <option value="Silent Legion">Silent Legion</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dateOfDeath">Date of Death</label>
                                <input type="date" id="dateOfDeath" name="dateOfDeath" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="causeOfDeath">Cause of Death</label>
                            <textarea id="causeOfDeath" name="causeOfDeath" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nature">Nature</label>
                                <select id="nature" name="nature" class="form-select">
                                    <option value="">Select Nature</option>
                                    <?php foreach ($archetypes as $arch): ?>
                                        <option value="<?php echo htmlspecialchars($arch); ?>"><?php echo htmlspecialchars($arch); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="demeanor">Demeanor</label>
                                <select id="demeanor" name="demeanor" class="form-select">
                                    <option value="">Select Demeanor</option>
                                    <?php foreach ($archetypes as $arch): ?>
                                        <option value="<?php echo htmlspecialchars($arch); ?>"><?php echo htmlspecialchars($arch); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="concept">Concept</label>
                            <input type="text" id="concept" name="concept" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="appearance">Mortal Appearance</label>
                            <textarea id="appearance" name="appearance" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="ghostlyAppearance">Ghostly Appearance</label>
                            <textarea id="ghostlyAppearance" name="ghostlyAppearance" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Fetters</label>
                            <div id="fettersList"></div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addFetterBtn">Add Fetter</button>
                        </div>
                        
                        <div class="form-group">
                            <label>Passions</label>
                            <div id="passionsList"></div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addPassionBtn">Add Passion</button>
                        </div>
                    </div>
                </div>
                
                <!-- Page 2: Traits -->
                <div class="tab-content" id="traitsTab">
                    <div class="tab-card">
                        <div class="card-header">
                            <h2 class="card-title">Traits</h2>
                            <p class="card-subtitle">Attributes, Abilities, Backgrounds, and Arcanoi</p>
                        </div>
                        
                        <!-- Attributes Section -->
                        <h3>Attributes</h3>
                        <div class="info-box">
                            <strong>Attribute Distribution:</strong> You have points to distribute across Physical, Social, and Mental attributes.
                            <ul>
                                <li>Each attribute starts at 1 and can be raised to 5</li>
                                <li>Click the attribute buttons below to increase their values</li>
                                <li>Click the × button to decrease an attribute value</li>
                            </ul>
                        </div>
                        
                        <div class="attributes-grid" id="attributesGrid">
                            <div class="attribute-category">
                                <h4>💪 Physical Attributes</h4>
                                <div class="attribute-progress">
                                    <div class="attribute-progress-label">
                                        <span>Total: <span id="physicalAttributesTotal">3</span></span>
                                    </div>
                                </div>
                                <div class="attribute-buttons">
                                    <button type="button" class="attribute-option-btn" data-category="Physical" data-attribute="Strength">Strength</button>
                                    <button type="button" class="attribute-option-btn" data-category="Physical" data-attribute="Dexterity">Dexterity</button>
                                    <button type="button" class="attribute-option-btn" data-category="Physical" data-attribute="Stamina">Stamina</button>
                                </div>
                                <div class="attribute-list" id="physicalAttributesList"></div>
                            </div>
                            
                            <div class="attribute-category">
                                <h4>👥 Social Attributes</h4>
                                <div class="attribute-progress">
                                    <div class="attribute-progress-label">
                                        <span>Total: <span id="socialAttributesTotal">3</span></span>
                                    </div>
                                </div>
                                <div class="attribute-buttons">
                                    <button type="button" class="attribute-option-btn" data-category="Social" data-attribute="Charisma">Charisma</button>
                                    <button type="button" class="attribute-option-btn" data-category="Social" data-attribute="Manipulation">Manipulation</button>
                                    <button type="button" class="attribute-option-btn" data-category="Social" data-attribute="Appearance">Appearance</button>
                                </div>
                                <div class="attribute-list" id="socialAttributesList"></div>
                            </div>
                            
                            <div class="attribute-category">
                                <h4>🧠 Mental Attributes</h4>
                                <div class="attribute-progress">
                                    <div class="attribute-progress-label">
                                        <span>Total: <span id="mentalAttributesTotal">3</span></span>
                                    </div>
                                </div>
                                <div class="attribute-buttons">
                                    <button type="button" class="attribute-option-btn" data-category="Mental" data-attribute="Perception">Perception</button>
                                    <button type="button" class="attribute-option-btn" data-category="Mental" data-attribute="Intelligence">Intelligence</button>
                                    <button type="button" class="attribute-option-btn" data-category="Mental" data-attribute="Wits">Wits</button>
                                </div>
                                <div class="attribute-list" id="mentalAttributesList"></div>
                            </div>
                        </div>
                        
                        <!-- Abilities Section -->
                        <h3>Abilities</h3>
                        <div class="info-box">
                            <strong>Ability Selection:</strong> Choose your abilities from the lists below.
                            <ul>
                                <li><strong>First 3 ability dots</strong> in each category are <strong>FREE</strong></li>
                                <li>Ability dots 4-5 cost <strong>2 XP each</strong></li>
                                <li><strong>Maximum 5 dots per individual ability</strong> (e.g., Athletics 5, Brawl 3, etc.)</li>
                                <li><strong>You can select the same ability multiple times</strong> - click the same ability button repeatedly to add dots</li>
                                <li><strong>Remove ability dots anytime</strong> - click the × button on any selected ability to remove dots</li>
                                <li>Each click adds 1 dot to that ability and counts toward your XP cost</li>
                            </ul>
                        </div>
                        
                        <!-- Physical Abilities -->
                        <div class="ability-section">
                            <div class="ability-header">
                                <h3>⚔️ Physical Abilities</h3>
                                <div class="ability-progress">
                                    <div class="ability-progress-label">
                                        <span><span id="physicalAbilitiesCountDisplay">0</span> dots</span>
                                        <span>3 required | 5 max per ability</span>
                                    </div>
                                    <div class="ability-progress-bar">
                                        <div class="ability-progress-fill incomplete" id="physicalAbilitiesProgressFill" style="width: 0%;">
                                            <div class="ability-progress-marker"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ability-options" id="physicalAbilitiesOptions">
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Athletics">Athletics</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Brawl">Brawl</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Dodge">Dodge</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Firearms">Firearms</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Melee">Melee</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Security">Security</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Stealth">Stealth</button>
                                <button type="button" class="ability-option-btn" data-category="Physical" data-ability="Survival">Survival</button>
                            </div>
                            <div class="ability-list" id="physicalAbilitiesList"></div>
                        </div>
                        
                        <!-- Social Abilities -->
                        <div class="ability-section">
                            <div class="ability-header">
                                <h3>💬 Social Abilities</h3>
                                <div class="ability-progress">
                                    <div class="ability-progress-label">
                                        <span><span id="socialAbilitiesCountDisplay">0</span> dots</span>
                                        <span>3 required | 5 max per ability</span>
                                    </div>
                                    <div class="ability-progress-bar">
                                        <div class="ability-progress-fill incomplete" id="socialAbilitiesProgressFill" style="width: 0%;">
                                            <div class="ability-progress-marker"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ability-options" id="socialAbilitiesOptions">
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Animal Ken">Animal Ken</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Empathy">Empathy</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Expression">Expression</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Intimidation">Intimidation</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Leadership">Leadership</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Subterfuge">Subterfuge</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Streetwise">Streetwise</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Etiquette">Etiquette</button>
                                <button type="button" class="ability-option-btn" data-category="Social" data-ability="Performance">Performance</button>
                            </div>
                            <div class="ability-list" id="socialAbilitiesList"></div>
                        </div>
                        
                        <!-- Mental Abilities -->
                        <div class="ability-section">
                            <div class="ability-header">
                                <h3>🧠 Mental Abilities</h3>
                                <div class="ability-progress">
                                    <div class="ability-progress-label">
                                        <span><span id="mentalAbilitiesCountDisplay">0</span> dots</span>
                                        <span>3 required | 5 max per ability</span>
                                    </div>
                                    <div class="ability-progress-bar">
                                        <div class="ability-progress-fill incomplete" id="mentalAbilitiesProgressFill" style="width: 0%;">
                                            <div class="ability-progress-marker"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ability-options" id="mentalAbilitiesOptions">
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Academics">Academics</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Computer">Computer</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Finance">Finance</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Investigation">Investigation</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Law">Law</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Linguistics">Linguistics</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Medicine">Medicine</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Occult">Occult</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Politics">Politics</button>
                                <button type="button" class="ability-option-btn" data-category="Mental" data-ability="Science">Science</button>
                            </div>
                            <div class="ability-list" id="mentalAbilitiesList"></div>
                        </div>
                        
                        <!-- Optional Abilities -->
                        <div class="ability-section">
                            <div class="ability-header">
                                <h3>🧩 Optional Abilities</h3>
                                <div class="ability-progress">
                                    <div class="ability-progress-label">
                                        <span><span id="optionalAbilitiesCountDisplay">0</span> dots</span>
                                        <span>0 required | 5 max per ability</span>
                                    </div>
                                    <div class="ability-progress-bar">
                                        <div class="ability-progress-fill incomplete" id="optionalAbilitiesProgressFill" style="width: 0%;">
                                            <div class="ability-progress-marker"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ability-options" id="optionalAbilitiesOptions">
                                <button type="button" class="ability-option-btn" data-category="Optional" data-ability="Alertness">Alertness</button>
                                <button type="button" class="ability-option-btn" data-category="Optional" data-ability="Awareness">Awareness</button>
                                <button type="button" class="ability-option-btn" data-category="Optional" data-ability="Drive">Drive</button>
                                <button type="button" class="ability-option-btn" data-category="Optional" data-ability="Crafts">Crafts</button>
                                <button type="button" class="ability-option-btn" data-category="Optional" data-ability="Firecraft">Firecraft</button>
                            </div>
                            <div class="ability-list" id="optionalAbilitiesList"></div>
                        </div>
                        
                        <!-- Backgrounds Section -->
                        <h3>Backgrounds</h3>
                        <div class="info-box">
                            <strong>Wraith Backgrounds:</strong> Select your character's resources, connections, and influence in the Shadowlands.
                            <ul>
                                <li>Click the level buttons (1-5) to set a background level</li>
                                <li>Available backgrounds: Memories, Status, Allies, Relic, Artifact, Haunt, Past Life, Notoriety, Requiem, Destiny</li>
                            </ul>
                        </div>
                        
                        <div class="backgrounds-summary">
                            <div class="summary-item">
                                <span class="summary-label">Total Background Points:</span>
                                <span class="summary-value" id="backgroundsTotal">0</span>
                            </div>
                        </div>
                        
                        <div class="backgrounds-container" id="backgroundsContainer">
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Memories</h4>
                                    <p class="background-description">Recollection of mortal life and connections</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Memories" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Memories" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Memories" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Memories" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Memories" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Status</h4>
                                    <p class="background-description">Social standing in Stygia</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Status" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Status" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Status" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Status" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Status" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Allies</h4>
                                    <p class="background-description">Friends and contacts among the dead</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Allies" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Allies" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Allies" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Allies" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Allies" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Relic</h4>
                                    <p class="background-description">Items from life that anchor you to the world</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Relic" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Relic" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Relic" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Relic" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Relic" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Artifact</h4>
                                    <p class="background-description">Powerful items from the Shadowlands</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Artifact" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Artifact" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Artifact" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Artifact" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Artifact" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Haunt</h4>
                                    <p class="background-description">A place in the Shadowlands you call home</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Haunt" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Haunt" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Haunt" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Haunt" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Haunt" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Past Life</h4>
                                    <p class="background-description">Knowledge and skills from previous incarnations</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Past Life" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Past Life" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Past Life" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Past Life" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Past Life" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Notoriety</h4>
                                    <p class="background-description">Infamy and reputation among wraiths</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Notoriety" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Notoriety" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Notoriety" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Notoriety" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Notoriety" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Requiem</h4>
                                    <p class="background-description">Funeral rites and death ceremonies</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Requiem" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Requiem" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Requiem" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Requiem" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Requiem" data-level="5">5</button>
                                </div>
                            </div>
                            
                            <div class="background-section">
                                <div class="background-header">
                                    <h4>Destiny</h4>
                                    <p class="background-description">Fate and purpose in the Underworld</p>
                                </div>
                                <div class="background-options">
                                    <button type="button" class="background-option-btn" data-background="Destiny" data-level="1">1</button>
                                    <button type="button" class="background-option-btn" data-background="Destiny" data-level="2">2</button>
                                    <button type="button" class="background-option-btn" data-background="Destiny" data-level="3">3</button>
                                    <button type="button" class="background-option-btn" data-background="Destiny" data-level="4">4</button>
                                    <button type="button" class="background-option-btn" data-background="Destiny" data-level="5">5</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="backgrounds-list" id="backgroundsList"></div>
                        
                        <!-- Arcanoi Section -->
                        <h3>Arcanoi</h3>
                        <div id="arcanoiList"></div>
                        <button type="button" class="btn btn-sm btn-secondary" id="addArcanoiBtn">Add Arcanoi</button>
                    </div>
                </div>
                
                <!-- Page 3: Shadow Sheet -->
                <div class="tab-content" id="shadowTab">
                    <div class="tab-card">
                        <div class="card-header">
                            <h2 class="card-title">Shadow Sheet</h2>
                            <p class="card-subtitle">Shadow archetype, angst, and dark passions</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="shadowArchetype">Shadow Archetype</label>
                            <select id="shadowArchetype" name="shadowArchetype" class="form-select">
                                <option value="">Select Archetype</option>
                                <option value="The Tempter">The Tempter</option>
                                <option value="The Judge">The Judge</option>
                                <option value="The Destroyer">The Destroyer</option>
                                <option value="The Martyr">The Martyr</option>
                                <option value="The Monster">The Monster</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="angstCurrent">Current Angst</label>
                                <input type="number" id="angstCurrent" name="angstCurrent" min="0" max="10" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="angstPermanent">Permanent Angst</label>
                                <input type="number" id="angstPermanent" name="angstPermanent" min="0" max="10" value="0" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Dark Passions</label>
                            <div id="darkPassionsList"></div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addDarkPassionBtn">Add Dark Passion</button>
                        </div>
                        
                        <div class="form-group">
                            <label for="thorns">Thorns</label>
                            <textarea id="thorns" name="thorns" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="shadowTraits">Shadow Traits</label>
                            <textarea id="shadowTraits" name="shadowTraits" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="shadowNotes">Shadow Notes</label>
                            <textarea id="shadowNotes" name="shadowNotes" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Page 4: Pathos & Corpus -->
                <div class="tab-content" id="pathosTab">
                    <div class="tab-card">
                        <div class="card-header">
                            <h2 class="card-title">Pathos & Corpus</h2>
                            <p class="card-subtitle">Health, pathos, corpus, and harrowing</p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pathosCurrent">Current Pathos</label>
                                <input type="number" id="pathosCurrent" name="pathosCurrent" min="0" max="10" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="pathosMax">Maximum Pathos</label>
                                <input type="number" id="pathosMax" name="pathosMax" min="0" max="10" value="0" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="corpusCurrent">Current Corpus</label>
                                <input type="number" id="corpusCurrent" name="corpusCurrent" min="0" max="10" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="corpusMax">Maximum Corpus</label>
                                <input type="number" id="corpusMax" name="corpusMax" min="0" max="10" value="0" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Health/Corpus Track</label>
                            <div id="healthTrack" class="health-track">
                                <p class="helper-text">Health levels will be displayed here</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastHarrowingDate">Last Harrowing Date</label>
                            <input type="date" id="lastHarrowingDate" name="lastHarrowingDate" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="harrowingNotes">Harrowing Notes</label>
                            <textarea id="harrowingNotes" name="harrowingNotes" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Page 5: Metadata -->
                <div class="tab-content" id="metadataTab">
                    <div class="tab-card">
                        <div class="card-header">
                            <h2 class="card-title">Metadata</h2>
                            <p class="card-subtitle">Experience points, notes, relationships, and artifacts</p>
                        </div>
                        
                        <h3>Experience Points</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="xpTotal">Total XP</label>
                                <input type="number" id="xpTotal" name="xpTotal" min="0" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="xpSpent">Spent XP</label>
                                <input type="number" id="xpSpent" name="xpSpent" min="0" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="xpAvailable">Available XP</label>
                                <input type="number" id="xpAvailable" name="xpAvailable" min="0" value="0" class="form-control" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shadowXpTotal">Total Shadow XP</label>
                                <input type="number" id="shadowXpTotal" name="shadowXpTotal" min="0" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="shadowXpSpent">Spent Shadow XP</label>
                                <input type="number" id="shadowXpSpent" name="shadowXpSpent" min="0" value="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="shadowXpAvailable">Available Shadow XP</label>
                                <input type="number" id="shadowXpAvailable" name="shadowXpAvailable" min="0" value="0" class="form-control" readonly>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="willpowerPermanent">Permanent Willpower</label>
                            <input type="number" id="willpowerPermanent" name="willpowerPermanent" min="1" max="10" value="5" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="willpowerCurrent">Current Willpower</label>
                            <input type="number" id="willpowerCurrent" name="willpowerCurrent" min="0" max="10" value="5" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="5"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="biography">Biography</label>
                            <textarea id="biography" name="biography" class="form-control" rows="10"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Relationships</label>
                            <div id="relationshipsList"></div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addRelationshipBtn">Add Relationship</button>
                        </div>
                        
                        <div class="form-group">
                            <label>Artifacts</label>
                            <div id="artifactsList"></div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addArtifactBtn">Add Artifact</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/wraith_char_create.js"></script>
<script src="js/exit-editor.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

