# Valley by Night – Errors Remediation Plan

This document summarizes the open issues from `errors.md` and provides a step-by-step remediation plan for each error. Errors are grouped by type and ordered from easiest to hardest.

## Legend
- **Difficulty**: Easy / Medium / Hard
- **Severity**: Low / Medium / High / Critical
- **Status**: From errors.md (all currently "Open")

---

## Group 1: JavaScript "Element not found" @ line 412

Common JavaScript error pattern affecting multiple pages where event handlers fail to find expected DOM elements at line 412.

### [ERR-025] Home Page Links JavaScript Error (`/index.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Runtime
- **Summary**: Multiple navigation links on the home page fail with "Element not found" error at line 412. This prevents users from accessing key functionality including "Take Quiz", "Open Agents", "View Character", "Create New", "Manage Location", and "Manage Item" links.
- **Similar Errors**: ERR-029, ERR-030, ERR-031

**Fix Plan (Step-by-step)**
1. Open `index.php` and locate line 412 to identify the problematic JavaScript code
2. Check if the element selector is correct and the target elements exist in the HTML
3. Verify the DOM is fully loaded before the event handler executes (use `DOMContentLoaded` or move script to end of body)
4. Inspect the actual HTML structure of the navigation links to ensure IDs/classes match
5. Update the JavaScript selector to match the actual HTML structure
6. Test all affected links to ensure they navigate correctly
7. Check browser console for any additional errors after fix

---

### [ERR-029] Items Page Action Buttons JavaScript Error (`/admin/admin_items.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Runtime
- **Summary**: View and Edit buttons in the items table fail with "Element not found" at line 412. The buttons do not open modals as expected, preventing users from viewing or editing item details.
- **Similar Errors**: ERR-025, ERR-030, ERR-031

**Fix Plan (Step-by-step)**
1. Open `admin/admin_items.php` and locate line 412 to identify the problematic JavaScript
2. Check the View and Edit button event handlers to verify element selectors
3. Inspect the modal HTML structure to ensure required elements exist with correct IDs/classes
4. Verify the JavaScript file `js/admin_items.js` is properly loaded and functions are defined
5. Update element selectors to match actual HTML structure if needed
6. Ensure modals are properly initialized before button click handlers are attached
7. Test View and Edit buttons to confirm modals open correctly

---

### [ERR-030] Boon Ledger Action Buttons JavaScript Error (`/admin/boon_ledger.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Runtime
- **Summary**: Edit, Cancel, and Delete buttons in the boons table fail with "Element not found" at line 412. The buttons do not function, preventing boon management operations.
- **Similar Errors**: ERR-025, ERR-029, ERR-031

**Fix Plan (Step-by-step)**
1. Open `admin/boon_ledger.php` and locate line 412 to identify the problematic JavaScript
2. Check the Edit, Cancel, and Delete button event handlers to verify element selectors
3. Inspect the modal/dialog HTML structure to ensure required elements exist
4. Verify the JavaScript functions for these buttons are properly defined
5. Update element selectors to match actual HTML structure if needed
6. Ensure modals and confirmation dialogs are properly initialized
7. Test all three button types to confirm they function correctly

---

### [ERR-031] NPC Briefing Pagination Buttons JavaScript Error (`/admin/admin_npc_briefing.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JavaScript Runtime
- **Summary**: Pagination buttons ("Next ›" and page numbers) fail with "Element not found" at line 412. The pagination does not work, preventing users from navigating through multiple pages of NPCs.
- **Similar Errors**: ERR-025, ERR-029, ERR-030

**Fix Plan (Step-by-step)**
1. Open `admin/admin_npc_briefing.php` and locate line 412 to identify the problematic JavaScript
2. Check the pagination button event handlers to verify element selectors
3. Inspect the pagination HTML structure to ensure buttons have correct IDs/classes
4. Verify the pagination JavaScript function is properly defined and handles page navigation
5. Check if the table container element exists and is accessible
6. Update element selectors to match actual HTML structure if needed
7. Ensure pagination state is properly managed (current page, total pages)
8. Test pagination buttons to confirm they navigate correctly through NPC pages

---

## Group 2: JavaScript Dropdown Selection Errors

Dropdown filter errors where JavaScript cannot find option values, likely due to value attribute mismatches.

### [ERR-027] Items/Equipment Rarity Dropdown Error (`/admin/admin_items.php`, `/admin/admin_equipment.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Runtime
- **Summary**: Selecting "Epic" or "Legendary" from the Rarity dropdown results in "Option with value 'Epic' not found" error at line 437. The dropdown options are visible but JavaScript cannot find them when attempting selection.
- **Similar Errors**: ERR-028

**Fix Plan (Step-by-step)**
1. Open `admin/admin_items.php` and `admin/admin_equipment.php` to locate line 437
2. Inspect the Rarity dropdown HTML to verify option values for "Epic" and "Legendary"
3. Check for whitespace, case sensitivity, or value attribute mismatches
4. Compare the JavaScript code at line 437 with how it selects dropdown options
5. Verify the option values in HTML match what JavaScript expects (exact string match)
6. Update either the HTML option values or JavaScript selection logic to match
7. Test "Epic" and "Legendary" selections on both Items and Equipment pages
8. Verify other rarity options (Common, Uncommon, Rare) still work correctly

---

### [ERR-028] Camarilla Positions Category Dropdown Error (`/admin/camarilla_positions.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Runtime
- **Summary**: Selecting "Primogen" (or other categories) from the Category dropdown results in "Option with value 'Primogen' not found" error at line 437. The dropdown options are visible but JavaScript cannot find them.
- **Similar Errors**: ERR-027

**Fix Plan (Step-by-step)**
1. Open `admin/camarilla_positions.php` to locate line 437
2. Inspect the Category dropdown HTML to verify option values for "Primogen" and other categories
3. Check for whitespace, case sensitivity, or value attribute mismatches
4. Compare the JavaScript code at line 437 with how it selects dropdown options
5. Verify the option values in HTML match what JavaScript expects (exact string match)
6. Update either the HTML option values or JavaScript selection logic to match
7. Test all category selections to confirm they filter correctly
8. Verify the table filtering functionality works after dropdown selection

---

## Group 3: JavaScript Syntax Errors

JavaScript syntax errors preventing code execution, including duplicate variable declarations and missing function definitions.

### [ERR-033] Locations JavaScript Syntax Error - viewContainer Duplicate (`js/admin_locations.js`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: JavaScript Syntax
- **Summary**: The variable `viewContainer` is declared multiple times in `js/admin_locations.js` at line 304, causing a syntax error that prevents JavaScript execution on the Locations page.
- **Similar Errors**: ERR-002

**Fix Plan (Step-by-step)**
1. Open `js/admin_locations.js` and locate all declarations of `viewContainer`
2. Identify the duplicate declaration at line 304 and earlier declarations
3. Determine which declaration is correct and which should be removed or renamed
4. If both are needed in different scopes, rename one to a unique variable name
5. If one is redundant, remove the duplicate declaration
6. Ensure the variable is properly scoped (function scope vs. global scope)
7. Test the Locations page to verify JavaScript loads without syntax errors
8. Verify related functionality (table loading, modals) works correctly

---

### [ERR-002] Locations "Add New Location" Button JavaScript Error (`/admin/admin_locations.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JavaScript Runtime
- **Summary**: Clicking "Add New Location" button triggers "ReferenceError: openAddLocationModal is not defined". The function is either missing from the JavaScript file or not properly loaded. Also related to ERR-033 syntax error.
- **Similar Errors**: ERR-033

**Fix Plan (Step-by-step)**
1. Open `js/admin_locations.js` and search for `openAddLocationModal` function definition
2. If function is missing, check if it exists in another file or was accidentally removed
3. If function exists but isn't loaded, verify the script tag in `admin/admin_locations.php` includes the file
4. Define the `openAddLocationModal` function if it doesn't exist, following the pattern of similar modal functions
5. Ensure the function properly opens a modal with the location creation form
6. Fix ERR-033 first (viewContainer duplicate) as it may prevent this function from loading
7. Test the "Add New Location" button to confirm the modal opens correctly
8. Verify the modal form contains all required fields for location creation

---

## Group 4: JavaScript Null Element Access

JavaScript errors where code attempts to access properties of null DOM elements, indicating missing or incorrect element selectors.

### [ERR-006] Items Page View Button JavaScript Error (`js/admin_items.js`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JavaScript Runtime
- **Summary**: Clicking the View button results in "TypeError: Cannot set properties of null (setting 'textContent')" at `js/admin_items.js:349:57`. The JavaScript is trying to set textContent on a DOM element that doesn't exist.
- **Similar Errors**: ERR-032

**Fix Plan (Step-by-step)**
1. Open `js/admin_items.js` and locate line 349, column 57 in the `viewItem` function
2. Identify which element selector is returning null
3. Inspect the item view modal HTML structure in `admin/admin_items.php`
4. Verify the element ID or class used in the selector matches the actual HTML
5. Check if the modal HTML exists in the page or needs to be created dynamically
6. Update the element selector to match the actual HTML structure
7. Add null checks before setting textContent to prevent errors
8. Test the View button to confirm the modal displays item details correctly

---

### [ERR-032] Admin Panel View Button JavaScript Error (`admin_panel.js`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JavaScript Runtime
- **Summary**: Clicking the View button results in "Cannot read properties of null (reading 'classList')" at `admin_panel.js:403`. The JavaScript is trying to access classList on a DOM element that doesn't exist.
- **Similar Errors**: ERR-006

**Fix Plan (Step-by-step)**
1. Open `js/admin_panel.js` (or the relevant JavaScript file) and locate line 403
2. Identify which element selector is returning null in the `viewCharacter` function
3. Inspect the character view modal HTML structure in `admin/admin_panel.php`
4. Verify the element ID or class used in the selector matches the actual HTML
5. Check if the modal HTML exists in the page or needs to be created dynamically
6. Update the element selector to match the actual HTML structure
7. Add null checks before accessing classList to prevent errors
8. Test the View button to confirm the modal displays character details correctly

---

## Group 5: UI/Styling Issues

Visual consistency and accessibility improvements that don't affect core functionality.

### [ERR-004] Account Page Password Form Accessibility Warning (`/account.php`)

- **Severity**: Low
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: UI/Accessibility
- **Summary**: Browser console shows accessibility warning that password forms should have a hidden username field. This doesn't affect functionality but impacts accessibility compliance.
- **Similar Errors**: None

**Fix Plan (Step-by-step)**
1. Open `account.php` and locate the "Change Password" form
2. Add a hidden input field with type="hidden" and name="username" inside the password form
3. Set the value to the current logged-in username (use PHP to populate)
4. Ensure the field is properly placed within the form element
5. Test the page to verify the accessibility warning no longer appears in console
6. Verify the password change functionality still works correctly
7. Check that the hidden field doesn't interfere with form submission

---

### [ERR-008] Items Page Edit Modal Styling (`/admin/admin_items.php`)

- **Severity**: Low
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: UI/Styling
- **Summary**: The edit item modal needs styling improvements to match the application's design standards and be consistent with other admin modals like Equipment and Positions.
- **Similar Errors**: ERR-010

**Fix Plan (Step-by-step)**
1. Open `admin/admin_items.php` and locate the edit item modal HTML structure
2. Compare the modal structure with working modals (Equipment edit modal, Positions edit modal)
3. Identify missing Bootstrap classes or CSS styling differences
4. Update the modal HTML to match the structure and classes of consistent modals
5. Check `css/admin_items.css` for modal-specific styles and update if needed
6. Ensure modal header, body, and footer sections match the design pattern
7. Test the edit modal to verify visual consistency with other admin modals
8. Verify all form fields are properly styled and aligned

---

### [ERR-010] Boon Ledger Page Styling (`/admin/boon_ledger.php`)

- **Severity**: Low
- **Status**: Open
- **Difficulty to Fix**: Easy
- **Category**: UI/Styling
- **Summary**: The Boon Ledger page needs styling improvements to match the application's design standards and be consistent with other admin pages like Equipment and Items.
- **Similar Errors**: ERR-008

**Fix Plan (Step-by-step)**
1. Open `admin/boon_ledger.php` and inspect the page structure
2. Compare with a well-styled admin page (e.g., `admin/admin_equipment.php` or `admin/admin_items.php`)
3. Identify missing Bootstrap classes, inconsistent spacing, or styling differences
4. Update the page HTML structure to match the consistent design pattern
5. Check if `css/boon_ledger.css` exists and update it, or create it if missing
6. Ensure table, filters, buttons, and modals have consistent styling
7. Test the page to verify visual consistency with other admin pages
8. Verify all interactive elements (buttons, dropdowns, modals) are properly styled

---

## Group 6: JSON/AJAX Data Loading Errors

AJAX requests failing to load data, either due to endpoint issues, invalid JSON responses, or network problems.

### [ERR-001] Locations Table Loading State (`/admin/admin_locations.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JSON/AJAX
- **Summary**: The locations table displays "Loading..." but never loads location data. Statistics show 4 locations exist, suggesting the AJAX endpoint may be failing or returning invalid data.
- **Similar Errors**: ERR-003, ERR-005

**Fix Plan (Step-by-step)**
1. Open `admin/admin_locations.php` and locate the JavaScript code that loads location data
2. Identify the AJAX endpoint URL being called (check network tab in browser console)
3. Verify the API endpoint exists and is accessible (e.g., check for `api_locations.php` or similar)
4. Test the API endpoint directly to see if it returns valid JSON
5. Check browser console network tab for failed requests or error responses
6. Verify the JavaScript handles both success and error cases properly
7. Check if the response format matches what the JavaScript expects
8. Update the AJAX call or API endpoint to return properly formatted JSON
9. Test the locations table to confirm it loads and displays location data
10. Verify table columns (ID, Name, Type, Status, District, Owner Type, Created, Actions) display correctly

---

### [ERR-003] Chat Page Character Loading Error (`/chat.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JSON/AJAX
- **Summary**: The chat page displays "Error loading characters. Please try again." instead of loading the user's characters. The AJAX request to load characters is failing.
- **Similar Errors**: ERR-001, ERR-005

**Fix Plan (Step-by-step)**
1. Open `chat.php` and locate the JavaScript code that loads characters
2. Identify the AJAX endpoint being called (likely `/includes/api_get_character_names.php` or similar)
3. Verify the API endpoint exists and is accessible
4. Test the API endpoint directly with the logged-in user's session to see response
5. Check browser console network tab for failed requests or error responses
6. Verify the endpoint requires authentication and session is properly passed
7. Check if the response format matches what the JavaScript expects (JSON array of characters)
8. Update the AJAX call error handling to provide more specific error messages
9. Test the chat page to confirm it loads and displays the user's characters
10. Verify the "Create your first character" link appears if no characters exist

---

### [ERR-005] NPC Briefing Modal JSON Parsing Error (`/admin/admin_npc_briefing.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: JSON/AJAX
- **Summary**: Clicking the briefing button opens a modal but displays "SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input". The API endpoint is returning invalid or empty JSON.
- **Similar Errors**: ERR-001, ERR-003

**Fix Plan (Step-by-step)**
1. Open `admin/admin_npc_briefing.php` and locate the JavaScript code that loads character briefing data
2. Identify the AJAX endpoint being called when the briefing button is clicked
3. Test the API endpoint directly to see what it returns (check for empty response or invalid JSON)
4. Check browser console network tab to see the actual response from the endpoint
5. Verify the endpoint returns valid JSON with character briefing information
6. Check if the endpoint handles errors properly and returns error JSON instead of empty response
7. Update the API endpoint to return properly formatted JSON with character data
8. Add error handling in JavaScript to check response before parsing JSON
9. Test the briefing button to confirm the modal displays NPC briefing information correctly
10. Verify the modal shows character details, background, motivations, and other relevant information

---

## Group 7: HTTP 403/500 Directory Access Errors

Server permission and configuration issues preventing access to report directories.

### [ERR-007] Boon Agent Reports Daily Directory 500 Error (`/agents/boon_agent/reports/daily/`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: Server Configuration
- **Summary**: Accessing the Boon Agent daily reports directory returns HTTP 500 Internal Server Error. This may be due to directory permissions, missing index file, or server configuration issue.
- **Similar Errors**: ERR-011, ERR-012

**Fix Plan (Step-by-step)**
1. Check if the directory `/agents/boon_agent/reports/daily/` exists on the server
2. Verify directory permissions (should be readable by web server, typically 755)
3. Check if an index file (index.php or index.html) exists in the directory
4. Review server error logs to identify the specific cause of the 500 error
5. If directory listing is intended, create an index.php file that lists report files
6. If directory access should be restricted, check .htaccess rules
7. Verify the web server has proper permissions to read files in the directory
8. Test directory access to confirm it loads without 500 error
9. Verify report files are accessible if directory listing is implemented

---

### [ERR-011] Boon Agent Validation Reports Directory 403 Forbidden (`/agents/boon_agent/reports/validation/`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: Server Configuration / UX
- **Summary**: Accessing the validation reports directory returns HTTP 403 Forbidden. Additionally, this should be converted to a modal dialog instead of a separate page (similar to ERR-009).
- **Similar Errors**: ERR-007, ERR-012, ERR-009

**Fix Plan (Step-by-step)**
1. Check directory permissions for `/agents/boon_agent/reports/validation/` (should be readable, typically 755)
2. Review .htaccess rules that might be blocking directory access
3. Verify the web server user has read permissions for the directory
4. Check if directory listing is disabled in server configuration
5. Fix directory permissions or .htaccess rules to allow admin access
6. **UX Improvement**: Convert directory access to modal (see ERR-009 pattern)
7. Create JavaScript function to fetch report file list via AJAX
8. Display report list in a modal dialog instead of navigating to directory
9. Test the modal to confirm it displays validation reports correctly
10. Verify admin users can access reports through the modal interface

---

### [ERR-012] Boon Agent Character Reports Directory 403 Forbidden (`/agents/boon_agent/reports/character/`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: Server Configuration / UX
- **Summary**: Accessing the character reports directory returns HTTP 403 Forbidden. Additionally, this should be converted to a modal dialog instead of a separate page (similar to ERR-009).
- **Similar Errors**: ERR-007, ERR-011, ERR-009

**Fix Plan (Step-by-step)**
1. Check directory permissions for `/agents/boon_agent/reports/character/` (should be readable, typically 755)
2. Review .htaccess rules that might be blocking directory access
3. Verify the web server user has read permissions for the directory
4. Check if directory listing is disabled in server configuration
5. Fix directory permissions or .htaccess rules to allow admin access
6. **UX Improvement**: Convert directory access to modal (see ERR-009 pattern)
7. Create JavaScript function to fetch report file list via AJAX
8. Display report list in a modal dialog instead of navigating to directory
9. Test the modal to confirm it displays character reports correctly
10. Verify admin users can access reports through the modal interface

---

## Group 8: UX Modal Conversion

Converting separate page navigation to modal dialogs for better user experience and consistency.

### [ERR-009] Character Agent Configuration Should Be Modal (`/agents/character_agent/config.php`)

- **Severity**: Medium
- **Status**: Open
- **Difficulty to Fix**: Medium
- **Category**: UX/Architecture
- **Summary**: The Character Agent Configuration page should be converted to a modal dialog instead of a separate page. Clicking "View Config" currently navigates away from the Agents Dashboard.
- **Similar Errors**: ERR-011, ERR-012

**Fix Plan (Step-by-step)**
1. Open the Agents Dashboard page (`/admin/admin_agents.php` or similar) and locate the "View Config" link
2. Create a JavaScript function to load configuration data via AJAX instead of navigating
3. Create a modal HTML structure in the Agents Dashboard page for displaying configuration
4. Update the "View Config" link to trigger the modal instead of navigation
5. Fetch configuration data from the API endpoint (or load from config file)
6. Display configuration JSON, file path, status, last modified date, and file size in the modal
7. Add a close button to the modal to return to the Agents Dashboard
8. Style the modal to match other admin modals in the application
9. Test the "View Config" link to confirm it opens the modal correctly
10. Verify the modal displays all configuration information properly

---

## Group 9: HTTP 404 Missing Pages

Pages that don't exist or are inaccessible, requiring file creation or path correction.

### [ERR-013] Admin Rumor Viewer Page 404 Error (`/admin/admin_rumor_viewer.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Rumor Viewer page returns 404 error. The page does not exist or is not accessible. This prevents access to Rumor Viewer functionality.
- **Similar Errors**: ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_rumor_viewer.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/rumor_viewer.php`) that should be used
4. Review the project structure to understand the intended rumor viewer functionality
5. If file needs to be created, base it on similar admin pages (e.g., `admin/admin_panel.php`)
6. Create the rumor viewer page with table of rumors, filters, search, and view/edit capabilities
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays rumors correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-014] Admin Wraith Panel Page 404 Error (`/admin/admin_wraith_panel.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Wraith Panel page returns 404 error. The page does not exist or is not accessible. This prevents access to Wraith Panel functionality.
- **Similar Errors**: ERR-013, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_wraith_panel.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/wraith_admin_panel.php`) that should be used
4. Review the project structure - note that `admin/wraith_admin_panel.php` exists in the file listing
5. Verify if the correct path should be `/admin/wraith_admin_panel.php` instead
6. If file needs to be created, base it on similar admin pages and wraith character functionality
7. Create the wraith panel page with table of wraith characters, filters, search, and view/edit capabilities
8. Include proper authentication and admin access checks
9. Test the page to confirm it loads and displays wraith characters correctly
10. Update all links to use the correct file path

---

### [ERR-015] Admin Questionnaire Page 404 Error (`/admin/admin_questionnaire.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Questionnaire page returns 404 error. The page does not exist or is not accessible. Note: The player-facing questionnaire page (`/questionnaire.php`) loads correctly.
- **Similar Errors**: ERR-013, ERR-014, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_questionnaire.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/questionnaire_admin.php`) that should be used
4. Review the project structure - note that `admin/questionnaire_admin.php` exists in the file listing
5. Verify if the correct path should be `/admin/questionnaire_admin.php` instead
6. If file needs to be created, base it on the existing `questionnaire_admin.php` structure
7. Create the admin questionnaire page with table of questions, filters, search, and edit/delete capabilities
8. Include proper authentication and admin access checks
9. Test the page to confirm it loads and displays questionnaire questions correctly
10. Update all links to use the correct file path

---

### [ERR-016] Admin Agents Page 404 Error (`/admin/admin_agents.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Agents page returns 404 error. The page does not exist or is not accessible. Note: The home page "Open Agents" link navigates to a different agents page that works correctly.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_agents.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/agents.php`) that should be used
4. Review the project structure - note that `admin/agents.php` exists in the file listing
5. Verify if the correct path should be `/admin/agents.php` instead
6. If file needs to be created, base it on the existing `agents.php` structure
7. Create the agents dashboard page showing available agents (Character Agent, Laws Agent, Positions Agent, Rumor Agent, Boon Agent, etc.)
8. Include proper authentication and admin access checks
9. Test the page to confirm it loads and displays agents correctly
10. Update all links to use the correct file path

---

### [ERR-017] Enhanced Sire/Childe Page 404 Error (`/admin/enhanced_sire_childe.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Enhanced Sire/Childe page returns 404 error. The page does not exist or is not accessible. Note: The regular Sire/Childe page (`/admin/admin_sire_childe.php`) loads correctly.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/enhanced_sire_childe.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/admin_sire_childe_enhanced.php`) that should be used
4. Review the project structure - note that `admin/admin_sire_childe_enhanced.php` exists in the file listing
5. Verify if the correct path should be `/admin/admin_sire_childe_enhanced.php` instead
6. If file needs to be created, base it on the existing `admin_sire_childe.php` and enhanced features
7. Create the enhanced sire/childe page with relationship analysis, suggestions, and verification features
8. Include proper authentication and admin access checks
9. Test the page to confirm it loads and displays enhanced relationship features correctly
10. Update all links to use the correct file path

---

### [ERR-018] Boon Agent Viewer Page 404 Error (`/agents/boon_agent/viewer.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Boon Agent Viewer page returns 404 error. The page does not exist or is not accessible. Note: The Boon Ledger page (`/admin/boon_ledger.php`) loads correctly.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/boon_agent/viewer.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the boon_agent directory structure to understand the intended viewer functionality
4. If file needs to be created, base it on similar agent viewer pages and boon ledger functionality
5. Create the boon agent viewer page with boon analysis tools, economy analysis, and boon relationship features
6. Include proper authentication and admin access checks
7. Test the page to confirm it loads and displays boon agent tools correctly
8. Verify all links to this page are updated with the correct path

---

### [ERR-019] Admin Camarilla Positions Page 404 Error (`/admin/admin_camarilla_positions.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Camarilla Positions page returns 404 error. The page does not exist or is not accessible. Note: The Positions Agent page (accessed via "Launch Positions Agent" link) loads correctly and shows positions.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_camarilla_positions.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `admin/camarilla_positions.php`) that should be used
4. Review the project structure - note that `admin/camarilla_positions.php` exists in the file listing
5. Verify if the correct path should be `/admin/camarilla_positions.php` instead
6. If file needs to be created, base it on the existing `camarilla_positions.php` structure
7. Create the admin camarilla positions page with positions table, filters, search, and view/edit capabilities
8. Include proper authentication and admin access checks
9. Test the page to confirm it loads and displays positions correctly
10. Update all links to use the correct file path

---

### [ERR-020] Character Agent View Reports Page 404 Error (`/agents/character_agent/view_reports.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Character Agent View Reports page returns 404 error. The page does not exist or is not accessible. This prevents access to Character Agent View Reports functionality.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/character_agent/view_reports.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the character_agent directory structure to understand the intended reports functionality
4. If file needs to be created, base it on similar report viewing pages
5. Create the view reports page showing available reports (daily, weekly, continuity reports, city history compilations)
6. Include functionality to view and download generated reports
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays reports correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-021] Character Agent Search Page 404 Error (`/agents/character_agent/search.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Character Agent Search page returns 404 error. The page does not exist or is not accessible. This prevents access to Character Agent Search functionality.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-022, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/character_agent/search.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the character_agent directory structure to understand the intended search functionality
4. If file needs to be created, base it on similar search pages and character agent functionality
5. Create the character search page with a search form for querying character information using natural language
6. Include functionality to process search queries and display character details
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and processes search queries correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-022] Positions Agent Viewer Page 404 Error (`/agents/positions_agent/viewer.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Positions Agent Viewer page returns 404 error. The page does not exist or is not accessible. Note: The Positions Agent page (accessed via "Launch Positions Agent" link) loads correctly and shows positions.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-023, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/positions_agent/viewer.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the positions_agent directory structure to understand the intended viewer functionality
4. If file needs to be created, base it on similar agent viewer pages and positions functionality
5. Create the positions agent viewer page with position analysis tools and position lookup features
6. Include functionality to view and manage position information
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays position tools correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-023] Rumor Agent Index Page 404 Error (`/agents/rumor_agent/index.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Rumor Agent index page returns 404 error. The page does not exist or is not accessible. This prevents access to Rumor Agent functionality. Note: ERR-013 documents that `/admin/admin_rumor_viewer.php` also returns a 404 error.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-024, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/rumor_agent/index.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the rumor_agent directory structure to understand the intended agent functionality
4. If file needs to be created, base it on similar agent index pages
5. Create the rumor agent index page with rumor management tools, search, and filtering features
6. Include functionality to view and manage rumors
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays rumor agent tools correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-024] Boon Agent Index Page 404 Error (`/agents/boon_agent/index.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Boon Agent index page returns 404 error. The page does not exist or is not accessible. This prevents access to Boon Agent functionality. Note: ERR-018 documents that `/agents/boon_agent/viewer.php` also returns a 404 error.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-026, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `agents/boon_agent/index.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. Review the boon_agent directory structure to understand the intended agent functionality
4. If file needs to be created, base it on similar agent index pages
5. Create the boon agent index page with boon management tools, analysis features, and boon relationship tools
6. Include functionality to view and manage boons
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays boon agent tools correctly
9. Verify all links to this page are updated with the correct path

---

### [ERR-026] Character Creation Page 404 Error (`/admin/lotn_char_create.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Character Creation page returns 404 error. The page does not exist or is not accessible. Note: Previous testing showed the "Create New" link from the home page working, suggesting the file may exist but the direct URL path is incorrect.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-034

**Fix Plan (Step-by-step)**
1. Check if the file `admin/lotn_char_create.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file (e.g., `lotn_char_create.php` in root) that should be used
4. Review the project structure - note that `lotn_char_create.php` exists in the root directory
5. Verify if the correct path should be `/lotn_char_create.php` instead of `/admin/lotn_char_create.php`
6. If file needs to be created in admin directory, base it on the existing `lotn_char_create.php` structure
7. Create the character creation page with form tabs (Basic Info, Traits, Abilities, Disciplines, Background, Morality, Merits & Flaws, Description, Final Details)
8. Include proper authentication and access checks
9. Test the page to confirm it loads and displays the character creation interface correctly
10. Update all links to use the correct file path

---

### [ERR-034] Admin Rumor Page 404 Error (`/admin/admin_rumor.php`)

- **Severity**: High
- **Status**: Open
- **Difficulty to Fix**: Hard
- **Category**: Missing Page
- **Summary**: The Admin Rumor page returns 404 error. The page does not exist or is not accessible. This prevents access to Rumor management functionality. Note: ERR-013 documents that `/admin/admin_rumor_viewer.php` also returns a 404 error.
- **Similar Errors**: ERR-013, ERR-014, ERR-015, ERR-016, ERR-017, ERR-018, ERR-019, ERR-020, ERR-021, ERR-022, ERR-023, ERR-024, ERR-026

**Fix Plan (Step-by-step)**
1. Check if the file `admin/admin_rumor.php` exists in the file system
2. If file exists, verify the file path is correct and file permissions allow web server access
3. If file doesn't exist, check if there's a similar file that should be used
4. Review the project structure to understand the intended rumor management functionality
5. If file needs to be created, base it on similar admin pages (e.g., `admin/admin_panel.php`)
6. Create the admin rumor page with table of rumors, filters, search, and view/edit capabilities
7. Include proper authentication and admin access checks
8. Test the page to confirm it loads and displays rumors correctly
9. Verify all links to this page are updated with the correct path

---

## Summary

This remediation plan covers all 34 errors from `errors.md`, organized into 9 groups by error type and ordered from easiest to hardest to fix:

- **Group 1**: JavaScript "Element not found" @ line 412 (4 errors) - Easy to Medium
- **Group 2**: JavaScript Dropdown Selection Errors (2 errors) - Easy
- **Group 3**: JavaScript Syntax Errors (2 errors) - Easy to Medium
- **Group 4**: JavaScript Null Element Access (2 errors) - Medium
- **Group 5**: UI/Styling Issues (3 errors) - Easy
- **Group 6**: JSON/AJAX Data Loading Errors (3 errors) - Medium
- **Group 7**: HTTP 403/500 Directory Access Errors (3 errors) - Medium
- **Group 8**: UX Modal Conversion (1 error) - Medium
- **Group 9**: HTTP 404 Missing Pages (14 errors) - Hard

**Total Errors**: 34
**Easy Fixes**: 9 errors
**Medium Fixes**: 12 errors
**Hard Fixes**: 13 errors

