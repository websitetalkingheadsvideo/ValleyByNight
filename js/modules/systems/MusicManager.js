/**
 * MusicManager.js - Music playback engine for NPCs, locations, and cinematic intros
 * Manages leitmotifs, ambient music, fades, ducking, and priority-based playback
 */

class MusicManager {
    constructor(eventManager) {
        this.eventManager = eventManager;
        
        // Audio context and channels
        this.audioContext = null;
        this.mainChannel = null;  // MusicMain channel (foreground bed)
        this.overlayChannel = null;  // MusicOverlay channel (stingers, overrides)
        
        // Registry and state
        this.registry = null;
        this.mixProfile = null;
        this.currentState = {
            locationRef: null,
            focusRef: null,
            presenceRefs: [],
            combatState: false
        };
        
        // Active tracks
        this.activeCues = {
            locationUnderbed: null,
            focusBed: null,
            overlay: null
        };
        
        // Previous state for restore after exclusive override
        this.previousState = null;
        
        // Audio elements cache
        this.audioCache = new Map();
        
        // Fade and transition state
        this.fadeTimers = new Map();
        
        // Debug mode
        this.debugMode = false;
        
        this.init();
    }
    
    /**
     * Initialize the music manager
     */
    async init() {
        try {
            // Initialize Web Audio API
            this.initializeAudioContext();
            
            // Load music registry
            await this.loadRegistry();
            
            // Setup event listeners
            this.setupEventListeners();
            
            if (this.debugMode) {
                console.log('[MusicManager] Initialized successfully');
            }
        } catch (error) {
            console.error('[MusicManager] Initialization failed:', error);
        }
    }
    
    /**
     * Initialize Web Audio API context
     */
    initializeAudioContext() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                throw new Error('Web Audio API not supported');
            }
            
            this.audioContext = new AudioContextClass();
            
            // Create gain nodes for channels
            this.mainChannel = this.audioContext.createGain();
            this.overlayChannel = this.audioContext.createGain();
            
            // Connect to destination
            this.mainChannel.connect(this.audioContext.destination);
            this.overlayChannel.connect(this.audioContext.destination);
            
            // Set initial volumes
            this.mainChannel.gain.value = 0.78;  // Default focus_bed gain
            this.overlayChannel.gain.value = 1.0;  // Default stinger gain
            
            if (this.debugMode) {
                console.log('[MusicManager] Audio context initialized');
            }
        } catch (error) {
            console.error('[MusicManager] Failed to initialize audio context:', error);
            throw error;
        }
    }
    
    /**
     * Load music registry from API
     */
    async loadRegistry() {
        try {
            const response = await fetch('/admin/api_music_registry.php');
            
            if (!response.ok) {
                throw new Error(`Failed to load registry: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success || !data.registry) {
                throw new Error('Invalid registry response');
            }
            
            this.registry = data.registry;
            
            // Load active mix profile
            const profileRef = this.registry.runtime_contract?.mix_profile_active_ref || 'mix_default';
            this.mixProfile = this.findMixProfile(profileRef);
            
            if (!this.mixProfile) {
                console.warn('[MusicManager] Mix profile not found, using defaults');
                this.mixProfile = this.getDefaultMixProfile();
            }
            
            if (this.debugMode) {
                console.log('[MusicManager] Registry loaded:', {
                    assets: this.registry.assets?.length || 0,
                    cues: this.registry.cues?.length || 0,
                    bindings: this.registry.bindings?.length || 0,
                    mixProfile: profileRef
                });
            }
        } catch (error) {
            console.error('[MusicManager] Failed to load registry:', error);
            throw error;
        }
    }
    
    /**
     * Find mix profile by ID
     */
    findMixProfile(profileId) {
        if (!this.registry?.mix_profiles) {
            return null;
        }
        
        return this.registry.mix_profiles.find(profile => profile.mix_profile_id === profileId) || null;
    }
    
    /**
     * Get default mix profile
     */
    getDefaultMixProfile() {
        return {
            gains: {
                location_underbed: 0.12,
                focus_bed: 0.78,
                stinger: 1.0
            },
            ducking: {
                duck_beds_on_stinger: 0.35,
                duck_attack_ms: 40,
                duck_release_ms: 900
            },
            default_fades_ms: {
                fade_in_ms: 1400,
                fade_out_ms: 1400,
                crossfade_ms: 1400
            },
            underbed_behavior: {
                enter_fade_ms: 9000,
                exit_fade_ms: 9000,
                raise_when_no_focus_gain: 0.18,
                raise_fade_ms: 1200
            },
            exclusive_defaults: {
                stop_mode: 'fade_out',
                stop_fade_ms: 250,
                resume_mode: 'restore_previous_state'
            }
        };
    }
    
    /**
     * Setup event listeners for music triggers
     */
    setupEventListeners() {
        // Handle both EventManager events (modular system) and document CustomEvents (admin pages)
        
        // Location events
        const locationEnteredHandler = (event) => {
            const detail = event.detail || {};
            const locationId = detail.locationId || detail.id;
            if (locationId) {
                this.handleLocationEnter(locationId);
            }
        };
        
        const locationExitedHandler = () => {
            this.handleLocationExit();
        };
        
        // NPC focus events
        const npcFocusAcquiredHandler = (event) => {
            const detail = event.detail || {};
            const characterId = detail.characterId || detail.id;
            if (characterId) {
                this.handleNPCFocus(characterId);
            }
        };
        
        const npcFocusLostHandler = () => {
            this.handleNPCFocusLost();
        };
        
        // Cinematic intro events
        const cinematicIntroStartHandler = (event) => {
            const detail = event.detail || {};
            const characterId = detail.characterId || detail.id;
            this.handleCinematicIntroStart(characterId);
        };
        
        const cinematicIntroEndHandler = () => {
            this.handleCinematicIntroEnd();
        };
        
        // Combat events
        const combatStartHandler = () => {
            this.handleCombatStart();
        };
        
        const combatEndHandler = () => {
            this.handleCombatEnd();
        };
        
        // Add EventManager listeners if available
        if (this.eventManager) {
            if (typeof this.eventManager.onCustomEvent === 'function') {
                this.eventManager.onCustomEvent('locationEntered', locationEnteredHandler);
                this.eventManager.onCustomEvent('locationExited', locationExitedHandler);
                this.eventManager.onCustomEvent('npcFocusAcquired', npcFocusAcquiredHandler);
                this.eventManager.onCustomEvent('npcFocusLost', npcFocusLostHandler);
                this.eventManager.onCustomEvent('cinematicIntroStart', cinematicIntroStartHandler);
                this.eventManager.onCustomEvent('cinematicIntroEnd', cinematicIntroEndHandler);
                this.eventManager.onCustomEvent('combatStart', combatStartHandler);
                this.eventManager.onCustomEvent('combatEnd', combatEndHandler);
            }
        }
        
        // Always add document-level listeners for admin pages and standalone usage
        document.addEventListener('locationEntered', locationEnteredHandler);
        document.addEventListener('locationExited', locationExitedHandler);
        document.addEventListener('npcFocusAcquired', npcFocusAcquiredHandler);
        document.addEventListener('npcFocusLost', npcFocusLostHandler);
        document.addEventListener('cinematicIntroStart', cinematicIntroStartHandler);
        document.addEventListener('cinematicIntroEnd', cinematicIntroEndHandler);
        document.addEventListener('combatStart', combatStartHandler);
        document.addEventListener('combatEnd', combatEndHandler);
        
        // Add direct method handlers for direct calls
        this.handleLocationEnter = this.handleLocationEnter.bind(this);
        this.handleLocationExit = this.handleLocationExit.bind(this);
        this.handleNPCFocus = this.handleNPCFocus.bind(this);
        this.handleNPCFocusLost = this.handleNPCFocusLost.bind(this);
        this.handleCinematicIntroStart = this.handleCinematicIntroStart.bind(this);
        this.handleCinematicIntroEnd = this.handleCinematicIntroEnd.bind(this);
        this.handleCombatStart = this.handleCombatStart.bind(this);
        this.handleCombatEnd = this.handleCombatEnd.bind(this);
        
        if (this.debugMode) {
            console.log('[MusicManager] Event listeners setup complete');
        }
    }
    
    /**
     * Handle location enter event
     */
    handleLocationEnter(locationId) {
        if (this.debugMode) {
            console.log('[MusicManager] Location entered:', locationId);
        }
        
        this.currentState.locationRef = `location:${locationId}`;
        
        // Find bindings for this location
        const bindings = this.findBindings('on_location_enter', 'location', locationId);
        
        if (bindings.length > 0) {
            // Use highest priority binding
            const binding = bindings.sort((a, b) => (b.priority || 0) - (a.priority || 0))[0];
            const cue = this.findCue(binding.play_cue_ref);
            
            if (cue && cue.role === 'location_underbed') {
                this.playLocationUnderbed(cue, binding);
            }
        }
    }
    
    /**
     * Handle location exit event
     */
    handleLocationExit() {
        if (this.debugMode) {
            console.log('[MusicManager] Location exited');
        }
        
        // Fade out location underbed
        if (this.activeCues.locationUnderbed) {
            this.stopCue(this.activeCues.locationUnderbed, this.mixProfile?.underbed_behavior?.exit_fade_ms || 9000);
            this.activeCues.locationUnderbed = null;
        }
        
        this.currentState.locationRef = null;
    }
    
    /**
     * Handle NPC focus acquired event
     */
    handleNPCFocus(characterId) {
        if (this.debugMode) {
            console.log('[MusicManager] NPC focus acquired:', characterId);
        }
        
        this.currentState.focusRef = `character:${characterId}`;
        
        // Find bindings for this NPC
        const bindings = this.findBindings('on_focus_acquired', 'character', characterId);
        
        if (bindings.length > 0) {
            const binding = bindings.sort((a, b) => (b.priority || 0) - (a.priority || 0))[0];
            const cue = this.findCue(binding.play_cue_ref);
            
            if (cue && cue.role === 'focus_bed') {
                this.playFocusBed(cue, binding);
            }
        }
    }
    
    /**
     * Handle NPC focus lost event
     */
    handleNPCFocusLost() {
        if (this.debugMode) {
            console.log('[MusicManager] NPC focus lost');
        }
        
        // Fade out focus bed, return to location ambient only
        if (this.activeCues.focusBed) {
            this.stopCue(this.activeCues.focusBed, this.mixProfile?.default_fades_ms?.fade_out_ms || 1400);
            this.activeCues.focusBed = null;
        }
        
        this.currentState.focusRef = null;
    }
    
    /**
     * Handle cinematic intro start
     */
    handleCinematicIntroStart(characterId) {
        if (this.debugMode) {
            console.log('[MusicManager] Cinematic intro start:', characterId);
        }
        
        // Store current state for restore after intro
        this.previousState = {
            locationRef: this.currentState.locationRef,
            focusRef: this.currentState.focusRef,
            locationUnderbed: this.activeCues.locationUnderbed,
            focusBed: this.activeCues.focusBed
        };
        
        // Find cinematic intro binding
        const bindings = this.findBindings('on_event', null, null, 'cinematic_intro_start');
        
        if (bindings.length > 0) {
            const binding = bindings[0];  // Should only be one
            const cue = this.findCue(binding.play_cue_ref);
            
            if (cue && cue.override?.mode === 'exclusive') {
                // Stop all other music
                this.stopAllMusic(cue.override.exclusive_stop_mode || 'fade_out', 
                                 cue.override.exclusive_stop_fade_ms || 300);
                
                // Play intro cue
                this.playOverlayCue(cue, binding);
            }
        }
    }
    
    /**
     * Handle cinematic intro end
     */
    handleCinematicIntroEnd() {
        if (this.debugMode) {
            console.log('[MusicManager] Cinematic intro end');
        }
        
        // Find the intro cue that just finished
        const introCue = this.activeCues.overlay;
        
        if (introCue && introCue.handoff) {
            // Execute handoff
            this.executeHandoff(introCue);
        } else {
            // Default: restore previous state
            this.restorePreviousState();
        }
        
        // Stop intro cue
        if (this.activeCues.overlay) {
            this.stopCue(this.activeCues.overlay, introCue?.fade_out_ms || 2000);
            this.activeCues.overlay = null;
        }
    }
    
    /**
     * Handle combat start
     */
    handleCombatStart() {
        this.currentState.combatState = true;
        // Combat handling would go here - similar to cinematic intro
    }
    
    /**
     * Handle combat end
     */
    handleCombatEnd() {
        this.currentState.combatState = false;
        // Restore previous state
    }
    
    /**
     * Find bindings matching criteria
     */
    findBindings(bindingType, targetType = null, targetId = null, eventKey = null) {
        if (!this.registry?.bindings) {
            return [];
        }
        
        return this.registry.bindings.filter(binding => {
            // Match binding type
            if (binding.binding_type !== bindingType) {
                return false;
            }
            
            // Match target type and ID
            if (targetType && targetId) {
                if (!binding.target_ref || 
                    binding.target_ref.type !== targetType || 
                    binding.target_ref.id !== targetId) {
                    return false;
                }
            }
            
            // Match event key
            if (eventKey) {
                if (!binding.event || binding.event.event_key !== eventKey) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Find cue by ID
     */
    findCue(cueId) {
        if (!this.registry?.cues) {
            return null;
        }
        
        return this.registry.cues.find(cue => cue.cue_id === cueId) || null;
    }
    
    /**
     * Find asset by ID
     */
    findAsset(assetId) {
        if (!this.registry?.assets) {
            return null;
        }
        
        return this.registry.assets.find(asset => asset.asset_id === assetId) || null;
    }
    
    /**
     * Play location underbed cue
     */
    async playLocationUnderbed(cue, binding) {
        if (this.debugMode) {
            console.log('[MusicManager] Playing location underbed:', cue.cue_id);
        }
        
        // Stop existing location underbed if any
        if (this.activeCues.locationUnderbed) {
            this.stopCue(this.activeCues.locationUnderbed, 
                        this.mixProfile?.underbed_behavior?.exit_fade_ms || 9000);
        }
        
        // Get asset and file
        const asset = this.findAsset(cue.asset_ref);
        if (!asset || !asset.files || asset.files.length === 0) {
            console.warn('[MusicManager] Asset not found or has no files:', cue.asset_ref);
            return;
        }
        
        const audioFile = asset.files[0];
        const audioPath = `/${audioFile.path}`;
        
        // Load and play audio
        const audio = await this.loadAudio(audioPath);
        if (!audio) return;
        
        // Create source node
        const source = this.audioContext.createMediaElementSource(audio);
        source.connect(this.mainChannel);
        
        // Set gain based on mix profile
        const gain = this.mixProfile?.gains?.location_underbed || 0.12;
        this.mainChannel.gain.value = gain;
        
        // Configure loop
        audio.loop = cue.loop || false;
        
        // Store active cue info
        this.activeCues.locationUnderbed = {
            cue: cue,
            binding: binding,
            audio: audio,
            source: source
        };
        
        // Fade in
        const fadeInMs = cue.fade_in_ms || this.mixProfile?.underbed_behavior?.enter_fade_ms || 9000;
        this.fadeIn(audio, fadeInMs);
        
        // Play
        audio.play().catch(error => {
            console.error('[MusicManager] Failed to play location underbed:', error);
        });
    }
    
    /**
     * Play focus bed cue (NPC leitmotif)
     */
    async playFocusBed(cue, binding) {
        if (this.debugMode) {
            console.log('[MusicManager] Playing focus bed:', cue.cue_id);
        }
        
        // Stop existing focus bed if any
        if (this.activeCues.focusBed) {
            this.stopCue(this.activeCues.focusBed, 
                        this.mixProfile?.default_fades_ms?.fade_out_ms || 1400);
        }
        
        // Get asset and file
        const asset = this.findAsset(cue.asset_ref);
        if (!asset || !asset.files || asset.files.length === 0) {
            console.warn('[MusicManager] Asset not found or has no files:', cue.asset_ref);
            return;
        }
        
        const audioFile = asset.files[0];
        const audioPath = `/${audioFile.path}`;
        
        // Load and play audio
        const audio = await this.loadAudio(audioPath);
        if (!audio) return;
        
        // Create source node (will replace location underbed on main channel for crossfade)
        const source = this.audioContext.createMediaElementSource(audio);
        source.connect(this.mainChannel);
        
        // Set gain based on mix profile
        const gain = this.mixProfile?.gains?.focus_bed || 0.78;
        this.mainChannel.gain.value = gain;
        
        // Configure loop
        audio.loop = cue.loop || false;
        
        // Store active cue info
        this.activeCues.focusBed = {
            cue: cue,
            binding: binding,
            audio: audio,
            source: source
        };
        
        // Crossfade in (while location underbed continues at lower volume)
        const fadeInMs = cue.fade_in_ms || this.mixProfile?.default_fades_ms?.crossfade_ms || 1400;
        this.fadeIn(audio, fadeInMs);
        
        // Play
        audio.play().catch(error => {
            console.error('[MusicManager] Failed to play focus bed:', error);
        });
    }
    
    /**
     * Play overlay cue (stingers, overrides)
     */
    async playOverlayCue(cue, binding) {
        if (this.debugMode) {
            console.log('[MusicManager] Playing overlay cue:', cue.cue_id);
        }
        
        // Duck main channel if configured
        if (this.mixProfile?.ducking?.duck_beds_on_stinger) {
            this.duckMainChannel(true);
        }
        
        // Get asset and file
        const asset = this.findAsset(cue.asset_ref);
        if (!asset || !asset.files || asset.files.length === 0) {
            console.warn('[MusicManager] Asset not found or has no files:', cue.asset_ref);
            return;
        }
        
        const audioFile = asset.files[0];
        const audioPath = `/${audioFile.path}`;
        
        // Load and play audio
        const audio = await this.loadAudio(audioPath);
        if (!audio) return;
        
        // Create source node on overlay channel
        const source = this.audioContext.createMediaElementSource(audio);
        source.connect(this.overlayChannel);
        
        // Set gain
        const gain = this.mixProfile?.gains?.stinger || 1.0;
        this.overlayChannel.gain.value = gain;
        
        // Configure loop
        audio.loop = cue.loop || false;
        
        // Store active cue info
        this.activeCues.overlay = {
            cue: cue,
            binding: binding,
            audio: audio,
            source: source
        };
        
        // Fade in
        const fadeInMs = cue.fade_in_ms || 50;
        this.fadeIn(audio, fadeInMs);
        
        // Play
        audio.play().catch(error => {
            console.error('[MusicManager] Failed to play overlay cue:', error);
        });
        
        // If not looping, handle end
        if (!audio.loop) {
            audio.addEventListener('ended', () => {
                if (this.mixProfile?.ducking?.duck_beds_on_stinger) {
                    this.duckMainChannel(false);
                }
                
                // Handle handoff or restore
                if (cue.handoff) {
                    this.executeHandoff(cue);
                } else if (cue.override?.exclusive_resume_mode === 'restore_previous_state') {
                    this.restorePreviousState();
                }
                
                // Clean up
                this.activeCues.overlay = null;
                source.disconnect();
            }, { once: true });
        }
    }
    
    /**
     * Load audio file (with caching)
     */
    async loadAudio(path) {
        // Check cache
        if (this.audioCache.has(path)) {
            const cached = this.audioCache.get(path);
            // Clone audio element for multiple playback
            const audio = cached.cloneNode();
            return audio;
        }
        
        try {
            const audio = new Audio(path);
            
            // Preload
            audio.preload = 'auto';
            
            // Cache original
            this.audioCache.set(path, audio);
            
            return audio;
        } catch (error) {
            console.error('[MusicManager] Failed to load audio:', path, error);
            return null;
        }
    }
    
    /**
     * Stop all music
     */
    stopAllMusic(stopMode = 'fade_out', fadeMs = 300) {
        if (this.activeCues.locationUnderbed) {
            this.stopCue(this.activeCues.locationUnderbed, fadeMs);
        }
        
        if (this.activeCues.focusBed) {
            this.stopCue(this.activeCues.focusBed, fadeMs);
        }
        
        if (this.activeCues.overlay) {
            this.stopCue(this.activeCues.overlay, fadeMs);
        }
        
        // Clear active cues after fade
        setTimeout(() => {
            this.activeCues.locationUnderbed = null;
            this.activeCues.focusBed = null;
            this.activeCues.overlay = null;
        }, fadeMs);
    }
    
    /**
     * Stop a specific cue
     */
    stopCue(cueData, fadeMs = 0) {
        if (!cueData || !cueData.audio) return;
        
        const audio = cueData.audio;
        
        if (fadeMs > 0) {
            this.fadeOut(audio, fadeMs);
            setTimeout(() => {
                audio.pause();
                audio.currentTime = 0;
                if (cueData.source) {
                    cueData.source.disconnect();
                }
            }, fadeMs);
        } else {
            audio.pause();
            audio.currentTime = 0;
            if (cueData.source) {
                cueData.source.disconnect();
            }
        }
    }
    
    /**
     * Fade in audio
     */
    fadeIn(audio, durationMs) {
        audio.volume = 0;
        
        const steps = 20;
        const stepDuration = durationMs / steps;
        const volumeStep = 1 / steps;
        
        let currentStep = 0;
        
        const fadeTimer = setInterval(() => {
            currentStep++;
            audio.volume = Math.min(1, currentStep * volumeStep);
            
            if (currentStep >= steps || audio.volume >= 1) {
                audio.volume = 1;
                clearInterval(fadeTimer);
            }
        }, stepDuration);
        
        this.fadeTimers.set(audio, fadeTimer);
    }
    
    /**
     * Fade out audio
     */
    fadeOut(audio, durationMs) {
        const startVolume = audio.volume;
        
        const steps = 20;
        const stepDuration = durationMs / steps;
        const volumeStep = startVolume / steps;
        
        let currentStep = 0;
        
        const fadeTimer = setInterval(() => {
            currentStep++;
            audio.volume = Math.max(0, startVolume - (currentStep * volumeStep));
            
            if (currentStep >= steps || audio.volume <= 0) {
                audio.volume = 0;
                clearInterval(fadeTimer);
                this.fadeTimers.delete(audio);
            }
        }, stepDuration);
        
        const existingTimer = this.fadeTimers.get(audio);
        if (existingTimer) {
            clearInterval(existingTimer);
        }
        
        this.fadeTimers.set(audio, fadeTimer);
    }
    
    /**
     * Duck main channel (for stingers)
     */
    duckMainChannel(duck) {
        const duckAmount = this.mixProfile?.ducking?.duck_beds_on_stinger || 0.35;
        const targetGain = duck ? duckAmount : (this.mixProfile?.gains?.location_underbed || 0.12);
        const durationMs = duck ? 
            (this.mixProfile?.ducking?.duck_attack_ms || 40) : 
            (this.mixProfile?.ducking?.duck_release_ms || 900);
        
        // Smooth transition
        this.mainChannel.gain.setTargetAtTime(
            targetGain, 
            this.audioContext.currentTime, 
            durationMs / 1000
        );
    }
    
    /**
     * Execute handoff after exclusive override
     */
    executeHandoff(cue) {
        if (!cue.handoff) return;
        
        if (this.debugMode) {
            console.log('[MusicManager] Executing handoff:', cue.handoff);
        }
        
        const handoff = cue.handoff;
        
        if (handoff.after_play === 'restore_location_or_focus') {
            this.restorePreviousState();
        } else if (handoff.after_play === 'enter_combat_state') {
            // Handle combat state transition
            this.currentState.combatState = true;
            // Additional combat music logic would go here
        }
    }
    
    /**
     * Restore previous state after exclusive override
     */
    restorePreviousState() {
        if (!this.previousState) return;
        
        if (this.debugMode) {
            console.log('[MusicManager] Restoring previous state:', this.previousState);
        }
        
        // Restore location
        if (this.previousState.locationRef) {
            const locationId = this.previousState.locationRef.split(':')[1];
            this.handleLocationEnter(locationId);
        }
        
        // Restore focus
        if (this.previousState.focusRef) {
            const characterId = this.previousState.focusRef.split(':')[1];
            this.handleNPCFocus(characterId);
        }
        
        this.previousState = null;
    }
    
    /**
     * Get debug information
     */
    getDebugInfo() {
        return {
            registry: {
                loaded: !!this.registry,
                assetsCount: this.registry?.assets?.length || 0,
                cuesCount: this.registry?.cues?.length || 0,
                bindingsCount: this.registry?.bindings?.length || 0
            },
            mixProfile: {
                active: this.mixProfile?.mix_profile_id || 'default',
                gains: this.mixProfile?.gains || {}
            },
            currentState: {
                ...this.currentState
            },
            activeCues: {
                locationUnderbed: this.activeCues.locationUnderbed?.cue?.cue_id || null,
                focusBed: this.activeCues.focusBed?.cue?.cue_id || null,
                overlay: this.activeCues.overlay?.cue?.cue_id || null
            },
            audioContext: {
                state: this.audioContext?.state || 'not initialized',
                sampleRate: this.audioContext?.sampleRate || 0
            }
        };
    }
    
    /**
     * Enable/disable debug mode
     */
    setDebugMode(enabled) {
        this.debugMode = enabled;
    }
}

