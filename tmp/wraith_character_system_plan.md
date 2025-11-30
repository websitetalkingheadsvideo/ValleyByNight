# Wraith: The Oblivion Character System Implementation Plan

## Overview
This plan details the implementation of a complete Wraith: The Oblivion character sheet and data structure for the Valley by Night project, based on the existing VtM LotN Revised character schema.

---

## 1. DATA MODEL CHANGES

### 1.1 New Schema File
- **File**: `reference/Characters/wraith_character.json`
- **Purpose**: Separate Wraith schema file (does not alter VtM schema)
- **Structure**: Object-based section organization matching existing `character.json` format

### 1.2 Core Identity Fields

#### Fields to ADD:
- `shadow_name` (string) - Wraith's shadow name
- `circle` (string) - Character's circle affiliation
- `guild` (string) - Character's guild
- `legion_at_death` (string) - Legion at time of death
- `date_of_death` (string/date) - When character died
- `cause_of_death` (string) - How character died
- `ghostly_appearance` (string) - Appearance in the Shadowlands

#### Fields to REMOVE (VtM-specific):
- `clan` → Remove
- `generation` → Remove
- `sire` → Remove
- `sect` → Remove
- `camarilla_status` → Remove
- `title` → Remove (VtM-specific)
- `epithet` → Remove (VtM-specific)
- `embrace_period` → Remove from timeline
- `bunker_acquisition` → Remove from timeline
- `years_in_bunker` → Remove from timeline

#### Fields to RENAME/REPURPOSE:
- `appearance` → Keep for mortal appearance, add `ghostly_appearance` for wraith form
- `timeline.birthplace` → Keep
- `timeline.arrival_in_phoenix` → Repurpose to `timeline.arrival_in_shadowlands` or remove
- `timeline.notable_mortal_past` → Keep
- `timeline.world_war_two_experience` → Keep if relevant

### 1.3 New Complex Data Structures

#### Fetters (Array of Objects)
```json
"fetters": [
  {
    "name": "Wedding Ring",
    "rating": 5,
    "description": "The ring from my wedding day"
  }
]
```

#### Passions (Array of Objects)
```json
"passions": [
  {
    "passion": "Protect my daughter",
    "rating": 4
  }
]
```

#### Arcanoi (Array of Objects)
```json
"arcanoi": [
  {
    "name": "Argos",
    "rating": 3,
    "arts": [
      {"level": 1, "power": "Sense the Living"},
      {"level": 2, "power": "Sight of the Living"},
      {"level": 3, "power": "Touch the Living"}
    ]
  }
]
```

#### Shadow Information (Object)
```json
"shadow": {
  "archetype": "The Tempter",
  "angst_current": 2,
  "angst_permanent": 3,
  "dark_passions": [
    {"passion": "Corrupt the innocent", "rating": 2}
  ],
  "thorns": [],
  "shadow_traits": [],
  "shadow_notes": ""
}
```

#### Pathos & Corpus (Object)
```json
"pathos_corpus": {
  "pathos_current": 5,
  "pathos_max": 7,
  "corpus_current": 6,
  "corpus_max": 6,
  "health_levels": ["Healthy", "Bruised", "Injured", "Wounded", "Mauled", "Crippled"]
}
```

#### Harrowing Information (Object)
```json
"harrowing": {
  "last_harrowing_date": "",
  "harrowing_notes": ""
}
```

### 1.4 Fields to MODIFY

#### Attributes
- **Keep**: Same 9-dot system (Physical, Social, Mental)
- **No changes needed**: Structure remains identical

#### Abilities
- **Keep**: Same 30 standard WoD abilities
- **Remove**: Any explicitly vampiric abilities (if any)
- **Keep**: Specializations system

#### Backgrounds
- **Replace VtM backgrounds** with Wraith backgrounds:
  - Memories
  - Status (in Stygia)
  - Allies (among the dead)
  - Relic
  - Artifact
  - Haunt
  - Past Life
  - Notoriety
  - Requiem
  - Destiny

#### Morality System
- **Remove**: `morality.path_name`, `morality.path_rating`, `morality.humanity`
- **Remove**: `morality.conscience`, `morality.self_control`, `morality.courage`
- **Keep**: `willpower_permanent`, `willpower_current` (repurpose for Wraith)
- **Add**: `pathos_corpus` object (see above)

#### Status Object
- **Remove**: `blood_pool_current`, `blood_pool_maximum`, `blood_per_turn`
- **Keep**: `xp_total`, `xp_spent`, `xp_available`
- **Add**: `shadow_xp_total`, `shadow_xp_spent`, `shadow_xp_available`
- **Modify**: `health_levels` → map to Corpus levels

#### Remove VtM-Specific Sections
- `domain_and_haven` → Remove (or repurpose for Haunt)
- `current_residents` → Remove
- `disciplines` → Replace with `arcanoi`
- `rituals` → Remove (or repurpose for Rites)

---

## 2. DATABASE SCHEMA CHANGES

### 2.1 New Table: `wraith_characters`
- **Purpose**: Separate table for Wraith characters (maintains VtM table integrity)
- **Columns**:
  - All standard character fields (id, user_id, character_name, player_name, etc.)
  - `shadow_name` VARCHAR(255)
  - `circle` VARCHAR(100)
  - `guild` VARCHAR(100)
  - `legion_at_death` VARCHAR(100)
  - `date_of_death` DATE
  - `cause_of_death` TEXT
  - `ghostly_appearance` TEXT
  - `fetters` JSON
  - `passions` JSON
  - `arcanoi` JSON
  - `shadow_data` JSON
  - `pathos_corpus` JSON
  - `harrowing` JSON
  - `shadow_xp_total` INT DEFAULT 0
  - `shadow_xp_spent` INT DEFAULT 0
  - `shadow_xp_available` INT DEFAULT 0
  - Remove: `clan`, `generation`, `sire`, `camarilla_status`, `blood_pool_*`

### 2.2 Migration Strategy
- Create new table without affecting existing `characters` table
- Use `character_type` enum or separate tables approach
- Ensure reversible migrations

---

## 3. UI FORM LAYOUT (5 Pages)

### Page 1: Identity & Background
**File**: `wraith_char_create.php` (new file, based on `lotn_char_create.php`)

**Sections**:
1. **Basic Identity**
   - Character Name (text input)
   - Shadow Name (text input)
   - Player Name (text input)
   - Chronicle (text input, default: "Valley by Night")

2. **Wraith Affiliation**
   - Guild (dropdown/select)
   - Circle (text input)
   - Legion at Death (dropdown/select)

3. **Death Information**
   - Date of Death (date picker)
   - Cause of Death (textarea)

4. **Appearance**
   - Mortal Appearance (textarea)
   - Ghostly Appearance (textarea)

5. **Fetters** (Editable List)
   - Add/Remove rows
   - Each row: Name (text), Rating (number 1-5), Description (textarea)

6. **Passions** (Editable List)
   - Add/Remove rows
   - Each row: Passion (text), Rating (number 1-5)

### Page 2: Traits
**Sections**:
1. **Attributes** (9-dot grid system)
   - Physical: Strength, Dexterity, Stamina
   - Social: Charisma, Manipulation, Appearance
   - Mental: Perception, Intelligence, Wits

2. **Abilities** (30 standard WoD abilities)
   - Talents / Skills / Knowledges
   - Specializations support

3. **Backgrounds** (Wraith-specific)
   - Memories, Status, Allies, Relic, Artifact, Haunt, Past Life, Notoriety, Requiem, Destiny
   - Each with rating (dots) and details field

4. **Arcanoi**
   - Add/Remove Arcanoi
   - Each: Name (select/dropdown), Rating (dots)
   - Expandable powers list per Arcanoi

### Page 3: Shadow Sheet
**Sections**:
1. **Shadow Archetype** (select/dropdown)
2. **Angst**
   - Current Angst (number)
   - Permanent Angst (number)
3. **Dark Passions** (editable list)
   - Add/Remove rows
   - Each: Passion (text), Rating (number)
4. **Thorns** (editable list)
5. **Shadow Traits** (editable list)
6. **Shadow Notes** (textarea)

### Page 4: Health, Pathos, Corpus
**Sections**:
1. **Pathos**
   - Current Pathos (number input)
   - Maximum Pathos (number input, calculated/editable)

2. **Corpus**
   - Current Corpus (number input)
   - Maximum Corpus (number input, calculated/editable)

3. **Health/Corpus Track**
   - Visual track showing Corpus levels
   - Checkboxes or visual indicators

4. **Harrowing History**
   - Last Harrowing Date (date picker)
   - Harrowing Notes (textarea)

### Page 5: Metadata
**Sections**:
1. **Experience Points**
   - Usual XP (total, spent, available)
   - Shadow XP (total, spent, available)

2. **Notes** (textarea)
3. **Relationships** (editable list - reuse existing structure)
4. **Artifacts** (editable list)
5. **Custom Fields** (JSON editor or structured inputs)

---

## 4. TABLE VIEW SCHEMA

### 4.1 Character List Table Columns
**File**: `admin/wraith_admin_panel.php` (new file) or modify existing with type filter

**Required Columns**:
- Name (character_name)
- Shadow Name (shadow_name)
- Guild (guild)
- Circle (circle)
- Legion (legion_at_death)
- Date of Death (date_of_death)
- Highest Arcanoi (calculated from arcanoi array)
- Pathos (current/max format: "5/7")
- Corpus (current/max format: "6/6")
- Angst (current/permanent format: "2/3")
- Fetter Summary (calculated: "# of fetters + strongest rating")
- Status (PC/NPC indicator)
- Actions (View, Edit, Delete buttons)

### 4.2 Table Implementation
- Use Bootstrap table classes (matching existing admin panel style)
- Sortable columns
- Filterable by Guild, Circle, Status
- Responsive design

---

## 5. BACKEND IMPLEMENTATION

### 5.1 New Files to Create

1. **Schema File**
   - `reference/Characters/wraith_character.json`

2. **Character Creation Form**
   - `wraith_char_create.php` (based on `lotn_char_create.php`)

3. **Save Handler**
   - `includes/save_wraith_character.php` (based on `save_character.php`)

4. **Admin Panel**
   - `admin/wraith_admin_panel.php` (based on `admin_panel.php`)

5. **View API**
   - `admin/view_wraith_character_api.php` (based on `view_character_api.php`)

6. **JavaScript Modules**
   - `js/wraith_char_create.js` (based on character creation JS)
   - `js/modules/wraith/` (modular JS for wraith-specific features)

7. **CSS**
   - `css/wraith_char_create.css` (page-specific styles)

### 5.2 Files to Modify

1. **Database Connection**
   - Ensure `connect.php` supports new table

2. **Navigation/Header**
   - Add Wraith character creation link
   - Add Wraith admin panel link

### 5.3 Database Migration Script
- `database/create_wraith_characters_table.php`
- Creates `wraith_characters` table with all required columns
- Includes indexes for performance
- Reversible (includes DROP TABLE for rollback)

---

## 6. IMPLEMENTATION CHECKLIST

### Phase 1: Schema & Database
- [ ] Create `wraith_character.json` schema file
- [ ] Create database migration script
- [ ] Test database table creation
- [ ] Verify JSON schema matches database structure

### Phase 2: Backend API
- [ ] Create `save_wraith_character.php`
- [ ] Create `view_wraith_character_api.php`
- [ ] Test save/load functionality
- [ ] Implement validation

### Phase 3: Frontend Form
- [ ] Create `wraith_char_create.php` (Page 1: Identity)
- [ ] Implement Page 2: Traits
- [ ] Implement Page 3: Shadow Sheet
- [ ] Implement Page 4: Pathos/Corpus
- [ ] Implement Page 5: Metadata
- [ ] Create JavaScript handlers
- [ ] Create CSS styling

### Phase 4: Table View
- [ ] Create admin panel table view
- [ ] Implement sorting
- [ ] Implement filtering
- [ ] Add view/edit/delete actions

### Phase 5: Testing & Refinement
- [ ] Test character creation flow
- [ ] Test character editing
- [ ] Test table view functionality
- [ ] Verify data persistence
- [ ] Cross-browser testing
- [ ] Mobile responsiveness

---

## 7. COMPATIBILITY NOTES

### 7.1 Maintain Existing Format Style
- Use same object-based section organization
- Use arrays of objects for multi-values
- Use same naming conventions where possible
- JSON structure matches existing `character.json` patterns

### 7.2 Reversible Design
- All changes in separate files/tables
- No modification to existing VtM character system
- Can be disabled/removed without affecting VtM system

### 7.3 Integration Points
- Share common UI components (modals, forms)
- Reuse existing authentication/authorization
- Use same database connection patterns
- Follow existing code style and conventions

---

## 8. TECHNICAL SPECIFICATIONS

### 8.1 Technology Stack
- **Backend**: PHP 8.4+ (strict typing)
- **Database**: MySQL (utf8mb4_unicode_ci)
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **Data Format**: JSON

### 8.2 Code Standards
- Follow existing PHP coding standards
- Use prepared statements for all DB queries
- Implement proper error handling
- Follow Bootstrap best practices
- External CSS/JS files (no inline styles/scripts)

### 8.3 Security
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS prevention (proper escaping)
- Authentication/authorization checks

---

## 9. NEXT STEPS

1. **Review and Approve Plan** - User reviews this plan
2. **Plan Mode Execution** - After approval, implement in Plan Mode only
3. **Iterative Development** - Implement phase by phase
4. **Testing** - Continuous testing throughout
5. **Documentation** - Update project documentation

---

## 10. REFERENCES

- Existing VtM Schema: `reference/Characters/character.json`
- Existing Character Creation: `lotn_char_create.php`
- Existing Save Handler: `includes/save_character.php`
- Existing Admin Panel: `admin/admin_panel.php`
- Database Columns: `database/character_table_columns.md`

---

**Plan Created**: 2025-01-24  
**Status**: Awaiting Approval  
**Estimated Implementation Time**: 3-5 days (depending on complexity)

