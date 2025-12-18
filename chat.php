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

<script src="js/chat.js" defer></script>

<?php
// Include footer
include 'includes/footer.php';
?>
