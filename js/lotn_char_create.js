/**
 * LOTN Character Creation - Main JavaScript
 * Extracted from lotn_char_create.php
 * Contains: Virtue management, Discipline system, Coterie & Relationships
 */

// ============================================================================
// VIRTUE MANAGEMENT SYSTEM
// ============================================================================

const MAX_VIRTUE_POINTS = 7;
const VIRTUE_KEY_MAP = {
    conscience: 'Conscience',
    selfcontrol: 'SelfControl',
    'self-control': 'SelfControl'
};

const MORALITY_STATE_LABELS = {
    10: 'Saintlike',
    9: 'Serene',
    8: 'Compassionate',
    7: 'Balanced',
    6: 'Conflicted',
    5: 'Turbulent',
    4: 'Falling',
    3: 'Bestial',
    2: 'Monstrous',
    1: 'Inhuman',
    0: 'Lost'
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Get element IDs for a virtue key
 * @param {string} virtueKey - The virtue key ('Conscience' or 'SelfControl')
 * @returns {Object} Object containing all element IDs for the virtue
 */
function getVirtueElementIds(virtueKey) {
    if (virtueKey === 'Conscience') {
        return {
            value: 'conscienceValue',
            progress: 'conscienceProgress',
            markers: 'conscienceMarkers',
            minus: 'conscienceMinus',
            plus: 'consciencePlus'
        };
    } else {
        return {
            value: 'selfControlValue',
            progress: 'selfControlProgress',
            markers: 'selfControlMarkers',
            minus: 'selfControlMinus',
            plus: 'selfControlPlus'
        };
    }
}

/**
 * Set button state (disabled/enabled and label)
 * @param {HTMLElement|string} button - Button element or element ID
 * @param {boolean} disabled - Whether the button should be disabled
 * @param {string|null} label - Optional label to set (preserves existing if null)
 */
function setButtonState(button, disabled, label = null) {
    const buttonEl = typeof button === 'string' ? document.getElementById(button) : button;
    if (!buttonEl) return;
    
    buttonEl.disabled = disabled;
    if (label !== null) {
        buttonEl.innerHTML = label;
    }
}

/**
 * Show alert with duplicate prevention (only for non-iframe contexts)
 * Note: Iframe success cases are handled separately with postMessage
 * @param {string} message - Alert message to display
 * @param {boolean} isError - Whether this is an error message (for potential future use)
 */
let alertShown = false;
function showAlert(message, isError = false) {
    // Only show alert if NOT in iframe and flag not already set
    if (!(window.parent && window.parent !== window) && !alertShown) {
        alertShown = true;
        alert(message);
        // Reset flag after delay to prevent rapid duplicate calls
        setTimeout(() => { alertShown = false; }, 500);
    }
}

/**
 * Calculate popover position with viewport guards
 * @param {DOMRect} buttonRect - Button bounding rectangle
 * @param {DOMRect} popoverRect - Popover bounding rectangle
 * @param {number} margin - Margin in pixels
 * @returns {Object} Object with left and top positions
 */
function calculatePopoverPosition(buttonRect, popoverRect, margin = 12) {
    const vpW = window.innerWidth;
    const vpH = window.innerHeight;
    
    // Horizontal placement: prefer right side, fall back to left if overflow
    let left = buttonRect.right + margin;
    if (left + popoverRect.width > vpW - margin) {
        left = buttonRect.left - popoverRect.width - margin;
    }
    if (left < margin) {
        left = margin;
    }
    
    // Vertical placement: center to button, clamp to viewport
    let top = buttonRect.top + (buttonRect.height - popoverRect.height) / 2;
    if (top < margin) {
        top = margin;
    } else if (top + popoverRect.height > vpH - margin) {
        top = Math.max(margin, vpH - popoverRect.height - margin);
    }
    
    return { left, top };
}

let hasVirtueSubscriptions = false;

function normalizeVirtueKey(rawKey) {
    if (!rawKey) {
        throw new Error('Virtue key is required');
    }
    const normalized = VIRTUE_KEY_MAP[rawKey.toString().trim().toLowerCase()];
    if (!normalized) {
        throw new Error(`Unknown virtue key "${rawKey}"`);
    }
    return normalized;
}

function getStateManager() {
    const app = window.characterCreationApp;
    if (!app || !app.modules || !app.modules.stateManager) {
        return null;
    }
    return app.modules.stateManager;
}

function computeTotalVirtuePoints(virtues) {
    return (virtues.Conscience ?? 1) + (virtues.SelfControl ?? 1);
}

function getVirtuesFromState() {
    const stateManager = getStateManager();
    if (!stateManager) {
        return { Conscience: 1, SelfControl: 1 };
    }
    const state = stateManager.getState();
    return {
        Conscience: state.virtues?.Conscience ?? 1,
        SelfControl: state.virtues?.SelfControl ?? 1
    };
}

function updateVirtueValueDisplay(virtueKey, level) {
    const ids = getVirtueElementIds(virtueKey);
    
    const valueElement = document.getElementById(ids.value);
    if (valueElement) {
        valueElement.textContent = level.toString();
    }

    const progressElement = document.getElementById(ids.progress);
    if (progressElement) {
        const clampedLevel = Math.max(1, Math.min(5, level));
        progressElement.style.width = `${(clampedLevel / 5) * 100}%`;
    }

    const markersContainer = document.getElementById(ids.markers);
    if (markersContainer && markersContainer.children.length === 5) {
        Array.from(markersContainer.children).forEach((marker, idx) => {
            if (idx < level) {
                marker.classList.add('active');
                marker.classList.remove('inactive');
            } else {
                marker.classList.remove('active');
                marker.classList.add('inactive');
            }
        });
    }

    setButtonState(ids.minus, level <= 1);
    setButtonState(ids.plus, level >= 5);
}

function updateVirtueSummary(virtues) {
    const totalPoints = computeTotalVirtuePoints(virtues);
    const remaining = Math.max(0, MAX_VIRTUE_POINTS - totalPoints);

    const remainingElement = document.getElementById('virtuePointsRemaining');
    if (remainingElement) {
        remainingElement.textContent = remaining.toString();
    }

    const humanity = Math.max(0, Math.min(10, virtues.Conscience + virtues.SelfControl));
    updateHumanityDisplay(virtues, humanity);
}

function getMoralStateLabel(humanity) {
    return MORALITY_STATE_LABELS.hasOwnProperty(humanity)
        ? MORALITY_STATE_LABELS[humanity]
        : `Humanity ${humanity}`;
}

function updateHumanityDisplay(virtues, humanity) {
    const humanityValueElement = document.getElementById('humanityValue');
    if (humanityValueElement) {
        humanityValueElement.textContent = humanity.toString();
    }

    const humanityFill = document.getElementById('humanityFill');
    if (humanityFill) {
        humanityFill.style.width = `${(humanity / 10) * 100}%`;
    }

    const humanityCalculationElement = document.getElementById('humanityCalculation');
    if (humanityCalculationElement) {
        humanityCalculationElement.textContent = `${virtues.Conscience} + ${virtues.SelfControl} = ${humanity}`;
    }

    const moralStateElement = document.getElementById('moralStateDisplay');
    if (moralStateElement) {
        moralStateElement.textContent = getMoralStateLabel(humanity);
    }
}

function syncVirtuesFromState() {
    const stateManager = getStateManager();
    if (!stateManager) {
        return;
    }

    if (!hasVirtueSubscriptions) {
        stateManager.subscribe('virtues', () => {
            const virtues = getVirtuesFromState();
            updateVirtueValueDisplay('Conscience', virtues.Conscience);
            updateVirtueValueDisplay('SelfControl', virtues.SelfControl);
            updateVirtueSummary(virtues);
        });

        stateManager.subscribe('humanity', () => {
            const virtues = getVirtuesFromState();
            const state = stateManager.getState();
            updateHumanityDisplay(virtues, state.humanity ?? (virtues.Conscience + virtues.SelfControl));
        });

        hasVirtueSubscriptions = true;
    }

    const virtues = getVirtuesFromState();
    updateVirtueValueDisplay('Conscience', virtues.Conscience);
    updateVirtueValueDisplay('SelfControl', virtues.SelfControl);
    updateVirtueSummary(virtues);

    const state = stateManager.getState();
    const humanity = state.humanity ?? (virtues.Conscience + virtues.SelfControl);
    updateHumanityDisplay(virtues, humanity);
}

function enqueueVirtueSync(attempt = 0) {
    try {
        syncVirtuesFromState();
    } catch (error) {
        if (attempt < 10) {
            setTimeout(() => enqueueVirtueSync(attempt + 1), 100);
        } else {
            console.error('Failed to synchronise virtues after multiple attempts:', error);
        }
    }
}

/**
 * Adjust a virtue value (Conscience or SelfControl)
 * @param {string} virtueKey - The virtue to adjust ('conscience' or 'selfcontrol')
 * @param {number} delta - Amount to adjust by (typically -1 or 1)
 */
window.adjustVirtue = function adjustVirtue(virtueKey, delta) {
    const stateManager = getStateManager();
    if (!stateManager) {
        console.error('StateManager is not ready; cannot adjust virtue.');
        return;
    }

    const normalizedKey = normalizeVirtueKey(virtueKey);
    const currentState = stateManager.getState();
    const currentVirtues = {
        Conscience: currentState.virtues?.Conscience ?? 1,
        SelfControl: currentState.virtues?.SelfControl ?? 1
    };

    const proposedLevel = Math.max(1, Math.min(5, currentVirtues[normalizedKey] + delta));
    if (proposedLevel === currentVirtues[normalizedKey]) {
        return;
    }

    const prospectiveVirtues = { ...currentVirtues, [normalizedKey]: proposedLevel };
    const totalPoints = computeTotalVirtuePoints(prospectiveVirtues);
    if (totalPoints > MAX_VIRTUE_POINTS) {
        return;
    }

    const humanity = Math.max(0, Math.min(10, prospectiveVirtues.Conscience + prospectiveVirtues.SelfControl));

    stateManager.setState({
        virtues: prospectiveVirtues,
        humanity
    });

    updateVirtueValueDisplay('Conscience', prospectiveVirtues.Conscience);
    updateVirtueValueDisplay('SelfControl', prospectiveVirtues.SelfControl);
    updateVirtueSummary(prospectiveVirtues);
};

// ============================================================================
// CHARACTER SAVE FUNCTIONALITY
// ============================================================================

/**
 * Sync abilities from DOM to state if state abilities are empty but DOM has abilities
 * @returns {Object|null} Updated state object or null if no sync was needed
 */
function syncAbilitiesFromDOM() {
    const state = window.characterCreationApp ? window.characterCreationApp.modules.stateManager.getState() : null;
    
    if (!state || !window.characterCreationApp || !window.characterCreationApp.modules.abilitySystem) {
        return null;
    }
    
    const stateAbilities = state.abilities || {};
    const hasStateAbilities = Object.values(stateAbilities).some(arr => Array.isArray(arr) && arr.length > 0);
    
    if (hasStateAbilities) {
        return null; // State already has abilities, no sync needed
    }
    
    // Read from DOM and sync to state
    const abilitiesFromDOM = { Physical: [], Social: [], Mental: [], Optional: [] };
    const categories = ['Physical', 'Social', 'Mental', 'Optional'];
    
    categories.forEach(category => {
        const listElement = document.getElementById(category.toLowerCase() + 'AbilitiesList');
        if (listElement) {
            const selectedAbilities = listElement.querySelectorAll('.selected-ability');
            selectedAbilities.forEach(abilityEl => {
                const abilityNameEl = abilityEl.querySelector('.ability-name');
                if (abilityNameEl) {
                    let abilityName = abilityNameEl.textContent.trim();
                    // Parse count from "AbilityName (2)" format
                    const countMatch = abilityName.match(/^(.+?)\s*\((\d+)\)$/);
                    if (countMatch) {
                        const name = countMatch[1].trim();
                        const count = parseInt(countMatch[2], 10);
                        for (let i = 0; i < count; i++) {
                            abilitiesFromDOM[category].push(name);
                        }
                    } else {
                        abilitiesFromDOM[category].push(abilityName);
                    }
                }
            });
        }
    });
    
    // Update state with DOM abilities if any found
    const hasDOMAbilities = Object.values(abilitiesFromDOM).some(arr => Array.isArray(arr) && arr.length > 0);
    if (hasDOMAbilities) {
        window.characterCreationApp.modules.stateManager.setState({ abilities: abilitiesFromDOM });
        return window.characterCreationApp.modules.stateManager.getState();
    }
    
    return null;
}

/**
 * Collect form data for character save
 * @param {Object} state - Current character state from StateManager
 * @param {number|null} effectiveId - Character ID (from hidden field or URL)
 * @param {string|undefined} imagePath - Character image path
 * @returns {Object} Form data object ready for save
 */
function collectFormData(state, effectiveId, imagePath) {
    const currentStateSelect = document.getElementById('currentState');
    const camarillaSelect = document.getElementById('camarillaStatus');
    const currentState = currentStateSelect ? (currentStateSelect.value || 'active') : 'active';
    const camarillaStatus = camarillaSelect ? (camarillaSelect.value || 'Unknown') : 'Unknown';
    
    // Check if NPC checkbox is checked - if so, set player_name to "NPC" and pc to 0
    const npcCheckbox = document.getElementById('npc');
    const playerNameInput = document.getElementById('playerName');
    const isNPC = npcCheckbox && npcCheckbox.checked;
    const playerNameValue = isNPC ? 'NPC' : (playerNameInput ? (playerNameInput.value || '') : '');
    const pcCheckbox = document.getElementById('pc');
    const pcValue = isNPC ? 0 : (pcCheckbox && pcCheckbox.checked ? 1 : 0);
    
    return {
        character_name: document.getElementById('characterName').value || '',
        player_name: playerNameValue,
        chronicle: document.getElementById('chronicle').value || 'Valley by Night',
        nature: document.getElementById('nature').value || '',
        demeanor: document.getElementById('demeanor').value || '',
        derangement: document.getElementById('derangement') ? document.getElementById('derangement').value || '' : '',
        concept: document.getElementById('concept').value || '',
        clan: document.getElementById('clan').value || '',
        generation: parseInt(document.getElementById('generation').value) || 13,
        sire: document.getElementById('sire').value || '',
        pc: pcValue,
        biography: '', // Field doesn't exist in basic tab
        equipment: '', // Field doesn't exist in basic tab
        total_xp: 30, // Default value
        spent_xp: 0, // Default value
        traits: state?.traits || {},
        negativeTraits: state?.negativeTraits || {},
        abilities: state?.abilities || { Physical: [], Social: [], Mental: [], Optional: [] },
        disciplinePowers: state?.disciplinePowers || {},
        backgrounds: state?.backgrounds || {},
        backgroundDetails: state?.backgroundDetails || {},
        merits_flaws: state?.selectedMeritsFlaws || [],
        status: currentState,
        current_state: currentState,
        camarilla_status: camarillaStatus,
        morality: {
            path_name: 'Humanity',
            path_rating: 7,
            conscience: 1,
            self_control: 1,
            courage: 1,
            willpower_permanent: 5,
            willpower_current: 5,
            humanity: 7
        },
        status_details: {
            current_state: currentState,
            camarilla_status: camarillaStatus
        },
        ...(effectiveId ? { id: effectiveId } : {}),
        ...(imagePath ? { imagePath } : {})
    };
}

/**
 * Handle save response with iframe detection and alert management
 * @param {string} data - Raw response text from server
 */
function handleSaveResponse(data) {
    console.log('Response data:', data);
    try {
        const jsonData = JSON.parse(data);
        if (jsonData.success) {
            // If in iframe, send postMessage and return - NO ALERTS EVER
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'characterSaved',
                    characterId: jsonData.character_id
                }, '*');
                return; // EXIT IMMEDIATELY - no alerts
            }
            
            showAlert('✅ Character saved successfully!', false);
        } else {
            showAlert('❌ Save failed: ' + jsonData.message, true);
        }
    } catch (e) {
        console.error('Invalid JSON response:', data);
        showAlert('❌ Invalid response from server: ' + data.substring(0, 200), true);
    }
}

/**
 * Save character data to server
 * @param {boolean} isFinalization - Whether this is a finalization save (affects button label)
 */
function saveCharacter(isFinalization = false) {
    // Show loading state
    const saveButtons = document.querySelectorAll('.save-btn');
    saveButtons.forEach(btn => {
        if (!btn.dataset.originalLabel) {
            btn.dataset.originalLabel = btn.innerHTML;
        }
        btn.disabled = true;
        btn.innerHTML = isFinalization ? '🎯 Finalizing...' : '💾 Saving...';
    });
    
    // Extract id from hidden field first, fallback to URL param
    const idEl = document.getElementById('characterId');
    const urlParams = new URLSearchParams(window.location.search);
    const idFromHidden = idEl && idEl.value ? parseInt(idEl.value, 10) : null;
    const idFromUrl = urlParams.get('id') ? parseInt(urlParams.get('id'), 10) : null;
    const effectiveId = idFromHidden || idFromUrl || null;

    // Extract imagePath from hidden
    const imgEl = document.getElementById('imagePath');
    const imagePath = imgEl && imgEl.value ? imgEl.value : undefined;

    // Sync abilities from DOM to state if needed
    const syncedState = syncAbilitiesFromDOM();
    
    // Collect state from CharacterCreationApp if available, otherwise use defaults
    let state = window.characterCreationApp ? window.characterCreationApp.modules.stateManager.getState() : null;
    
    // Use synced state if available
    if (syncedState) {
        state = syncedState;
    }
    
    // Collect form data
    const formData = collectFormData(state, effectiveId, imagePath);
    
    console.log('Sending data:', formData);
    console.log('Abilities being sent:', formData.abilities);
    
    fetch('includes/save_character.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(data => {
        handleSaveResponse(data);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('❌ Error: ' + error.message, true);
    })
    .finally(() => {
        // Reset button state
        saveButtons.forEach(btn => {
            btn.disabled = false;
            if (btn.dataset.originalLabel) {
                btn.innerHTML = btn.dataset.originalLabel;
            }
        });
    });
}

/**
 * Finalize character (triggers save with finalization flag)
 */
function finalizeCharacter() {
    saveCharacter(true);
}

/**
 * Download character sheet as PDF (placeholder - functionality coming soon)
 */
function downloadCharacterSheet() {
    alert('PDF download functionality coming soon!');
}

// ============================================================================
// DISCIPLINE SYSTEM
// ============================================================================

// Discipline system variables
let currentPopoverTimeout = null;
let currentPopoverButton = null;

// Discipline powers data
const disciplinePowers = {
    'Animalism': [
        { level: 1, name: 'Feral Whispers', description: 'Communicate with animals' },
        { level: 2, name: 'Animal Succulence', description: 'Feed from animals' },
        { level: 3, name: 'Quell the Beast', description: 'Calm frenzied vampires' },
        { level: 4, name: 'Subsume the Spirit', description: 'Possess animals' },
        { level: 5, name: 'Animal Dominion', description: 'Command all animals in area' }
    ],
    'Auspex': [
        { level: 1, name: 'Heightened Senses', description: 'Enhanced perception' },
        { level: 2, name: 'Aura Perception', description: 'See emotional auras' },
        { level: 3, name: 'The Spirit\'s Touch', description: 'Read objects\' history' },
        { level: 4, name: 'Telepathy', description: 'Read minds' },
        { level: 5, name: 'Psychic Projection', description: 'Astral projection' }
    ],
    'Celerity': [
        { level: 1, name: 'Quickness', description: 'The vampire can move and react at superhuman speeds, allowing them to perform actions much faster than normal.' },
        { level: 2, name: 'Sprint', description: 'The vampire can achieve incredible bursts of speed over short distances.' },
        { level: 3, name: 'Enhanced Reflexes', description: 'The vampire\'s reaction time becomes so fast they can dodge bullets and catch arrows in flight.' },
        { level: 4, name: 'Blur', description: 'The vampire moves so fast they become a blur, making them nearly impossible to target.' },
        { level: 5, name: 'Accelerated Movement', description: 'The vampire can maintain superhuman speed for extended periods.' }
    ],
    'Dominate': [
        { level: 1, name: 'Cloud Memory', description: 'Erase recent memories' },
        { level: 2, name: 'Mesmerize', description: 'Compel simple actions' },
        { level: 3, name: 'The Forgetful Mind', description: 'Implant false memories' },
        { level: 4, name: 'Mass Manipulation', description: 'Affect multiple targets' },
        { level: 5, name: 'Possession', description: 'Take control of body' }
    ],
    'Fortitude': [
        { level: 1, name: 'Resilience', description: 'Resist physical damage' },
        { level: 2, name: 'Unswayable Mind', description: 'Resist mental influence' },
        { level: 3, name: 'Toughness', description: 'Ignore wound penalties' },
        { level: 4, name: 'Defy Bane', description: 'Resist supernatural effects' },
        { level: 5, name: 'Fortify the Inner Facade', description: 'Become immune to damage' }
    ],
    'Obfuscate': [
        { level: 1, name: 'Cloak of Shadows', description: 'Hide in darkness' },
        { level: 2, name: 'Silence of Death', description: 'Move without sound' },
        { level: 3, name: 'Mask of a Thousand Faces', description: 'Change appearance' },
        { level: 4, name: 'Vanish', description: 'Become completely invisible' },
        { level: 5, name: 'Cloak the Gathering', description: 'Hide groups of people' }
    ],
    'Potence': [
        { level: 1, name: 'Lethal Body', description: 'Enhanced physical strength' },
        { level: 2, name: 'Prowess', description: 'Devastating physical attacks' },
        { level: 3, name: 'Brutal Feed', description: 'Feed through violence' },
        { level: 4, name: 'Spark of Rage', description: 'Cause frenzy in others' },
        { level: 5, name: 'Earthshock', description: 'Create earthquakes' }
    ],
    'Presence': [
        { level: 1, name: 'Awe', description: 'Inspire admiration' },
        { level: 2, name: 'Dread Gaze', description: 'Cause fear' },
        { level: 3, name: 'Entrancement', description: 'Create devoted followers' },
        { level: 4, name: 'Summon', description: 'Compel others to come' },
        { level: 5, name: 'Majesty', description: 'Become untouchable' }
    ],
    'Protean': [
        { level: 1, name: 'Eyes of the Beast', description: 'Enhanced night vision' },
        { level: 2, name: 'Shape of the Beast', description: 'Transform into animal' },
        { level: 3, name: 'Mist Form', description: 'Become mist' },
        { level: 4, name: 'Form of the Ancient', description: 'Become giant bat' },
        { level: 5, name: 'Earth Meld', description: 'Merge with earth' }
    ],
    'Vicissitude': [
        { level: 1, name: 'Malleable Visage', description: 'Change facial features' },
        { level: 2, name: 'Fleshcraft', description: 'Modify body structure' },
        { level: 3, name: 'Bonecraft', description: 'Manipulate bones' },
        { level: 4, name: 'Horrid Form', description: 'Take monstrous shape' },
        { level: 5, name: 'Metamorphosis', description: 'Complete body transformation' }
    ],
    'Dementation': [
        { level: 1, name: 'Confusion', description: 'Cause mental disorientation' },
        { level: 2, name: 'The Haunting', description: 'Create hallucinations' },
        { level: 3, name: 'Nightmare', description: 'Induce terrifying dreams' },
        { level: 4, name: 'Total Insanity', description: 'Drive target completely mad' },
        { level: 5, name: 'The Beast Within', description: 'Unleash inner monster' }
    ],
    'Thaumaturgy': [
        { level: 1, name: 'A Taste for Blood', description: 'Sense blood and vitae' },
        { level: 2, name: 'Blood Rage', description: 'Cause frenzy in others' },
        { level: 3, name: 'The Blood Bond', description: 'Create blood bonds' },
        { level: 4, name: 'Blood of Acid', description: 'Corrupt blood' },
        { level: 5, name: 'Cauldron of Blood', description: 'Mass blood manipulation' }
    ],
    'Necromancy': [
        { level: 1, name: 'Speak with the Dead', description: 'Communicate with spirits' },
        { level: 2, name: 'Summon Soul', description: 'Call forth spirits' },
        { level: 3, name: 'Compel Soul', description: 'Force spirit obedience' },
        { level: 4, name: 'Reanimate Corpse', description: 'Raise the dead' },
        { level: 5, name: 'Soul Stealing', description: 'Capture souls' }
    ],
    'Quietus': [
        { level: 1, name: 'Silence of Death', description: 'Move without sound' },
        { level: 2, name: 'Touch of Death', description: 'Poisonous touch' },
        { level: 3, name: 'Baal\'s Caress', description: 'Lethal blood attack' },
        { level: 4, name: 'Blood of the Lamb', description: 'Corrupt blood' },
        { level: 5, name: 'The Killing Word', description: 'Death by command' }
    ],
    'Serpentis': [
        { level: 1, name: 'Eyes of the Serpent', description: 'Hypnotic gaze' },
        { level: 2, name: 'Tongue of the Asp', description: 'Venomous bite' },
        { level: 3, name: 'Form of the Cobra', description: 'Transform into snake' },
        { level: 4, name: 'The Serpent\'s Kiss', description: 'Paralyzing venom' },
        { level: 5, name: 'The Serpent\'s Embrace', description: 'Complete serpent form' }
    ],
    'Obtenebration': [
        { level: 1, name: 'Shroud of Night', description: 'Create darkness' },
        { level: 2, name: 'Arms of the Abyss', description: 'Shadow tentacles' },
        { level: 3, name: 'Shadow Form', description: 'Become living shadow' },
        { level: 4, name: 'Summon the Abyss', description: 'Call forth darkness' },
        { level: 5, name: 'Black Metamorphosis', description: 'Become shadow demon' }
    ],
    'Chimerstry': [
        { level: 1, name: 'Ignis Fatuus', description: 'Create false lights' },
        { level: 2, name: 'Fata Morgana', description: 'Create illusions' },
        { level: 3, name: 'Permanency', description: 'Make illusions real' },
        { level: 4, name: 'Horrid Reality', description: 'Create nightmare illusions' },
        { level: 5, name: 'Reality\'s Curtain', description: 'Alter reality itself' }
    ],
    'Daimoinon': [
        { level: 1, name: 'Summon Demon', description: 'Call forth minor demons' },
        { level: 2, name: 'Bind Demon', description: 'Control summoned demons' },
        { level: 3, name: 'Demon\'s Kiss', description: 'Gain demonic powers' },
        { level: 4, name: 'Hell\'s Gate', description: 'Open portal to Hell' },
        { level: 5, name: 'Infernal Mastery', description: 'Command all demons' }
    ],
    'Melpominee': [
        { level: 1, name: 'The Tragic Muse', description: 'Inspire artistic genius' },
        { level: 2, name: 'The Tragic Flaw', description: 'Reveal fatal weaknesses' },
        { level: 3, name: 'The Tragic Hero', description: 'Create doomed champions' },
        { level: 4, name: 'The Tragic End', description: 'Ensure dramatic deaths' },
        { level: 5, name: 'The Tragic Cycle', description: 'Control fate itself' }
    ],
    'Valeren': [
        { level: 1, name: 'The Healing Touch', description: 'Heal others' },
        { level: 2, name: 'The Warrior\'s Resolve', description: 'Enhance combat abilities' },
        { level: 3, name: 'The Martyr\'s Blessing', description: 'Absorb others\' pain' },
        { level: 4, name: 'The Saint\'s Grace', description: 'Become immune to harm' },
        { level: 5, name: 'The Messiah\'s Return', description: 'Resurrect the dead' }
    ],
    'Mortis': [
        { level: 1, name: 'Speak with the Dead', description: 'Communicate with corpses' },
        { level: 2, name: 'Animate Corpse', description: 'Raise the dead' },
        { level: 3, name: 'Bone Craft', description: 'Manipulate bones' },
        { level: 4, name: 'Soul Stealing', description: 'Capture souls' },
        { level: 5, name: 'Death\'s Embrace', description: 'Become death itself' }
    ]
};

/**
 * Show discipline power popover when hovering over discipline button
 * @param {Event} event - Mouse event from discipline button
 * @param {string} disciplineName - Name of the discipline to show powers for
 */
function showDisciplinePopover(event, disciplineName) {
    const button = event.target.closest('.discipline-option-btn');
    if (!button || button.disabled) {
        return;
    }
    
    // Clear any existing timeout
    if (currentPopoverTimeout) {
        clearTimeout(currentPopoverTimeout);
        currentPopoverTimeout = null;
    }
    
    const popover = document.getElementById('disciplinePopover');
    const popoverTitle = document.getElementById('popoverTitle');
    const popoverPowers = document.getElementById('popoverPowers');
    
    // Set title (no "Powers" suffix per UX guidance)
    popoverTitle.textContent = `${disciplineName}`;
    
    // Get available powers for this discipline
    const availablePowers = getAvailablePowers(disciplineName);
    
    // Clear existing content
    popoverPowers.innerHTML = '';
    
    // Generate power options
    availablePowers.forEach(power => {
        const powerOption = document.createElement('div');
        powerOption.className = 'power-option';
        // Legacy onclick removed - DisciplineSystem handles power selection now
        // powerOption.onclick = () => selectPower(disciplineName, power);
        powerOption.innerHTML = `
            <strong>Level ${power.level}:</strong> ${power.name}
            <br><small>${power.description}</small>
        `;
        popoverPowers.appendChild(powerOption);
    });
    
    // Wire close button for immediate close
    const closeBtn = document.getElementById('popoverClose');
    if (closeBtn) {
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            // immediate close without delay
            clearPopoverTimeout();
            const p = document.getElementById('disciplinePopover');
            if (p) p.style.display = 'none';
            currentPopoverButton = null;
        };
    }

    // Keep open when hovering popover; hide on leave with tolerance
    popover.onmouseenter = clearPopoverTimeout;
    popover.onmouseleave = hideDisciplinePopover;

    // Position popover next to hovered button with viewport guards
    const buttonRect = button.getBoundingClientRect();
    const margin = 12;

    popover.style.position = 'fixed';
    popover.style.visibility = 'hidden';
    popover.style.display = 'block';
    popover.style.zIndex = '1000';

    const popoverRect = popover.getBoundingClientRect();
    const position = calculatePopoverPosition(buttonRect, popoverRect, margin);

    // Update hover tracking
    if (currentPopoverButton) {
        currentPopoverButton.classList.remove('popover-target');
    }
    button.classList.add('popover-target');

    popover.style.left = `${position.left}px`;
    popover.style.top = `${position.top}px`;
    popover.style.visibility = 'visible';
    currentPopoverButton = button;
}

// Clear popover timeout
function clearPopoverTimeout() {
    if (currentPopoverTimeout) {
        clearTimeout(currentPopoverTimeout);
        currentPopoverTimeout = null;
    }
}

// Hide discipline power popover
function hideDisciplinePopover() {
    currentPopoverTimeout = setTimeout(() => {
        const popover = document.getElementById('disciplinePopover');
        popover.style.display = 'none';
        currentPopoverButton = null; // Clear button reference
    }, 500); // Longer delay to allow moving to popover
}

// Get available powers for a discipline (not yet selected)
function getAvailablePowers(disciplineName) {
    const allPowers = disciplinePowers[disciplineName] || [];
    const selectedPowers = getSelectedPowers(disciplineName);
    
    return allPowers.filter(power => 
        !selectedPowers.some(selected => selected.level === power.level)
    );
}

// Get selected powers for a discipline
function getSelectedPowers(disciplineName) {
    // For now, return empty array since we don't have discipline selection implemented yet
    // This will show all powers as available
    return [];
}

// Legacy selectPower function removed - DisciplineSystem handles power selection now

// Remove discipline from list
function removeDiscipline(disciplinePowerName, level) {
    const disciplineList = document.getElementById('clanDisciplinesList');
    if (disciplineList) {
        const items = disciplineList.querySelectorAll('.discipline-item');
        items.forEach(item => {
            const nameSpan = item.querySelector('.discipline-name');
            const levelSpan = item.querySelector('.discipline-level');
            if (nameSpan && levelSpan && 
                nameSpan.textContent === disciplinePowerName && 
                levelSpan.textContent === level.toString()) {
                item.remove();
                
                // Update count
                const countDisplay = document.getElementById('clanDisciplinesCountDisplay');
                if (countDisplay) {
                    const remainingItems = disciplineList.querySelectorAll('.discipline-item');
                    countDisplay.textContent = remainingItems.length;
                }
            }
        });
    }
}

/**
 * Update discipline button availability based on selected clan
 * Enables/disables discipline buttons based on clan-specific discipline access
 */
function updateDisciplineAvailability() {
    const clan = document.getElementById('clan').value;
    const clanDisciplines = {
        'Toreador': ['Auspex', 'Celerity', 'Presence'],
        'Brujah': ['Celerity', 'Potence', 'Presence'],
        'Ventrue': ['Dominate', 'Fortitude', 'Presence'],
        'Gangrel': ['Animalism', 'Fortitude', 'Protean'],
        'Nosferatu': ['Animalism', 'Obfuscate', 'Potence'],
        'Malkavian': ['Auspex', 'Dementation', 'Obfuscate'],
        'Tremere': ['Auspex', 'Dominate', 'Thaumaturgy'],
        'Assamite': ['Celerity', 'Obfuscate', 'Quietus'],
        'Followers of Set': ['Obfuscate', 'Presence', 'Serpentis'],
        'Giovanni': ['Dominate', 'Potence', 'Necromancy'],
        'Lasombra': ['Dominate', 'Obfuscate', 'Obtenebration'],
        'Ravnos': ['Animalism', 'Chimerstry', 'Fortitude'],
        'Tzimisce': ['Animalism', 'Auspex', 'Vicissitude'],
        'Caitiff': [] // Caitiff can learn any discipline
    };
    
    // Get all discipline buttons
    const disciplineButtons = document.querySelectorAll('.discipline-option-btn');
    
    disciplineButtons.forEach(button => {
        const disciplineName = button.getAttribute('data-discipline');
        let disabled, opacity, cursor, title, classes;
        
        if (!clan) {
            // No clan selected - disable all
            disabled = true;
            classes = { add: ['disabled', 'discipline-disabled'], remove: ['caitiff-available'] };
            opacity = '0.4';
            cursor = 'not-allowed';
            title = 'Select a clan to unlock disciplines';
        } else if (clan === 'Caitiff') {
            // Caitiff can learn any discipline - enable all
            disabled = false;
            classes = { add: ['caitiff-available'], remove: ['disabled', 'discipline-disabled'] };
            opacity = '1';
            cursor = 'pointer';
            title = '';
        } else if (!clanDisciplines[clan] || !clanDisciplines[clan].includes(disciplineName)) {
            // Discipline not available to clan - disable
            disabled = true;
            classes = { add: ['disabled', 'discipline-disabled'], remove: ['caitiff-available'] };
            opacity = '0.4';
            cursor = 'not-allowed';
            title = `${disciplineName} is not available to ${clan}`;
        } else {
            // Discipline available to clan - enable
            disabled = false;
            classes = { add: [], remove: ['disabled', 'discipline-disabled', 'caitiff-available'] };
            opacity = '1';
            cursor = 'pointer';
            title = '';
        }
        
        // Apply state
        setButtonState(button, disabled);
        classes.add.forEach(cls => button.classList.add(cls));
        classes.remove.forEach(cls => button.classList.remove(cls));
        button.style.opacity = opacity;
        button.style.cursor = cursor;
        button.title = title;
    });
}

// ============================================================================
// COTERIE & RELATIONSHIPS MANAGEMENT
// ============================================================================

// Coterie and Relationships Management Functions
window.coterieCounter = 0;
window.relationshipCounter = 0;

/**
 * Create a dynamic entry with common container/empty state management
 * @param {string} containerId - ID of the container element
 * @param {string} emptyStateId - ID of the empty state element
 * @param {string} entryClassName - CSS class name for the entry
 * @param {Object} counterObj - Counter object (e.g., window) with counter property name
 * @param {string} counterProperty - Property name on counterObj to increment (e.g., 'coterieCounter')
 * @param {Function} templateFn - Function that returns HTML template string: (index, data) => string
 * @param {Function} removeHandler - Function called when remove button is clicked: (entry, container, emptyState) => void
 * @param {Object|null} entryData - Optional data to populate entry fields
 * @returns {HTMLElement|null} Created entry element or null if container not found
 */
function createDynamicEntry(containerId, emptyStateId, entryClassName, counterObj, counterProperty, templateFn, removeHandler, entryData = null) {
    const container = document.getElementById(containerId);
    const emptyState = document.getElementById(emptyStateId);
    if (!container) {
        console.error(`${containerId} container not found`);
        return null;
    }
    
    if (emptyState) emptyState.style.display = 'none';
    
    const index = counterObj[counterProperty]++;
    const entry = document.createElement('div');
    entry.className = entryClassName;
    entry.dataset.index = index;
    
    entry.innerHTML = templateFn(index, entryData);
    
    container.appendChild(entry);
    
    // Find remove button and attach handler
    const removeBtn = entry.querySelector(`.remove-${entryClassName.replace('-entry', '')}-btn`);
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            removeHandler(entry, container, emptyState);
        });
    }
    
    return entry;
}

/**
 * Collect all coterie entries from the DOM
 * @returns {Array} Array of coterie objects with coterie_name, coterie_type, role, description, notes
 */
function collectCoteries() {
    const coteries = [];
    const coterieEntries = document.querySelectorAll('.coterie-entry');
    coterieEntries.forEach(entry => {
        const coterie = {
            coterie_name: entry.querySelector('.coterie-name')?.value || '',
            coterie_type: entry.querySelector('.coterie-type')?.value || '',
            role: entry.querySelector('.coterie-role')?.value || '',
            description: entry.querySelector('.coterie-description')?.value || '',
            notes: entry.querySelector('.coterie-notes')?.value || ''
        };
        if (coterie.coterie_name.trim()) {
            coteries.push(coterie);
        }
    });
    return coteries;
}

/**
 * Collect all relationship entries from the DOM
 * @returns {Array} Array of relationship objects with related_character_name, relationship_type, relationship_subtype, strength, description
 */
function collectRelationships() {
    const relationships = [];
    const relationshipEntries = document.querySelectorAll('.relationship-entry');
    relationshipEntries.forEach(entry => {
        const characterSelect = entry.querySelector('.relationship-character-name');
        const relationship = {
            related_character_name: characterSelect ? characterSelect.value : '',
            relationship_type: entry.querySelector('.relationship-type')?.value || '',
            relationship_subtype: entry.querySelector('.relationship-subtype')?.value || '',
            strength: entry.querySelector('.relationship-strength')?.value || '',
            description: entry.querySelector('.relationship-description')?.value || ''
        };
        if (relationship.related_character_name.trim()) {
            relationships.push(relationship);
        }
    });
    return relationships;
}

/**
 * Add a new coterie entry to the form
 * @param {Object|null} coterieData - Optional initial data for the coterie entry
 */
window.addCoterieEntry = function(coterieData = null) {
    createDynamicEntry(
        'coterieContainer',
        'coterieEmptyState',
        'coterie-entry',
        window,
        'coterieCounter',
        (index, data) => `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h5 style="margin: 0; color: #d4af37;">Coterie ${index + 1}</h5>
            <button type="button" class="remove-coterie-btn">Remove</button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label class="form-label">Coterie Name *</label>
                <input type="text" class="form-control coterie-name" placeholder="e.g., The Phoenix Circle" value="${data?.coterie_name || ''}" required>
            </div>
            <div>
                <label class="form-label">Type</label>
                <select class="form-control coterie-type">
                    <option value="">Select type...</option>
                    <option value="faction" ${data?.coterie_type === 'faction' ? 'selected' : ''}>Faction</option>
                    <option value="role" ${data?.coterie_type === 'role' ? 'selected' : ''}>Role</option>
                    <option value="membership" ${data?.coterie_type === 'membership' ? 'selected' : ''}>Membership</option>
                    <option value="informal_group" ${data?.coterie_type === 'informal_group' ? 'selected' : ''}>Informal Group</option>
                </select>
            </div>
            <div>
                <label class="form-label">Role</label>
                <input type="text" class="form-control coterie-role" placeholder="e.g., Leader, Member, Advisor" value="${data?.role || ''}">
            </div>
        </div>
        <div style="margin-bottom: 15px;">
            <label class="form-label">Description</label>
            <textarea class="form-control coterie-description" rows="2" placeholder="Describe the coterie and your character's involvement">${data?.description || ''}</textarea>
        </div>
        <div>
            <label class="form-label">Notes</label>
            <textarea class="form-control coterie-notes" rows="2" placeholder="Additional notes about this coterie association">${data?.notes || ''}</textarea>
        </div>
    `,
        (entry, container, emptyState) => {
            entry.remove();
            if (container.querySelectorAll('.coterie-entry').length === 0 && emptyState) {
                emptyState.style.display = 'block';
            }
        },
        coterieData
    );
};

/**
 * Add a new relationship entry to the form
 * @param {Object|null} relationshipData - Optional initial data for the relationship entry
 */
window.addRelationshipEntry = function(relationshipData = null) {
    createDynamicEntry(
        'relationshipsContainer',
        'relationshipsEmptyState',
        'relationship-entry',
        window,
        'relationshipCounter',
        (index, data) => {
            // Build character options HTML
            let characterOptions = '<option value="">Select character...</option>';
            if (window.allCharacters && Array.isArray(window.allCharacters)) {
                window.allCharacters.forEach(char => {
                    const selected = data?.related_character_name === char.name ? 'selected' : '';
                    characterOptions += `<option value="${char.name.replace(/"/g, '&quot;')}" ${selected}>${char.name}</option>`;
                });
            }
            
            return `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h5 style="margin: 0; color: #d4af37;">Relationship ${index + 1}</h5>
            <button type="button" class="remove-relationship-btn">Remove</button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label class="form-label">Character Name *</label>
                <select class="form-control relationship-character-name" required>
                    ${characterOptions}
                </select>
            </div>
            <div>
                <label class="form-label">Relationship Type</label>
                <select class="form-control relationship-type">
                    <option value="">Select type...</option>
                    <option value="sire" ${data?.relationship_type === 'sire' ? 'selected' : ''}>Sire</option>
                    <option value="childe" ${data?.relationship_type === 'childe' ? 'selected' : ''}>Childe</option>
                    <option value="mentor" ${data?.relationship_type === 'mentor' ? 'selected' : ''}>Mentor</option>
                    <option value="ally" ${data?.relationship_type === 'ally' ? 'selected' : ''}>Ally</option>
                    <option value="contact" ${data?.relationship_type === 'contact' ? 'selected' : ''}>Contact</option>
                    <option value="rival" ${data?.relationship_type === 'rival' ? 'selected' : ''}>Rival</option>
                    <option value="enemy" ${data?.relationship_type === 'enemy' ? 'selected' : ''}>Enemy</option>
                    <option value="other" ${data?.relationship_type === 'other' ? 'selected' : ''}>Other</option>
                </select>
            </div>
            <div>
                <label class="form-label">Subtype</label>
                <input type="text" class="form-control relationship-subtype" placeholder="e.g., Business partner, Former lover" value="${data?.relationship_subtype || ''}">
            </div>
            <div>
                <label class="form-label">Strength</label>
                <input type="text" class="form-control relationship-strength" placeholder="e.g., Strong, Weak, Neutral" value="${data?.strength || ''}">
            </div>
        </div>
        <div>
            <label class="form-label">Description</label>
            <textarea class="form-control relationship-description" rows="3" placeholder="Describe the nature of this relationship">${data?.description || ''}</textarea>
        </div>
    `;
        },
        (entry, container, emptyState) => {
            entry.remove();
            if (container.querySelectorAll('.relationship-entry').length === 0 && emptyState) {
                emptyState.style.display = 'block';
            }
        },
        relationshipData
    );
};

// Make collect functions available to DataManager if needed
window.collectCoteries = collectCoteries;
window.collectRelationships = collectRelationships;

// Load character names for relationship dropdowns
async function loadCharacterNames() {
    try {
        const response = await fetch('includes/api_get_character_names.php');
        const data = await response.json();
        if (data.success && Array.isArray(data.characters)) {
            window.allCharacters = data.characters;
            console.log('Loaded', data.characters.length, 'characters for relationship dropdowns');
        } else {
            console.error('Failed to load character names:', data.error);
            window.allCharacters = [];
        }
    } catch (error) {
        console.error('Error loading character names:', error);
        window.allCharacters = [];
    }
}

// ============================================================================
// DOM INITIALIZATION & EVENT LISTENERS
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize virtue sync
    enqueueVirtueSync();
    
    // Setup NPC checkbox - when checked, set player name field to "NPC" and disable it
    const npcCheckbox = document.getElementById('npc');
    const playerNameInput = document.getElementById('playerName');
    const playerNameRequired = document.getElementById('playerNameRequired');
    
    // Function to update NPC state based on checkbox and player name value
    function updateNPCState() {
        if (!npcCheckbox || !playerNameInput) return;
        
        const isNPC = playerNameInput.value === 'NPC';
        if (isNPC) {
            npcCheckbox.checked = true;
            playerNameInput.disabled = true;
            playerNameInput.removeAttribute('required');
            if (playerNameRequired) playerNameRequired.style.display = 'none';
        }
    }
    
    if (npcCheckbox && playerNameInput) {
        npcCheckbox.addEventListener('change', function() {
            if (this.checked) {
                playerNameInput.value = 'NPC';
                playerNameInput.disabled = true;
                playerNameInput.removeAttribute('required');
                if (playerNameRequired) playerNameRequired.style.display = 'none';
            } else {
                playerNameInput.disabled = false;
                playerNameInput.setAttribute('required', 'required');
                if (playerNameRequired) playerNameRequired.style.display = 'inline';
                if (playerNameInput.value === 'NPC') {
                    playerNameInput.value = '';
                }
            }
        });
        
        // Watch for player name changes to update NPC checkbox
        playerNameInput.addEventListener('input', function() {
            if (this.value === 'NPC' && !npcCheckbox.checked) {
                npcCheckbox.checked = true;
                this.disabled = true;
                this.removeAttribute('required');
                if (playerNameRequired) playerNameRequired.style.display = 'none';
            } else if (this.value !== 'NPC' && npcCheckbox.checked) {
                npcCheckbox.checked = false;
                this.disabled = false;
                this.setAttribute('required', 'required');
                if (playerNameRequired) playerNameRequired.style.display = 'inline';
            }
        });
        
        // Initialize: if player_name is "NPC" on load, check NPC checkbox
        // Also check periodically in case form is populated after DOMContentLoaded
        updateNPCState();
        setTimeout(updateNPCState, 500);
        setTimeout(updateNPCState, 1000);
        setTimeout(updateNPCState, 2000);
    }
    
    // Setup save button listeners
    console.log('Setting up save button listeners...');
    // If character is loaded via URL ?id=, seed hidden field so updates don't insert
    const params = new URLSearchParams(location.search);
    const urlId = params.get('id');
    if (urlId && document.getElementById('characterId')) {
        document.getElementById('characterId').value = urlId;
    }
    const rawReturnUrl = params.get('returnUrl');
    const fallbackExitUrl = '/admin/admin_panel.php';
    let exitTargetUrl = fallbackExitUrl;

    if (rawReturnUrl) {
        try {
            const decodedReturn = decodeURIComponent(rawReturnUrl);
            if (decodedReturn.includes('admin/admin_panel.php')) {
                if (decodedReturn.startsWith('http://') || decodedReturn.startsWith('https://')) {
                    exitTargetUrl = decodedReturn;
                } else if (decodedReturn.startsWith('/')) {
                    exitTargetUrl = decodedReturn;
                } else {
                    exitTargetUrl = '/' + decodedReturn;
                }
            }
        } catch (error) {
            console.warn('Invalid returnUrl parameter', error);
        }
    }
    
    // Add click listeners to all save buttons
    const saveButtons = document.querySelectorAll('.save-btn');
    console.log('Found save buttons:', saveButtons.length);
    
    saveButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Save button clicked!');
            saveCharacter();
        });
    });

    // Exit button handler (local to this page)
    const exitBtn = document.getElementById('exitEditorBtn');
    const handleExit = (event) => {
        if (event) {
            event.preventDefault();
        }
        console.log('Exit Editor button pressed');
        window.location.href = exitTargetUrl;
    };
    if (exitBtn) {
        exitBtn.addEventListener('click', handleExit);
    }

    // Bind all inline Exit buttons (e.g., in mobile-save-container)
    const exitInlineBtns = document.querySelectorAll('.exit-inline');
    exitInlineBtns.forEach(function(btn){
        btn.addEventListener('click', handleExit);
    });

    // Image upload wiring for this page's IDs
    // NOTE: This inline code works alongside js/character_image.js CharacterImageManager
    // The CharacterImageManager handles most functionality, this is a fallback
    const fileInput = document.getElementById('characterImageInput');
    const uploadBtn = document.getElementById('uploadCharacterImageBtn');
    const removeBtn = document.getElementById('removeCharacterImageBtn');
    const previewImg = document.getElementById('characterImagePreview');
    const placeholder = document.getElementById('characterImagePlaceholder');

    function getEffectiveCharacterId() {
        const hid = document.getElementById('characterId');
        if (hid && hid.value) return parseInt(hid.value, 10);
        const p = new URLSearchParams(location.search);
        return p.get('id') ? parseInt(p.get('id'), 10) : null;
    }

    function showPreview(fileOrName) {
        if (!fileOrName) return;
        if (typeof fileOrName === 'string') {
            if (previewImg) previewImg.src = '/uploads/characters/' + fileOrName;
            if (placeholder) placeholder.style.display = 'none';
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (previewImg) previewImg.src = String(reader.result);
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(fileOrName);
    }

    // Ensure label click triggers file input
    const fileLabel = document.querySelector('label[for="characterImageInput"]');
    if (fileLabel && fileInput) {
        fileLabel.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Make input visible and clickable
            fileInput.style.position = 'fixed';
            fileInput.style.top = '50%';
            fileInput.style.left = '50%';
            fileInput.style.transform = 'translate(-50%, -50%)';
            fileInput.style.opacity = '0.01';
            fileInput.style.display = 'block';
            fileInput.style.width = '1px';
            fileInput.style.height = '1px';
            fileInput.style.zIndex = '999999';
            fileInput.style.pointerEvents = 'auto';
            
            // Force a reflow
            fileInput.offsetHeight;
            
            // Click the input
            fileInput.focus();
            fileInput.click();
            
            // Hide it again after a delay
            setTimeout(() => {
                fileInput.style.display = 'none';
                fileInput.style.position = '';
                fileInput.style.top = '';
                fileInput.style.left = '';
                fileInput.style.transform = '';
                fileInput.style.opacity = '';
                fileInput.style.zIndex = '';
                fileInput.style.width = '';
                fileInput.style.height = '';
                fileInput.style.pointerEvents = '';
            }, 300);
        }, true); // Capture phase to catch it early
    }

    // File input change handler (fallback if CharacterImageManager doesn't handle it)
    if (fileInput) {
        const changeHandler = function(e) {
            console.log('[inline] File input change event fired', e);
            console.log('[inline] Event target:', e.target);
            console.log('[inline] Event target ID:', e.target.id);
            console.log('[inline] Event target files:', e.target.files);
            console.log('[inline] Event target files length:', e.target.files ? e.target.files.length : 'null');
            
            // Check both event.target and fileInput
            const targetInput = e.target;
            const file = targetInput.files && targetInput.files[0];
            if (!file) {
                console.log('[inline] No file in input - user may have canceled dialog');
                return;
            }
            console.log('[inline] File selected:', file.name, file.type, file.size);
            if (uploadBtn) {
                uploadBtn.style.display = 'inline-block';
                console.log('[inline] Upload button shown');
            }
            showPreview(file);
            console.log('[inline] Preview shown');
        };
        
        // Add listeners without replacing the input (that breaks label connection)
        fileInput.addEventListener('change', changeHandler, false);
        fileInput.addEventListener('change', changeHandler, true);
        
        console.log('[inline] File input change listeners attached to element:', fileInput);
        console.log('[inline] File input ID:', fileInput.id);
        console.log('[inline] Label for attribute:', fileLabel ? fileLabel.getAttribute('for') : 'no label found');
    }

    // Upload button handler (fallback)
    if (uploadBtn) {
        uploadBtn.addEventListener('click', async function() {
            const file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) { alert('Select an image first.'); return; }
            try {
                const form = new FormData();
                form.append('image', file);
                const cid = getEffectiveCharacterId();
                if (cid) form.append('characterId', String(cid));
                console.log('[image] Upload request sent');
                const resp = await fetch('includes/upload_character_image.php', { method: 'POST', body: form });
                const data = await resp.json();
                if (!resp.ok || !data || !data.success) throw new Error((data && data.error) || ('HTTP '+resp.status));
                const filename = data.filePath || data.image_path;
                console.log('[image] Upload successful:', filename);
                const hidden = document.getElementById('imagePath');
                if (hidden && filename) hidden.value = filename;
                showPreview(filename);
                alert('Image uploaded.');
            } catch (err) {
                console.error('[image] Upload failed:', err);
                alert('Image upload failed: '+ err.message);
            }
        });
    }

    // Remove button handler (fallback)
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            if (previewImg) previewImg.src = '';
            if (placeholder) placeholder.style.display = '';
            const hidden = document.getElementById('imagePath');
            if (hidden) hidden.value = '';
            if (fileInput) fileInput.value = '';
            if (uploadBtn) uploadBtn.style.display = 'none';
        });
    }

    // If an image filename is already present (loaded character), show it
    (function seedPreviewFromHidden(){
        const hidden = document.getElementById('imagePath');
        if (hidden && hidden.value) {
            showPreview(hidden.value);
        }
    })();

    // Note: Character image is loaded by the main app via DataManager.loadCharacter()
    // No need for duplicate fetch here - the main app will populate imagePath when character loads
    
    // ============================================================================
    // INLINE EVENT HANDLER CONVERSIONS
    // ============================================================================
    
    // Virtue adjustment buttons
    const conscienceMinus = document.getElementById('conscienceMinus');
    const consciencePlus = document.getElementById('consciencePlus');
    const selfControlMinus = document.getElementById('selfControlMinus');
    const selfControlPlus = document.getElementById('selfControlPlus');
    
    if (conscienceMinus) {
        conscienceMinus.addEventListener('click', () => adjustVirtue('conscience', -1));
    }
    if (consciencePlus) {
        consciencePlus.addEventListener('click', () => adjustVirtue('conscience', 1));
    }
    if (selfControlMinus) {
        selfControlMinus.addEventListener('click', () => adjustVirtue('selfControl', -1));
    }
    if (selfControlPlus) {
        selfControlPlus.addEventListener('click', () => adjustVirtue('selfControl', 1));
    }
    
    // Coterie and Relationship buttons
    const addCoterieBtn = document.getElementById('addCoterieBtn');
    const addRelationshipBtn = document.getElementById('addRelationshipBtn');
    
    if (addCoterieBtn) {
        addCoterieBtn.addEventListener('click', () => addCoterieEntry());
    }
    if (addRelationshipBtn) {
        addRelationshipBtn.addEventListener('click', () => addRelationshipEntry());
    }
    
    // Finalize and Download buttons
    const finalizeBtn = document.querySelector('button[onclick="finalizeCharacter()"]');
    const downloadBtn = document.querySelector('button[onclick="downloadCharacterSheet()"]');
    
    if (finalizeBtn) {
        finalizeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            finalizeCharacter();
        });
    }
    if (downloadBtn) {
        downloadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            downloadCharacterSheet();
        });
    }
    
    // Event delegation for remove discipline buttons (dynamically generated)
    const disciplineList = document.getElementById('clanDisciplinesList');
    if (disciplineList) {
        disciplineList.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-discipline-btn');
            if (removeBtn) {
                const disciplineName = removeBtn.getAttribute('data-discipline-name');
                const disciplineLevel = removeBtn.getAttribute('data-discipline-level');
                if (disciplineName && disciplineLevel) {
                    removeDiscipline(disciplineName, parseInt(disciplineLevel, 10));
                }
            }
        });
    }
    
    // Update discipline availability when clan changes
    const clanSelect = document.getElementById('clan');
    if (clanSelect) {
        clanSelect.addEventListener('change', updateDisciplineAvailability);
        // Initial update
        updateDisciplineAvailability();
    }
    
    // Load character names for relationship dropdowns
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCharacterNames);
    } else {
        loadCharacterNames();
    }
});

// ============================================================================
// GLOBAL WRAPPER FUNCTIONS FOR TRAIT SYSTEM
// ============================================================================

/**
 * Global wrapper function for selecting traits
 * Called from onclick handlers in HTML
 */
window.selectTrait = function(category, traitName) {
    const app = window.characterCreationApp;
    if (!app || !app.modules || !app.modules.traitSystem) {
        console.error('TraitSystem is not ready; cannot select trait.');
        return;
    }
    app.modules.traitSystem.selectTrait(category, traitName);
};

/**
 * Global wrapper function for selecting negative traits
 * Called from onclick handlers in HTML
 */
window.selectNegativeTrait = function(category, traitName) {
    const app = window.characterCreationApp;
    if (!app || !app.modules || !app.modules.traitSystem) {
        console.error('TraitSystem is not ready; cannot select negative trait.');
        return;
    }
    app.modules.traitSystem.selectNegativeTrait(category, traitName);
};

/**
 * Global wrapper function for showing tabs
 * Called from onclick handlers in HTML
 */
window.showTab = function(tabIndex) {
    const app = window.characterCreationApp;
    if (!app || !app.modules || !app.modules.tabManager) {
        console.error('TabManager is not ready; cannot show tab.');
        // Fallback to direct DOM manipulation if TabManager isn't ready
        if (typeof tabIndex === 'number') {
            const tabContent = document.querySelector(`#tab${tabIndex}`);
            if (tabContent) {
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                // Show selected tab
                tabContent.classList.add('active');
                // Update tab buttons
                document.querySelectorAll('.tab').forEach(btn => {
                    btn.classList.remove('active');
                });
                const tabBtn = document.querySelectorAll('.tab')[tabIndex];
                if (tabBtn) {
                    tabBtn.classList.add('active');
                }
            }
        }
        return;
    }
    // Convert numeric index to tab ID if needed
    if (typeof tabIndex === 'number') {
        const tabInfo = app.modules.tabManager.tabs.find(tab => tab.index === tabIndex);
        if (tabInfo) {
            app.modules.tabManager.showTab(tabInfo.id);
        } else {
            // Fallback: try direct numeric index
            app.modules.tabManager.showTab(tabIndex);
        }
    } else {
        app.modules.tabManager.showTab(tabIndex);
    }
};

