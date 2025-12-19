# TM-09: Music System for NPCs & Locations — Implementation Plan

## Repository Analysis Summary

### Existing Infrastructure
1. **Music Registry**: `assets/music/music_registry.json` exists with:
   - Asset registry (music files with metadata)
   - Cue definitions (playback configurations)
   - Bindings (when/where music triggers)
   - Mix profiles (gain, ducking, fades)
   - Runtime contract specifications

2. **NPC System**: 
   - NPCs stored in `characters` table (`player_name = 'NPC'`)
   - Admin interface: `admin/admin_npc_briefing.php`
   - JavaScript: `js/admin_npc_briefing.js` handles modal interactions

3. **Location System**:
   - Locations in `locations` table with comprehensive fields
   - Admin interface: `admin/admin_locations.php`
   - JavaScript: `js/admin_locations.js` handles location interactions

4. **Cinematic Intros**:
   - Markdown files in `reference/Scenes/Character Teasers/`
   - Guide: `reference/Scenes/Character Teasers/Valley_by_Night_Cinematic_Intro_Guide.md`
   - No JavaScript integration yet

5. **JavaScript Architecture**:
   - Modular system: `js/modules/` with core/system/ui modules
   - Event system: `EventManager` class for custom events
   - State management: `StateManager` class
   - Main app: `js/modules/main.js` initializes modules

6. **Audio Assets**:
   - Directory structure: `assets/music/characters/`, `assets/music/locations/`, `assets/music/events/`
   - One existing asset: `assets/music/locations/_shared/Dark Ambient Gotham.mp3`

---

## Implementation Plan

### Phase 1: Registry Enhancement & Data Model

#### Task 1.1: Extend Music Registry with NPC & Location Bindings
**Files to Modify:**
- `assets/music/music_registry.json`

**Action:**
- Add example bindings for NPCs (`on_focus_acquired` with `target_ref.type = "character"`)
- Add example bindings for locations (`on_location_enter` already exists, add more examples)
- Add example bindings for cinematic intros (`on_event` with `event_key = "cinematic_intro_start"`)
- Document the binding system in registry comments

**Acceptance Criteria:**
- Registry has at least 3 example NPC bindings
- Registry has at least 3 example location bindings
- Registry has 1 example cinematic intro binding with handoff

---

#### Task 1.2: Create PHP API Endpoints for Music Registry Access
**Files to Create:**
- `admin/api_music_registry.php` - GET endpoint to fetch full registry
- `admin/api_music_bindings.php` - GET/POST endpoints for bindings CRUD

**Files to Modify:**
- `includes/connect.php` (verify DB connection pattern)

**Action:**
- Create GET endpoint that returns `music_registry.json` as JSON
- Create GET endpoint that returns filtered bindings (by NPC ID, location ID, etc.)
- Create POST endpoint to update bindings (for admin UI later)
- Add proper error handling and JSON responses

**Acceptance Criteria:**
- `GET /admin/api_music_registry.php` returns full registry
- `GET /admin/api_music_bindings.php?type=npc&id=123` returns NPC bindings
- `GET /admin/api_music_bindings.php?type=location&id=456` returns location bindings

---

### Phase 2: JavaScript Playback Engine

#### Task 2.1: Create MusicManager Core Module
**Files to Create:**
- `js/modules/systems/MusicManager.js`

**Action:**
- Implement core `MusicManager` class with:
  - Audio context initialization (Web Audio API)
  - Track loading and playback management
  - Channel system (`MusicMain`, `MusicOverlay`)
  - State tracking (current location, focus NPC, active cues)
  - Rule evaluation (precedence: cinematic > situation > npc > location)
- Implement fade system (crossfade, fade-in, fade-out)
- Implement ducking system (volume reduction on stinger/override)
- Load music registry on initialization

**Dependencies:**
- Use existing `EventManager` for events
- Fetch registry from `admin/api_music_registry.php`

**Acceptance Criteria:**
- MusicManager can load and play music from registry
- Fades work correctly (crossfade, fade-in, fade-out)
- Ducking reduces volume when stinger plays
- State machine tracks location/focus/events

---

#### Task 2.2: Implement Override & Priority System
**Files to Modify:**
- `js/modules/systems/MusicManager.js`

**Action:**
- Implement precedence rules:
  - Cinematic intro overrides everything (exclusive mode)
  - Situations override NPC/location (based on priority)
  - NPC leitmotif may override location (configurable via binding priority)
- Implement exclusive override handling:
  - Stop/fade-out other music per `exclusive_stop_mode`
  - Resume previous state per `exclusive_resume_mode`
- Implement priority-based selection when multiple bindings match

**Acceptance Criteria:**
- Cinematic intro stops all other music
- Higher priority bindings override lower ones
- Exclusive mode properly stops/resumes music
- Priority comparison works correctly

---

#### Task 2.3: Implement Handoff System for Cinematic Intros
**Files to Modify:**
- `js/modules/systems/MusicManager.js`

**Action:**
- Implement handoff logic:
  - When cinematic intro ends, check `handoff.after_play` field
  - Transition to next context track (location ambient or NPC leitmotif)
  - Use smooth crossfade per mix profile settings
- Store previous state before exclusive override
- Restore or transition based on `handoff` configuration

**Acceptance Criteria:**
- Cinematic intro plays and stops correctly
- Handoff transitions smoothly to location/NPC music
- Previous state is restored when handoff specifies

---

### Phase 3: Integration Points

#### Task 3.1: Integrate with NPC Interaction System
**Files to Modify:**
- `js/admin_npc_briefing.js`
- `js/modules/systems/MusicManager.js`

**Action:**
- Add event listener in `admin_npc_briefing.js` when NPC modal opens:
  - Emit `npcFocusAcquired` event with NPC ID
- Add handler in `MusicManager`:
  - Listen for `npcFocusAcquired` event
  - Lookup bindings with `binding_type = "on_focus_acquired"` and matching NPC ID
  - Apply NPC leitmotif if binding exists (with priority/crossfade)
- Add event listener when NPC modal closes:
  - Emit `npcFocusLost` event
  - MusicManager returns to location ambient

**Acceptance Criteria:**
- Opening NPC briefing modal triggers NPC leitmotif (if configured)
- Closing modal returns to location ambient
- Crossfade transitions work smoothly

---

#### Task 3.2: Integrate with Location System
**Files to Modify:**
- `js/admin_locations.js`
- `js/modules/systems/MusicManager.js`

**Action:**
- Add event listener when location is viewed/entered:
  - Emit `locationEntered` event with location ID
- Add handler in `MusicManager`:
  - Listen for `locationEntered` event
  - Lookup bindings with `binding_type = "on_location_enter"` and matching location ID
  - Apply location ambient as underbed (with long fade-in per registry settings)
- Ensure location ambient persists while in location
- Handle location exit (emit `locationExited`, fade-out location ambient)

**Acceptance Criteria:**
- Viewing/entering location triggers location ambient (if configured)
- Location ambient plays as persistent underbed
- Location exit fades out ambient correctly

---

#### Task 3.3: Integrate with Cinematic Intro System
**Files to Create:**
- `js/cinematic_intro.js` (new file for cinematic intro player)

**Files to Modify:**
- `js/modules/systems/MusicManager.js`

**Action:**
- Create `CinematicIntroPlayer` class that:
  - Loads markdown cinematic intro files
  - Displays intro content in modal/fullscreen
  - Emits events: `cinematicIntroStart`, `cinematicIntroEnd`
- Integrate with `MusicManager`:
  - Listen for `cinematicIntroStart` event
  - Lookup override cue for cinematic intro
  - Apply exclusive override (stop other music)
  - On `cinematicIntroEnd`, execute handoff logic
- Add UI trigger: button/link in character teaser pages to play intro

**Acceptance Criteria:**
- Cinematic intro can be triggered from character teaser pages
- Intro music overrides all other music
- Handoff to location/NPC music after intro ends
- Smooth transitions throughout

---

#### Task 3.4: Initialize MusicManager in Main App
**Files to Modify:**
- `js/modules/main.js`

**Action:**
- Add MusicManager to system modules initialization
- Initialize MusicManager after EventManager and StateManager
- Pass EventManager reference to MusicManager
- Ensure MusicManager loads registry on app initialization
- Set default location (if available from URL/state)

**Acceptance Criteria:**
- MusicManager initializes on page load
- Registry loads successfully
- MusicManager listens to events correctly

---

### Phase 4: Documentation & Tooling

#### Task 4.1: Create Music System Documentation
**Files to Create:**
- `docs/MUSIC_SYSTEM.md`

**Content:**
- Registry schema explanation
- Override precedence rules (truth table)
- Fade/crossfade behavior explanation
- How to add new NPC/location music (step-by-step)
- How to test the system locally
- Integration examples
- Troubleshooting guide

**Acceptance Criteria:**
- Documentation explains all registry fields
- Precedence rules are clear with examples
- Step-by-step guide for adding music
- Testing instructions included

---

#### Task 4.2: Create Envato Elements AI Search Helper Tool
**Files to Create:**
- `scripts/envato_music_prompts.js` (Node.js script)
- `docs/ENVATO_MUSIC_PROMPTS.md` (documentation)

**Action:**
- Create Node.js script that:
  - Generates prompt templates for:
    - Character Leitmotif
    - Location Ambient
    - Situation Cue
  - Accepts parameters (character name, location name, mood, etc.)
  - Opens Envato Elements AI search in browser
  - Copies prompt to clipboard
- Create documentation with:
  - Prompt template examples
  - Tag checklist (loopable, no vocals, stems, etc.)
  - Usage instructions
  - Link to Envato Elements

**Alternative (if Node.js not available):**
- Create PHP page: `admin/envato_music_helper.php`
- Provides form to generate prompts
- Opens Envato search in new window
- Copies prompt to clipboard via JavaScript

**Acceptance Criteria:**
- Tool generates valid Envato AI search prompts
- Opens Envato Elements in browser
- Prompts are copyable
- Documentation is complete

---

#### Task 4.3: Populate Registry with Initial Entries
**Files to Modify:**
- `assets/music/music_registry.json`

**Action:**
- Add at least 3 NPC music entries (for major NPCs)
- Add at least 3 location music entries (for major locations)
- Add 1 cinematic intro override cue
- Add corresponding bindings
- Ensure all entries follow registry schema

**Acceptance Criteria:**
- Registry has real NPC bindings
- Registry has real location bindings
- Registry has cinematic intro binding
- All entries are valid JSON and follow schema

---

### Phase 5: Testing & Debug Tools

#### Task 5.1: Create Debug UI for Music System
**Files to Create:**
- `admin/music_debug.php` (admin page)

**Files to Modify:**
- `js/modules/systems/MusicManager.js` (add debug methods)

**Action:**
- Create admin page that displays:
  - Current resolved music context
  - Active track information
  - Next transition info
  - Fade state and remaining time
  - Active bindings
  - Channel states (Main, Overlay)
- Add debug methods to MusicManager:
  - `getDebugInfo()` - returns current state
  - `getActiveCues()` - returns active cues
  - `getPendingTransitions()` - returns queued transitions
- Add manual trigger buttons:
  - Test NPC focus
  - Test location enter
  - Test cinematic intro
  - Force fade/crossfade

**Acceptance Criteria:**
- Debug page shows current music state
- Manual triggers work correctly
- All debug info is accurate

---

#### Task 5.2: Add Console Logging & Error Handling
**Files to Modify:**
- `js/modules/systems/MusicManager.js`

**Action:**
- Add comprehensive console logging:
  - Track loading
  - Cue resolution
  - Fade transitions
  - Override events
- Add error handling:
  - Failed audio file loads
  - Invalid registry data
  - Missing bindings
  - Web Audio API errors
- Add warning messages for:
  - Missing music files
  - Bindings without assets
  - Conflicting priorities

**Acceptance Criteria:**
- All operations log appropriately
- Errors are caught and logged
- Warnings help diagnose issues

---

## Implementation Order

1. **Phase 1** (Registry & API) - Foundation
2. **Phase 2** (Playback Engine) - Core functionality
3. **Phase 3** (Integration) - Connect to existing systems
4. **Phase 4** (Documentation & Tooling) - Polish and usability
5. **Phase 5** (Testing & Debug) - Quality assurance

## Technical Notes

### Web Audio API Considerations
- Use `AudioContext` for playback
- Support multiple audio elements for crossfading
- Handle browser autoplay policies (may require user interaction)
- Consider audio format compatibility (MP3, OGG)

### Performance Considerations
- Lazy load music files (only when needed)
- Preload next track during crossfade
- Limit concurrent audio channels
- Cache loaded audio buffers

### Browser Compatibility
- Test in Chrome, Firefox, Safari, Edge
- Handle autoplay restrictions gracefully
- Provide fallback for unsupported browsers

---

## Acceptance Criteria Summary

✅ Music can be assigned to major NPCs and locations (registry populated)
✅ Fade and override rules are implemented and demonstrably used
✅ System is integrated with cinematic intros (intro triggers correct override + transition)
✅ Music registry populated with initial entries and clear structure
✅ Playback rules documented in repo docs
✅ Envato Elements AI Search helper tool created
✅ Debug tools available for testing

---

## Estimated Complexity

- **Phase 1**: Low-Medium (JSON + PHP APIs)
- **Phase 2**: High (Complex audio state machine)
- **Phase 3**: Medium (Event integration)
- **Phase 4**: Low (Documentation)
- **Phase 5**: Low-Medium (Debug UI)

**Total Estimated Time**: 15-20 hours

