/**
 * Chat Room - Character Selection and Chat Interface
 * Extracted from chat.php
 */

let selectedCharacter = null;
let userCharacters = [];
let filteredCharacters = [];
let isAdmin = window.isAdmin || false;
let pendingFilterType = 'all'; // Store filter selection until characters load

// Set up character type filter for admins
function setupCharacterFilter() {
    if (isAdmin) {
        const filterSelect = document.getElementById('characterTypeFilter');
        console.log('Setting up filter, element found:', filterSelect);
        if (filterSelect) {
            // Add event listener directly
            filterSelect.addEventListener('change', function(e) {
                console.log('Filter changed to:', this.value);
                pendingFilterType = this.value; // Store the selection
                filterCharactersByType(this.value); // Try to apply immediately
            });
            console.log('Filter event listener attached');
        } else {
            console.error('characterTypeFilter element not found');
        }
    } else {
        console.log('User is not admin, filter not available');
    }
}

// Load user's characters when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set up filter immediately (dropdown should exist from PHP)
    setupCharacterFilter();
    
    loadUserCharacters();
    
    // Event delegation for character card clicks
    const characterList = document.getElementById('characterList');
    if (characterList) {
        characterList.addEventListener('click', function(event) {
            const characterCard = event.target.closest('.character-card');
            if (characterCard) {
                const characterId = characterCard.getAttribute('data-character-id');
                if (characterId) {
                    selectCharacter(parseInt(characterId, 10), event);
                }
            }
        });
    }
});

async function loadUserCharacters() {
    const list = document.getElementById('characterList');
    if (list) list.setAttribute('aria-busy', 'true');
    try {
        console.log('Fetching characters from API...');
        const response = await fetch('api_get_characters.php');
        console.log('API response status:', response.status);
        const data = await response.json();
        console.log('API response data:', data);
        
        if (data.success) {
            userCharacters = data.characters || [];
            console.log('Loaded characters:', userCharacters.length);
            if (userCharacters.length > 0) {
                console.log('Sample character:', userCharacters[0]);
            }
            
            // Apply stored filter after loading characters - this will now work since characters are loaded
            const filterSelect = document.getElementById('characterTypeFilter');
            if (filterSelect) {
                // Update dropdown to match stored filter
                filterSelect.value = pendingFilterType;
            }
            // Apply the filter now that characters are loaded
            console.log('Characters loaded, applying filter:', pendingFilterType, 'with', userCharacters.length, 'characters');
            filterCharactersByType(pendingFilterType);
            
            if (list) {
                list.setAttribute('aria-busy', 'false');
                let status = document.getElementById('characterListStatus');
                if (!status) {
                    status = document.createElement('div');
                    status.id = 'characterListStatus';
                    status.setAttribute('role', 'status');
                    status.className = 'visually-hidden';
                    list.prepend(status);
                }
                status.textContent = `${filteredCharacters.length} character${filteredCharacters.length === 1 ? '' : 's'} loaded`;
            }
        } else {
            if (list) {
                list.setAttribute('aria-busy', 'false');
                list.innerHTML = '<div class="no-characters">No characters found. <a href="lotn_char_create.php">Create your first character</a></div>';
            }
        }
    } catch (error) {
        console.error('Error loading characters:', error);
        if (list) {
            list.setAttribute('aria-busy', 'false');
            list.innerHTML = '<div class="no-characters">Error loading characters. Please try again.</div>';
        }
    }
}

function filterCharactersByType(type) {
    // If characters haven't loaded yet, just store the selection - it will be applied once characters load
    if (!userCharacters || userCharacters.length === 0) {
        pendingFilterType = type; // Update stored filter
        return; // Wait for characters to load
    }
    
    console.log('Filtering by type:', type);
    console.log('Total characters:', userCharacters.length);
    
    // Helper function to determine if character is NPC
    const isNPC = (char) => {
        return char.is_npc === true || char.player_name === 'NPC';
    };
    
    const npcCount = userCharacters.filter(isNPC).length;
    console.log('Characters with is_npc:', npcCount);
    
    if (type === 'all') {
        filteredCharacters = userCharacters;
    } else if (type === 'pc') {
        filteredCharacters = userCharacters.filter(char => !isNPC(char));
    } else if (type === 'npc') {
        filteredCharacters = userCharacters.filter(isNPC);
    }
    
    console.log('Filtered characters:', filteredCharacters.length);
    displayCharacters(filteredCharacters);
    
    // Update status
    const list = document.getElementById('characterList');
    if (list) {
        let status = document.getElementById('characterListStatus');
        if (status) {
            status.textContent = `${filteredCharacters.length} character${filteredCharacters.length === 1 ? '' : 's'} loaded`;
        }
    }
}

function displayCharacters(characters) {
    const characterList = document.getElementById('characterList');
    
    if (characters.length === 0) {
        characterList.innerHTML = 
            '<div class="no-characters">No characters found. <a href="lotn_char_create.php">Create your first character</a></div>';
        return;
    }

    characterList.innerHTML = characters.map(character => {
        const isNPC = character.is_npc === true || character.player_name === 'NPC';
        const npcBadge = isNPC ? '<span class="badge bg-purple ms-2">NPC</span>' : '';
        return `
        <div class="character-card" data-character-id="${character.id}" data-is-npc="${isNPC ? 'true' : 'false'}">
            <div class="character-name">
                ${character.character_name}${npcBadge}
            </div>
            <div class="character-details">
                <span><strong>Clan:</strong> ${character.clan || 'N/A'}</span>
                <span><strong>Generation:</strong> ${character.generation || 'N/A'}</span>
                <span><strong>Concept:</strong> ${character.concept || 'N/A'}</span>
                <span><strong>Nature:</strong> ${character.nature || 'N/A'}</span>
            </div>
        </div>
        `;
    }).join('');
}

function selectCharacter(characterId, event) {
    // Remove previous selection
    document.querySelectorAll('.character-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add selection to clicked card
    if (event && event.target) {
        const clickedCard = event.target.closest('.character-card');
        if (clickedCard) {
            clickedCard.classList.add('selected');
        }
    }

    // Find the selected character (search in all characters, not just filtered)
    selectedCharacter = userCharacters.find(char => char.id == characterId);
    
    if (selectedCharacter) {
        // Show selected character info
        const selectedEl = document.getElementById('selectedCharacter');
        if (selectedEl) selectedEl.classList.remove('hidden');
        document.getElementById('characterInfo').innerHTML = `
            <div class="info-item">
                <div class="info-label">Name:</div>
                <div>${selectedCharacter.character_name}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Clan:</div>
                <div>${selectedCharacter.clan}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Generation:</div>
                <div>${selectedCharacter.generation}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Concept:</div>
                <div>${selectedCharacter.concept}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Nature:</div>
                <div>${selectedCharacter.nature}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Demeanor:</div>
                <div>${selectedCharacter.demeanor}</div>
            </div>
        `;

        // Show chat interface
        const chatInterface = document.getElementById('chatInterface');
        if (chatInterface) chatInterface.classList.remove('hidden');
        document.getElementById('chatCharacterName').textContent = selectedCharacter.character_name;

        // Scroll to chat interface
        document.getElementById('chatInterface').scrollIntoView({ behavior: 'smooth' });
    }
}

