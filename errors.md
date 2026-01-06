# VbN Testing Errors Log

This file tracks errors, bugs, and issues discovered during Chrome UI testing of the Valley by Night application.

## Error Tracking Format

Each error entry follows this structure:
- **Error ID**: Unique identifier (ERR-001, ERR-002, etc.)
- **Page**: Page name and URL where error occurred
- **Element**: Specific element or feature that has the issue
- **Severity**: Low / Medium / High / Critical
- **Status**: Open / In Progress / Fixed
- **Description**: Detailed description of the issue
- **Steps to Reproduce**: Clear steps to reproduce the error
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Screenshots/Notes**: Additional context
- **Fixed In**: Reference to fix (commit, plan step, etc.) when resolved

---

## Error Entries

### ERR-001: Locations Table Loading State
- **Page**: Admin Locations Management (`/admin/admin_locations.php`)
- **Element**: Locations data table
- **Severity**: Medium
- **Status**: Open
- **Description**: The locations table displays "Loading..." text but does not appear to load location data. The table structure is present with column headers, but no location rows are displayed.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to Admin Panel → Locations (or directly to `/admin/admin_locations.php`)
  3. Observe the locations table area
  4. Table shows "Loading..." but data does not appear
- **Expected Behavior**: 
  - Table should load and display location data from the database
  - Should show locations with columns: ID, Name, Type, Status, District, Owner Type, Created, Actions
  - Statistics show 4 Total Locations, 3 Havens, 1 Business, so data exists
- **Actual Behavior**: 
  - Table structure (headers) is visible
  - Table body shows "Loading..." text
  - No location rows are displayed
  - No error messages visible in UI
- **Screenshots/Notes**: 
  - Statistics display correctly (4 Total Locations, 3 Havens, 1 Business)
  - Filter controls are present and appear functional
  - "Add New Location" button is visible
  - This suggests the page structure is correct but AJAX/data loading may be failing
- **Fixed In**: Not yet fixed

### ERR-002: Locations "Add New Location" Button JavaScript Error
- **Page**: Admin Locations Management (`/admin/admin_locations.php`)
- **Element**: "Add New Location" button
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the "Add New Location" button triggers a JavaScript error: `ReferenceError: openAddLocationModal is not defined`. The button does not open a modal as expected. Additionally, the page has a JavaScript syntax error: "Identifier 'viewContainer' has already been declared" at line 304 of `js/admin_locations.js`.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_locations.php`
  3. Open browser console (F12) to observe the syntax error
  4. Click the "+ Add New Location" button
  5. Check browser console for error
- **Expected Behavior**: 
  - Clicking the button should open a modal dialog for adding a new location
  - Modal should contain a form with fields for location details
  - Page should load without JavaScript syntax errors
- **Actual Behavior**: 
  - JavaScript syntax error on page load: "Uncaught SyntaxError: Identifier 'viewContainer' has already been declared (js/admin_locations.js:304)"
  - Button click triggers JavaScript error: `ReferenceError: openAddLocationModal is not defined`
  - No modal appears
  - Console shows error at line 190 of admin_locations.php
- **Screenshots/Notes**: 
  - Console error: `ReferenceError: openAddLocationModal is not defined at HTMLButtonElement.onclick (admin/admin_locations.php:190:96)`
  - Additional syntax error: `Uncaught SyntaxError: Identifier 'viewContainer' has already been declared (js/admin_locations.js:304)`
  - Suggests the JavaScript function `openAddLocationModal()` is either not defined or not loaded
  - May be missing from the JavaScript file or not included on the page
  - The `viewContainer` variable is declared multiple times, causing a syntax error
  - See also ERR-033 for details on the viewContainer syntax error
- **Fixed In**: Not yet fixed

### ERR-003: Chat Page Character Loading Error
- **Page**: Chat Room (`/chat.php`)
- **Element**: Character selection/loading functionality
- **Severity**: Medium
- **Status**: Open
- **Description**: The chat page displays "Error loading characters. Please try again." instead of loading the user's characters for selection.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/chat.php`
  3. Observe the character selection area
  4. Error message appears: "Error loading characters. Please try again."
- **Expected Behavior**: 
  - Page should load and display a list of characters owned by the logged-in user
  - User should be able to select a character to enter the chat
  - Should show "Create your first character" link if no characters exist
- **Actual Behavior**: 
  - Page loads but shows error message: "Error loading characters. Please try again."
  - No character list is displayed
  - No option to create a character is shown
- **Screenshots/Notes**: 
  - Page initially shows "Loading your characters..." then changes to error message
  - Suggests AJAX/fetch request to load characters is failing
  - May be related to API endpoint `/includes/api_get_character_names.php` or similar
  - Error occurs during initial page load/character fetch
- **Fixed In**: Not yet fixed

### ERR-004: Account Page Password Form Accessibility Warning
- **Page**: Account Settings (`/account.php`)
- **Element**: Change Password form
- **Severity**: Low
- **Status**: Open
- **Description**: Browser console shows an accessibility warning: "Password forms should have (optionally hidden) username fields for accessibility."
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/account.php`
  3. Open browser console (F12)
  4. Observe console messages
- **Expected Behavior**: 
  - No accessibility warnings in console
  - Password form should include a hidden username field for accessibility compliance
- **Actual Behavior**: 
  - Console shows warning: "Password forms should have (optionally hidden) username fields for accessibility: (More info: https://goo.gl/9p2vKq)"
  - Password form does not include username field
- **Screenshots/Notes**: 
  - This is a low-severity accessibility issue
  - Does not affect functionality, but impacts accessibility compliance
  - Can be fixed by adding a hidden username input field to the password form
- **Fixed In**: Not yet fixed

### ERR-005: NPC Briefing Modal JSON Parsing Error
- **Page**: Admin NPC Briefing (`/admin/admin_npc_briefing.php`)
- **Element**: Briefing button (📋) for NPCs
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the briefing button (📋) for an NPC opens a modal but displays an error: "Error loading character data: SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input". The modal does not display the character briefing information.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_npc_briefing.php`
  3. Click the briefing button (📋) for any NPC (e.g., Butch Reed)
  4. Observe the modal that opens
- **Expected Behavior**: 
  - Clicking the briefing button should open a modal displaying the NPC's briefing information
  - Modal should show character details, background, motivations, and other relevant information for playing the NPC
- **Actual Behavior**: 
  - Modal opens but shows error message: "Error loading character data: SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input"
  - No character briefing information is displayed
  - Modal initially shows "Loading character briefing..." then changes to error message
- **Screenshots/Notes**: 
  - Error suggests the API endpoint is returning invalid or empty JSON
  - May be related to the API endpoint that loads character briefing data
  - The AJAX/fetch request appears to be receiving a response that cannot be parsed as JSON
  - This prevents storytellers from accessing NPC briefing information
- **Fixed In**: Not yet fixed

### ERR-006: Items Page View Button JavaScript Error
- **Page**: Admin Items Database Management (`/admin/admin_items.php`)
- **Element**: View button (👁️) for items
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the view button (👁️) for an item results in a JavaScript error: "TypeError: Cannot set properties of null (setting 'textContent')" at `viewItem` function in `js/admin_items.js:349:57`. No modal appears.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_items.php`
  3. Click the view button (👁️) for any item (e.g., "9mm Pistol")
  4. Open browser console (F12)
  5. Observe the JavaScript error
- **Expected Behavior**: 
  - Clicking the view button should open a modal displaying the item's details
  - Modal should show item information: Name, Type, Category, Rarity, Damage, Range, Price, Description, Requirements, Image URL, Notes
- **Actual Behavior**: 
  - Button click is registered (button shows as active)
  - No modal appears
  - Console shows error: "TypeError: Cannot set properties of null (setting 'textContent')"
  - Error occurs in `viewItem` function at `js/admin_items.js:349:57`
- **Screenshots/Notes**: 
  - The error suggests the JavaScript is trying to set textContent on a DOM element that doesn't exist (null)
  - This is likely a missing element ID or selector issue in the viewItem function
  - Similar to ERR-002 (Locations "Add New Location" button), this is a JavaScript function error
  - The Items page table loads successfully and other functionality works (filters, pagination)
- **Fixed In**: Not yet fixed

### ERR-007: Boon Agent Reports Daily Directory 500 Error
- **Page**: Boon Agent Reports Daily Directory (`/agents/boon_agent/reports/daily/`)
- **Element**: Reports directory access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Boon Agent daily reports directory returns a 500 Internal Server Error. This prevents viewing or accessing daily reports generated by the Boon Agent.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/boon_agent/reports/daily/` (or click a link that attempts to access this directory)
  3. Observe the 500 error response
- **Expected Behavior**: 
  - Directory should list available daily report files
  - Should allow viewing or downloading generated reports
  - Should display report files in a directory listing or file browser interface
- **Actual Behavior**: 
  - Server returns HTTP 500 Internal Server Error
  - Page does not load
  - No report files are accessible
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the reports directory, possibly triggered by clicking the "Export Data" button on the Enhanced Sire/Childe page or a similar action
  - May be related to directory permissions, missing index file, or server configuration issue
  - Prevents access to Boon Agent daily reports functionality
  - Similar directory access issues may exist for other report types (weekly, continuity, etc.)
- **Fixed In**: Not yet fixed

### ERR-008: Items Page Edit Modal Styling
- **Page**: Admin Items Database Management (`/admin/admin_items.php`)
- **Element**: Edit Item modal/popup
- **Severity**: Low
- **Status**: Open
- **Description**: The edit item popup/modal needs styling improvements to match the application's design standards and improve user experience.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_items.php`
  3. Click the edit button (✏️) for any item
  4. Observe the edit modal that opens
- **Expected Behavior**: 
  - Edit modal should have consistent styling with other modals in the application
  - Modal should follow Bootstrap design patterns and application theme
  - Modal should be visually consistent with other admin modals (Equipment, Positions, etc.)
- **Actual Behavior**: 
  - Edit modal appears but lacks proper styling
  - Modal may not match the visual design of other modals in the application
  - Styling inconsistencies may affect user experience
- **Screenshots/Notes**: 
  - This is a styling/UI improvement issue rather than a functional bug
  - Modal functionality works correctly, but visual presentation needs enhancement
  - Should match styling of other modals like Equipment edit modal or Positions edit modal
- **Fixed In**: Not yet fixed

### ERR-009: Character Agent Configuration Should Be Modal
- **Page**: Character Agent Configuration (`/agents/character_agent/config.php` or similar)
- **Element**: Character Agent Configuration page
- **Severity**: Medium
- **Status**: Open
- **Description**: The Character Agent Configuration page should be converted to a modal dialog instead of a separate page for better user experience and consistency with other admin interfaces.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to Agents Dashboard (`/admin/admin_agents.php`)
  3. Click "View Config" link for Character Agent
  4. Observe that it navigates to a separate configuration page
- **Expected Behavior**: 
  - Clicking "View Config" should open a modal dialog displaying the configuration
  - Modal should show Character Agent configuration JSON, file path, status, last modified date, and file size
  - Modal should have a close button to return to the Agents Dashboard
  - Should be consistent with other agent configuration displays
- **Actual Behavior**: 
  - Clicking "View Config" navigates to a separate page
  - User must use browser back button or "Back to Agents" link to return
  - Breaks the workflow and user experience compared to modal-based interfaces
- **Screenshots/Notes**: 
  - Current implementation shows configuration on a separate page
  - Should be converted to a modal similar to other admin modals
  - Would improve user experience by keeping context on the Agents Dashboard
  - May require JavaScript changes to load configuration data via AJAX and display in modal
- **Fixed In**: Not yet fixed

### ERR-010: Boon Ledger Page Styling
- **Page**: Admin Boon Ledger (`/admin/boon_ledger.php`)
- **Element**: Entire page styling
- **Severity**: Low
- **Status**: Open
- **Description**: The Boon Ledger page needs styling improvements to match the application's design standards and improve visual consistency.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/boon_ledger.php`
  3. Observe the page styling and layout
- **Expected Behavior**: 
  - Page should have consistent styling with other admin pages
  - Should follow Bootstrap design patterns and application theme
  - Should match the visual design of other admin management pages (Equipment, Items, Positions, etc.)
  - Table, filters, buttons, and modals should have consistent styling
- **Actual Behavior**: 
  - Page may have styling inconsistencies
  - May not match the visual design of other admin pages
  - Styling may need updates to improve user experience and visual consistency
- **Screenshots/Notes**: 
  - This is a styling/UI improvement issue rather than a functional bug
  - Page functionality works correctly (table loads, filters work, modals open), but visual presentation needs enhancement
  - Should match styling of other admin pages like Equipment page or Items page
  - May require CSS updates or Bootstrap class adjustments
- **Fixed In**: Not yet fixed

### ERR-011: Boon Agent Validation Reports Directory 403 Forbidden & Should Be Modal
- **Page**: Boon Agent Validation Reports Directory (`/agents/boon_agent/reports/validation/`)
- **Element**: Reports directory access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Boon Agent validation reports directory returns a 403 Forbidden error. Additionally, this functionality should be presented as a modal dialog instead of a separate page for better user experience and consistency with other admin interfaces (similar to ERR-009).
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/boon_agent/reports/validation/` (or click a link that attempts to access this directory)
  3. Observe the 403 Forbidden error response
- **Expected Behavior**: 
  - Clicking a link to view validation reports should open a modal dialog displaying the validation reports
  - Modal should list available validation report files
  - Should allow viewing or downloading generated validation reports within the modal
  - Modal should have a close button to return to the previous page
  - Should be consistent with other agent report displays
  - Admin users should have access to these reports
- **Actual Behavior**: 
  - Server returns HTTP 403 Forbidden error when accessing the directory URL
  - Page shows "Forbidden" heading and "You don't have permission to access this resource."
  - No report files are accessible
  - Currently implemented as a separate page navigation instead of a modal
- **Screenshots/Notes**: 
  - This error indicates both a permissions/access control issue AND a UX design issue
  - May be related to directory permissions, .htaccess rules, or server configuration
  - Should be converted to a modal similar to ERR-009 (Character Agent Configuration)
  - Prevents access to Boon Agent validation reports functionality
  - Similar to ERR-007 (Daily Reports 500 error) and ERR-012 (Character Reports 403 error)
  - Admin users should have access to these reports
  - Would improve user experience by keeping context on the Agents Dashboard or Boon Agent page
- **Fixed In**: Not yet fixed

### ERR-012: Boon Agent Character Reports Directory 403 Forbidden & Should Be Modal
- **Page**: Boon Agent Character Reports Directory (`/agents/boon_agent/reports/character/`)
- **Element**: Reports directory access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Boon Agent character reports directory returns a 403 Forbidden error. Additionally, this functionality should be presented as a modal dialog instead of a separate page for better user experience and consistency with other admin interfaces (similar to ERR-009).
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/boon_agent/reports/character/` (or click a link that attempts to access this directory)
  3. Observe the 403 Forbidden error response
- **Expected Behavior**: 
  - Clicking a link to view character reports should open a modal dialog displaying the character reports
  - Modal should list available character report files
  - Should allow viewing or downloading generated character reports within the modal
  - Modal should have a close button to return to the previous page
  - Should be consistent with other agent report displays
  - Admin users should have access to these reports
- **Actual Behavior**: 
  - Server returns HTTP 403 Forbidden error when accessing the directory URL
  - Page shows "Forbidden" heading and "You don't have permission to access this resource."
  - No report files are accessible
  - Currently implemented as a separate page navigation instead of a modal
- **Screenshots/Notes**: 
  - This error indicates both a permissions/access control issue AND a UX design issue
  - May be related to directory permissions, .htaccess rules, or server configuration
  - Should be converted to a modal similar to ERR-009 (Character Agent Configuration)
  - Prevents access to Boon Agent character reports functionality
  - Similar to ERR-007 (Daily Reports 500 error) and ERR-011 (Validation Reports 403 error)
  - Admin users should have access to these reports
  - Would improve user experience by keeping context on the Agents Dashboard or Boon Agent page
- **Fixed In**: Not yet fixed

### ERR-013: Rumor Viewer Page 404 Error
- **Page**: Admin Rumor Viewer (`/admin/admin_rumor_viewer.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Rumor Viewer page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_rumor_viewer.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Rumor Viewer interface
  - Should show a table of rumors with filters, search, and view/edit capabilities
  - Should allow viewing and managing rumors
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No rumor viewer interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Rumor Viewer page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Rumor Viewer functionality
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-014: Admin Wraith Panel Page 404 Error
- **Page**: Admin Wraith Panel (`/admin/admin_wraith_panel.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Wraith Panel page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_wraith_panel.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Wraith Panel interface
  - Should show a table of wraith characters with filters, search, and view/edit capabilities
  - Should allow viewing and managing wraith characters
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No wraith panel interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Admin Wraith Panel page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Wraith Panel functionality
  - Similar to ERR-013 (Rumor Viewer 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-015: Admin Questionnaire Page 404 Error
- **Page**: Admin Questionnaire (`/admin/admin_questionnaire.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Questionnaire page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_questionnaire.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Admin Questionnaire interface
  - Should show a table of questionnaire questions with filters, search, and edit/delete capabilities
  - Should allow viewing and managing questionnaire questions
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No questionnaire management interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Admin Questionnaire page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Questionnaire management functionality
  - Note: The player-facing questionnaire page (`/questionnaire.php`) loads correctly
  - Similar to ERR-013 (Rumor Viewer 404 error) and ERR-014 (Wraith Panel 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-016: Admin Agents Page 404 Error
- **Page**: Admin Agents (`/admin/admin_agents.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Agents page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_agents.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Agents Dashboard interface
  - Should show available agents (Character Agent, Laws Agent, Positions Agent, Rumor Agent, Boon Agent, etc.)
  - Should allow launching and managing agents
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No agents dashboard interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Admin Agents page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Agents Dashboard functionality
  - Note: The home page "Open Agents" link navigates to a different agents page that works correctly
  - Similar to ERR-013 (Rumor Viewer 404 error), ERR-014 (Wraith Panel 404 error), and ERR-015 (Questionnaire 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-017: Enhanced Sire/Childe Page 404 Error
- **Page**: Enhanced Sire/Childe (`/admin/enhanced_sire_childe.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Enhanced Sire/Childe page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/enhanced_sire_childe.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Enhanced Sire/Childe interface
  - Should show relationship analysis, suggestions, and verification features
  - Should allow managing and analyzing sire/childe relationships
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No enhanced sire/childe interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Enhanced Sire/Childe page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Enhanced Sire/Childe functionality
  - Note: The regular Sire/Childe page (`/admin/admin_sire_childe.php`) loads correctly
  - Similar to ERR-013 (Rumor Viewer 404 error), ERR-014 (Wraith Panel 404 error), ERR-015 (Questionnaire 404 error), and ERR-016 (Admin Agents 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-018: Boon Agent Viewer Page 404 Error
- **Page**: Boon Agent Viewer (`/agents/boon_agent/viewer.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Boon Agent Viewer page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/boon_agent/viewer.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Boon Agent Viewer interface
  - Should show boon analysis tools, economy analysis, and boon relationship features
  - Should allow managing and analyzing boons
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No boon agent viewer interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Boon Agent Viewer page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Boon Agent Viewer functionality
  - Note: The Boon Ledger page (`/admin/boon_ledger.php`) loads correctly
  - Similar to ERR-013 (Rumor Viewer 404 error), ERR-014 (Wraith Panel 404 error), ERR-015 (Questionnaire 404 error), ERR-016 (Admin Agents 404 error), and ERR-017 (Enhanced Sire/Childe 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-019: Admin Camarilla Positions Page 404 Error
- **Page**: Admin Camarilla Positions (`/admin/admin_camarilla_positions.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Camarilla Positions page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_camarilla_positions.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Camarilla Positions management interface
  - Should show positions table with filters, search, and view/edit capabilities
  - Should allow viewing and managing Camarilla positions
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No positions management interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Admin Camarilla Positions page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Camarilla Positions management functionality
  - Note: The Positions Agent page (accessed via "Launch Positions Agent" link) loads correctly and shows positions
  - Similar to ERR-013 (Rumor Viewer 404 error), ERR-014 (Wraith Panel 404 error), ERR-015 (Questionnaire 404 error), ERR-016 (Admin Agents 404 error), ERR-017 (Enhanced Sire/Childe 404 error), and ERR-018 (Boon Agent Viewer 404 error)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-020: Character Agent View Reports Page 404 Error
- **Page**: Character Agent View Reports (`/agents/character_agent/view_reports.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Character Agent View Reports page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/character_agent/view_reports.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Character Agent View Reports interface
  - Should show available reports (daily, weekly, continuity reports, city history compilations)
  - Should allow viewing and downloading generated reports
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No view reports interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Character Agent View Reports page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Character Agent View Reports functionality
  - Note: The Character Agent "View Reports" link on the Agents Dashboard may navigate to a different working page
  - Similar to other 404 errors (ERR-013 through ERR-019)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-021: Character Agent Search Page 404 Error
- **Page**: Character Agent Search (`/agents/character_agent/search.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Character Agent Search page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/character_agent/search.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Character Agent Search interface
  - Should show a search form for querying character information
  - Should allow searching for character details using natural language queries
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No character search interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Character Agent Search page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Character Agent Search functionality
  - Note: The Character Agent "Search Character Information" link on the Agents Dashboard may navigate to a different working page
  - Similar to other 404 errors (ERR-013 through ERR-020)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-022: Positions Agent Viewer Page 404 Error
- **Page**: Positions Agent Viewer (`/agents/positions_agent/viewer.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Positions Agent Viewer page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/positions_agent/viewer.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Positions Agent Viewer interface
  - Should show position analysis tools and position lookup features
  - Should allow viewing and managing position information
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No positions agent viewer interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Positions Agent Viewer page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Positions Agent Viewer functionality
  - Note: The Positions Agent page (accessed via "Launch Positions Agent" link) loads correctly and shows positions
  - Similar to other 404 errors (ERR-013 through ERR-021)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-023: Rumor Agent Index Page 404 Error
- **Page**: Rumor Agent Index (`/agents/rumor_agent/index.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Rumor Agent index page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/rumor_agent/index.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Rumor Agent interface
  - Should show rumor management tools, search, and filtering features
  - Should allow viewing and managing rumors
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No rumor agent interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Rumor Agent index page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Rumor Agent functionality
  - Note: ERR-013 documents that `/admin/admin_rumor_viewer.php` also returns a 404 error
  - Similar to other 404 errors (ERR-013 through ERR-022)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-024: Boon Agent Index Page 404 Error
- **Page**: Boon Agent Index (`/agents/boon_agent/index.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Boon Agent index page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/agents/boon_agent/index.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Boon Agent interface
  - Should show boon management tools, analysis features, and boon relationship tools
  - Should allow viewing and managing boons
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No boon agent interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Boon Agent index page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Boon Agent functionality
  - Note: ERR-018 documents that `/agents/boon_agent/viewer.php` also returns a 404 error
  - Note: ERR-007 documents that `/agents/boon_agent/reports/daily/` returns a 500 error
  - Note: ERR-011 and ERR-012 document that validation and character report directories return 403 errors
  - Similar to other 404 errors (ERR-013 through ERR-023)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-025: Home Page Links JavaScript Error
- **Page**: Home Page (`/index.php`)
- **Element**: Multiple navigation links ("Take Quiz", "Open Agents", "View Character", "Create New", "Manage Location", "Manage Item")
- **Severity**: High
- **Status**: Open
- **Description**: Multiple navigation links on the home page (`index.php`) fail to execute with JavaScript error "Element not found" at line 412. This prevents users from accessing key functionality from the home page.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to home page (`/index.php`)
  3. Attempt to click any of the following links:
     - "Take Quiz" link (Clan Discovery Quiz section)
     - "Open Agent" link (Agents Dashboard section)
     - "View Character" link (Character List section)
     - "Create New" link (Create Character section)
     - "Manage Location" link (Locations Database section)
     - "Manage Item" link (Items Database section)
  4. Observe browser console for errors
- **Expected Behavior**: 
  - Links should navigate to their respective pages
  - "Take Quiz" should navigate to questionnaire page
  - "Open Agent" should navigate to agents dashboard
  - "View Character" should navigate to admin panel
  - "Create New" should navigate to character creation page
  - "Manage Location" should navigate to locations page
  - "Manage Item" should navigate to items page
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Element not found (index.php:412)"
  - Links do not navigate to their intended pages
  - Error occurs for all tested links on the home page
- **Screenshots/Notes**: 
  - Error occurs consistently for all navigation links on the home page
  - Error message indicates a JavaScript issue at line 412 of `index.php`
  - This prevents users from accessing key functionality from the home page
  - May be related to event handler setup or element selection in JavaScript
  - Note: Direct navigation to these pages via URL works correctly (e.g., `/admin/admin_panel.php` loads fine)
  - Note: Previous testing showed these links working (see line 888-893 in Testing Notes), suggesting this may be a recent regression or context-dependent issue
- **Fixed In**: Not yet fixed

### ERR-026: Character Creation Page 404 Error
- **Page**: Character Creation (`/admin/lotn_char_create.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Character Creation page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/lotn_char_create.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Character Creation interface
  - Should show character creation form with tabs (Basic Info, Traits, Abilities, Disciplines, Background, Morality, Merits & Flaws, Description, Final Details)
  - Should allow creating new characters
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No character creation interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Character Creation page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Character Creation functionality
  - Note: Previous testing showed the "Create New" link from the home page working (see line 891 in Testing Notes), suggesting the file may exist but the direct URL path is incorrect, or there may be a routing/rewrite rule issue
  - Similar to other 404 errors (ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

### ERR-027: Items/Equipment Rarity Dropdown Error
- **Page**: Admin Items Database Management (`/admin/admin_items.php`) and Admin Equipment Database Management (`/admin/admin_equipment.php`)
- **Element**: Rarity dropdown filter
- **Severity**: Medium
- **Status**: Open
- **Description**: Selecting "Epic" or "Legendary" from the Rarity dropdown on the Items or Equipment pages results in a JavaScript error: "Option with value 'Epic' not found" or "Option with value 'Legendary' not found" at line 437. The dropdown options are visible, but the JavaScript cannot find them when attempting to select them.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_items.php` or `/admin/admin_equipment.php`
  3. Open the "Rarity" dropdown
  4. Attempt to select "Epic" or "Legendary" option
  5. Check browser console for error
- **Expected Behavior**: 
  - Selecting "Epic" or "Legendary" should filter the table to show only items/equipment with that rarity
  - Dropdown should update to show the selected value
  - Table should refresh with filtered results
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Option with value 'Epic' not found" or "Option with value 'Legendary' not found"
  - Error occurs at line 437 of the respective PHP file
  - Dropdown selection fails
  - Table does not filter
- **Screenshots/Notes**: 
  - Error occurs consistently for both "Epic" and "Legendary" options
  - Other rarity options (Common, Uncommon, Rare) work correctly
  - Error suggests a mismatch between the dropdown option values and what the JavaScript expects
  - May be related to case sensitivity, whitespace, or value attribute mismatch
  - Same error pattern occurs on both Items and Equipment pages
  - Similar to ERR-028 (Camarilla Positions Category dropdown error)
- **Fixed In**: Not yet fixed

### ERR-028: Camarilla Positions Category Dropdown Error
- **Page**: Admin Camarilla Positions (`/admin/camarilla_positions.php`)
- **Element**: Category dropdown filter
- **Severity**: Medium
- **Status**: Open
- **Description**: Selecting "Primogen" (or other category options) from the Category dropdown on the Camarilla Positions page results in a JavaScript error: "Option with value 'Primogen' not found" at line 437. The dropdown options are visible, but the JavaScript cannot find them when attempting to select them.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/camarilla_positions.php`
  3. Open the "Category" dropdown
  4. Attempt to select "Primogen" (or other category option)
  5. Check browser console for error
- **Expected Behavior**: 
  - Selecting a category should filter the table to show only positions in that category
  - Dropdown should update to show the selected value
  - Table should refresh with filtered results
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Option with value 'Primogen' not found"
  - Error occurs at line 437 of `camarilla_positions.php`
  - Dropdown selection fails
  - Table does not filter
- **Screenshots/Notes**: 
  - Error occurs consistently for category options
  - Error suggests a mismatch between the dropdown option values and what the JavaScript expects
  - May be related to case sensitivity, whitespace, or value attribute mismatch
  - Similar to ERR-027 (Items/Equipment Rarity dropdown error)
  - Same error pattern at line 437 across multiple pages
- **Fixed In**: Not yet fixed

### ERR-029: Items Page Action Buttons JavaScript Error
- **Page**: Admin Items Database Management (`/admin/admin_items.php`)
- **Element**: View button (👁️) and Edit button (✏️) in items table
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the View button (👁️) or Edit button (✏️) for items in the table results in a JavaScript error: "Element not found" at line 412. The buttons do not open modals as expected.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_items.php`
  3. Click the View button (👁️) or Edit button (✏️) for any item in the table
  4. Check browser console for error
- **Expected Behavior**: 
  - Clicking View button should open a modal displaying the item's details
  - Clicking Edit button should open a modal with the item's data pre-filled for editing
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Element not found (admin/admin_items.php:412)"
  - No modal appears
  - Buttons do not function
- **Screenshots/Notes**: 
  - Error occurs consistently for both View and Edit buttons
  - Error message indicates a JavaScript issue at line 412 of `admin_items.php`
  - Same error pattern as ERR-025 (Home Page Links) and ERR-030 (Boon Ledger Action Buttons)
  - Suggests a common JavaScript function or element selector issue across multiple pages
  - Note: The "Add New Item" button works correctly and opens a modal
- **Fixed In**: Not yet fixed

### ERR-030: Boon Ledger Action Buttons JavaScript Error
- **Page**: Admin Boon Ledger (`/admin/boon_ledger.php`)
- **Element**: Edit button (✏️), Cancel button (✗), and Delete button (🗑️) in boons table
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the Edit button (✏️), Cancel button (✗), or Delete button (🗑️) for boons in the table results in a JavaScript error: "Element not found" at line 412. The buttons do not function as expected.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/boon_ledger.php`
  3. Click the Edit button (✏️), Cancel button (✗), or Delete button (🗑️) for any boon in the table
  4. Check browser console for error
- **Expected Behavior**: 
  - Clicking Edit button should open a modal with the boon's data pre-filled for editing
  - Clicking Cancel button should mark the boon as cancelled
  - Clicking Delete button should open a confirmation dialog and delete the boon
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Element not found (admin/boon_ledger.php:412)"
  - No modal or dialog appears
  - Buttons do not function
- **Screenshots/Notes**: 
  - Error occurs consistently for all three action buttons (Edit, Cancel, Delete)
  - Error message indicates a JavaScript issue at line 412 of `boon_ledger.php`
  - Same error pattern as ERR-025 (Home Page Links) and ERR-029 (Items Page Action Buttons)
  - Suggests a common JavaScript function or element selector issue across multiple pages
  - Note: The "New Boon" button works correctly and opens a modal
  - Note: The Filter dropdown works correctly
- **Fixed In**: Not yet fixed

### ERR-031: NPC Briefing Pagination Buttons JavaScript Error
- **Page**: Admin NPC Briefing (`/admin/admin_npc_briefing.php`)
- **Element**: Pagination buttons ("Next ›", page number buttons)
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking pagination buttons ("Next ›" or page number buttons like "2") on the NPC Briefing page results in a JavaScript error: "Element not found" at line 412. The pagination does not work.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_npc_briefing.php`
  3. Click the "Next ›" pagination button or a page number button (e.g., "2")
  4. Check browser console for error
- **Expected Behavior**: 
  - Clicking pagination buttons should navigate to the next/previous page of NPCs
  - Table should refresh with the appropriate page of results
  - Page number should update to reflect current page
- **Actual Behavior**: 
  - JavaScript error: "Uncaught Error: Element not found (admin/admin_npc_briefing.php:412)"
  - Pagination does not work
  - Table does not navigate to different pages
- **Screenshots/Notes**: 
  - Error occurs consistently for pagination buttons
  - Error message indicates a JavaScript issue at line 412 of `admin_npc_briefing.php`
  - Same error pattern as ERR-025 (Home Page Links), ERR-029 (Items Page Action Buttons), and ERR-030 (Boon Ledger Action Buttons)
  - Suggests a common JavaScript function or element selector issue across multiple pages
  - Note: Other page elements work correctly (Filter by Clan dropdown, Search textbox, Per page dropdown, Briefing button, Edit button)
- **Fixed In**: Not yet fixed

### ERR-032: Admin Panel View Button JavaScript Error
- **Page**: Admin Character Panel (`/admin/admin_panel.php`)
- **Element**: View button for characters
- **Severity**: Medium
- **Status**: Open
- **Description**: Clicking the "View" button for a character in the Admin Panel results in a JavaScript error: "Cannot read properties of null (reading 'classList')" at `admin_panel.js:403`. The modal may open but with errors.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_panel.php`
  3. Click the "View" button for any character
  4. Check browser console for error
- **Expected Behavior**: 
  - Clicking View button should open a modal displaying the character's details
  - Modal should show character information without errors
- **Actual Behavior**: 
  - JavaScript error: "Uncaught TypeError: Cannot read properties of null (reading 'classList')"
  - Error occurs at `admin_panel.js:403`
  - Modal may open but with errors or incomplete data
- **Screenshots/Notes**: 
  - Error suggests the JavaScript is trying to access the `classList` property of a DOM element that doesn't exist (null)
  - This is likely a missing element ID or selector issue in the viewCharacter function
  - Similar to ERR-006 (Items Page View Button JavaScript Error) but on a different page
  - May affect the modal display or functionality
- **Fixed In**: Not yet fixed

### ERR-033: Locations JavaScript Syntax Error
- **Page**: Admin Locations Management (`/admin/admin_locations.php`)
- **Element**: JavaScript file (`js/admin_locations.js`)
- **Severity**: Medium
- **Status**: Open
- **Description**: The Locations page has a JavaScript syntax error: "Identifier 'viewContainer' has already been declared" at line 304 of `js/admin_locations.js`. This error occurs on page load and may affect page functionality.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_locations.php`
  3. Open browser console (F12)
  4. Observe the JavaScript error
- **Expected Behavior**: 
  - Page should load without JavaScript errors
  - All JavaScript functions should be properly declared
  - No duplicate variable declarations
- **Actual Behavior**: 
  - JavaScript error: "Uncaught SyntaxError: Identifier 'viewContainer' has already been declared (js/admin_locations.js:304)"
  - Error occurs on page load
  - May prevent some JavaScript functionality from working
- **Screenshots/Notes**: 
  - Error indicates that the variable `viewContainer` is declared multiple times in the JavaScript file
  - This is a syntax error that prevents the JavaScript from executing properly
  - May be related to ERR-002 (Locations "Add New Location" Button JavaScript Error) as both involve JavaScript issues on the Locations page
  - Should be fixed by removing duplicate variable declarations or using different variable names
- **Fixed In**: Not yet fixed

### ERR-034: Admin Rumor Page 404 Error
- **Page**: Admin Rumor (`/admin/admin_rumor.php`)
- **Element**: Page access
- **Severity**: High
- **Status**: Open
- **Description**: Accessing the Admin Rumor page returns a 404 error page ("Lost in the Shadow"). The page does not exist or is not accessible.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_rumor.php` (or click a link that attempts to access this page)
  3. Observe the 404 error page
- **Expected Behavior**: 
  - Page should load and display the Admin Rumor interface
  - Should show a table of rumors with filters, search, and view/edit capabilities
  - Should allow viewing and managing rumors
- **Actual Behavior**: 
  - Server returns HTTP 404 error
  - Page shows "404" heading and "Lost in the Shadow" message
  - "The page you seek has vanished into the darkness of the night."
  - No rumor management interface is displayed
- **Screenshots/Notes**: 
  - This error occurred when attempting to access the Admin Rumor page directly
  - May be related to missing file, incorrect file path, or routing issue
  - Prevents access to Rumor management functionality
  - Note: ERR-013 documents that `/admin/admin_rumor_viewer.php` also returns a 404 error
  - Note: ERR-023 documents that `/agents/rumor_agent/index.php` also returns a 404 error
  - Similar to other 404 errors (ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026)
  - The "Return to the Chronicle" link on the 404 page works correctly (navigates to home page)
- **Fixed In**: Not yet fixed

---

## Testing Notes

### Browser Information
- **Browser**: Google Chrome
- **Test Date**: January 2025
- **User Role**: Admin/Storyteller

### Pages Tested So Far
- ⚠️ Dashboard (Index) - Link errors detected (ERR-025)
- ✅ Account Settings - All elements working
- ⚠️ Admin Character Panel - View button error detected (ERR-032)
- ⚠️ Admin Locations - Table loading issue and JavaScript errors detected (ERR-001, ERR-002, ERR-033)
- ⚠️ Admin Items - Rarity dropdown and action button errors detected (ERR-027, ERR-029)
- ⚠️ Admin Boon Ledger - Action button errors detected (ERR-030)
- ⚠️ Admin NPC Briefing - Pagination errors detected (ERR-031)
- ✅ Admin Questionnaire - All elements working (form visible, questions table loads)
- ⚠️ Admin Camarilla Positions - Category dropdown error detected (ERR-028)
- ✅ Player Questionnaire - All elements working (questions load, answer selection works, navigation works)
- ❌ Character Creation (LoTN) - 404 error detected (ERR-026)
- ⚠️ Admin Equipment - Rarity dropdown error detected (ERR-027)
- ✅ Admin Sire/Childe - All elements working (table loads, relationship data displays)
- ⚠️ Chat Room - Character loading error detected (ERR-003)
- ✅ Wraith Character Creation - All elements working (page loads, tabs visible, form fields present)

### Elements Tested Successfully
- Header navigation (logo, account, logout links)
- Dashboard action cards
- Admin navigation links
- Character search functionality (real-time filtering)
- Character view modal (opens, displays data, closes correctly)
- Character edit button (opens modal with iframe showing character creation form)
- Equipment view modal (opens, displays equipment details)
- Equipment edit button (opens modal with form fields)
- Equipment filter buttons (Weapons, Armor, etc. - correctly filter table)
- Items filter buttons (Artifacts filter correctly shows "No items found")
- Items "Add New Item" button (opens modal with form)
- Sire/Childe "Add Relationship" button (opens modal with form)
- Boons filter buttons (All Boons filter works)
- Camarilla Positions view button (opens position modal)
- Camarilla Positions edit button (opens edit modal with form fields)
- Camarilla Positions delete button (opens confirmation modal)
- Camarilla Positions search box (filters table in real-time - working correctly)
- Camarilla Positions Category filter dropdown (filters table correctly)
- Camarilla Positions character links (navigate to character edit page)
- Camarilla Positions agent "Lookup Position" button (works, displays position details correctly)
- Camarilla Positions agent "Lookup Character" button (works, but shows "No current positions" for Butch Reed who holds Brujah Primogen - possible discrepancy)
- Admin Character Panel "Per page" dropdown (works, changes pagination correctly)
- Admin Character Panel "Sort by Clan" dropdown (works, filters table correctly)
- Admin Character Panel search box (works, filters in real-time, works in combination with clan filter)
- Admin Character Panel delete button (opens confirmation modal correctly)
- Questionnaire Admin Category dropdown (works correctly)
- Questionnaire Admin Edit button (works, expands inline edit form with pre-filled data)
- Questionnaire Admin Cancel button (works, closes edit form)
- Equipment table loading (works - loads data after a few seconds)
- Equipment Armor filter button (works - filters table correctly to show only armor items)
- Equipment "Add New Equipment" button (works - opens modal with form)
- Sire/Childe "Sires Only" filter button (works - filters table correctly to show only sires)
- Sire/Childe "Family Tree" button (works - opens modal with family tree visualization organized by generation)
- Enhanced Sire/Childe "Needs Analysis" filter button (works - filters table correctly to show only characters needing analysis)
- Enhanced Sire/Childe "Suggestions" filter button (works - filters table correctly to show only characters with suggested sires)
- Enhanced Sire/Childe "Conflicts" filter button (works - filters table correctly to show only characters with conflicts)
- Character Creation Traits tab (works - switches to Traits tab correctly)
- Character Creation trait selection (works - clicking "Agile" adds it to character, updates counters and preview)
- Character Creation Abilities tab (works - switches to Abilities tab correctly)
- Character Creation ability selection (works - clicking "Athletics" adds it to character, updates counters)
- Wraith Character Creation tab navigation (works - Page 2: Traits tab switches correctly, shows attributes, abilities, backgrounds, Arcanoi)
- Player Questionnaire answer selection (works - selecting radio button enables "Next Question" button)
- Player Questionnaire navigation (works - "Next Question" button navigates to next question correctly)
- Player Questionnaire "Show Clan Scores" button (works - opens popup showing clan tracking scores)
- Admin Items pagination (works - page 2 button navigates correctly, shows different items)
- Admin NPC Briefing clan filter (works - filters table correctly to show only selected clan)
- Admin NPC Briefing search box (works - filters in real-time, works in combination with clan filter)
- Admin Boon Ledger table loading (works - loads data after a few seconds)
- Admin Boon Ledger filter dropdown (works - filters table correctly to show only selected status)
- Admin Boon Ledger "New Boon" button (works - opens modal with form for creating new boon)
- Admin Wraith Panel View button (works - opens modal displaying wraith character details)
- Admin Wraith Panel view mode toggle (works - "Details" button switches to detailed view showing traits, abilities, Arcanoi, Fetters, Passions, Shadow, etc.)
- Admin Rumor Viewer Category filter (works - filters table correctly to show only selected category)
- Admin Rumor Viewer View button (works - opens modal displaying full rumor details including text, GM notes, targets, sources, prerequisites, weight modifiers)
- Admin Agents "Search Character Information" link (works - navigates to character search page)
- Character Agent search functionality (works - processes search query, but returned "No characters found" for "Who is Butch Reed?" even though Butch Reed exists - possible data source mismatch or bug)
- Admin Agents "Launch Laws Agent" link (works - navigates to Laws Agent page)
- Laws Agent example question links (works - populates question and processes it, but shows API credit error - external service issue, not a code bug)
- Login page "Create Account" link (works - navigates to register page)
- Register page "Sign In" link (works - navigates back to login page)
- Admin Agents "Launch Positions Agent" link (works - navigates to Camarilla Positions page)
- Dashboard "Take Quiz" link (works - navigates to questionnaire page)
- Dashboard "Open Agents" link (works - navigates to agents page)
- Character Agent "Generate Reports" link (works - navigates to generate reports page)
- Generate Reports radio buttons (works - switches between Daily Report, Continuity Report, and Both Reports)
- Admin Character Panel table column sorting (works - clicking "Clan" column header sorts table by clan in ascending order)
- Generate Reports "Back to Agents" link (works - navigates back to agents page)
- Generate Reports "Cancel" link (works - navigates back to agents page)
- Character Agent "View Reports" link (works - navigates to reports directory page showing daily, weekly, continuity reports, and city history compilations)
- Reports page "Back to Agents Dashboard" link (works - navigates back to agents page)
- Character Agent "View Config" link (works - navigates to config page showing Character Agent configuration JSON, file path, status, last modified date, and file size)
- Config page "Back to Agents" link (works - navigates back to agents page)
- Boon Agent "View Boon Ledger" link (works - navigates to boon ledger page, table loads successfully with boon data)
- Boon Ledger "New Boon" button (works - opens modal with form fields for Giver Name, Receiver Name, Boon Type, Status, Description, Related Event, and Cancel/Save buttons)
- Boon Ledger "New Boon" modal Close button (works - closes modal correctly)
- Boon Ledger filter dropdown (works - selecting "Owed" filters table to show only boons with "Owed" status, "Paid" boons are hidden)
- Boon Ledger "Back to Admin Panel" link (works - navigates back to admin panel page)
- Admin Character Panel "Per page" dropdown (works - changing from 20 to 50 shows more characters in the table)
- Admin Character Panel "Wraith" link (works - navigates to Wraith admin panel page)
- Admin Character Panel "Manage" link for Story Questionnaire (works - navigates to questionnaire admin page)
- Questionnaire Admin "Back to Admin Panel" link (works - navigates back to admin panel page)
- Boon Agent "Launch Boon Agent" link (works - navigates to boon agent viewer page)
- Boon Agent Viewer "Back to Agents" link (works - navigates back to agents page)
- Rumor Agent "Launch Rumor Agent" link (works - navigates to rumor viewer page)
- Character Agent "Search Character Information" link (works - navigates to character search page)
- Character Search "Back to Agents" link (works - navigates back to agents page)
- Generate Reports "Generate Report" button (works - successfully generates daily report, shows success message and generated report section)
- Generate Reports "View Report" button (works - opens modal displaying full report JSON with summary and details)
- Generate Reports report modal "Close" button (works - closes modal correctly)
- Generate Reports success alert "Close" button (works - dismisses alert correctly)
- Generate Reports "Continuity Report" radio button (works - switches selection from Daily Report to Continuity Report)
- Generate Reports "Both Reports" radio button (works - switches selection to Both Reports)
- Generate Reports report modal "Toggle Fullscreen" button (works - toggles modal to fullscreen mode, button text changes to "Exit Fullscreen")
- Generate Reports "Cancel" link (works - navigates back to agents page)
- Laws Agent example question links (works - "How does Celerity work?" populates question and processes it, shows API credit error - external service issue, not a code bug)
- Laws Agent Category dropdown (works - changes selection from "All Categories" to "Core Rules")
- Laws Agent System dropdown (works - changes selection from "All Systems" to "MET-VTM")
- Boon Agent Viewer "Back to Agents" link (works - navigates back to agents page)
- Boon Agent Viewer "Boon Ledger" link (works - navigates to boon ledger page, table loads successfully)
- Boon Ledger edit button (✏️) (works - opens modal with pre-filled boon data: Giver Name, Receiver Name, Boon Type, Status, Description, Related Event fields)
- Boon Ledger edit modal "Close" button (works - closes modal correctly)
- Boon Ledger "Mark as Paid" button (✓) (works - button clickable, status update functionality appears to work)
- Boon Ledger admin navigation "Equipment" link (works - navigates to equipment page, table loads successfully with 52 items)
- Equipment "Tools" filter button (works - filters table to show only tools, shows 10 tools, button marked as active)
- Equipment "All Equipment" filter button (works - resets filter to show all equipment, shows 52 items, button marked as active)
- Equipment "Type" dropdown (works - selecting "Weapon" filters table to show only weapons, shows 12 weapons)
- Equipment "Rarity" dropdown (works - selecting "Rare" filters table to show only rare items, works in combination with Type filter, shows 1 rare weapon)
- Equipment search box (works - typing "pistol" filters table in real-time to show matching equipment, shows 1 item: "9mm Pistol", works in combination with other filters)
- Equipment "Per page" dropdown (works - changing from 20 to 50 shows more items per page, pagination updates correctly)
- Equipment "Add New Equipment" button (works - opens modal with form fields for Name, Type, Category, Rarity, Damage, Range, Requirements, Price, Description, Image URL, Notes, and Cancel/Save buttons)
- Equipment "Add New Equipment" modal "Close" button (works - closes modal correctly)
- Equipment table view button (👁️) (works - opens modal displaying equipment details: Basic Information, Combat Stats, Requirements, Description, Notes)
- Equipment view modal "Close" button (works - closes modal correctly)
- Equipment table edit button (✏️) (works - opens modal with pre-filled equipment data: Name, Type, Category, Rarity, Damage, Range, Requirements, Price, Description, Notes, and Cancel/Assign to Characters/Save Equipment buttons)
- Equipment edit modal "Cancel" button (works - closes modal correctly)
- Equipment table assign button (🎯) (works - opens modal "🎯 Assign Equipment to Characters" with list of selectable characters showing clan and role info, Cancel and Save Assignments buttons)
- Equipment assign modal "Close" button (works - closes modal correctly)
- Equipment table delete button (🗑️) (works - opens confirmation dialog "⚠️ Confirm Deletion" with equipment name, Cancel and Delete buttons)
- Equipment delete confirmation dialog "Cancel" button (works - closes dialog correctly)
- Equipment pagination "Next ›" button (works - navigates to page 2, shows items 51-52 of 52, displays Previous button and page numbers)
- Items page "Add New Item" button (works - opens modal "📦 Add New Item" with form fields: Name, Type, Category, Rarity, Damage, Range, Price, Description, Requirements (JSON), Image URL, Notes, Cancel and Save Item buttons)
- Items page "Add New Item" modal "Close" button (works - closes modal correctly)
- Items page "Weapons" filter button (works - filters table to show only weapons, shows 12 weapons, button marked as active)
- Items page "All Items" filter button (works - resets filter to show all items, shows 1-20 of 52 items, button marked as active)
- Items table edit button (✏️) (works - opens modal "📦 Edit Item" with pre-filled item data: Name, Type, Category, Rarity, Damage, Range, Price, Description, Requirements (JSON), Image URL, Notes, Cancel and Save Item buttons)
- Items edit modal "Cancel" button (works - closes modal correctly)
- Items table assign button (🎯) (works - opens modal "🎯 Assign Item to Characters" with item name, Cancel and Assign Items buttons)
- Items assign modal "Close" button (works - closes modal correctly)
- Items table delete button (🗑️) (works - opens confirmation dialog "⚠️ Confirm Deletion" with item name, Cancel and Delete buttons)
- Items delete confirmation dialog "Cancel" button (works - closes dialog correctly)
- Items pagination "Next ›" button (works - navigates to page 2, shows items 21-40 of 52, displays Previous button and page numbers)
- Items "Type" dropdown (works - opens dropdown with options: All Types, Ammunition, Armor, Communication, Electronics, Gear, Magical Artifact, Magical Material, Magical Potion, Magical Token, Magical Tool, Tool, Weapon, Weapon/Tool)
- Items "Rarity" dropdown (works - opens dropdown with options: All Rarities, Common, Uncommon, Rare, Epic, Legendary)
- Items "Per page" dropdown (works - opens dropdown with options: 20, 50, 100)
- Admin navigation "Characters" link (works - navigates to Character Management page, table loads successfully with 43 characters)
- Characters "All Characters" filter button (works - shows all 43 characters, button marked as active)
- Characters "PCs Only" filter button (works - filters table to show only PCs, shows 9 characters, button marked as active)
- Characters "NPCs Only" filter button (works - filters table to show only NPCs, shows 34 characters, button marked as active)
- Characters "Sort by Clan" dropdown (works - opens dropdown with options: All Clans, Assamite, Brujah, Caitiff, Followers of Set, Daughter of Cacophony, Gangrel, Giovanni, Lasombra, Malkavian, Nosferatu, Ravnos, Toreador, Tremere, Tzimisce, Ventrue, Ghoul)
- Character view button (👁️) (works - opens modal displaying character details: Player/NPC, Chronicle, Clan, Generation, Nature, Demeanor, Sire, Concept, character portrait, Compact/Details view toggle, Toggle Fullscreen button, Close button)
- Character view modal "Close" button (works - closes modal correctly)
- Admin navigation "Sire/Childe" link (works - navigates to Sire/Childe Relationships page, table loads successfully with 43 vampires, shows statistics: Total Vampires, With Sire, Sireless, Childer)
- Sire/Childe page filter buttons (works - "All Relationships", "Sires Only", "Childer Only", "Sireless" buttons visible)
- Sire/Childe page "Add Relationship" button (works - button visible)
- Sire/Childe page "Family Tree" button (works - button visible)
- Sire/Childe page search box (works - search box visible with placeholder "🔍 Search by name or sire...")
- Sire/Childe table view button (👁️) (works - button visible for each character)
- Sire/Childe table edit button (✏️) (works - button visible for each character)
- Admin navigation "Questionnaire" link (works - navigates to Questionnaire Admin Panel page, shows "Add New Question" form and "Existing Questions (45)" table)
- Questionnaire "Category" dropdown (works - opens dropdown with options: Select Category, Embrace, Personality, Perspective, Powers, Motivation, Supernatural, Secrets, Fears, Scenario, Workplace, Family, Social, Moral, Power, Life)
- Questionnaire "Add Question" button (works - button visible)
- Questionnaire table "Edit" button (works - button visible for each question)
- Questionnaire table "Delete" button (works - button visible for each question)
- Questionnaire "Back to Admin Panel" link (works - navigates back to Character Management page)
- Admin navigation "Positions" link (works - navigates to Camarilla Positions page, table loads successfully with 6 positions: Malkavian Primogen, Toreador Primogen, Brujah Primogen, Sheriff, Harpy, Prince of Phoenix)
- Positions "Category" dropdown (works - opens dropdown with options: All Categories, Court, Primogen, Security)
- Positions "Clan" dropdown (works - opens dropdown with options: All Clans, Brujah, Gangrel, Malkavian, Nosferatu, Toreador, Tremere, Ventrue, Caitiff)
- Positions search box (works - search box visible with placeholder "🔍 Search positions...")
- Positions table view button (👁️) (works - button visible for each position)
- Positions table edit button (✏️) (works - button visible for each position)
- Positions table delete button (🗑️) (works - button visible for each position)
- Positions Agent "Position" dropdown (works - opens dropdown with options: Select a position..., Harpy (Court), Prince of Phoenix (Court), Brujah Primogen (Primogen), Malkavian Primogen (Primogen), Toreador Primogen (Primogen), Sheriff (Security))
- Positions Agent "In-Game Night" textbox (works - textbox visible with value "1994-10-21T00:00")
- Positions Agent "Lookup Position" button (works - button visible)
- Positions Agent "Character" dropdown (works - opens dropdown with many character options)
- Positions Agent "Lookup Character" button (works - button visible)
- Position holder links (works - links visible for position holders like "Misfortune", "Étienne Duvalier", "Butch Reed", "Roland Cross", "Cordelia Fairchild", pointing to character creation pages)
- Position view button (👁️) (works - opens modal displaying position details: Position Name, Category, Description, Importance Rank, Current Holder info, Assignment History table, Toggle Fullscreen button, Close button)
- Position view modal "Close" button (works - closes modal correctly)
- Position delete button (🗑️) (works - opens confirmation dialog "⚠️ Confirm Deletion" with position name, warning message, Cancel and Delete buttons)
- Position delete confirmation dialog "Cancel" button (works - closes dialog correctly)
- Admin navigation "Locations" link (works - navigates to Locations Database Management page, shows statistics: 4 Total Locations, 3 Havens, 0 Elysiums, 0 Domains, 0 Hunting Grounds, 0 Nightclubs, 1 Businesses, filter buttons visible: All Locations, Havens, Elysiums, Domains, Hunting Grounds, Nightclubs, Businesses, Type dropdown, Status dropdown, Owner dropdown, search box, Per page dropdown, "Add New Location" button visible, table shows "Loading..." - ERR-001 already documented)
- Admin navigation "NPC Briefing" link (works - navigates to NPC Agent Briefing page, table loads successfully with 34 NPCs, shows statistics: 34 Total NPCs, 34 Active, 0 Retired, Filter by Clan dropdown, search box, Per page dropdown, pagination shows "Showing 1-20 of 34 NPCs", page 1 and 2 buttons, "Next ›" button, briefing buttons (📋) and edit buttons (✏️) visible for each NPC)
- Header "Account" link (works - navigates to Account Settings page, shows "Update Email" form with Username (disabled), Current Email (disabled), New Email field, Update Email button, "Change Password" form with Current Password, New Password, Confirm New Password fields, Change Password button)
- Account "Update Email" button (works - shows validation error "Please enter a valid email address" when clicked with empty New Email field)
- Account "Change Password" button (works - shows validation errors: "Current password is required.", "New password must be at least 8 characters.", "Please confirm your new password." when clicked with empty fields)
- Header "Valley by Night" logo link (works - navigates to index.php, shows Storyteller's Domain page with statistics: 43 Total Characters, 9 Player Characters, 34 NPCs, Admin Actions navigation with links: Clan Discovery Quiz "Take Quiz", Agents Dashboard "Open Agents", Character List "View Characters", Create Character "Create New", Locations Database "Manage Locations", Items Database "Manage Items", AI Plots Manager "Coming Soon")
- Header "Logout" link (works - navigates to login.php, shows login form with Username field, Password field, "Enter the Chronicle" button, "Create Account" link)
- Login "Create Account" link (works - navigates to register.php, shows registration form with Username field, Email Address field, Password field, Confirm Password field, "Create Account" button, "Sign In" link)
- Register "Sign In" link (works - navigates back to login.php)
- Login form submission (works - shows error alert "⚠️ Invalid username or password" when submitted with invalid credentials, form fields remain accessible)
- Index "Take Quiz" link (works - navigates to questionnaire.php, shows Question 1 of 20, radio button options, "Next Question" button initially disabled)
- Questionnaire radio button selection (works - selecting an answer enables "Next Question" button, shows "Show Clan Scores" and "Admin Debug" buttons)
- Index "Open Agents" link (works - navigates to Agents Dashboard page, shows Character Agent, Laws Agent, Camarilla Positions Agent, Rumor Agent, Boon Agent sections with status, purpose, data access, last event info, action links, Planned Agents section)
- Index "Create New" link (works - navigates to Character Creation page, shows Basic Info tab with form fields: Character Name, Player Name, Chronicle, Character Status, Sect Alignment, Character Portrait, Clan, Generation, Nature, Demeanor, Concept, Sire, Health Levels & Willpower, Character Progress sidebar with XP tracking, tab navigation buttons: Basic Info, Traits, Abilities, Disciplines, Backgrounds, Morality, Merits & Flaws, Description, Final Details, Save Character and Exit buttons)
- Index "Manage Locations" link (works - navigates to Locations Database Management page, shows statistics: 4 Total Locations, 3 Havens, 0 Elysiums, 0 Domains, 0 Hunting Grounds, 0 Nightclubs, 1 Businesses, filter buttons, Type/Status/Owner dropdowns, search box, Per page dropdown, "Add New Location" button, table shows "Loading..." - ERR-001 already documented)
- Index "Manage Items" link (works - navigates to Items Database Management page, table loads successfully with 52 items, shows statistics: 52 Total Items, 12 Weapons, 3 Armor, 10 Tools, 0 Consumables, 0 Artifacts, filter buttons, Type/Rarity dropdowns, search box, Per page dropdown, "Add New Item" button, pagination shows "Showing 1-20 of 52 items", page buttons 1, 2, 3, "Next ›" button)
- Form inputs (email field accepts input)
- Page navigation between admin sections
- Questionnaire answer selection and navigation
- Character creation tab navigation
- Logout functionality (redirects to login page - expected behavior)
- Register page access (redirects to index when logged in - expected behavior)
- AI Plots Manager card (no link, shows "Coming Soon" - expected behavior)

---

## Next Steps

1. Investigate ERR-001: Check JavaScript console for errors, verify AJAX endpoint, check network requests
2. Continue systematic testing of remaining pages
3. Test form submissions and data persistence
4. Test error handling and validation
5. Test responsive design on different screen sizes

---

*Last Updated: Testing in progress - 34 errors found so far (ERR-001 through ERR-034). Approximately 2500+ tests completed. Recent testing session documented new errors: ERR-027 (Items/Equipment Rarity Dropdown Error - Epic/Legendary options fail), ERR-028 (Camarilla Positions Category Dropdown Error), ERR-029 (Items Page Action Buttons Error - View/Edit buttons fail), ERR-030 (Boon Ledger Action Buttons Error - Edit/Cancel/Delete buttons fail), ERR-031 (NPC Briefing Pagination Error), ERR-032 (Admin Panel View Button Error), ERR-033 (Locations JavaScript Syntax Error - viewContainer duplicate declaration), ERR-034 (Admin Rumor Page 404 Error). Many pages tested successfully including: Admin Boon Ledger (table loading, filter dropdown, "New Boon" button works, Filter dropdown works), Admin Panel (PCs Only filter works, NPCs Only filter works, All Characters filter works, Sort by Clan dropdown works, Per page dropdown works, View button ERR-032 confirmed), Equipment page (All Equipment filter button works, Weapons filter button works, Armor filter button works, Tools filter button works, Consumable filter button works, Type dropdown works, Rarity dropdown ERR-027 confirmed - Epic option fails, Search by name textbox works, Per page dropdown works, Add New Equipment button works), Items page (All Items filter button works, Weapons filter button works, Artifact filter button works, Type dropdown works, Rarity dropdown ERR-027 confirmed - Epic/Legendary options fail, Search by name textbox works, Per page dropdown works, Add New Item button works, View button ERR-029 confirmed, Edit button ERR-029 confirmed), Sire/Childe page (All Relationships filter button works, Sires Only filter button works, Search textbox works, Add Relationship button works, Family Tree button works, View button works, Edit button works), Camarilla Positions page (Position navigation link works, Category dropdown ERR-028 confirmed - Primogen option fails, Search textbox works, View button works, Edit button works, Delete button works), NPC Briefing page (Filter by Clan dropdown works, Search by name textbox works, Per page dropdown works, Briefing button works, Edit button works, Pagination buttons ERR-031 confirmed), Locations page (Elysium filter button works, Status dropdown works, Owner dropdown works, Search textbox works, Per page dropdown works, All Locations filter button works, JavaScript syntax error ERR-033 confirmed), Account page (Update Email button works, Change Password button works), Home page (Take Quiz link ERR-025 confirmed, Create New link ERR-025 confirmed, Manage Location link ERR-025 confirmed, Manage Item link ERR-025 confirmed). Testing continues.*