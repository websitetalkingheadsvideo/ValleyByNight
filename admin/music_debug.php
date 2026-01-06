<?php
/**
 * Music System Debug & Test Page
 * Provides interface to view music state, trigger events, and test playback
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('LOTN_VERSION', '0.6.2');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/header.php';

// Get sample NPCs and locations for testing
$sample_npcs = db_fetch_all($conn, 
    "SELECT id, character_name, clan FROM characters WHERE player_name = 'NPC' ORDER BY character_name LIMIT 10"
);

$sample_locations = db_fetch_all($conn, 
    "SELECT id, name, type FROM locations ORDER BY name LIMIT 10"
);
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <h1 class="display-5 text-light fw-bold mb-1">🎵 Music System Debug & Test</h1>
    <p class="lead text-light fst-italic mb-4">Monitor music state, trigger events, and test playback</p>
    
    
    <!-- Current State Display -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card bg-dark border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">📊 Current Music State</h3>
                </div>
                <div class="card-body">
                    <div id="musicStateDisplay" class="text-light">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="spinner-border spinner-border-sm text-danger" role="status"></span>
                            <span>Loading music state...</span>
                        </div>
                    </div>
                    <button id="refreshStateBtn" class="btn btn-outline-danger btn-sm mt-3">
                        <i class="fas fa-sync-alt"></i> Refresh State
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Manual Triggers -->
    <div class="row g-3 mb-4">
        <!-- Location Triggers -->
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-warning">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">📍 Location Events</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-light mb-2">Select Location:</label>
                        <select id="locationSelect" class="form-select bg-dark text-light border-warning">
                            <option value="">-- Select Location --</option>
                            <?php foreach ($sample_locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc['id']); ?>" 
                                        data-name="<?php echo htmlspecialchars($loc['name']); ?>">
                                    <?php echo htmlspecialchars($loc['name']); ?> (<?php echo htmlspecialchars($loc['type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="triggerLocationEnterBtn" class="btn btn-warning">
                            <i class="fas fa-sign-in-alt"></i> Enter Location
                        </button>
                        <button id="triggerLocationExitBtn" class="btn btn-outline-warning">
                            <i class="fas fa-sign-out-alt"></i> Exit Location
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- NPC Focus Triggers -->
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-info">
                <div class="card-header bg-info text-dark">
                    <h4 class="mb-0">👤 NPC Focus Events</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-light mb-2">Select NPC:</label>
                        <select id="npcSelect" class="form-select bg-dark text-light border-info">
                            <option value="">-- Select NPC --</option>
                            <?php foreach ($sample_npcs as $npc): ?>
                                <option value="<?php echo htmlspecialchars($npc['id']); ?>" 
                                        data-name="<?php echo htmlspecialchars($npc['character_name']); ?>">
                                    <?php echo htmlspecialchars($npc['character_name']); ?> (<?php echo htmlspecialchars($npc['clan']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="triggerNPCFocusBtn" class="btn btn-info">
                            <i class="fas fa-user-check"></i> Focus NPC
                        </button>
                        <button id="triggerNPCFocusLostBtn" class="btn btn-outline-info">
                            <i class="fas fa-user-times"></i> Lose Focus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cinematic Intro & Combat Triggers -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-purple">
                <div class="card-header" style="background: #6f42c1; color: white;">
                    <h4 class="mb-0">🎬 Cinematic Intro Events</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-light mb-2">Character for Intro:</label>
                        <select id="introCharacterSelect" class="form-select bg-dark text-light" style="border-color: #6f42c1;">
                            <option value="">-- Select Character --</option>
                            <?php foreach ($sample_npcs as $npc): ?>
                                <option value="<?php echo htmlspecialchars($npc['id']); ?>">
                                    <?php echo htmlspecialchars($npc['character_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="triggerCinematicIntroStartBtn" class="btn text-white" style="background: #6f42c1; border-color: #6f42c1;">
                            <i class="fas fa-play"></i> Start Intro
                        </button>
                        <button id="triggerCinematicIntroEndBtn" class="btn btn-outline-secondary text-white" style="border-color: #6f42c1; color: #6f42c1;">
                            <i class="fas fa-stop"></i> End Intro
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="card bg-dark border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">⚔️ Combat Events</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button id="triggerCombatStartBtn" class="btn btn-danger">
                            <i class="fas fa-fist-raised"></i> Start Combat
                        </button>
                        <button id="triggerCombatEndBtn" class="btn btn-outline-danger">
                            <i class="fas fa-peace"></i> End Combat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Cues Display -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card bg-dark border-success">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">🎵 Active Cues & Transitions</h3>
                </div>
                <div class="card-body">
                    <div id="activeCuesDisplay" class="text-light">
                        <p class="opacity-75">No active cues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registry Info -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h3 class="mb-0">📚 Registry Information</h3>
                </div>
                <div class="card-body">
                    <div id="registryInfoDisplay" class="text-light">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                            <span>Loading registry info...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Debug Controls -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card bg-dark border-primary">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">⚙️ Debug Controls</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button id="toggleDebugModeBtn" class="btn btn-primary">
                            <i class="fas fa-bug"></i> Toggle Debug Mode
                        </button>
                        <button id="stopAllMusicBtn" class="btn btn-danger">
                            <i class="fas fa-stop"></i> Stop All Music
                        </button>
                        <button id="testFadeBtn" class="btn btn-warning">
                            <i class="fas fa-wave-square"></i> Test Fade
                        </button>
                        <button id="clearCacheBtn" class="btn btn-secondary">
                            <i class="fas fa-trash"></i> Clear Audio Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-purple {
    border-color: #6f42c1 !important;
}
.card {
    border-width: 2px;
}
#musicStateDisplay, #activeCuesDisplay, #registryInfoDisplay {
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    white-space: pre-wrap;
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 5px;
    min-height: 100px;
}
.state-item {
    margin: 5px 0;
    padding: 5px;
    background: rgba(139, 0, 0, 0.2);
    border-left: 3px solid #8B0000;
    padding-left: 10px;
}
.cue-active {
    color: #28a745;
    font-weight: bold;
}
.cue-inactive {
    color: #6c757d;
}
</style>

<!-- Music System -->
<script src="../js/modules/systems/MusicManager.js"></script>
<script src="../js/music_init.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for music manager to initialize
    const waitForMusicManager = setInterval(() => {
        if (window.musicManager) {
            clearInterval(waitForMusicManager);
            initializeDebugPage();
        }
    }, 100);
    
    function initializeDebugPage() {
        const mm = window.musicManager;
        
        // Enable debug mode
        mm.setDebugMode(true);
        
        // Refresh state display
        function refreshStateDisplay() {
            const debugInfo = mm.getDebugInfo();
            const stateHtml = `
<strong>Registry Status:</strong>
  • Loaded: ${debugInfo.registry.loaded ? '✅' : '❌'}
  • Assets: ${debugInfo.registry.assetsCount}
  • Cues: ${debugInfo.registry.cuesCount}
  • Bindings: ${debugInfo.registry.bindingsCount}

<strong>Mix Profile:</strong>
  • Active: ${debugInfo.mixProfile.active}
  • Gains: Location=${debugInfo.mixProfile.gains.location_underbed || 'N/A'}, Focus=${debugInfo.mixProfile.gains.focus_bed || 'N/A'}, Stinger=${debugInfo.mixProfile.gains.stinger || 'N/A'}

<strong>Current State:</strong>
  • Location: ${debugInfo.currentState.locationRef || 'None'}
  • Focus: ${debugInfo.currentState.focusRef || 'None'}
  • Combat: ${debugInfo.currentState.combatState ? 'Yes' : 'No'}

<strong>Audio Context:</strong>
  • State: ${debugInfo.audioContext.state}
  • Sample Rate: ${debugInfo.audioContext.sampleRate} Hz
            `.trim();
            
            document.getElementById('musicStateDisplay').innerHTML = stateHtml.replace(/\n/g, '<br>');
            
            // Active cues
            const cuesHtml = `
<strong>Active Cues:</strong>
  • Location Underbed: <span class="${debugInfo.activeCues.locationUnderbed ? 'cue-active' : 'cue-inactive'}">${debugInfo.activeCues.locationUnderbed || 'None'}</span>
  • Focus Bed: <span class="${debugInfo.activeCues.focusBed ? 'cue-active' : 'cue-inactive'}">${debugInfo.activeCues.focusBed || 'None'}</span>
  • Overlay: <span class="${debugInfo.activeCues.overlay ? 'cue-active' : 'cue-inactive'}">${debugInfo.activeCues.overlay || 'None'}</span>
            `.trim();
            
            document.getElementById('activeCuesDisplay').innerHTML = cuesHtml.replace(/\n/g, '<br>');
            
            // Registry info
            fetch('api_music_registry.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.registry) {
                        const reg = data.registry;
                        const regHtml = `
<strong>Registry Structure:</strong>
  • Schema Version: ${reg.schema?.version || 'N/A'}
  • Assets: ${reg.assets?.length || 0} entries
  • Cues: ${reg.cues?.length || 0} entries
  • Bindings: ${reg.bindings?.length || 0} entries
  • Mix Profiles: ${reg.mix_profiles?.length || 0} entries
                        `.trim();
                        document.getElementById('registryInfoDisplay').innerHTML = regHtml.replace(/\n/g, '<br>');
                    }
                });
        }
        
        // Event triggers
        document.getElementById('triggerLocationEnterBtn').addEventListener('click', () => {
            const select = document.getElementById('locationSelect');
            const locationId = select.value;
            const locationName = select.options[select.selectedIndex]?.dataset.name || locationId;
            if (locationId) {
                mm.handleLocationEnter(locationId);
                console.log(`[Debug] Triggered location enter: ${locationName} (${locationId})`);
                setTimeout(refreshStateDisplay, 500);
            }
        });
        
        document.getElementById('triggerLocationExitBtn').addEventListener('click', () => {
            mm.handleLocationExit();
            console.log('[Debug] Triggered location exit');
            setTimeout(refreshStateDisplay, 500);
        });
        
        document.getElementById('triggerNPCFocusBtn').addEventListener('click', () => {
            const select = document.getElementById('npcSelect');
            const npcId = select.value;
            const npcName = select.options[select.selectedIndex]?.dataset.name || npcId;
            if (npcId) {
                mm.handleNPCFocus(npcId);
                console.log(`[Debug] Triggered NPC focus: ${npcName} (${npcId})`);
                setTimeout(refreshStateDisplay, 500);
            }
        });
        
        document.getElementById('triggerNPCFocusLostBtn').addEventListener('click', () => {
            mm.handleNPCFocusLost();
            console.log('[Debug] Triggered NPC focus lost');
            setTimeout(refreshStateDisplay, 500);
        });
        
        document.getElementById('triggerCinematicIntroStartBtn').addEventListener('click', () => {
            const select = document.getElementById('introCharacterSelect');
            const charId = select.value;
            if (charId) {
                mm.handleCinematicIntroStart(charId);
                console.log(`[Debug] Triggered cinematic intro start: ${charId}`);
                setTimeout(refreshStateDisplay, 500);
            }
        });
        
        document.getElementById('triggerCinematicIntroEndBtn').addEventListener('click', () => {
            mm.handleCinematicIntroEnd();
            console.log('[Debug] Triggered cinematic intro end');
            setTimeout(refreshStateDisplay, 500);
        });
        
        document.getElementById('triggerCombatStartBtn').addEventListener('click', () => {
            mm.handleCombatStart();
            console.log('[Debug] Triggered combat start');
            setTimeout(refreshStateDisplay, 500);
        });
        
        document.getElementById('triggerCombatEndBtn').addEventListener('click', () => {
            mm.handleCombatEnd();
            console.log('[Debug] Triggered combat end');
            setTimeout(refreshStateDisplay, 500);
        });
        
        // Debug controls
        document.getElementById('refreshStateBtn').addEventListener('click', refreshStateDisplay);
        
        document.getElementById('toggleDebugModeBtn').addEventListener('click', () => {
            const current = mm.debugMode;
            mm.setDebugMode(!current);
            alert(`Debug mode ${!current ? 'enabled' : 'disabled'}`);
        });
        
        document.getElementById('stopAllMusicBtn').addEventListener('click', () => {
            mm.stopAllMusic('fade_out', 500);
            console.log('[Debug] Stopped all music');
            setTimeout(refreshStateDisplay, 1000);
        });
        
        document.getElementById('clearCacheBtn').addEventListener('click', () => {
            mm.audioCache.clear();
            console.log('[Debug] Cleared audio cache');
            alert('Audio cache cleared');
        });
        
        // Initial display
        refreshStateDisplay();
        
        // Auto-refresh every 2 seconds
        setInterval(refreshStateDisplay, 2000);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

