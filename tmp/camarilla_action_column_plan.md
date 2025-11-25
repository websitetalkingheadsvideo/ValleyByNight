# Plan: Add Action Column with Modals to camarilla_positions.php

## Current State Analysis

### Existing Implementation (admin_panel.php)
- **Three Action Buttons:**
  1. 👁️ View button (`view-btn`) - Opens read-only modal via `viewCharacter(characterId)`
  2. ✏️ Edit button (`edit-btn`) - Links to character editor page
  3. 🗑️ Delete button (`delete-btn`) - Opens delete confirmation modal

- **Modal System:**
  - Uses `includes/character_view_modal.php` for view modal
  - Modal loads data from `/admin/view_character_api.php`
  - Bootstrap modal with read-only display
  - JavaScript function `viewCharacter()` handles opening and populating

- **Button Structure:**
  ```html
  <div class="btn-group btn-group-sm" role="group">
    <button class="action-btn view-btn btn btn-primary" data-id="...">👁️</button>
    <a href="..." class="action-btn edit-btn btn btn-warning">✏️</a>
    <button class="action-btn delete-btn btn btn-danger" data-id="...">🗑️</button>
  </div>
  ```

### Current camarilla_positions.php State
- Has a table with positions data
- Currently has a simple "View History" button in Actions column
- No modal system for viewing/editing positions
- No API endpoint for position data

## Final State

### Target Implementation
- **Action Column** with three buttons matching admin_panel.php style:
  1. 👁️ View - Opens modal with read-only position details
  2. ✏️ Edit - Opens same modal in editable mode
  3. 🗑️ Delete - Opens delete confirmation modal

- **Position View/Edit Modal:**
  - Bootstrap modal matching character_view_modal.php style
  - Read-only mode for view button
  - Editable mode for edit button
  - Fields: position name, category, description, importance_rank, etc.

- **API Endpoint:**
  - `/admin/view_position_api.php` - Returns position data with current holder info

## Files to Modify/Create

### 1. Create API Endpoint
**File:** `admin/view_position_api.php` (NEW)
- Purpose: Return position data for modal display
- Returns: Position details, current holder, assignment history
- Similar structure to `view_character_api.php`

### 2. Create Position Modal Include
**File:** `includes/position_view_modal.php` (NEW)
- Purpose: Reusable modal HTML and JavaScript for viewing/editing positions
- Structure: Similar to `character_view_modal.php`
- Features:
  - Bootstrap modal with same styling
  - Read-only and editable modes
  - Form for editing position fields
  - Save functionality

### 3. Update camarilla_positions.php
**File:** `admin/camarilla_positions.php`
- Add Action column header to table
- Replace current "View History" button with three-button group
- Include position_view_modal.php
- Add delete confirmation modal

### 4. Update JavaScript
**File:** `js/admin_camarilla_positions.js`
- Add functions for:
  - `viewPosition(positionId)` - Opens modal in read-only mode
  - `editPosition(positionId)` - Opens modal in editable mode
  - `deletePosition(positionId)` - Opens delete confirmation
  - Modal initialization and data loading
  - Form submission for edits

### 5. Create/Update CSS (if needed)
**File:** `css/admin_camarilla_positions.css`
- Ensure action buttons match admin_panel.php styling
- Modal styling (may reuse character_view.css patterns)

## Implementation Steps

### Step 1: Create Position API Endpoint
- Create `admin/view_position_api.php`
- Query position data from `camarilla_positions` table
- Include current holder information
- Return JSON response matching character API pattern

### Step 2: Create Position Modal Include
- Create `includes/position_view_modal.php`
- Copy structure from `character_view_modal.php`
- Adapt for position fields:
  - Position Name
  - Category
  - Description
  - Importance Rank
  - Current Holder (read-only display)
  - Assignment History (read-only display)
- Add edit mode toggle
- Add form submission handler

### Step 3: Update Table Structure
- Modify `camarilla_positions.php` table header to add "Actions" column
- Update each table row to include three-button action group
- Add data attributes for position_id

### Step 4: Wire Up JavaScript
- Add button event listeners in `admin_camarilla_positions.js`
- Implement `viewPosition()` function
- Implement `editPosition()` function
- Implement `deletePosition()` function
- Add modal initialization code

### Step 5: Add Delete Functionality
- Create delete API endpoint or add to existing
- Add delete confirmation modal (similar to admin_panel.php)
- Wire up delete button handler

### Step 6: Add Edit/Save Functionality
- Create update API endpoint `admin/update_position_api.php`
- Add form submission handler in modal
- Handle success/error responses
- Refresh table after successful update

### Step 7: Testing & Validation
- Test view button opens modal with correct data
- Test edit button opens modal in editable mode
- Test save functionality updates position
- Test delete functionality removes position
- Verify styling matches admin_panel.php

## Database Schema Reference

### camarilla_positions table
- position_id (string, primary key)
- name (string)
- category (string)
- description (text, nullable)
- importance_rank (int, nullable)

### camarilla_position_assignments table
- position_id (string)
- character_id (string - character name transformed)
- start_night (datetime)
- end_night (datetime, nullable)
- is_acting (boolean)

## Assumptions

1. Position editing will update the `camarilla_positions` table directly
2. Position deletion will require confirmation and may need to handle assignments
3. Modal styling should match character_view_modal.php exactly
4. Edit mode will show same fields as view mode, but editable
5. Current holder information is read-only in both modes

## Notes

- Follow Bootstrap patterns from admin_panel.php
- Reuse existing CSS classes where possible
- Maintain consistent button sizing and styling
- Ensure modal is responsive like character modal
- Use same error handling patterns as character API

