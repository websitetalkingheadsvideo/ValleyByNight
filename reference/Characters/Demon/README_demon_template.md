# Demon Character Sheet Template – Database Mapping

Use **demon_character_template.json** as the base for Demon: The Fallen characters. Fill in fields and import with:

**CLI:**
```bash
php tools/repeatable/php/database-tools/import_demon_characters.php path/to/your_demon.json
```

**Web:**  
`tools/repeatable/php/database-tools/import_demon_characters.php?file=your_demon.json`  
Or import all (except the template): `?all=1`

Demons use a **single table**: **demon_characters**. (No row in the main `characters` table.)

---

## Required

- **character_name** – Required. Used for upsert lookup (update if exists, insert if new).

---

## Demon-specific terms

- **house** – House (Devil, Serpent, Whispers, Scourge, Malefactor, etc.).
- **faction** – Faction (Luciferan, Faustian, Raveners, Cryptics, Reconcilers).
- **cover** – Mortal host/identity the demon wears.
- **host_form_appearance** – Appearance in host body.
- **torment** – Permanent and current torment (0–10).
- **faith** – Permanent and current faith (power from belief).
- **apocalyptic_form** – Object: description, traits, visage (true form); JSON.
- **evocations** – Array of House powers (name, house, level, effect); JSON.
- **lores** – Array of celestial lores; JSON.
- **thralls_pacts** – Thralls and pacts; JSON.

---

## demon_characters table

| Template key | demon_characters column | Notes |
|--------------|-------------------------|--------|
| character_name | character_name | Required |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| nature | nature | |
| demeanor | demeanor | |
| concept | concept | |
| house | house | |
| faction | faction | |
| cover | cover | |
| pc | pc | 0 = NPC, 1 = PC |
| appearance | appearance | |
| host_form_appearance | host_form_appearance | |
| biography | biography | |
| notes | notes | |
| equipment | equipment | |
| character_image | character_image | |
| status | status | active, inactive, archived, dead, missing |
| torment_permanent | torment_permanent | INT, 0–10 |
| torment_current | torment_current | INT |
| faith_permanent | faith_permanent | INT, 0–10 |
| faith_current | faith_current | INT |
| willpower_permanent | willpower_permanent | INT, default 5 |
| willpower_current | willpower_current | INT, default 5 |
| apocalyptic_form | apocalyptic_form | JSON |
| evocations | evocations | JSON |
| lores | lores | JSON |
| attributes | attributes | JSON (Physical/Social/Mental) |
| abilities | abilities | JSON |
| backgrounds | backgrounds | JSON |
| backgroundDetails | backgroundDetails | JSON |
| merits_flaws | merits_flaws | JSON |
| thralls_pacts | thralls_pacts | JSON |
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

- **Admin panel:** Add a demon admin panel (e.g. `admin/demon_admin_panel.php`) that queries `demon_characters` if you want a UI like the wraith/ghoul panels.
- **View/Edit:** Use demon-specific view/edit endpoints reading from `demon_characters` only.
- **Template file:** The importer skips `demon_character_template.json` when using `?all=1`.
