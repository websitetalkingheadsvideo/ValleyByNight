/**
 * Wraith Character Creation JavaScript
 * Handles form interactions, tab navigation, and saving
 */

// Tab management
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + 'Tab').classList.add('active');
        });
    });
    
    // Save button handler
    const saveBtn = document.getElementById('saveHeaderBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveWraithCharacter);
    }
    
    // XP calculation
    const xpInputs = ['xpTotal', 'xpSpent', 'shadowXpTotal', 'shadowXpSpent'];
    xpInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', calculateAvailableXp);
        }
    });
    
    // Initialize lists
    initializeLists();
    
    // Setup handlers after DOM is ready
    setTimeout(() => {
        setupAttributeHandlers();
        updateBackgroundButtons();
    }, 100);
    
    // Add button event listeners (replacing inline onclick handlers)
    const addFetterBtn = document.getElementById('addFetterBtn');
    if (addFetterBtn) {
        addFetterBtn.addEventListener('click', addFetter);
    }
    
    const addPassionBtn = document.getElementById('addPassionBtn');
    if (addPassionBtn) {
        addPassionBtn.addEventListener('click', addPassion);
    }
    
    const addArcanoiBtn = document.getElementById('addArcanoiBtn');
    if (addArcanoiBtn) {
        addArcanoiBtn.addEventListener('click', addArcanoi);
    }
    
    const addDarkPassionBtn = document.getElementById('addDarkPassionBtn');
    if (addDarkPassionBtn) {
        addDarkPassionBtn.addEventListener('click', addDarkPassion);
    }
    
    const addRelationshipBtn = document.getElementById('addRelationshipBtn');
    if (addRelationshipBtn) {
        addRelationshipBtn.addEventListener('click', addRelationship);
    }
    
    const addArtifactBtn = document.getElementById('addArtifactBtn');
    if (addArtifactBtn) {
        addArtifactBtn.addEventListener('click', addArtifact);
    }
});

// Initialize dynamic lists and state
function initializeLists() {
    // Initialize fetters, passions, arcanoi, etc. with empty arrays
    if (!window.wraithData) {
        window.wraithData = {
            attributes: {
                Physical: { Strength: 1, Dexterity: 1, Stamina: 1 },
                Social: { Charisma: 1, Manipulation: 1, Appearance: 1 },
                Mental: { Perception: 1, Intelligence: 1, Wits: 1 }
            },
            abilities: {
                Physical: [],
                Social: [],
                Mental: [],
                Optional: []
            },
            backgrounds: {},
            fetters: [],
            passions: [],
            arcanoi: [],
            darkPassions: [],
            relationships: [],
            artifacts: []
        };
    }
    
    // Initialize attributes display
    renderAttributes();
    
    // Initialize abilities
    setupAbilityHandlers();
    
    // Initialize backgrounds
    setupBackgroundHandlers();
}

// Add Fetter
function addFetter() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.fetters.length;
    window.wraithData.fetters.push({name: '', rating: 1, description: ''});
    renderFetters();
}

function renderFetters() {
    const container = document.getElementById('fettersList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.fetters.forEach((fetter, index) => {
        const div = document.createElement('div');
        div.className = 'fetter-item mb-2';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Fetter Name" 
                           value="${fetter.name}" onchange="updateFetter(${index}, 'name', this.value)">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Rating" min="1" max="5"
                           value="${fetter.rating}" onchange="updateFetter(${index}, 'rating', this.value)">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" placeholder="Description"
                           value="${fetter.description}" onchange="updateFetter(${index}, 'description', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFetter(${index})">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateFetter(index, field, value) {
    window.wraithData.fetters[index][field] = value;
}

function removeFetter(index) {
    window.wraithData.fetters.splice(index, 1);
    renderFetters();
}

// Add Passion
function addPassion() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.passions.length;
    window.wraithData.passions.push({passion: '', rating: 1});
    renderPassions();
}

function renderPassions() {
    const container = document.getElementById('passionsList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.passions.forEach((passion, index) => {
        const div = document.createElement('div');
        div.className = 'passion-item mb-2';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" placeholder="Passion"
                           value="${passion.passion}" onchange="updatePassion(${index}, 'passion', this.value)">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Rating" min="1" max="5"
                           value="${passion.rating}" onchange="updatePassion(${index}, 'rating', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePassion(${index})">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updatePassion(index, field, value) {
    window.wraithData.passions[index][field] = value;
}

function removePassion(index) {
    window.wraithData.passions.splice(index, 1);
    renderPassions();
}

// Add Arcanoi
function addArcanoi() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.arcanoi.length;
    window.wraithData.arcanoi.push({name: '', rating: 1, arts: []});
    renderArcanoi();
}

function renderArcanoi() {
    const container = document.getElementById('arcanoiList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.arcanoi.forEach((arcanoi, index) => {
        const div = document.createElement('div');
        div.className = 'arcanoi-item mb-3 p-3 border rounded';
        div.innerHTML = `
            <div class="row g-2 mb-2">
                <div class="col-md-8">
                    <input type="text" class="form-control" placeholder="Arcanoi Name"
                           value="${arcanoi.name}" onchange="updateArcanoi(${index}, 'name', this.value)">
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" placeholder="Rating" min="1" max="5"
                           value="${arcanoi.rating}" onchange="updateArcanoi(${index}, 'rating', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArcanoi(${index})">×</button>
                </div>
            </div>
            <div class="arts-list" id="artsList${index}"></div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addArt(${index})">Add Art</button>
        `;
        container.appendChild(div);
    });
}

function updateArcanoi(index, field, value) {
    window.wraithData.arcanoi[index][field] = field === 'rating' ? parseInt(value) : value;
}

function removeArcanoi(index) {
    window.wraithData.arcanoi.splice(index, 1);
    renderArcanoi();
}

function addArt(arcanoiIndex) {
    if (!window.wraithData.arcanoi[arcanoiIndex].arts) {
        window.wraithData.arcanoi[arcanoiIndex].arts = [];
    }
    window.wraithData.arcanoi[arcanoiIndex].arts.push({level: 1, power: ''});
    renderArts(arcanoiIndex);
}

function renderArts(arcanoiIndex) {
    const container = document.getElementById('artsList' + arcanoiIndex);
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.arcanoi[arcanoiIndex].arts.forEach((art, artIndex) => {
        const div = document.createElement('div');
        div.className = 'art-item mb-2';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Level" min="1" max="5"
                           value="${art.level}" onchange="updateArt(${arcanoiIndex}, ${artIndex}, 'level', this.value)">
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" placeholder="Power Name"
                           value="${art.power}" onchange="updateArt(${arcanoiIndex}, ${artIndex}, 'power', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArt(${arcanoiIndex}, ${artIndex})">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateArt(arcanoiIndex, artIndex, field, value) {
    window.wraithData.arcanoi[arcanoiIndex].arts[artIndex][field] = field === 'level' ? parseInt(value) : value;
}

function removeArt(arcanoiIndex, artIndex) {
    window.wraithData.arcanoi[arcanoiIndex].arts.splice(artIndex, 1);
    renderArts(arcanoiIndex);
}

// Add Dark Passion
function addDarkPassion() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.darkPassions.length;
    window.wraithData.darkPassions.push({passion: '', rating: 1});
    renderDarkPassions();
}

function renderDarkPassions() {
    const container = document.getElementById('darkPassionsList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.darkPassions.forEach((passion, index) => {
        const div = document.createElement('div');
        div.className = 'dark-passion-item mb-2';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" placeholder="Dark Passion"
                           value="${passion.passion}" onchange="updateDarkPassion(${index}, 'passion', this.value)">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Rating" min="1" max="5"
                           value="${passion.rating}" onchange="updateDarkPassion(${index}, 'rating', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeDarkPassion(${index})">×</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateDarkPassion(index, field, value) {
    window.wraithData.darkPassions[index][field] = value;
}

function removeDarkPassion(index) {
    window.wraithData.darkPassions.splice(index, 1);
    renderDarkPassions();
}

// Add Relationship
function addRelationship() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.relationships.length;
    window.wraithData.relationships.push({
        related_character_name: '',
        relationship_type: '',
        relationship_subtype: '',
        strength: '',
        description: ''
    });
    renderRelationships();
}

function renderRelationships() {
    const container = document.getElementById('relationshipsList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.relationships.forEach((rel, index) => {
        const div = document.createElement('div');
        div.className = 'relationship-item mb-2 p-2 border rounded';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Character Name"
                           value="${rel.related_character_name}" onchange="updateRelationship(${index}, 'related_character_name', this.value)">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" placeholder="Type"
                           value="${rel.relationship_type}" onchange="updateRelationship(${index}, 'relationship_type', this.value)">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" placeholder="Subtype"
                           value="${rel.relationship_subtype}" onchange="updateRelationship(${index}, 'relationship_subtype', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeRelationship(${index})">×</button>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-11">
                    <textarea class="form-control form-control-sm" placeholder="Description" rows="2"
                              onchange="updateRelationship(${index}, 'description', this.value)">${rel.description}</textarea>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateRelationship(index, field, value) {
    window.wraithData.relationships[index][field] = value;
}

function removeRelationship(index) {
    window.wraithData.relationships.splice(index, 1);
    renderRelationships();
}

// Add Artifact
function addArtifact() {
    if (!window.wraithData) initializeLists();
    const index = window.wraithData.artifacts.length;
    window.wraithData.artifacts.push({name: '', description: ''});
    renderArtifacts();
}

function renderArtifacts() {
    const container = document.getElementById('artifactsList');
    if (!container) return;
    
    container.innerHTML = '';
    window.wraithData.artifacts.forEach((artifact, index) => {
        const div = document.createElement('div');
        div.className = 'artifact-item mb-2';
        div.innerHTML = `
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" class="form-control form-control-sm" placeholder="Artifact Name"
                           value="${artifact.name}" onchange="updateArtifact(${index}, 'name', this.value)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArtifact(${index})">×</button>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-11">
                    <textarea class="form-control form-control-sm" placeholder="Description" rows="2"
                              onchange="updateArtifact(${index}, 'description', this.value)">${artifact.description}</textarea>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function updateArtifact(index, field, value) {
    window.wraithData.artifacts[index][field] = value;
}

function removeArtifact(index) {
    window.wraithData.artifacts.splice(index, 1);
    renderArtifacts();
}

// Calculate available XP
function calculateAvailableXp() {
    const xpTotal = parseInt(document.getElementById('xpTotal')?.value || 0);
    const xpSpent = parseInt(document.getElementById('xpSpent')?.value || 0);
    const shadowXpTotal = parseInt(document.getElementById('shadowXpTotal')?.value || 0);
    const shadowXpSpent = parseInt(document.getElementById('shadowXpSpent')?.value || 0);
    
    const xpAvailable = document.getElementById('xpAvailable');
    const shadowXpAvailable = document.getElementById('shadowXpAvailable');
    
    if (xpAvailable) xpAvailable.value = Math.max(0, xpTotal - xpSpent);
    if (shadowXpAvailable) shadowXpAvailable.value = Math.max(0, shadowXpTotal - shadowXpSpent);
}

// Attribute handlers
function setupAttributeHandlers() {
    document.querySelectorAll('.attribute-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;
            const attribute = this.dataset.attribute;
            
            if (!window.wraithData.attributes[category]) {
                window.wraithData.attributes[category] = {};
            }
            if (!window.wraithData.attributes[category][attribute]) {
                window.wraithData.attributes[category][attribute] = 0;
            }
            
            if (window.wraithData.attributes[category][attribute] < 5) {
                window.wraithData.attributes[category][attribute]++;
                renderAttributes();
            }
        });
    });
}

function renderAttributes() {
    if (!window.wraithData?.attributes) return;
    
    ['Physical', 'Social', 'Mental'].forEach(category => {
        const listEl = document.getElementById(category.toLowerCase() + 'AttributesList');
        const totalEl = document.getElementById(category.toLowerCase() + 'AttributesTotal');
        
        if (!listEl) return;
        
        const attributes = window.wraithData.attributes[category] || {};
        let total = 0;
        let html = '';
        
        Object.keys(attributes).forEach(attr => {
            const value = attributes[attr] || 0;
            total += value;
            
            if (value > 0) {
                html += `
                    <div class="selected-attribute">
                        <span class="attribute-name">${attr}</span>
                        <span class="attribute-value">${value}</span>
                        <button type="button" class="remove-attribute-btn" 
                                data-category="${category}" data-attribute="${attr}">×</button>
                    </div>
                `;
            }
        });
        
        listEl.innerHTML = html || '<p class="helper-text">No attributes selected</p>';
        
        if (totalEl) {
            totalEl.textContent = total;
        }
        
        // Add remove handlers
        listEl.querySelectorAll('.remove-attribute-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cat = this.dataset.category;
                const attr = this.dataset.attribute;
                if (window.wraithData.attributes[cat][attr] > 1) {
                    window.wraithData.attributes[cat][attr]--;
                    renderAttributes();
                }
            });
        });
    });
}

// Ability handlers
function setupAbilityHandlers() {
    document.querySelectorAll('.ability-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;
            const ability = this.dataset.ability;
            
            if (!window.wraithData.abilities[category]) {
                window.wraithData.abilities[category] = [];
            }
            
            // Count current level of this ability
            const currentCount = window.wraithData.abilities[category].filter(a => a === ability).length;
            
            if (currentCount < 5) {
                window.wraithData.abilities[category].push(ability);
                renderAbilities();
            }
        });
    });
}

function renderAbilities() {
    if (!window.wraithData?.abilities) return;
    
    ['Physical', 'Social', 'Mental', 'Optional'].forEach(category => {
        const listEl = document.getElementById(category.toLowerCase() + 'AbilitiesList');
        const countEl = document.getElementById(category.toLowerCase() + 'AbilitiesCountDisplay');
        
        if (!listEl) return;
        
        const abilities = window.wraithData.abilities[category] || [];
        
        // Count occurrences to get levels
        const abilityCounts = {};
        abilities.forEach(ability => {
            abilityCounts[ability] = (abilityCounts[ability] || 0) + 1;
        });
        
        let totalDots = 0;
        let html = '';
        
        Object.keys(abilityCounts).forEach(ability => {
            const level = abilityCounts[ability];
            totalDots += level;
            html += `
                <div class="selected-ability">
                    <span class="ability-name">${ability}</span>
                    <span class="ability-level">${level}</span>
                    <button type="button" class="remove-ability-btn" 
                            data-category="${category}" data-ability="${ability}">×</button>
                </div>
            `;
        });
        
        listEl.innerHTML = html || '<p class="helper-text">No abilities selected</p>';
        
        if (countEl) {
            countEl.textContent = totalDots;
        }
        
        // Add remove handlers
        listEl.querySelectorAll('.remove-ability-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cat = this.dataset.category;
                const ability = this.dataset.ability;
                const index = window.wraithData.abilities[cat].indexOf(ability);
                if (index > -1) {
                    window.wraithData.abilities[cat].splice(index, 1);
                    renderAbilities();
                }
            });
        });
    });
}

// Background handlers
function setupBackgroundHandlers() {
    document.querySelectorAll('.background-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const background = this.dataset.background;
            const level = parseInt(this.dataset.level);
            
            if (!window.wraithData.backgrounds) {
                window.wraithData.backgrounds = {};
            }
            
            window.wraithData.backgrounds[background] = level;
            renderBackgrounds();
            updateBackgroundButtons();
        });
    });
}

function renderBackgrounds() {
    if (!window.wraithData?.backgrounds) return;
    
    const listEl = document.getElementById('backgroundsList');
    const totalEl = document.getElementById('backgroundsTotal');
    
    if (!listEl) return;
    
    const backgrounds = window.wraithData.backgrounds || {};
    let total = 0;
    let html = '';
    
    Object.keys(backgrounds).forEach(bg => {
        const level = backgrounds[bg];
        if (level > 0) {
            total += level;
            html += `
                <div class="selected-background">
                    <span class="background-name">${bg}</span>
                    <span class="background-level">${level}/5</span>
                    <button type="button" class="remove-background-btn" 
                            data-background="${bg}">×</button>
                </div>
            `;
        }
    });
    
    listEl.innerHTML = html || '<p class="helper-text">No backgrounds selected</p>';
    
    if (totalEl) {
        totalEl.textContent = total;
    }
    
    // Add remove handlers
    listEl.querySelectorAll('.remove-background-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bg = this.dataset.background;
            delete window.wraithData.backgrounds[bg];
            renderBackgrounds();
            updateBackgroundButtons();
        });
    });
}

function updateBackgroundButtons() {
    const backgrounds = window.wraithData?.backgrounds || {};
    
    document.querySelectorAll('.background-option-btn').forEach(btn => {
        const background = btn.dataset.background;
        const level = parseInt(btn.dataset.level);
        const currentLevel = backgrounds[background] || 0;
        
        btn.classList.toggle('selected', currentLevel >= level);
    });
}

// Save Wraith Character
function saveWraithCharacter() {
    const form = document.getElementById('wraithCharacterForm');
    if (!form) return;
    
    // Collect form data
    const formData = {
        id: document.getElementById('characterId')?.value || 0,
        character_name: document.getElementById('characterName')?.value || '',
        shadow_name: document.getElementById('shadowName')?.value || '',
        player_name: document.getElementById('playerName')?.value || '',
        chronicle: document.getElementById('chronicle')?.value || 'Valley by Night',
        nature: document.getElementById('nature')?.value || '',
        demeanor: document.getElementById('demeanor')?.value || '',
        concept: document.getElementById('concept')?.value || '',
        circle: document.getElementById('circle')?.value || '',
        guild: document.getElementById('guild')?.value || '',
        legion_at_death: document.getElementById('legionAtDeath')?.value || '',
        date_of_death: document.getElementById('dateOfDeath')?.value || '',
        cause_of_death: document.getElementById('causeOfDeath')?.value || '',
        pc: document.getElementById('pc')?.checked ? 1 : 0,
        appearance: document.getElementById('appearance')?.value || '',
        ghostly_appearance: document.getElementById('ghostlyAppearance')?.value || '',
        biography: document.getElementById('biography')?.value || '',
        notes: document.getElementById('notes')?.value || '',
        willpower_permanent: parseInt(document.getElementById('willpowerPermanent')?.value || 5),
        willpower_current: parseInt(document.getElementById('willpowerCurrent')?.value || 5),
        attributes: window.wraithData?.attributes || {
            Physical: { Strength: 1, Dexterity: 1, Stamina: 1 },
            Social: { Charisma: 1, Manipulation: 1, Appearance: 1 },
            Mental: { Perception: 1, Intelligence: 1, Wits: 1 }
        },
        abilities: window.wraithData?.abilities || {
            Physical: [],
            Social: [],
            Mental: [],
            Optional: []
        },
        backgrounds: window.wraithData?.backgrounds || {},
        fetters: window.wraithData?.fetters || [],
        passions: window.wraithData?.passions || [],
        arcanoi: window.wraithData?.arcanoi || [],
        shadow: {
            archetype: document.getElementById('shadowArchetype')?.value || '',
            angst_current: parseInt(document.getElementById('angstCurrent')?.value || 0),
            angst_permanent: parseInt(document.getElementById('angstPermanent')?.value || 0),
            dark_passions: window.wraithData?.darkPassions || [],
            thorns: document.getElementById('thorns')?.value || '',
            shadow_traits: document.getElementById('shadowTraits')?.value || '',
            shadow_notes: document.getElementById('shadowNotes')?.value || ''
        },
        pathos_corpus: {
            pathos_current: parseInt(document.getElementById('pathosCurrent')?.value || 0),
            pathos_max: parseInt(document.getElementById('pathosMax')?.value || 0),
            corpus_current: parseInt(document.getElementById('corpusCurrent')?.value || 0),
            corpus_max: parseInt(document.getElementById('corpusMax')?.value || 0),
            health_levels: []
        },
        harrowing: {
            last_harrowing_date: document.getElementById('lastHarrowingDate')?.value || '',
            harrowing_notes: document.getElementById('harrowingNotes')?.value || ''
        },
        status: {
            xp_total: parseInt(document.getElementById('xpTotal')?.value || 0),
            xp_spent: parseInt(document.getElementById('xpSpent')?.value || 0),
            xp_available: parseInt(document.getElementById('xpAvailable')?.value || 0),
            shadow_xp_total: parseInt(document.getElementById('shadowXpTotal')?.value || 0),
            shadow_xp_spent: parseInt(document.getElementById('shadowXpSpent')?.value || 0),
            shadow_xp_available: parseInt(document.getElementById('shadowXpAvailable')?.value || 0)
        },
        relationships: window.wraithData?.relationships || [],
        artifacts: window.wraithData?.artifacts || [],
        imagePath: document.getElementById('imagePath')?.value || ''
    };
    
    // Send to server
    fetch('includes/save_wraith_character.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.text())
    .then(data => {
        try {
            const jsonData = JSON.parse(data);
            if (jsonData.success) {
                alert('✅ Character saved successfully! Character ID: ' + jsonData.character_id);
                if (jsonData.character_id && !formData.id) {
                    document.getElementById('characterId').value = jsonData.character_id;
                }
            } else {
                alert('❌ Save failed: ' + jsonData.message);
            }
        } catch (e) {
            console.error('Invalid JSON response:', data);
            alert('❌ Invalid response from server');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error saving character: ' + error.message);
    });
}

