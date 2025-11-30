<?php
/**
 * Character View Modal Include
 * Provides reusable modal HTML and JavaScript for viewing character details
 * 
 * @param string $apiEndpoint - Path to the character API endpoint (default: '/admin/view_character_api.php')
 * @param string $modalId - ID for the modal element (default: 'viewCharacterModal')
 */

// Set defaults if not provided
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : '/admin/view_character_api.php';
$modalId = isset($modalId) ? $modalId : 'viewCharacterModal';

// Calculate path prefix for CSS (same logic as header.php)
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);
if ($script_dir === '/') {
    $path_prefix = '';
} else {
    $path_segments = trim($script_dir, '/');
    $segment_count = $path_segments === '' ? 0 : substr_count($path_segments, '/') + 1;
    $path_prefix = str_repeat('../', $segment_count);
}
?>

<!-- Character View Modal CSS -->
<link rel="stylesheet" href="<?php echo htmlspecialchars($path_prefix . 'css/character_view.css', ENT_QUOTES, 'UTF-8'); ?>">

<!-- Character View Modal -->
<div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1" aria-labelledby="viewCharacterName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2" id="viewCharacterName">
                        <span aria-hidden="true">📄</span>
                        <span>Character Details</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <div class="view-mode-toggle btn-group btn-group-sm" role="group" aria-label="View mode">
                        <button type="button" class="mode-btn btn btn-outline-danger active" data-view-mode="compact">Compact</button>
                        <button type="button" class="mode-btn btn btn-outline-danger" data-view-mode="full">Details</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <div id="characterHeader" class="character-header-section mb-3" aria-live="polite">
                    <!-- Populated dynamically -->
                </div>
                <div id="viewCharacterContent" class="view-content" aria-live="polite">
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // Configuration
    const API_ENDPOINT = <?php echo json_encode($apiEndpoint); ?>;
    const MODAL_ID = <?php echo json_encode($modalId); ?>;
    
    // State
    let currentViewMode = 'compact';
    let currentViewData = null;
    let viewModalInstance = null;
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCharacterView);
    } else {
        initializeCharacterView();
    }
    
    function initializeCharacterView() {
        initializeViewModeToggle();
    }
    
    // Global function to open character view
    window.viewCharacter = function(characterId) {
        if (!characterId) return;
        
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Character view modal not found. Modal ID: ' + MODAL_ID);
            return;
        }
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error('Bootstrap modal runtime not loaded; cannot open character view.');
            return;
        }
        
        viewModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true
        });
        
        if (!modalEl.dataset.viewModalInit) {
            // Remove focus from any focused element before modal is hidden
            modalEl.addEventListener('hide.bs.modal', function() {
                const activeElement = document.activeElement;
                if (activeElement && modalEl.contains(activeElement)) {
                    activeElement.blur();
                }
            });
            
            modalEl.addEventListener('hidden.bs.modal', function() {
                currentViewData = null;
                const headerEl = document.getElementById('characterHeader');
                const contentEl = document.getElementById('viewCharacterContent');
                if (headerEl) {
                    headerEl.innerHTML = '';
                }
                if (contentEl) {
                    contentEl.innerHTML = '';
                    contentEl.removeAttribute('aria-busy');
                }
                setViewMode('compact');
            });
            modalEl.dataset.viewModalInit = 'true';
        }
        
        const header = document.getElementById('characterHeader');
        const content = document.getElementById('viewCharacterContent');
        const title = document.getElementById('viewCharacterName');
        
        if (header) {
            header.innerHTML = '';
        }
        
        if (title) {
            title.textContent = 'Character Details';
        }
        
        if (content) {
            content.setAttribute('aria-busy', 'true');
            content.textContent = 'Loading...';
        }
        
        // Reset to compact mode by default each time modal opens
        setViewMode('compact');
        
        viewModalInstance.show();
        
        const requestUrl = API_ENDPOINT + '?id=' + encodeURIComponent(characterId) + '&_t=' + Date.now();
        
        fetch(requestUrl)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data && data.success) {
                    // Normalize Wraith API response format to match VtM format
                    const isWraithApi = requestUrl.includes('view_wraith_character_api.php');
                    if (isWraithApi && data.character) {
                        // Transform Wraith API response to match expected structure
                        const char = data.character;
                        
                        // Transform traits from Wraith format to VtM format
                        let traitsArray = [];
                        if (char.traits) {
                            const traits = char.traits;
                            if (traits.Physical && Array.isArray(traits.Physical)) {
                                traits.Physical.forEach(function(t) {
                                    let traitName = typeof t === 'string' ? t : t.trait_name || t;
                                    traitsArray.push({
                                        trait_name: traitName,
                                        trait_category: 'Physical'
                                    });
                                });
                            }
                            if (traits.Social && Array.isArray(traits.Social)) {
                                traits.Social.forEach(function(t) {
                                    let traitName = typeof t === 'string' ? t : t.trait_name || t;
                                    traitsArray.push({
                                        trait_name: traitName,
                                        trait_category: 'Social'
                                    });
                                });
                            }
                            if (traits.Mental && Array.isArray(traits.Mental)) {
                                traits.Mental.forEach(function(t) {
                                    let traitName = typeof t === 'string' ? t : t.trait_name || t;
                                    traitsArray.push({
                                        trait_name: traitName,
                                        trait_category: 'Mental'
                                    });
                                });
                            }
                        }
                        
                        currentViewData = {
                            success: true,
                            character: char,
                            traits: traitsArray,
                            abilities: char.abilities || [],
                            disciplines: char.arcanoi || [],
                            backgrounds: char.backgrounds || [],
                            morality: null,
                            merits_flaws: char.merits_flaws || [],
                            status: char.status_details || null,
                            coteries: [],
                            relationships: char.relationships || []
                        };
                    } else {
                        currentViewData = data;
                    }
                    if (title) {
                        title.textContent = currentViewData.character.character_name || 'Character Details';
                    }
                    renderCharacterView(currentViewMode);
                } else {
                    if (header) {
                        header.innerHTML = '';
                    }
                    if (content) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger mb-0';
                        alert.setAttribute('role', 'alert');
                        alert.textContent = 'Error: ' + (data && data.message ? data.message : 'Unknown error.');
                        content.innerHTML = '';
                        content.appendChild(alert);
                        content.setAttribute('aria-busy', 'false');
                    }
                }
            })
            .catch(function(error) {
                console.error('view_character_api error', error);
                if (header) {
                    header.innerHTML = '';
                }
                if (content) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger mb-0';
                    alert.setAttribute('role', 'alert');
                    alert.textContent = 'Error loading character.';
                    content.innerHTML = '';
                    content.appendChild(alert);
                    content.setAttribute('aria-busy', 'false');
                }
            });
    };
    
    function setViewMode(mode) {
        currentViewMode = mode;
        
        const modeButtons = document.querySelectorAll('.mode-btn');
        modeButtons.forEach(function(btn) {
            const btnMode = btn.dataset.viewMode || 'compact';
            const isActive = btnMode === mode;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        
        const modalContent = document.querySelector('.character-view-modal');
        if (modalContent) {
            if (mode === 'compact') {
                modalContent.classList.add('compact-mode');
            } else {
                modalContent.classList.remove('compact-mode');
            }
        }
        
        if (currentViewData) {
            renderCharacterView(mode);
        }
    }
    
    function initializeViewModeToggle() {
        const modeButtons = document.querySelectorAll('.view-mode-toggle .mode-btn');
        modeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(event) {
                event.preventDefault();
                const nextMode = btn.dataset.viewMode || 'compact';
                setViewMode(nextMode);
            });
        });
        // Ensure initial aria-pressed state
        setViewMode(currentViewMode);
    }
    
    function renderCharacterView(mode) {
        const char = currentViewData.character;
        const headerEl = document.getElementById('characterHeader');
        const contentEl = document.getElementById('viewCharacterContent');
        let headerHtml = '';
        let contentHtml = '';
        
        const escapeHtml = function(input) {
            return String(input)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };
        
        const normalizeValue = function(value) {
            if (value === null || value === undefined) {
                return null;
            }
            if (typeof value === 'string') {
                const trimmed = value.trim();
                return trimmed === '' ? null : trimmed;
            }
            return value;
        };
        
        const displayValue = function(value, fallback) {
            if (fallback === undefined) {
                fallback = 'N/A';
            }
            const normalized = normalizeValue(value);
            if (normalized === null) {
                return fallback;
            }
            return escapeHtml(normalized);
        };
        
        function clanLogoUrl(clan) {
            if (!clan) return null;
            const basePath = '../images/Clan%20Logos/';
            let clean = String(clan).trim().toLowerCase();
            // Remove emoji and special characters
            clean = clean.replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, '');
            clean = clean.replace(/[^\x00-\x7F]/g, '');
            const map = {
                'assamite': 'LogoClanAssamite.webp',
                'brujah': 'LogoClanBrujah.webp',
                'followers of set': 'LogoClanFollowersofSet.webp',
                'daughter of cacophony': 'LogoBloodlineDaughtersofCacophony.webp',
                'gangrel': 'LogoClanGangrel.webp',
                'giovanni': 'LogoClanGiovanni.webp',
                'lasombra': 'LogoClanLasombra.webp',
                'malkavian': 'LogoClanMalkavian.webp',
                'nosferatu': 'LogoClanNosferatu.webp',
                'ravnos': 'LogoClanRavnos.webp',
                'toreador': 'LogoClanToreador.webp',
                'tremere': 'LogoClanTremere.webp',
                'tzimisce': 'LogoClanTzimisce.webp',
                'ventrue': 'LogoClanVentrue.webp',
                'caitiff': 'LogoBloodlineCaitiff.webp'
            };
            const file = map[clean];
            if (!file) return null;
            const url = basePath + file;
            return url;
        }
        
        // Detect if this is a Wraith character
        const isWraith = !!(char.shadow_name || char.guild || char.circle);
        
        const hasPortrait = !!char.character_image;
        let fallbackUrl = null;
        if (isWraith) {
            // Wraith fallback image
            fallbackUrl = '../images/Clan Logos/WtOlogo.webp';
        } else {
            // VtM fallback to clan logo
            fallbackUrl = char.clan_logo_url || clanLogoUrl(char.clan);
        }
        const imageUrl = hasPortrait ? ('../uploads/characters/' + char.character_image) : fallbackUrl;
        const sanitizedImageUrl = imageUrl ? escapeHtml(imageUrl) : null;
        
        const rawState = normalizeValue(char.current_state || char.status) || 'active';
        const formattedState = escapeHtml(rawState.toString().charAt(0).toUpperCase() + rawState.toString().slice(1));
        const playerName = normalizeValue(char.player_name) || 'NPC';
        
        let summaryFields = [];
        if (isWraith) {
            // Wraith character fields
            summaryFields = [
                { label: 'Player', value: displayValue(playerName, 'NPC') },
                { label: 'Chronicle', value: displayValue(char.chronicle, 'N/A') },
                { label: 'Shadow Name', value: displayValue(char.shadow_name, 'N/A') },
                { label: 'Guild', value: displayValue(char.guild, 'Unknown') },
                { label: 'Circle', value: displayValue(char.circle, 'N/A') },
                { label: 'Legion at Death', value: displayValue(char.legion_at_death, 'N/A') },
                { label: 'Nature', value: displayValue(char.nature, 'N/A') },
                { label: 'Demeanor', value: displayValue(char.demeanor, 'N/A') },
                { label: 'Concept', value: displayValue(char.concept, 'N/A') }
            ];
        } else {
            // VtM character fields
            const generationValue = normalizeValue(char.generation);
            const generationDisplay = generationValue === null ? 'N/A' : escapeHtml(generationValue + 'th');
            summaryFields = [
                { label: 'Player', value: displayValue(playerName, 'NPC') },
                { label: 'Chronicle', value: displayValue(char.chronicle, 'N/A') },
                { label: 'Clan', value: displayValue(char.clan, 'Unknown') },
                { label: 'Generation', value: generationDisplay },
                { label: 'Nature', value: displayValue(char.nature, 'N/A') },
                { label: 'Demeanor', value: displayValue(char.demeanor, 'N/A') },
                { label: 'Sire', value: displayValue(char.sire, 'Unknown') },
                { label: 'Concept', value: displayValue(char.concept, 'N/A') }
            ];
        }
        
        headerHtml += '<div class="row g-4 align-items-start">';
        headerHtml += '<div class="col-lg-8">';
        headerHtml += '<div class="row g-2 character-summary">';
        summaryFields.forEach(field => {
            headerHtml += '<div class="col-5 col-sm-4 character-summary-label">' + field.label + '</div>';
            headerHtml += '<div class="col-7 col-sm-8 character-summary-value">' + field.value + '</div>';
        });
        headerHtml += '</div>';
        headerHtml += '</div>';
        
        headerHtml += '<div class="col-lg-4 col-xl-3 ms-lg-auto">';
        headerHtml += '<div class="character-portrait-wrapper">';
        headerHtml += '<div class="character-portrait-media">';
        if (sanitizedImageUrl) {
            const imageClass = isWraith && !hasPortrait ? 'character-portrait-image character-portrait-logo img-fluid' : 'character-portrait-image img-fluid';
            headerHtml += '<img src="' + sanitizedImageUrl + '" class="' + imageClass + '" alt="Character portrait" onerror="this.classList.add(\'d-none\'); this.nextElementSibling.classList.remove(\'d-none\');" />';
        }
        const placeholderClass = sanitizedImageUrl ? ' d-none' : '';
        headerHtml += '<div class="character-portrait-placeholder' + placeholderClass + '">No Image</div>';
        headerHtml += '</div>';
        headerHtml += '</div>';
        headerHtml += '</div>';
        headerHtml += '</div>';
        
        if (headerEl) {
            headerEl.innerHTML = headerHtml;
        }
        
        if (mode === 'compact') {
            contentHtml = '<div class="character-details compact">';
            contentHtml += '</div>';
            if (contentEl) {
                contentEl.innerHTML = contentHtml;
                contentEl.setAttribute('aria-busy', 'false');
            }
            return;
        }
        
        contentHtml = '<div class="character-details full">';
        
        // XP Information
        contentHtml += '<h3>Experience Points</h3>';
        contentHtml += '<div class="row g-3 mt-2">';
        if (isWraith) {
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Total XP:</strong> ' + (char.experience_total || 0) + '</p></div>';
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Spent XP:</strong> ' + (char.spent_xp || 0) + '</p></div>';
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-12"><p><strong>Available XP:</strong> ' + (char.experience_unspent || 0) + '</p></div>';
            if (char.shadow_xp_total > 0) {
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Shadow XP Total:</strong> ' + (char.shadow_xp_total || 0) + '</p></div>';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Shadow XP Spent:</strong> ' + (char.shadow_xp_spent || 0) + '</p></div>';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-12"><p><strong>Shadow XP Available:</strong> ' + (char.shadow_xp_available || 0) + '</p></div>';
            }
        } else {
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Total XP:</strong> ' + (char.total_xp || 0) + '</p></div>';
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Spent XP:</strong> ' + (char.spent_xp || 0) + '</p></div>';
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-12"><p><strong>Available XP:</strong> ' + ((char.total_xp || 0) - (char.spent_xp || 0)) + '</p></div>';
        }
        contentHtml += '</div>';
        
        // Character Traits
        contentHtml += '<h3>Character Traits</h3>';
        if (isWraith && char.traits) {
            // Wraith traits are stored as an object with Physical/Social/Mental arrays
            const traits = char.traits;
            const physical = Array.isArray(traits.Physical) ? traits.Physical : [];
            const social = Array.isArray(traits.Social) ? traits.Social : [];
            const mental = Array.isArray(traits.Mental) ? traits.Mental : [];
            
            if (physical.length > 0 || social.length > 0 || mental.length > 0) {
                contentHtml += '<div class="row g-3 mt-2">';
                
                if (physical.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6">';
                    contentHtml += '<h4>Physical</h4>';
                    contentHtml += '<div class="trait-list">';
                    physical.forEach(t => {
                        const traitName = typeof t === 'string' ? t : (t.trait_name || t);
                        contentHtml += '<span class="trait-badge">' + escapeHtml(traitName) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                if (social.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6">';
                    contentHtml += '<h4>Social</h4>';
                    contentHtml += '<div class="trait-list">';
                    social.forEach(t => {
                        const traitName = typeof t === 'string' ? t : (t.trait_name || t);
                        contentHtml += '<span class="trait-badge">' + escapeHtml(traitName) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                if (mental.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-12">';
                    contentHtml += '<h4>Mental</h4>';
                    contentHtml += '<div class="trait-list">';
                    mental.forEach(t => {
                        const traitName = typeof t === 'string' ? t : (t.trait_name || t);
                        contentHtml += '<span class="trait-badge">' + escapeHtml(traitName) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                contentHtml += '</div>';
            } else {
                contentHtml += '<p class="empty-state">No character traits found.</p>';
            }
        } else if (currentViewData.traits && currentViewData.traits.length > 0) {
            // VtM traits from database
            const physical = currentViewData.traits.filter(t => {
                const category = (t.trait_category || '').toString().trim();
                return category.toLowerCase() === 'physical';
            });
            const social = currentViewData.traits.filter(t => {
                const category = (t.trait_category || '').toString().trim();
                return category.toLowerCase() === 'social';
            });
            const mental = currentViewData.traits.filter(t => {
                const category = (t.trait_category || '').toString().trim();
                return category.toLowerCase() === 'mental';
            });
            
            if (physical.length > 0 || social.length > 0 || mental.length > 0) {
                contentHtml += '<div class="row g-3 mt-2">';
                
                if (physical.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6">';
                    contentHtml += '<h4>Physical</h4>';
                    contentHtml += '<div class="trait-list">';
                    physical.forEach(t => {
                        contentHtml += '<span class="trait-badge">' + escapeHtml(t.trait_name) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                if (social.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6">';
                    contentHtml += '<h4>Social</h4>';
                    contentHtml += '<div class="trait-list">';
                    social.forEach(t => {
                        contentHtml += '<span class="trait-badge">' + escapeHtml(t.trait_name) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                if (mental.length > 0) {
                    contentHtml += '<div class="col-lg-4 col-md-4 col-sm-12">';
                    contentHtml += '<h4>Mental</h4>';
                    contentHtml += '<div class="trait-list">';
                    mental.forEach(t => {
                        contentHtml += '<span class="trait-badge">' + escapeHtml(t.trait_name) + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                }
                
                contentHtml += '</div>';
            } else {
                contentHtml += '<p class="empty-state">No character traits found.</p>';
            }
        } else {
            contentHtml += '<p class="empty-state">No character traits found.</p>';
        }
        
        // Abilities
        contentHtml += '<h3>Abilities</h3>';
        if (isWraith && char.abilities && Array.isArray(char.abilities)) {
            // Wraith abilities are stored as array with name, category, level, specialization
            const categorized = {
                physical: [],
                social: [],
                mental: [],
                optional: []
            };
            
            char.abilities.forEach(ability => {
                if (!ability || !ability.name) return;
                const category = (ability.category || '').toLowerCase();
                const normalizedCategory = ['physical', 'social', 'mental', 'optional'].includes(category) ? category : 'optional';
                categorized[normalizedCategory].push({
                    ability_name: ability.name,
                    level: ability.level || 0,
                    specialization: ability.specialization || ''
                });
            });
            
            const presentGroups = Object.entries(categorized)
                .filter(function(entry) {
                    return entry[1].length > 0;
                })
                .map(function(entry) {
                    return {
                        title: entry[0].charAt(0).toUpperCase() + entry[0].slice(1),
                        abilities: entry[1]
                    };
                });
            
            if (presentGroups.length === 0) {
                contentHtml += '<p class="empty-state">No abilities recorded.</p>';
            } else {
                contentHtml += '<div class="row g-4 mb-4 ability-grid">';
                presentGroups.forEach((group, index) => {
                    const isStartOfRow = index % 2 === 0;
                    if (isStartOfRow) {
                        contentHtml += '<div class="col-12 d-flex flex-column flex-md-row gap-4">';
                    }
                    
                    contentHtml += '<div class="col-md-6 ability-column">';
                    contentHtml += '<h4>' + escapeHtml(group.title) + '</h4>';
                    contentHtml += '<div class="trait-list">';
                    group.abilities.forEach(a => {
                        let badge = escapeHtml(a.ability_name);
                        if (a.level && a.level > 0) badge += ' x' + escapeHtml(a.level);
                        if (a.specialization && a.specialization.trim()) badge += ' (' + escapeHtml(a.specialization.trim()) + ')';
                        contentHtml += '<span class="trait-badge">' + badge + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                    
                    const isEndOfRow = index % 2 === 1 || index === presentGroups.length - 1;
                    if (isEndOfRow) {
                        contentHtml += '</div>';
                    }
                });
                contentHtml += '</div>';
            }
        } else if (currentViewData.abilities && currentViewData.abilities.length > 0) {
            // VtM abilities from database
            const categorized = {
                physical: [],
                social: [],
                mental: [],
                optional: []
            };
            
            currentViewData.abilities.forEach(ability => {
                if (!ability || !ability.ability_name) return;
                const category = (ability.ability_category || '').toLowerCase();
                const normalizedCategory = ['physical', 'social', 'mental', 'optional'].includes(category) ? category : 'optional';
                categorized[normalizedCategory].push(ability);
            });
            
            const presentGroups = Object.entries(categorized)
                .filter(function(entry) {
                    return entry[1].length > 0;
                })
                .map(function(entry) {
                    return {
                        title: entry[0].charAt(0).toUpperCase() + entry[0].slice(1),
                        abilities: entry[1]
                    };
                });
            
            if (presentGroups.length === 0) {
                contentHtml += '<p class="empty-state">No abilities recorded.</p>';
            } else {
                contentHtml += '<div class="row g-4 mb-4 ability-grid">';
                presentGroups.forEach((group, index) => {
                    const isStartOfRow = index % 2 === 0;
                    if (isStartOfRow) {
                        contentHtml += '<div class="col-12 d-flex flex-column flex-md-row gap-4">';
                    }
                    
                    contentHtml += '<div class="col-md-6 ability-column">';
                    contentHtml += '<h4>' + escapeHtml(group.title) + '</h4>';
                    contentHtml += '<div class="trait-list">';
                    group.abilities.forEach(a => {
                        let badge = escapeHtml(a.ability_name);
                        if (a.level && a.level > 0) badge += ' x' + escapeHtml(a.level);
                        if (a.specialization && a.specialization.trim()) badge += ' (' + escapeHtml(a.specialization.trim()) + ')';
                        contentHtml += '<span class="trait-badge">' + badge + '</span>';
                    });
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                    
                    const isEndOfRow = index % 2 === 1 || index === presentGroups.length - 1;
                    if (isEndOfRow) {
                        contentHtml += '</div>';
                    }
                });
                contentHtml += '</div>';
            }
        } else {
            contentHtml += '<p class="empty-state">No abilities recorded.</p>';
        }
        
        // Disciplines (VtM only) or Arcanoi (Wraith)
        if (isWraith) {
            // Arcanoi
            contentHtml += '<h3>Arcanoi</h3>';
            if (char.arcanoi && Array.isArray(char.arcanoi) && char.arcanoi.length > 0) {
                contentHtml += '<div class="discipline-list">';
                char.arcanoi.forEach(arc => {
                    const arcName = escapeHtml(arc.name || 'Unknown');
                    const rating = arc.rating || 0;
                    const arts = arc.arts || [];
                    
                    contentHtml += '<div class="vbn-discipline-item">';
                    contentHtml += '<div class="vbn-discipline-item-wrapper">';
                    contentHtml += '<div class="vbn-discipline-header">';
                    contentHtml += '<strong>' + arcName + ' ' + rating + '</strong>';
                    if (arts.length > 0) {
                        contentHtml += '<span class="vbn-discipline-power-count">' + arts.length + ' arts</span>';
                    }
                    contentHtml += '</div>';
                    
                    if (arts.length > 0) {
                        contentHtml += '<div class="vbn-power-list">';
                        arts.forEach(art => {
                            const artName = escapeHtml(art.power || 'Unknown');
                            contentHtml += '<div class="vbn-power-item">• ' + artName + ' <span class="vbn-power-level">(Level ' + (art.level || 0) + ')</span></div>';
                        });
                        contentHtml += '</div>';
                    }
                    
                    contentHtml += '</div>';
                    contentHtml += '</div>';
                });
                contentHtml += '</div>';
            } else {
                contentHtml += '<p class="empty-state">No Arcanoi recorded.</p>';
            }
            
            // Fetters
            contentHtml += '<h3>Fetters</h3>';
            if (char.fetters && Array.isArray(char.fetters) && char.fetters.length > 0) {
                contentHtml += '<div class="trait-list">';
                char.fetters.forEach(fetter => {
                    const name = escapeHtml(fetter.name || 'Unknown');
                    const rating = fetter.rating || 0;
                    contentHtml += '<div class="merit-flaw-item">';
                    contentHtml += '<span class="trait-badge">' + name + ' (Rating ' + rating + ')</span>';
                    if (fetter.description) {
                        const descEscaped = escapeHtml(fetter.description).replace(/\n/g, '<br>');
                        contentHtml += '<p class="item-description">' + descEscaped + '</p>';
                    }
                    contentHtml += '</div>';
                });
                contentHtml += '</div>';
            } else {
                contentHtml += '<p class="empty-state">No Fetters recorded.</p>';
            }
            
            // Passions
            contentHtml += '<h3>Passions</h3>';
            if (char.passions && Array.isArray(char.passions) && char.passions.length > 0) {
                contentHtml += '<div class="trait-list">';
                char.passions.forEach(passion => {
                    const passionText = escapeHtml(passion.passion || 'Unknown');
                    const rating = passion.rating || 0;
                    contentHtml += '<div class="merit-flaw-item">';
                    contentHtml += '<span class="trait-badge">' + passionText + ' (Rating ' + rating + ')</span>';
                    contentHtml += '</div>';
                });
                contentHtml += '</div>';
            } else {
                contentHtml += '<p class="empty-state">No Passions recorded.</p>';
            }
            
            // Pathos & Corpus
            if (char.pathos_corpus) {
                contentHtml += '<h3>Pathos & Corpus</h3>';
                const pc = char.pathos_corpus;
                contentHtml += '<div class="row g-3 mt-2">';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Pathos:</strong> ' + (pc.pathos_current || 0) + '/' + (pc.pathos_max || 0) + '</p></div>';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Corpus:</strong> ' + (pc.corpus_current || 0) + '/' + (pc.corpus_max || 0) + '</p></div>';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Willpower:</strong> ' + (char.willpower_current || 0) + '/' + (char.willpower_permanent || 0) + '</p></div>';
                contentHtml += '</div>';
            }
            
            // Shadow
            if (char.shadow) {
                contentHtml += '<h3>Shadow</h3>';
                const shadow = char.shadow;
                contentHtml += '<div class="row g-3 mt-2">';
                if (shadow.archetype) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Archetype:</strong> ' + escapeHtml(shadow.archetype) + '</p></div>';
                contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Angst:</strong> ' + (shadow.angst_current || 0) + '/' + (shadow.angst_permanent || 0) + '</p></div>';
                if (shadow.dark_passions && Array.isArray(shadow.dark_passions) && shadow.dark_passions.length > 0) {
                    contentHtml += '<div class="col-12"><p><strong>Dark Passions:</strong></p><div class="trait-list">';
                    shadow.dark_passions.forEach(dp => {
                        const dpText = escapeHtml(dp.passion || 'Unknown');
                        const rating = dp.rating || 0;
                        contentHtml += '<span class="trait-badge">' + dpText + ' (' + rating + ')</span>';
                    });
                    contentHtml += '</div></div>';
                }
                if (shadow.shadow_notes) {
                    const notesEscaped = escapeHtml(shadow.shadow_notes).replace(/\n/g, '<br>');
                    contentHtml += '<div class="col-12"><p><strong>Notes:</strong></p><div class="text-content">' + notesEscaped + '</div></div>';
                }
                contentHtml += '</div>';
            }
            
            // Date of Death
            if (char.date_of_death) {
                contentHtml += '<h3>Death Information</h3>';
                contentHtml += '<div class="row g-3 mt-2">';
                contentHtml += '<div class="col-lg-6 col-md-6 col-sm-12"><p><strong>Date of Death:</strong> ' + escapeHtml(char.date_of_death) + '</p></div>';
                if (char.cause_of_death) {
                    const causeEscaped = escapeHtml(char.cause_of_death).replace(/\n/g, '<br>');
                    contentHtml += '<div class="col-lg-6 col-md-6 col-sm-12"><p><strong>Cause of Death:</strong></p><div class="text-content">' + causeEscaped + '</div></div>';
                }
                contentHtml += '</div>';
            }
            
            // Ghostly Appearance
            if (char.ghostly_appearance) {
                contentHtml += '<h3>Ghostly Appearance</h3>';
                const ghostEscaped = escapeHtml(char.ghostly_appearance).replace(/\n/g, '<br>');
                contentHtml += '<div class="text-content">' + ghostEscaped + '</div>';
            }
        } else {
            // Disciplines (VtM)
            contentHtml += '<h3>Disciplines</h3>';
            if (currentViewData.disciplines && currentViewData.disciplines.length > 0) {
            contentHtml += '<div class="discipline-list">';
            currentViewData.disciplines.forEach(d => {
                const discName = (d.discipline_name || 'Unknown').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const level = d.level || 0;
                const powerCount = d.power_count || (d.powers ? d.powers.length : 0);
                const isCustom = d.is_custom || false;
                
                contentHtml += '<div class="vbn-discipline-item">';
                contentHtml += '<div class="vbn-discipline-item-wrapper">';
                contentHtml += '<div class="vbn-discipline-header">';
                contentHtml += '<strong>' + discName + ' ' + level + '</strong>';
                if (powerCount > 0) {
                    contentHtml += '<span class="vbn-discipline-power-count">' + powerCount + ' powers</span>';
                } else if (isCustom) {
                    contentHtml += '<span class="vbn-discipline-custom-label">Custom/Path</span>';
                }
                contentHtml += '</div>';
                
                if (d.powers && d.powers.length > 0) {
                    contentHtml += '<div class="vbn-power-list">';
                    d.powers.forEach(power => {
                        const powerName = (power.power_name || 'Unknown').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        contentHtml += '<div class="vbn-power-item">• ' + powerName + ' <span class="vbn-power-level">(Level ' + (power.level || 0) + ')</span></div>';
                    });
                    contentHtml += '</div>';
                }
                
                contentHtml += '</div>';
                contentHtml += '</div>';
            });
            contentHtml += '</div>';
        } else {
            contentHtml += '<p class="empty-state">No disciplines recorded.</p>';
        }
        
        // Backgrounds
        if (currentViewData.backgrounds && currentViewData.backgrounds.length > 0) {
            contentHtml += '<h3>Backgrounds</h3>';
            contentHtml += '<div class="trait-list">';
            currentViewData.backgrounds.forEach(b => {
                let badge = b.background_name + ' x' + b.level;
                contentHtml += '<span class="trait-badge">' + badge + '</span>';
            });
            contentHtml += '</div>';
        }
        
        // Morality & Virtues
        if (currentViewData.morality) {
            const m = currentViewData.morality;
            contentHtml += '<h3>Morality & Virtues</h3>';
            contentHtml += '<div class="row g-3 mt-2">';
            if (m.path_name) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Path:</strong> ' + m.path_name + ' (' + (m.path_rating || 'N/A') + ')</p></div>';
            if (m.humanity !== null && m.humanity !== undefined) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Humanity:</strong> ' + m.humanity + '/10</p></div>';
            contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Willpower:</strong> ' + (m.willpower_current || 0) + '/' + (m.willpower_permanent || 0) + '</p></div>';
            if (m.conscience !== null && m.conscience !== undefined) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Conscience:</strong> ' + m.conscience + '</p></div>';
            if (m.self_control !== null && m.self_control !== undefined) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Self-Control:</strong> ' + m.self_control + '</p></div>';
            if (m.courage !== null && m.courage !== undefined) contentHtml += '<div class="col-lg-4 col-md-4 col-sm-6"><p><strong>Courage:</strong> ' + m.courage + '</p></div>';
            contentHtml += '</div>';
        }
        
        // Merits & Flaws
        if (currentViewData.merits_flaws && currentViewData.merits_flaws.length > 0) {
            const merits = currentViewData.merits_flaws.filter(m => m.type && m.type.toLowerCase() === 'merit');
            const flaws = currentViewData.merits_flaws.filter(m => m.type && m.type.toLowerCase() === 'flaw');
            
            contentHtml += '<div class="row g-4 mb-4">';
            if (merits.length > 0) {
                contentHtml += '<div class="col-md-6">';
                contentHtml += '<h3>Merits</h3>';
                merits.forEach(m => {
                    let badge = m.name + ' (' + m.point_value + ')';
                    if (m.xp_bonus) badge += ' [XP Bonus: ' + m.xp_bonus + ']';
                    contentHtml += '<div class="merit-flaw-item">';
                    contentHtml += '<span class="trait-badge">' + badge.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
                    if (m.category) contentHtml += '<span class="item-category">' + m.category.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
                    if (m.description) {
                        const descEscaped = m.description.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                        contentHtml += '<p class="item-description">' + descEscaped.replace(/\n/g, '<br>') + '</p>';
                    }
                    contentHtml += '</div>';
                });
                contentHtml += '</div>';
            }
            
            if (flaws.length > 0) {
                contentHtml += '<div class="col-md-6">';
                contentHtml += '<h3>Flaws</h3>';
                flaws.forEach(f => {
                    let badge = f.name + ' (' + f.point_value + ')';
                    if (f.xp_bonus) badge += ' [XP Bonus: ' + f.xp_bonus + ']';
                    contentHtml += '<div class="merit-flaw-item">';
                    contentHtml += '<span class="trait-badge">' + badge.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
                    if (f.category) contentHtml += '<span class="item-category">' + f.category.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
                    if (f.description) {
                        const descEscaped = f.description.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                        contentHtml += '<p class="item-description">' + descEscaped.replace(/\n/g, '<br>') + '</p>';
                    }
                    contentHtml += '</div>';
                });
                contentHtml += '</div>';
            }
            contentHtml += '</div>';
        }
        
        // Status & Resources
        contentHtml += '<h3>Status & Resources</h3>';
        if (currentViewData.status) {
            const s = currentViewData.status;
            const lifecycleStatus = (char.current_state || 'active').toString();
            const formattedLifecycle = lifecycleStatus.charAt(0).toUpperCase() + lifecycleStatus.slice(1);
            contentHtml += '<div class="row g-3 mt-2">';
            contentHtml += '<div class="col-md-6"><p><strong>Status:</strong> ' + formattedLifecycle + '</p></div>';
            contentHtml += '<div class="col-md-6"><p><strong>Sect Alignment:</strong> ' + (char.camarilla_status || 'Unknown') + '</p></div>';
            contentHtml += '<div class="col-md-6"><p><strong>Health Levels:</strong> ' + (s.health_levels || 'N/A') + '</p></div>';
            contentHtml += '<div class="col-md-6"><p><strong>Blood Pool:</strong> ' + (s.blood_pool_current || 0) + '/' + (s.blood_pool_maximum || 0) + '</p></div>';
            if (s.sect_status) contentHtml += '<div class="col-md-6"><p><strong>Sect Status:</strong> ' + s.sect_status + '</p></div>';
            if (s.clan_status) contentHtml += '<div class="col-md-6"><p><strong>Clan Status:</strong> ' + s.clan_status + '</p></div>';
            if (s.city_status) contentHtml += '<div class="col-md-6"><p><strong>City Status:</strong> ' + s.city_status + '</p></div>';
            contentHtml += '</div>';
        } else {
            const lifecycleStatus = (char.current_state || 'active').toString();
            const formattedLifecycle = lifecycleStatus.charAt(0).toUpperCase() + lifecycleStatus.slice(1);
            contentHtml += '<div class="row g-3 mt-2">';
            contentHtml += '<div class="col-md-6"><p><strong>Status:</strong> ' + formattedLifecycle + '</p></div>';
            contentHtml += '<div class="col-md-6"><p><strong>Sect Alignment:</strong> ' + (char.camarilla_status || 'Unknown') + '</p></div>';
            contentHtml += '<div class="col-12"><p class="empty-state">No additional status track information recorded.</p></div>';
            contentHtml += '</div>';
        }
        
        // Biography
        if (char.biography) {
            contentHtml += '<h3>Biography</h3>';
            const bioEscaped = char.biography.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            contentHtml += '<div class="text-content">' + bioEscaped.replace(/\n/g, '<br>') + '</div>';
        }
        
        // Appearance
        if (char.appearance) {
            contentHtml += '<h3>Appearance</h3>';
            const appEscaped = char.appearance.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            contentHtml += '<div class="text-content">' + appEscaped.replace(/\n/g, '<br>') + '</div>';
        }
        
        // Notes
        if (char.notes) {
            contentHtml += '<h3>Notes</h3>';
            const notesEscaped = char.notes.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            contentHtml += '<div class="text-content">' + notesEscaped.replace(/\n/g, '<br>') + '</div>';
        }
        
        // Custom Data
        contentHtml += '<h3>Custom Data</h3>';
        if (char.custom_data) {
            try {
                const customData = typeof char.custom_data === 'string' ? JSON.parse(char.custom_data) : char.custom_data;
                contentHtml += '<div class="text-content">';
                contentHtml += '<pre class="custom-data-json">' + JSON.stringify(customData, null, 2).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>';
                contentHtml += '</div>';
            } catch (e) {
                contentHtml += '<div class="text-content">' + char.custom_data.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</div>';
            }
        } else {
            contentHtml += '<p class="empty-state">No custom data recorded.</p>';
        }
        
        // Coterie
        contentHtml += '<h3>Coterie</h3>';
        if (currentViewData.coteries && currentViewData.coteries.length > 0) {
            contentHtml += '<div class="row g-3 mt-2">';
            currentViewData.coteries.forEach(c => {
                contentHtml += '<div class="col-md-6">';
                contentHtml += '<div class="coterie-card">';
                contentHtml += '<h4>' + (c.coterie_name || 'Unknown Coterie').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h4>';
                if (c.coterie_type) contentHtml += '<p><strong>Type:</strong> ' + c.coterie_type.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                if (c.role) contentHtml += '<p><strong>Role:</strong> ' + c.role.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                if (c.description) {
                    const descEscaped = c.description.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    contentHtml += '<p><strong>Description:</strong> ' + descEscaped.replace(/\n/g, '<br>') + '</p>';
                }
                if (c.notes) {
                    const notesEscaped = c.notes.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    contentHtml += '<p><strong>Notes:</strong> ' + notesEscaped.replace(/\n/g, '<br>') + '</p>';
                }
                contentHtml += '</div>';
                contentHtml += '</div>';
            });
            contentHtml += '</div>';
        } else {
            contentHtml += '<p class="empty-state">No coterie associations recorded.</p>';
        }
        
        // Relationships
        contentHtml += '<h3>Relationships</h3>';
        if (currentViewData.relationships && currentViewData.relationships.length > 0) {
            contentHtml += '<div class="row g-3 mt-2">';
            currentViewData.relationships.forEach(r => {
                contentHtml += '<div class="col-md-6">';
                contentHtml += '<div class="relationship-card">';
                contentHtml += '<h4>' + (r.related_character_name || 'Unknown Character').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h4>';
                if (r.relationship_type) contentHtml += '<p><strong>Type:</strong> ' + r.relationship_type.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                if (r.relationship_subtype) contentHtml += '<p><strong>Subtype:</strong> ' + r.relationship_subtype.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                if (r.strength) contentHtml += '<p><strong>Strength:</strong> ' + r.strength.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                if (r.description) {
                    const descEscaped = r.description.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    contentHtml += '<p><strong>Description:</strong> ' + descEscaped.replace(/\n/g, '<br>') + '</p>';
                }
                contentHtml += '</div>';
                contentHtml += '</div>';
            });
            contentHtml += '</div>';
        } else {
            contentHtml += '<p class="empty-state">No relationships recorded.</p>';
        }
        }
        
        // Equipment
        if (char.equipment) {
            contentHtml += '<h3>Equipment</h3>';
            let equipmentHtml = '';
            
            // Try to parse as JSON first
            try {
                const equipmentData = JSON.parse(char.equipment);
                
                // If it's an array, render as list
                if (Array.isArray(equipmentData)) {
                    equipmentHtml = '<ul class="equipment-list">';
                    equipmentData.forEach(item => {
                        if (typeof item === 'string') {
                            equipmentHtml += '<li>' + escapeHtml(item) + '</li>';
                        } else if (item && item.name) {
                            equipmentHtml += '<li><strong>' + escapeHtml(item.name) + '</strong>';
                            if (item.quantity && item.quantity > 1) {
                                equipmentHtml += ' <span class="vbn-equipment-badge">(x' + item.quantity + ')</span>';
                            }
                            if (item.description) {
                                equipmentHtml += '<br><span class="vbn-equipment-description">' + escapeHtml(item.description) + '</span>';
                            }
                            equipmentHtml += '</li>';
                        }
                    });
                    equipmentHtml += '</ul>';
                } 
                // If it's an object, render as structured data
                else if (typeof equipmentData === 'object' && equipmentData !== null) {
                    equipmentHtml = '<div class="equipment-content">';
                    for (const [key, value] of Object.entries(equipmentData)) {
                        equipmentHtml += '<p><strong>' + escapeHtml(key) + ':</strong> ';
                        if (typeof value === 'string') {
                            equipmentHtml += escapeHtml(value);
                        } else {
                            equipmentHtml += escapeHtml(JSON.stringify(value, null, 2));
                        }
                        equipmentHtml += '</p>';
                    }
                    equipmentHtml += '</div>';
                } else {
                    // Fallback: stringify and display
                    equipmentHtml = '<div class="text-content">' + escapeHtml(JSON.stringify(equipmentData, null, 2)) + '</div>';
                }
            } catch (e) {
                // Not JSON, treat as HTML or plain text
                // Check if it looks like HTML (contains tags)
                if (char.equipment.includes('<') && char.equipment.includes('>')) {
                    // Already HTML, use as-is (but sanitize)
                    equipmentHtml = '<div class="text-content">' + char.equipment + '</div>';
                } else {
                    // Plain text, convert newlines to <br>
                    const equipEscaped = char.equipment.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    equipmentHtml = '<div class="text-content">' + equipEscaped.replace(/\n/g, '<br>') + '</div>';
                }
            }
            
            contentHtml += equipmentHtml;
        }
        
        // Metadata
        contentHtml += '<h3>Character Metadata</h3>';
        contentHtml += '<div class="row g-3 mt-2">';
        if (char.created_at) {
            const created = new Date(char.created_at);
            contentHtml += '<div class="col-md-6"><p><strong>Created:</strong> ' + created.toLocaleString() + '</p></div>';
        }
        if (char.updated_at) {
            const updated = new Date(char.updated_at);
            contentHtml += '<div class="col-md-6"><p><strong>Last Updated:</strong> ' + updated.toLocaleString() + '</p></div>';
        }
        contentHtml += '</div>';
        
        contentHtml += '</div>';
        if (contentEl) {
            contentEl.innerHTML = contentHtml;
            contentEl.setAttribute('aria-busy', 'false');
        }
    }
})();
</script>

