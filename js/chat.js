/**
 * Chat Room - Character Selection and Chat Interface
 * Extracted from chat.php
 */

let selectedCharacter = null;
let userCharacters = [];

// Load user's characters when page loads
document.addEventListener('DOMContentLoaded', function() {
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
        const response = await fetch('api_get_characters.php');
        const data = await response.json();
        
        if (data.success) {
            userCharacters = data.characters || [];
            displayCharacters(userCharacters);
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
                status.textContent = `${userCharacters.length} character${userCharacters.length === 1 ? '' : 's'} loaded`;
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

function displayCharacters(characters) {
    const characterList = document.getElementById('characterList');
    
    if (characters.length === 0) {
        characterList.innerHTML = 
            '<div class="no-characters">No characters found. <a href="lotn_char_create.php">Create your first character</a></div>';
        return;
    }

    characterList.innerHTML = characters.map(character => `
        <div class="character-card" data-character-id="${character.id}">
            <div class="character-name">${character.character_name}</div>
            <div class="character-details">
                <span><strong>Clan:</strong> ${character.clan}</span>
                <span><strong>Generation:</strong> ${character.generation}</span>
                <span><strong>Concept:</strong> ${character.concept}</span>
                <span><strong>Nature:</strong> ${character.nature}</span>
            </div>
        </div>
    `).join('');
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

    // Find the selected character
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

