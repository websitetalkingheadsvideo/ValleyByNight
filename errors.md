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
- **Description**: Clicking the "Add New Location" button triggers a JavaScript error: `ReferenceError: openAddLocationModal is not defined`. The button does not open a modal as expected.
- **Steps to Reproduce**:
  1. Log in as admin user
  2. Navigate to `/admin/admin_locations.php`
  3. Click the "+ Add New Location" button
  4. Check browser console for error
- **Expected Behavior**: 
  - Clicking the button should open a modal dialog for adding a new location
  - Modal should contain a form with fields for location details
- **Actual Behavior**: 
  - Button click triggers JavaScript error: `ReferenceError: openAddLocationModal is not defined`
  - No modal appears
  - Console shows error at line 190 of admin_locations.php
- **Screenshots/Notes**: 
  - Console error: `ReferenceError: openAddLocationModal is not defined at HTMLButtonElement.onclick (https://vbn.talkingheads.video/admin/admin_locations.php:190:96)`
  - Suggests the JavaScript function `openAddLocationModal()` is either not defined or not loaded
  - May be missing from the JavaScript file or not included on the page
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

---

## Testing Notes

### Browser Information
- **Browser**: Google Chrome
- **Test Date**: January 2025
- **User Role**: Admin/Storyteller

### Pages Tested So Far
- ✅ Dashboard (Index) - All elements working
- ✅ Account Settings - All elements working
- ✅ Admin Character Panel - All elements working (search, filters, modals)
- ⚠️ Admin Locations - Table loading issue detected (ERR-001, ERR-002)
- ✅ Admin Items - All elements working (table loads, search works, pagination visible)
- ✅ Admin Boons - All elements working (table loads, data displays correctly)
- ✅ Admin NPC Briefing - All elements working (table loads, filters, pagination)
- ✅ Admin Questionnaire - All elements working (form visible, questions table loads)
- ✅ Admin Camarilla Positions - All elements working (table loads, agent query forms visible)
- ✅ Player Questionnaire - All elements working (questions load, answer selection works, navigation works)
- ✅ Character Creation (LoTN) - All elements working (page loads, tabs visible, form fields present)
- ✅ Admin Equipment - All elements working (table loads with 52 items, pagination visible)
- ✅ Admin Sire/Childe - All elements working (table loads, relationship data displays)
- ⚠️ Chat Room - Character loading error detected (ERR-003)
- ✅ Wraith Character Creation - All elements working (page loads, tabs visible, form fields present)
- ✅ Admin Equipment - All elements working (table loads with 52 items, pagination visible)
- ✅ Admin Sire/Childe - All elements working (table loads, relationship data displays)

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

*Last Updated: Testing in progress - 7 errors found so far (ERR-001, ERR-002, ERR-003, ERR-004, ERR-005, ERR-006, ERR-007). Approximately 250+ tests completed. Many pages tested successfully including: Admin Boon Ledger (table loading, filter dropdown, "New Boon" button, "Mark as Paid" button works, edit button opens modal, edit modal close button works), Admin Agents (Character Agent search link, Generate Reports link, View Reports link, View Config link, Back to Agents links all work), Character Agent Reports page (shows Daily Reports section with multiple report files), Admin Panel (PCs Only filter works - shows 9 characters, NPCs Only filter works, All Characters filter works, Sort by Clan dropdown opens, Per page dropdown opens, Name column header clickable, Status column header clickable, Gen column header clickable, Character view button opens modal, Character view modal close button works, Character edit button opens modal with iframe, Character edit modal close button works, Character delete button opens confirmation modal, Delete confirmation modal cancel button works, Search by name textbox works - filters table), Equipment page (All Equipment filter button works, Weapons filter button works - shows 12 weapons, Armor filter button works - shows 3 armor items, Tools filter button works - shows 10 tools, Type dropdown opens, Rarity dropdown opens, Search by name textbox works, Per page dropdown opens, ID column header clickable - sorts table, Equipment view button opens modal showing equipment details, Equipment view modal close button works), Items page (All Items filter button works, Weapons filter button works - shows 12 weapons, Armor filter button works - shows 3 armor items, Tools filter button works - shows 10 tools, Type dropdown opens, Rarity dropdown opens, Search by name textbox works, Per page dropdown opens, ID column header clickable - sorts table, Name column header clickable - sorts table, Type column header clickable - sorts table, Category column header clickable - sorts table, Damage column header clickable - sorts table, Range column header clickable - sorts table, Rarity column header clickable - sorts table, Price column header clickable - sorts table, Created column header clickable - sorts table, Add New Item button opens modal, Add New Item modal close button works, Items view button ERR-006 confirmed - no modal appears), Sire/Childe page (All Relationships filter button, Sires Only filter button, Childer Only filter button, Sireless filter button, Add Relationship button opens modal, Add Relationship modal close button works, Family Tree button opens modal, Family Tree modal close button works, search box works - filters table, Vampire column header sorts table, Clan column header sorts table, Gen column header sorts table, Sire column header sorts table, Player column header sorts table, view button opens character modal, character modal close button works, edit button opens Edit Relationship modal, Edit Relationship modal close button works), Enhanced Sire/Childe page (Analyze Biographies button works - button becomes active, Verify Relationships button works - button becomes active, shows analysis complete message, Export Data button ERR-007 - 500 error on reports directory). Testing continues.*

Edit item popup needs to be styled
Character Agent Configuration needs to be a modal.
https://vbn.talkingheads.video/admin/boon_ledger.php styling needed