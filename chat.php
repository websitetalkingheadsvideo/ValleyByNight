<?php
/**
 * Chat Room - Valley by Night
 * Character selection and chat interface
 */
define('LOTN_VERSION', '0.6.0');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include header with chat CSS
$extra_css = ['css/chat.css'];
include 'includes/header.php';
?>

<div class="chat-container">
    <h2 class="section-heading">💬 Chat Room</h2>
    <p class="welcome-text">Select a character to enter the chat.</p>
    
    <div class="chat-content">
            <div class="character-selection">
                <h3>Select Character for Chat</h3>
                <div class="character-list" id="characterList" role="status" aria-live="polite" aria-busy="true">
                    <p>Loading your characters...</p>
                </div>
                <div class="selected-character hidden" id="selectedCharacter" role="status" aria-live="polite">
                    <h4>Selected Character:</h4>
                    <div class="character-info" id="characterInfo"></div>
                </div>
            </div>
            
            <div class="chat-interface hidden" id="chatInterface">
                <div class="chat-placeholder">
                    <h2>Chat System</h2>
                    <p>Chat as: <span id="chatCharacterName"></span></p>
                    <p>This is a placeholder for the chat functionality.</p>
                    <p>Future features may include:</p>
                    <ul class="inline-list">
                        <li>Real-time messaging</li>
                        <li>Character roleplay channels</li>
                        <li>Game master communications</li>
                        <li>Player discussions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
        let selectedCharacter = null;
        let userCharacters = [];

        // Load user's characters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadUserCharacters();
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
                <div class="character-card" onclick="selectCharacter(${character.id})">
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

        function selectCharacter(characterId) {
            // Remove previous selection
            document.querySelectorAll('.character-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selection to clicked card
            event.target.closest('.character-card').classList.add('selected');

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
    </script>

<?php
// Include footer
include 'includes/footer.php';
?>
