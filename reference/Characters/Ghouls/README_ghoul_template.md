# Ghoul Character Sheet Template – Database Mapping

Use **ghoul_template.json** as the base for new ghoul characters. Fill in fields and import with:

```bash
php tools/repeatable/import_ghouls.php path/to/your_ghoul.json
```

The importer writes to two tables: **characters** (main row) and **ghouls** (ghoul overlay).

---

## Required

- **character_name** – Required. Used for characters table and for upsert lookup. Can also be set via `name.full`.

---

## Characters Table

These template keys map to `characters` columns (only columns that exist on your schema are written):

| Template key | characters column | Notes |
|--------------|-------------------|--------|
| character_name | character_name | Required |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| pc | pc | Default: 0 |
| status | status | |
| camarilla_status | camarilla_status | |
| clan | clan | Forced to "Ghoul" by importer |
| nature | nature | Also taken from mechanics.nature |
| demeanor | demeanor | Also taken from mechanics.demeanor |
| derangement | derangement | |
| concept | concept | Also taken from mechanics.concept |
| appearance | appearance | |
| biography | biography | |
| notes | notes | If object, flattened from one_liner + expanded + st_only |
| custom_data | custom_data | JSON |
| character_image | character_image | |
| generation | generation | |
| sire | sire | |
| experience_total | experience_total | |
| experience_unspent | experience_unspent | |
| spent_xp | spent_xp | |
| willpower_current | willpower_current | |
| willpower_permanent | willpower_permanent | |
| blood_pool_current | blood_pool_current | |
| blood_pool_max | blood_pool_max | |
| path_rating | path_rating | |
| morality_path | morality_path | |
| conscience | conscience | |
| self_control | self_control | |
| courage | courage | |
| equipment | equipment | |
| health_status | health_status | |

Any other top-level key in the JSON that matches a column name in `characters` is written. User id is set by the importer (defaults in code; see import_ghouls.php).

---

## Ghouls Table

The importer sets **character_id** from the inserted/updated character. These keys map to `ghouls` columns (only if the column exists):

| Template key | ghouls column | Notes |
|--------------|----------------|--------|
| domitor_character_id or domitor.domitor_character_id | domitor_character_id | FK to characters.id (Kindred). Default in importer: 139 |
| blood_bond_stage or bond.blood_bond_stage | blood_bond_stage | 0–3 |
| is_active | is_active | |
| retainer_level | retainer_level | |
| vitae_last_fed_at | vitae_last_fed_at | |
| vitae_frequency | vitae_frequency | |
| first_fed_at | first_fed_at | |
| is_family | is_family | |
| loyalty | loyalty | |
| independent_will | independent_will | |
| escape_risk | escape_risk | |
| risk_level | risk_level | |
| discipline_cap_override | discipline_cap_override | |
| addiction_severity | addiction_severity | |
| withdrawal_effects | withdrawal_effects | |
| domitor_control_style | domitor_control_style | |
| handler_notes | handler_notes | |
| masquerade_liability | masquerade_liability | |
| notes | notes | Overlay notes if column exists |
| custom_data | custom_data | Ghoul-specific JSON if column exists |

---

## Nested Structures

- **name** – Importer uses `name.full` as character_name if character_name is empty.
- **domitor** – Importer reads `domitor.domitor_character_id` (or top-level domitor_character_id) for the Kindred FK.
- **bond** – Importer reads `bond.blood_bond_stage` for ghouls.blood_bond_stage.
- **mechanics** – Importer copies mechanics.nature, mechanics.demeanor, mechanics.concept to top-level for characters.
- **notes** – If notes is an object with one_liner, expanded, st_only, the importer flattens it to a single text string for characters.notes.

---

## After Import

- **Admin panel**: Ghouls appear in `admin/ghoul_admin_panel.php` (characters with clan = Ghoul and a row in ghouls).
- **View/Edit**: Use view character API and lotn_char_create.php; ghoul overlay is loaded when clan is Ghoul.
- **Abilities / disciplines / etc.**: Stored in character_abilities, character_disciplines, and related tables; the template’s mechanics block is for reference and custom_data. Full sync from template mechanics into those tables would require a separate script or the normal character edit flow.
