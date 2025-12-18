# Plan to Fix Broken Links

## Overview
This plan addresses all broken links identified in `plan/broken_links.md`. Each issue will be investigated, fixed, or rerouted to existing equivalents.

## Task Breakdown

### Task 1: Verify/Fix logout.php Link
**Priority:** High  
**File:** `includes/header.php:120`

**Status:** `logout.php` exists in root directory (verified in file listing)

**Action:**
- Verify `logout.php` contains proper logout logic
- If missing functionality, implement session destruction and redirect
- Test logout flow

**Files to Check:**
- `logout.php` (root)
- `includes/header.php:120`

---

### Task 2: Fix character_sheet.php Missing File
**Priority:** High  
**Files:** `index.php:268`, `js/questionnaire.js:509`

**Investigation:**
- Check if character viewing is handled via modals (found `includes/character_view_modal.php`)
- Check if `admin/view_character_api.php` is the correct endpoint
- Determine if a standalone page is needed or if links should point to modal trigger

**Action:**
- Option A: Create `character_sheet.php` as standalone character viewing page
- Option B: Update links to use existing modal system or `admin/view_character_api.php`
- Update `index.php:268` and `js/questionnaire.js:509`

**Files to Modify:**
- `index.php:268`
- `js/questionnaire.js:509`
- Possibly create `character_sheet.php`

---

### Task 3: Fix Missing css/header.css
**Priority:** Medium  
**File:** `agents/laws_agent/index.php:43`

**Action:**
- Check if `css/header.css` should exist or if another stylesheet should be used
- Review existing CSS files in `css/` directory
- Either create `css/header.css` or update reference to existing stylesheet

**Files to Check:**
- `css/` directory contents
- `agents/laws_agent/index.php:43`

---

### Task 4: Fix Missing Admin Items API Endpoints
**Priority:** High  
**File:** `js/admin_items.js`

**Missing Endpoints:**
- `api_items.php` (line 67)
- `api_admin_items_crud.php` (lines 278, 572, 595)
- `api_admin_add_equipment.php` (line 511)

**Investigation:**
- Check if similar endpoints exist (e.g., `admin/api_admin_equipment_crud.php`)
- Review `js/admin_items.js` to understand expected API contract
- Check `admin/admin_items.php` for clues about expected endpoints

**Action:**
- Create missing endpoints or update JavaScript to use existing equivalents
- Ensure API contracts match JavaScript expectations

**Files to Check:**
- `js/admin_items.js`
- `admin/admin_items.php`
- `admin/api_admin_equipment_crud.php` (may be equivalent)

---

### Task 5: Fix Missing Admin Locations API Endpoints
**Priority:** High  
**File:** `js/admin_locations.js`

**Missing Endpoints:**
- `api_admin_location_assignments.php` (lines 536, 692, 815)
- `api_delete_location_simple.php` (line 842)

**Investigation:**
- Check if `admin/api_admin_locations_crud.php` handles similar functionality
- Review `js/admin_locations.js` to understand expected API contract
- Check `admin/admin_locations.php` for clues

**Action:**
- Create missing endpoints or update JavaScript to use existing equivalents
- Ensure API contracts match JavaScript expectations

**Files to Check:**
- `js/admin_locations.js`
- `admin/admin_locations.php`
- `admin/api_admin_locations_crud.php` (may be equivalent)

---

### Task 6: Fix Missing NPC Notes API Endpoint
**Priority:** Medium  
**File:** `js/admin_npc_briefing.js`

**Missing Endpoint:**
- `api_update_npc_notes.php` (lines 137, 351)

**Investigation:**
- Check if `admin/api_npc_briefing.php` handles similar functionality
- Review `js/admin_npc_briefing.js` to understand expected API contract
- Check `admin/admin_npc_briefing.php` for clues

**Action:**
- Create missing endpoint or update JavaScript to use existing equivalent
- Ensure API contract matches JavaScript expectations

**Files to Check:**
- `js/admin_npc_briefing.js`
- `admin/admin_npc_briefing.php`
- `admin/api_npc_briefing.php` (may be equivalent)

---

### Task 7: Fix Missing DataManager.js API Endpoints
**Priority:** High  
**File:** `js/modules/core/DataManager.js`

**Missing Endpoints:**
- `admin/api_disciplines.php` (line 283)
- `load_character.php` (line 321)
- `get_characters.php` (line 339)
- `delete_character.php` (line 357)
- `upload.php` (line 375)

**Investigation:**
- Check if `admin/get_abilities_api.php` or similar handles disciplines
- Check if `api_get_characters.php` (exists in root) is equivalent to `get_characters.php`
- Check if `admin/delete_character_api.php` is equivalent to `delete_character.php`
- Review `js/modules/core/DataManager.js` to understand expected API contracts

**Action:**
- Create missing endpoints or update JavaScript to use existing equivalents
- Map existing endpoints to DataManager.js expectations
- Ensure API contracts match

**Files to Check:**
- `js/modules/core/DataManager.js`
- `api_get_characters.php` (root - may be equivalent)
- `admin/delete_character_api.php` (may be equivalent)
- `admin/get_abilities_api.php` (may handle disciplines)

---

### Task 8: Fix Missing Character Image Removal Endpoint
**Priority:** Medium  
**File:** `js/character_image.js:159`

**Missing Endpoint:**
- `remove_character_image.php`

**Investigation:**
- Review `js/character_image.js` to understand expected API contract
- Check if image upload/removal is handled elsewhere
- Check upload handling in existing code

**Action:**
- Create `remove_character_image.php` endpoint
- Or update JavaScript to use existing equivalent endpoint
- Ensure proper image deletion and database cleanup

**Files to Check:**
- `js/character_image.js:159`
- Any existing image upload/management code

---

### Task 9: Verification and Documentation
**Priority:** High  
**Dependencies:** Tasks 1-8

**Action:**
- Test all fixed links/endpoints
- Re-run broken-link check (if tool exists)
- Update `plan/broken_links.md` to mark resolved issues
- Document any intentional redirects or API consolidations

**Files to Update:**
- `plan/broken_links.md`

---

## Implementation Order

1. **High Priority (Critical Functionality):**
   - Task 1: logout.php (verify)
   - Task 2: character_sheet.php
   - Task 4: Admin Items APIs
   - Task 5: Admin Locations APIs
   - Task 7: DataManager.js APIs

2. **Medium Priority (UI/UX):**
   - Task 3: css/header.css
   - Task 6: NPC Notes API
   - Task 8: Character Image Removal

3. **Verification:**
   - Task 9: Final verification and documentation

---

## Notes

- Many "missing" endpoints may have equivalent functionality under different names
- Priority is to maintain existing functionality while fixing broken references
- Consider API consolidation if multiple endpoints serve similar purposes
- Test each fix individually before moving to next task

