# Mortal Character Sheet Template – Database Mapping

Use **mortal_character_template.json** as the base for mortal characters with powers (psychics, sorcerers, hunters, mediums, ghouls, etc.). Fill in fields and import with:

**CLI:**
```bash
php tools/repeatable/php/database-tools/import_mortal_characters.php path/to/your_mortal.json
```

**Web:**  
`tools/repeatable/php/database-tools/import_mortal_characters.php?file=your_mortal.json`  
Or import all (except the template): `?all=1`

Mortals use a **single table**: **mortal_characters**. (No row in the main `characters` table.)

---

## Required

- **character_name** – Required. Used for upsert lookup (update if exists, insert if new).

---

## Mortal-specific terms

- **power_source** – Origin of powers: Psychic, Sorcerer, Linear Sorcerer, Hedge Mage, Medium, Ghoul, Hunter, Psychic Vampire, etc.
- **powers** – Array of supernatural abilities (name, level, path/discipline, description, effects). Stored as JSON.
- **attributes** – Physical, Social, Mental (same 3×3 as WoD).
- **abilities** – Skills/talents (JSON array).
- **willpower** – Permanent and current (no Rage/Gnosis; mortals use Willpower only).

---

## mortal_characters table

| Template key | mortal_characters column | Notes |
|--------------|--------------------------|--------|
| character_name | character_name | Required |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| nature | nature | |
| demeanor | demeanor | |
| concept | concept | |
| power_source | power_source | Psychic, Sorcerer, Hunter, etc. |
| pc | pc | 0 = NPC, 1 = PC |
| appearance | appearance | |
| biography | biography | |
| notes | notes | |
| equipment | equipment | |
| character_image | character_image | |
| status | status | active, inactive, archived, dead, missing |
| willpower_permanent | willpower_permanent | INT, default 5 |
| willpower_current | willpower_current | INT, default 5 |
| attributes | attributes | JSON (Physical/Social/Mental) |
| abilities | abilities | JSON |
| powers | powers | JSON |
| backgrounds | backgrounds | JSON |
| backgroundDetails | backgroundDetails | JSON |
| merits_flaws | merits_flaws | JSON |
| health_levels | health_levels | JSON |
| relationships | relationships | JSON |
| custom_data | custom_data | JSON |
| actingNotes | actingNotes | |
| agentNotes | agentNotes | |
| health_status | health_status | |
| experience_total | experience_total | INT |
| spent_xp | spent_xp | INT |
| experience_unspent | experience_unspent | INT |

**Set by database:** `id`, `user_id` (importer uses IMPORT_USER_ID = 1), `created_at`, `updated_at`.

---

## After import

- **Admin panel:** Add a mortal admin panel (e.g. `admin/mortal_admin_panel.php`) that queries `mortal_characters` if you want a UI like the wraith/ghoul panels.
- **View/Edit:** Use mortal-specific view/edit endpoints reading from `mortal_characters` only.
- **Template file:** The importer skips `mortal_character_template.json` when using `?all=1`.
