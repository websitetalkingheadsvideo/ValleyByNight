# VbN Chrome UI Testing Checklist

## Overview

This document provides a comprehensive manual testing checklist for the Valley by Night (VbN) project. All tests should be performed in **Google Chrome** browser.

### Purpose
- Systematically test every page and interactive element in the VbN application
- Document any bugs, errors, or unexpected behaviors discovered during testing
- Create a prioritized list of issues for resolution

### How to Use This Document

1. **Open the application in Chrome** at `https://vbn.talkingheads.video/` (or your local development URL)
2. **Test each page** listed in the Test Matrix below
3. **For each interactive element**:
   - Follow the "Steps to Test" instructions
   - Mark the result as **Pass** or **Fail** in the Result column
   - If it fails, add detailed notes in the "Notes / Error Details" column
   - If it's a significant error, create an entry in the **Error Log** section at the bottom
4. **Document errors** in the Error Log with:
   - Clear steps to reproduce
   - Expected vs actual behavior
   - Severity level (Low/Medium/High/Critical)
   - Screenshots if applicable

### Testing Guidelines

- **Browser**: Google Chrome (latest version)
- **Authentication**: Test both as a regular player and as an admin/storyteller user
- **Screenshots**: Take screenshots of any errors or unexpected behaviors
- **Incremental Testing**: Test one page at a time, completing all elements before moving to the next page
- **Update Status**: Mark Error Log entries as "Fixed" when issues are resolved

---

## Test Matrix by Page

### Page: Login
- **Route/URL**: `/login.php`
- **Category**: Public
- **Authentication Required**: No
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| LOG-1 | Username input field | Form Input | Accepts username input | 1. Navigate to login.php<br>2. Click in username field<br>3. Type a username | | |
| LOG-2 | Password input field | Form Input | Accepts password input (masked) | 1. Click in password field<br>2. Type a password<br>3. Verify characters are masked | | |
| LOG-3 | "Sign In" submit button | Button | Submits login form and redirects on success | 1. Enter valid credentials<br>2. Click "Sign In"<br>3. Verify redirect to index.php | | |
| LOG-4 | "Sign In" button with invalid credentials | Button | Shows error message on failure | 1. Enter invalid credentials<br>2. Click "Sign In"<br>3. Verify error message displays | | |
| LOG-5 | "Create Account" link | Link | Navigates to register.php | 1. Click "Create Account" link<br>2. Verify navigation to register.php | | |
| LOG-6 | Form validation (empty fields) | Form Validation | Prevents submission and shows validation messages | 1. Leave fields empty<br>2. Click "Sign In"<br>3. Verify HTML5 validation messages appear | | |

---

### Page: Register
- **Route/URL**: `/register.php`
- **Category**: Public
- **Authentication Required**: No
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| REG-1 | Username input field | Form Input | Accepts username (3-50 chars, alphanumeric + underscore) | 1. Navigate to register.php<br>2. Click username field<br>3. Type valid username | | |
| REG-2 | Email input field | Form Input | Accepts email address | 1. Click email field<br>2. Type valid email<br>3. Verify email format validation | | |
| REG-3 | Password input field | Form Input | Accepts password (min 8 chars, masked) | 1. Click password field<br>2. Type password<br>3. Verify masking and min length validation | | |
| REG-4 | Confirm Password input field | Form Input | Validates password match | 1. Enter password<br>2. Enter different confirm password<br>3. Verify mismatch error appears | | |
| REG-5 | "Create Account" submit button | Button | Submits registration form | 1. Fill all fields correctly<br>2. Click "Create Account"<br>3. Verify account creation and redirect | | |
| REG-6 | "Sign In" link | Link | Navigates to login.php | 1. Click "Sign In" link<br>2. Verify navigation to login.php | | |
| REG-7 | Form validation (username pattern) | Form Validation | Validates username pattern | 1. Enter invalid username (special chars)<br>2. Try to submit<br>3. Verify validation message | | |
| REG-8 | Password match validation | JavaScript Validation | Validates passwords match in real-time | 1. Enter password<br>2. Enter different confirm password<br>3. Verify real-time error message | | |

---

### Page: Dashboard (Index)
- **Route/URL**: `/index.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Different views for admin vs player

#### Interactive Elements (Player View)

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| IDX-P-1 | "Create New Character" button | Link/Button | Navigates to lotn_char_create.php | 1. Log in as player<br>2. Navigate to index.php<br>3. Click "Create New Character"<br>4. Verify navigation | | |
| IDX-P-2 | "Discover Your Clan" button | Link/Button | Navigates to questionnaire.php | 1. Click "Discover Your Clan"<br>2. Verify navigation to questionnaire.php | | |
| IDX-P-3 | Character card "View/Edit" button | Link/Button | Opens character sheet (if character exists) | 1. If character exists, click "View/Edit"<br>2. Verify character sheet opens | | |
| IDX-P-4 | Header "Account" link | Link | Navigates to account.php | 1. Click "Account" in header<br>2. Verify navigation | | |
| IDX-P-5 | Header "Logout" link | Link | Logs out user | 1. Click "Logout"<br>2. Verify logout and redirect to login | | |
| IDX-P-6 | Header logo/title link | Link | Navigates to index.php | 1. Click logo or title<br>2. Verify navigation to index | | |

#### Interactive Elements (Admin/Storyteller View)

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| IDX-A-1 | "Clan Discovery Quiz" card button | Link/Button | Navigates to questionnaire.php | 1. Log in as admin<br>2. Click "Take Quiz" button<br>3. Verify navigation | | |
| IDX-A-2 | "Agents Dashboard" card button | Link/Button | Navigates to admin/agents.php | 1. Click "Open Agents" button<br>2. Verify navigation | | |
| IDX-A-3 | "Character List" card button | Link/Button | Navigates to admin/admin_panel.php | 1. Click "View Characters" button<br>2. Verify navigation | | |
| IDX-A-4 | "Create Character" card button | Link/Button | Navigates to lotn_char_create.php | 1. Click "Create New" button<br>2. Verify navigation | | |
| IDX-A-5 | "Locations Database" card button | Link/Button | Navigates to admin/admin_locations.php | 1. Click "Manage Locations" button<br>2. Verify navigation | | |
| IDX-A-6 | "Items Database" card button | Link/Button | Navigates to admin/admin_items.php | 1. Click "Manage Items" button<br>2. Verify navigation | | |
| IDX-A-7 | Statistics display (Total Characters, PCs, NPCs) | Display | Shows correct counts | 1. Verify statistics display correctly<br>2. Check counts match database | | |

---

### Page: Account Settings
- **Route/URL**: `/account.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ACC-1 | "Update Email" form - New Email input | Form Input | Accepts new email address | 1. Navigate to account.php<br>2. Enter new email in "Update Email" form<br>3. Verify input accepted | | |
| ACC-2 | "Update Email" submit button | Button | Updates email address | 1. Enter new email<br>2. Click "Update Email"<br>3. Verify success message and email updated | | |
| ACC-3 | "Change Password" form - Current Password input | Form Input | Accepts current password (masked) | 1. Enter current password<br>2. Verify masking | | |
| ACC-4 | "Change Password" form - New Password input | Form Input | Accepts new password (min 8 chars, masked) | 1. Enter new password<br>2. Verify masking and validation | | |
| ACC-5 | "Change Password" form - Confirm Password input | Form Input | Validates password match | 1. Enter new password<br>2. Enter confirm password<br>3. Verify match validation | | |
| ACC-6 | "Change Password" submit button | Button | Updates password | 1. Fill all password fields correctly<br>2. Click "Change Password"<br>3. Verify success message | | |
| ACC-7 | Form validation (email format) | Form Validation | Validates email format | 1. Enter invalid email<br>2. Try to submit<br>3. Verify validation message | | |
| ACC-8 | Form validation (password length) | Form Validation | Validates min password length | 1. Enter password < 8 chars<br>2. Try to submit<br>3. Verify validation message | | |
| ACC-9 | Header navigation links | Link | Navigate to other pages | 1. Click header links (Account, Logout)<br>2. Verify navigation works | | |

---

### Page: Questionnaire (Clan Discovery)
- **Route/URL**: `/questionnaire.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| QUIZ-1 | Question radio buttons (Answer 1-4) | Radio Button | Selects answer choice | 1. Navigate to questionnaire.php<br>2. Click a radio button for answer<br>3. Verify selection | | |
| QUIZ-2 | "Next Question" or "Submit" button | Button | Advances to next question or submits | 1. Select an answer<br>2. Click next/submit button<br>3. Verify question advances or results shown | | |
| QUIZ-3 | Progress indicator | Display | Shows quiz progress | 1. Verify progress updates as questions answered | | |
| QUIZ-4 | Results display | Display | Shows clan recommendation | 1. Complete all questions<br>2. Verify results display with clan recommendation | | |
| QUIZ-5 | Header navigation | Link | Navigate to other pages | 1. Click header links<br>2. Verify navigation works | | |

---

### Page: Character Creation (LOTN)
- **Route/URL**: `/lotn_char_create.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| CHAR-1 | Tab buttons (Identity, Attributes, Abilities, etc.) | Tab/Button | Switches between character creation tabs | 1. Navigate to lotn_char_create.php<br>2. Click each tab button<br>3. Verify tab content changes | | |
| CHAR-2 | "Save" button (header) | Button | Saves character data | 1. Fill in character data<br>2. Click "Save" button<br>3. Verify save confirmation | | |
| CHAR-3 | "Exit" button | Button | Exits editor (with confirmation) | 1. Click "Exit" button<br>2. Verify confirmation dialog<br>3. Confirm exit | | |
| CHAR-4 | Form inputs (name, concept, clan, etc.) | Form Input | Accepts character data | 1. Fill in various form fields<br>2. Verify input accepted | | |
| CHAR-5 | Dropdown selects (clan, nature, demeanor, etc.) | Dropdown/Select | Selects from options | 1. Click dropdown<br>2. Select option<br>3. Verify selection | | |
| CHAR-6 | Trait/Ability point allocation | Interactive Element | Allocates points to traits/abilities | 1. Click to allocate points<br>2. Verify point totals update<br>3. Verify validation (max points) | | |
| CHAR-7 | Image upload | File Input | Uploads character image | 1. Click image upload<br>2. Select image file<br>3. Verify upload and preview | | |
| CHAR-8 | Form validation | Form Validation | Validates required fields | 1. Try to save with missing required fields<br>2. Verify validation messages | | |
| CHAR-9 | Auto-save functionality | JavaScript | Auto-saves periodically | 1. Make changes<br>2. Wait for auto-save<br>3. Verify save indicator | | |

---

### Page: Wraith Character Creation
- **Route/URL**: `/wraith_char_create.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| WRAITH-1 | Tab buttons (Identity, Traits, Shadow, Pathos/Corpus, Metadata) | Tab/Button | Switches between tabs | 1. Navigate to wraith_char_create.php<br>2. Click each tab<br>3. Verify tab content changes | | |
| WRAITH-2 | "Save" button (header) | Button | Saves wraith character | 1. Fill in character data<br>2. Click "Save"<br>3. Verify save confirmation | | |
| WRAITH-3 | "Exit" button | Button | Exits editor | 1. Click "Exit"<br>2. Verify exit behavior | | |
| WRAITH-4 | Form inputs (name, shadow name, guild, etc.) | Form Input | Accepts wraith character data | 1. Fill in form fields<br>2. Verify input accepted | | |
| WRAITH-5 | Dropdown selects | Dropdown/Select | Selects options | 1. Use dropdowns<br>2. Verify selections work | | |
| WRAITH-6 | Tab progress bar | Display | Shows completion progress | 1. Fill in tabs<br>2. Verify progress bar updates | | |

---

### Page: Chat Room
- **Route/URL**: `/chat.php`
- **Category**: Player/Admin
- **Authentication Required**: Yes
- **Role Restrictions**: None

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| CHAT-1 | Character selection cards | Interactive Card | Selects character for chat | 1. Navigate to chat.php<br>2. Click a character card<br>3. Verify character selected | | |
| CHAT-2 | Character list loading | AJAX/Display | Loads user's characters | 1. Verify characters load on page load<br>2. Check loading indicator | | |
| CHAT-3 | Selected character display | Display | Shows selected character info | 1. Select character<br>2. Verify character info displays | | |
| CHAT-4 | Chat interface (placeholder) | Display | Shows chat placeholder | 1. Select character<br>2. Verify chat interface appears | | |
| CHAT-5 | "Create your first character" link | Link | Navigates to character creation | 1. If no characters, click link<br>2. Verify navigation | | |

---

### Page: Admin Panel (Character Management)
- **Route/URL**: `/admin/admin_panel.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-CHAR-1 | Admin navigation buttons (Characters, Sire/Childe, Items, etc.) | Link/Button | Navigates to admin pages | 1. Log in as admin<br>2. Navigate to admin_panel.php<br>3. Click each nav button<br>4. Verify navigation | | |
| ADMIN-CHAR-2 | Character search input | Form Input | Filters character list | 1. Type in search box<br>2. Verify character list filters | | |
| ADMIN-CHAR-3 | Status filter dropdown | Dropdown/Select | Filters by character status | 1. Select status from dropdown<br>2. Verify list filters | | |
| ADMIN-CHAR-4 | Clan filter dropdown | Dropdown/Select | Filters by clan | 1. Select clan from dropdown<br>2. Verify list filters | | |
| ADMIN-CHAR-5 | Character table row click | Interactive | Opens character view modal | 1. Click on character row<br>2. Verify modal opens with character details | | |
| ADMIN-CHAR-6 | "View" button in character row | Button | Opens character view | 1. Click "View" button<br>2. Verify character view opens | | |
| ADMIN-CHAR-7 | "Edit" button in character row | Button | Opens character editor | 1. Click "Edit" button<br>2. Verify editor opens | | |
| ADMIN-CHAR-8 | "Delete" button in character row | Button | Deletes character (with confirmation) | 1. Click "Delete"<br>2. Verify confirmation dialog<br>3. Confirm deletion | | |
| ADMIN-CHAR-9 | Statistics display | Display | Shows character counts | 1. Verify statistics display correctly | | |
| ADMIN-CHAR-10 | Pagination controls | Button/Link | Navigates between pages | 1. If multiple pages, click pagination<br>2. Verify page changes | | |
| ADMIN-CHAR-11 | Sortable column headers | Interactive | Sorts table by column | 1. Click column header<br>2. Verify table sorts | | |

---

### Page: Admin Items Management
- **Route/URL**: `/admin/admin_items.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-ITEMS-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-ITEMS-2 | Filter buttons (All Items, Weapons, Armor, etc.) | Button | Filters items by type | 1. Click each filter button<br>2. Verify items filter | | |
| ADMIN-ITEMS-3 | Type filter dropdown | Dropdown/Select | Filters by item type | 1. Select type<br>2. Verify filtering | | |
| ADMIN-ITEMS-4 | Rarity filter dropdown | Dropdown/Select | Filters by rarity | 1. Select rarity<br>2. Verify filtering | | |
| ADMIN-ITEMS-5 | Search input | Form Input | Searches items by name | 1. Type search term<br>2. Verify search results | | |
| ADMIN-ITEMS-6 | Page size dropdown | Dropdown/Select | Changes items per page | 1. Select page size<br>2. Verify items per page changes | | |
| ADMIN-ITEMS-7 | "Add New Item" button | Button | Opens add item modal | 1. Click "Add New Item"<br>2. Verify modal opens | | |
| ADMIN-ITEMS-8 | Item row "Edit" button | Button | Opens edit modal | 1. Click "Edit" on item<br>2. Verify edit modal opens | | |
| ADMIN-ITEMS-9 | Item row "Delete" button | Button | Deletes item | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-ITEMS-10 | Item row "Assign" button | Button | Opens assignment modal | 1. Click "Assign"<br>2. Verify assignment modal opens | | |
| ADMIN-ITEMS-11 | Statistics display | Display | Shows item counts | 1. Verify statistics display | | |
| ADMIN-ITEMS-12 | Add/Edit item form submission | Form | Saves item data | 1. Fill item form<br>2. Submit<br>3. Verify save | | |

---

### Page: Admin Locations Management
- **Route/URL**: `/admin/admin_locations.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-LOC-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-LOC-2 | Filter buttons (All Locations, Havens, Elysiums, etc.) | Button | Filters locations by type | 1. Click each filter button<br>2. Verify locations filter | | |
| ADMIN-LOC-3 | Type filter dropdown | Dropdown/Select | Filters by location type | 1. Select type<br>2. Verify filtering | | |
| ADMIN-LOC-4 | Status filter dropdown | Dropdown/Select | Filters by status | 1. Select status<br>2. Verify filtering | | |
| ADMIN-LOC-5 | Owner filter dropdown | Dropdown/Select | Filters by owner type | 1. Select owner<br>2. Verify filtering | | |
| ADMIN-LOC-6 | Search input | Form Input | Searches locations by name | 1. Type search term<br>2. Verify search results | | |
| ADMIN-LOC-7 | Page size dropdown | Dropdown/Select | Changes locations per page | 1. Select page size<br>2. Verify items per page changes | | |
| ADMIN-LOC-8 | "Add New Location" button | Button | Opens add location modal | 1. Click "Add New Location"<br>2. Verify modal opens | | |
| ADMIN-LOC-9 | Location row "Edit" button | Button | Opens edit modal | 1. Click "Edit"<br>2. Verify edit modal opens | | |
| ADMIN-LOC-10 | Location row "Delete" button | Button | Deletes location | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-LOC-11 | Location row "Assign" button | Button | Opens assignment modal | 1. Click "Assign"<br>2. Verify assignment modal opens | | |
| ADMIN-LOC-12 | Statistics display | Display | Shows location counts | 1. Verify statistics display | | |

---

### Page: Admin Equipment Management
- **Route/URL**: `/admin/admin_equipment.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-EQ-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-EQ-2 | Filter buttons (All Equipment, Weapons, Armor, etc.) | Button | Filters equipment by type | 1. Click each filter button<br>2. Verify equipment filters | | |
| ADMIN-EQ-3 | Type filter dropdown | Dropdown/Select | Filters by equipment type | 1. Select type<br>2. Verify filtering | | |
| ADMIN-EQ-4 | Rarity filter dropdown | Dropdown/Select | Filters by rarity | 1. Select rarity<br>2. Verify filtering | | |
| ADMIN-EQ-5 | Search input | Form Input | Searches equipment by name | 1. Type search term<br>2. Verify search results | | |
| ADMIN-EQ-6 | Page size dropdown | Dropdown/Select | Changes equipment per page | 1. Select page size<br>2. Verify items per page changes | | |
| ADMIN-EQ-7 | "Add New Equipment" button | Button | Opens add equipment modal | 1. Click "Add New Equipment"<br>2. Verify modal opens | | |
| ADMIN-EQ-8 | Equipment row "Edit" button | Button | Opens edit modal | 1. Click "Edit"<br>2. Verify edit modal opens | | |
| ADMIN-EQ-9 | Equipment row "Delete" button | Button | Deletes equipment | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-EQ-10 | Equipment row "Assign" button | Button | Opens assignment modal | 1. Click "Assign"<br>2. Verify assignment modal opens | | |

---

### Page: Admin Sire/Childe Relationships
- **Route/URL**: `/admin/admin_sire_childe.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-SC-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-SC-2 | "Enhanced Analysis" link | Link | Navigates to enhanced analysis page | 1. Click "Enhanced Analysis"<br>2. Verify navigation | | |
| ADMIN-SC-3 | Statistics display | Display | Shows relationship statistics | 1. Verify statistics display | | |
| ADMIN-SC-4 | Relationship visualization | Display | Shows sire/childe tree | 1. Verify relationship tree displays | | |
| ADMIN-SC-5 | Search/filter inputs | Form Input | Filters relationships | 1. Use search/filter<br>2. Verify filtering works | | |

---

### Page: Admin Camarilla Positions
- **Route/URL**: `/admin/camarilla_positions.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-POS-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-POS-2 | Category filter dropdown | Dropdown/Select | Filters positions by category | 1. Select category<br>2. Verify filtering | | |
| ADMIN-POS-3 | Clan filter dropdown | Dropdown/Select | Filters by clan | 1. Select clan<br>2. Verify filtering | | |
| ADMIN-POS-4 | Search input | Form Input | Searches positions | 1. Type search term<br>2. Verify search results | | |
| ADMIN-POS-5 | Position lookup form | Form | Looks up position holder | 1. Fill position lookup form<br>2. Submit<br>3. Verify results | | |
| ADMIN-POS-6 | Character lookup form | Form | Looks up character positions | 1. Fill character lookup form<br>2. Submit<br>3. Verify results | | |
| ADMIN-POS-7 | "Assign Position" button | Button | Opens assignment modal | 1. Click "Assign Position"<br>2. Verify modal opens | | |
| ADMIN-POS-8 | Position row actions | Button | Edits/deletes positions | 1. Click action buttons<br>2. Verify functionality | | |

---

### Page: Admin Boon Ledger
- **Route/URL**: `/admin/boon_ledger.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-BOON-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-BOON-2 | "New Boon" button | Button | Opens boon creation modal | 1. Click "New Boon"<br>2. Verify modal opens | | |
| ADMIN-BOON-3 | Status filter dropdown | Dropdown/Select | Filters boons by status | 1. Select status<br>2. Verify filtering | | |
| ADMIN-BOON-4 | Boons table display | Display | Shows all boons | 1. Verify table loads and displays boons | | |
| ADMIN-BOON-5 | Boon row "Edit" button | Button | Opens edit modal | 1. Click "Edit"<br>2. Verify edit modal opens | | |
| ADMIN-BOON-6 | Boon row "Delete" button | Button | Deletes boon | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-BOON-7 | Boon form submission | Form | Saves boon data | 1. Fill boon form<br>2. Submit<br>3. Verify save | | |

---

### Page: Admin Questionnaire Management
- **Route/URL**: `/admin/questionnaire_admin.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-QUIZ-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-QUIZ-2 | "Add New Question" form | Form | Adds question to database | 1. Fill question form<br>2. Submit<br>3. Verify question added | | |
| ADMIN-QUIZ-3 | Question list display | Display | Shows all questions | 1. Verify questions display | | |
| ADMIN-QUIZ-4 | Question "Edit" button | Button | Opens edit form | 1. Click "Edit"<br>2. Verify edit form opens | | |
| ADMIN-QUIZ-5 | Question "Delete" button | Button | Deletes question | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-QUIZ-6 | Form validation | Form Validation | Validates required fields | 1. Try to submit incomplete form<br>2. Verify validation | | |

---

### Page: Admin NPC Briefing
- **Route/URL**: `/admin/admin_npc_briefing.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-NPC-1 | Admin navigation | Link/Button | Navigates to other admin pages | 1. Click navigation buttons<br>2. Verify navigation | | |
| ADMIN-NPC-2 | Clan filter dropdown | Dropdown/Select | Filters NPCs by clan | 1. Select clan<br>2. Verify filtering | | |
| ADMIN-NPC-3 | Search input | Form Input | Searches NPCs by name | 1. Type search term<br>2. Verify search results | | |
| ADMIN-NPC-4 | Page size dropdown | Dropdown/Select | Changes NPCs per page | 1. Select page size<br>2. Verify items per page changes | | |
| ADMIN-NPC-5 | NPC card/row click | Interactive | Opens NPC details | 1. Click NPC<br>2. Verify details display | | |
| ADMIN-NPC-6 | Statistics display | Display | Shows NPC counts | 1. Verify statistics display | | |

---

### Page: Admin Agents Dashboard
- **Route/URL**: `/admin/agents.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-AGENT-1 | Agent card action links | Link | Navigates to agent pages | 1. Click agent action links<br>2. Verify navigation | | |
| ADMIN-AGENT-2 | Agent status display | Display | Shows agent status | 1. Verify agent status displays | | |
| ADMIN-AGENT-3 | Agent information display | Display | Shows agent details | 1. Verify agent information displays | | |

---

### Page: Admin Rumor Viewer
- **Route/URL**: `/admin/rumor_viewer.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-RUMOR-1 | Rumor list display | Display | Shows all rumors | 1. Verify rumors display | | |
| ADMIN-RUMOR-2 | Filter controls | Form Input/Dropdown | Filters rumors | 1. Use filters<br>2. Verify filtering works | | |
| ADMIN-RUMOR-3 | Rumor details view | Display | Shows rumor details | 1. Click rumor<br>2. Verify details display | | |
| ADMIN-RUMOR-4 | Search input | Form Input | Searches rumors | 1. Type search term<br>2. Verify search results | | |

---

### Page: Admin Wraith Panel
- **Route/URL**: `/admin/wraith_admin_panel.php`
- **Category**: Admin
- **Authentication Required**: Yes
- **Role Restrictions**: Admin/Storyteller only

#### Interactive Elements

| ID | Element Description | Type | Expected Behavior | Steps to Test | Result (Pass/Fail) | Notes / Error Details |
|----|---------------------|------|-------------------|---------------|--------------------|-----------------------|
| ADMIN-WRAITH-1 | Wraith character table | Display | Shows all wraith characters | 1. Verify table displays | | |
| ADMIN-WRAITH-2 | Sortable column headers | Interactive | Sorts table | 1. Click column header<br>2. Verify sorting | | |
| ADMIN-WRAITH-3 | Character row "View" button | Button | Opens character view | 1. Click "View"<br>2. Verify view opens | | |
| ADMIN-WRAITH-4 | Character row "Edit" button | Button | Opens character editor | 1. Click "Edit"<br>2. Verify editor opens | | |
| ADMIN-WRAITH-5 | Character row "Delete" button | Button | Deletes character | 1. Click "Delete"<br>2. Verify deletion | | |
| ADMIN-WRAITH-6 | Search/filter inputs | Form Input | Filters characters | 1. Use search/filter<br>2. Verify filtering | | |

---

## Error Log

Use this section to document any bugs, errors, or unexpected behaviors discovered during testing. Each error should be documented with clear steps to reproduce and severity assessment.

### Error Entry Template

```markdown
## Error ID: [Auto-increment: ERR-001, ERR-002, etc.]
- **Page**: [Page Name / URL]
- **Element**: [Element Description / ID from test matrix]
- **Steps to Reproduce**:
  1. [Step 1]
  2. [Step 2]
  3. [Step 3]
- **Expected Behavior**: [What should happen]
- **Actual Behavior**: [What actually happens]
- **Severity**: [Low/Medium/High/Critical]
- **Screenshots/Additional Info**: [Optional - describe or reference screenshot]
- **Status**: [Open / In Progress / Fixed]
- **Fixed In**: [Commit/Plan step reference when fixed]
```

---

## Testing Notes

### Authentication Testing
- Test all pages as both a regular player and as an admin/storyteller
- Verify role-based access restrictions work correctly
- Test logout functionality from all pages

### Browser Compatibility
- Primary testing browser: Google Chrome
- Test responsive design on different screen sizes
- Verify all modals and dropdowns work correctly

### Performance Notes
- Note any pages that load slowly
- Document any AJAX requests that fail or timeout
- Report any JavaScript errors in browser console

### Accessibility Notes
- Verify keyboard navigation works
- Check that screen readers can access content
- Verify ARIA labels and roles are present where needed

---

## Completion Checklist

- [ ] All public pages tested
- [ ] All player pages tested
- [ ] All admin pages tested
- [ ] All interactive elements tested
- [ ] All errors documented in Error Log
- [ ] Screenshots taken for critical errors
- [ ] Testing notes completed

---

*Last Updated: [Date will be filled during testing]*

