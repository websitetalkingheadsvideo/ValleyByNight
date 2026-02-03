# Wraith Character Sheet Template – Database Mapping

Use **wraith_character_template.json** as the base for new Wraith characters. Fill in fields and import with:

**CLI:**
```bash
php tools/repeatable/php/database-tools/import_wraith_characters.php path/to/your_wraith.json
```

**Web:**  
`tools/repeatable/php/database-tools/import_wraith_characters.php?file=your_wraith.json`  
Or import all (except the template): `?all=1`

Wraiths use a **single table**: **wraith_characters**. (There is no separate `characters` row; the wraith admin panel and view/edit use this table only.)

---

## Required

- **character_name** – Required. Used for upsert lookup (update if exists, insert if new).

---

## wraith_characters Table

All template keys below map to `wraith_characters` columns. The importer writes only the keys it knows; JSON/object fields are stored as JSON in the matching column.

| Template key | wraith_characters column | Notes |
|--------------|---------------------------|--------|
| character_name | character_name | Required |
| shadow_name | shadow_name | |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| nature | nature | |
| demeanor | demeanor | |
| concept | concept | |
| circle | circle | |
| guild | guild | |
| legion_at_death | legion_at_death | |
| date_of_death | date_of_death | DATE; ISO YYYY-MM-DD or null |
| cause_of_death | cause_of_death | |
| pc | pc | 0 = NPC, 1 = PC |
| appearance | appearance | |
| ghostly_appearance | ghostly_appearance | |
| biography | biography | |
| notes | notes | |
| equipment | equipment | |
| character_image | character_image | |
| status | status | active, inactive, archived, dead, missing |
| timeline | timeline | JSON |
| personality | personality | JSON |
| traits | traits | JSON |
| negativeTraits | negativeTraits | JSON |
| abilities | abilities | JSON |
| specializations | specializations | JSON |
| fetters | fetters | JSON |
| passions | passions | JSON |
| arcanoi | arcanoi | JSON |
| backgrounds | backgrounds | JSON |
| backgroundDetails | backgroundDetails | JSON |
| willpower_permanent | willpower_permanent | INT, default 5 |
| willpower_current | willpower_current | INT, default 5 |
| pathos_corpus | pathos_corpus | JSON |
| shadow | shadow | JSON |
| harrowing | harrowing | JSON |
| merits_flaws | merits_flaws | JSON |
| status_details | status_details | JSON |
| relationships | relationships | JSON |
| artifacts | artifacts | JSON |
| custom_data | custom_data | JSON |
| actingNotes | actingNotes | |
| agentNotes | agentNotes | |
| health_status | health_status | |
| experience_total | experience_total | INT |
| spent_xp | spent_xp | INT |
| experience_unspent | experience_unspent | INT |
| shadow_xp_total | shadow_xp_total | INT |
| shadow_xp_spent | shadow_xp_spent | INT |
| shadow_xp_available | shadow_xp_available | INT |

**Set by database (do not set in template for new characters):**  
`id`, `user_id` (importer uses IMPORT_USER_ID = 1), `created_at`, `updated_at`.

---

## After Import

- **Admin panel:** Wraiths appear in `admin/wraith_admin_panel.php` (all rows from wraith_characters).
- **View/Edit:** Use the wraith character view/edit endpoints; character data is read from wraith_characters only.
- **Template file:** The importer skips files named `wraith_character_template.json` when using `?all=1`.
