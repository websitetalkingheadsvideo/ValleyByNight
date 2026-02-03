# Mage Character Sheet Template – Database Mapping

Use **mage_character_template.json** as the base for Mage: The Ascension characters. Fill in fields and import with:

**CLI:**
```bash
php tools/repeatable/php/database-tools/import_mage_characters.php path/to/your_mage.json
```

**Web:**  
`tools/repeatable/php/database-tools/import_mage_characters.php?file=your_mage.json`  
Or import all (except the template): `?all=1`

Mages use a **single table**: **mage_characters**. (No row in the main `characters` table.)

---

## Required

- **character_name** – Required. Used for upsert lookup (update if exists, insert if new).

---

## Mage-specific terms

- **tradition** – Tradition or Convention (Order of Hermes, Virtual Adepts, Celestial Chorus, Orphan, etc.).
- **paradigm** – Worldview through which the mage works magic.
- **practice** – Magical practice (e.g. High Ritual, Techgnosis).
- **instruments** – Array of instruments/focus (stored as JSON).
- **spheres** – Object: sphere name → level (1–5). MtA: Correspondence, Entropy, Forces, Life, Matter, Mind, Prime, Spirit, Time.
- **arete** – Enlightenment rating (1–10); limits sphere dice pools.
- **quintessence** – Object: current, max, pool (or node/tass); JSON.
- **paradox** – Object: current, permanent, notes; JSON.
- **rotes** – Array of known rotes (name, spheres, effect, dice).

---

## mage_characters table

| Template key | mage_characters column | Notes |
|--------------|------------------------|--------|
| character_name | character_name | Required |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| nature | nature | |
| demeanor | demeanor | |
| concept | concept | |
| tradition | tradition | |
| paradigm | paradigm | |
| practice | practice | |
| instruments | instruments | JSON |
| pc | pc | 0 = NPC, 1 = PC |
| appearance | appearance | |
| biography | biography | |
| notes | notes | |
| equipment | equipment | |
| character_image | character_image | |
| status | status | active, inactive, archived, dead, missing |
| arete | arete | INT, 1–10 |
| willpower_permanent | willpower_permanent | INT, default 5 |
| willpower_current | willpower_current | INT, default 5 |
| quintessence | quintessence | JSON |
| paradox | paradox | JSON (paradox pool; column name is paradox) |
| spheres | spheres | JSON |
| attributes | attributes | JSON (Physical/Social/Mental) |
| abilities | abilities | JSON |
| rotes | rotes | JSON |
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

- **Admin panel:** Add a mage admin panel (e.g. `admin/mage_admin_panel.php`) that queries `mage_characters` if you want a UI like the wraith/ghoul panels.
- **View/Edit:** Use mage-specific view/edit endpoints reading from `mage_characters` only.
- **Template file:** The importer skips `mage_character_template.json` when using `?all=1`.
