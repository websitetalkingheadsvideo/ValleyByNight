# Werewolf Character Sheet Template – How to Use

Use **werewolf_character_template.json** in this folder as the base for new Werewolf: The Apocalypse LARP characters (Mind's Eye Theatre / By Night Studios style). Fill in fields and import with:

**CLI:**
```bash
php tools/repeatable/php/database-tools/import_werewolf_characters.php path/to/your_werewolf.json
```

**Web:**  
`tools/repeatable/php/database-tools/import_werewolf_characters.php?file=your_werewolf.json`  
Or import all (except the template): `?all=1`

Werewolves use a **single table**: **werewolf_characters**. (No row in the main `characters` table.)

---

## Required

- **character_name** – Required. Used for upsert lookup (update if exists, insert if new).

---

## Werewolf-specific terms (from LARP / WtA)

- **Breed** – Homid (human-born) or Lupus (wolf-born).
- **Auspice** – Moon phase at first change: Ragabash (New), Theurge (Crescent), Philodox (Half), Galliard (Gibbous), Ahroun (Full).
- **Tribe** – Pack society (e.g. Black Furies, Get of Fenris, Glass Walkers, Shadow Lords, Silver Fangs).
- **Pack name / Pack totem** – Pack identity and spirit totem.
- **Rank** – Rank title from renown (e.g. Cliath, Fostern, Adren, Athro, Elder).
- **Renown** – Glory, Honor, Wisdom (tracked in `renown` JSON).
- **Rage** – Aggressive nature pool (current/permanent).
- **Gnosis** – Spiritual connection pool (current/permanent).
- **Forms** – Homid, Glabro, Crinos, Hispo, Lupus (stored in `forms` JSON).
- **Gifts / Rites** – Supernatural abilities and rituals (JSON arrays).
- **Touchstones** – Important relationships/anchors (JSON).
- **Harano / Hauglosk** – Depression and shame mechanics (JSON).

---

## werewolf_characters table

| Template key | werewolf_characters column | Notes |
|--------------|----------------------------|--------|
| character_name | character_name | Required |
| player_name | player_name | Default: NPC |
| chronicle | chronicle | Default: Valley by Night |
| nature | nature | |
| demeanor | demeanor | |
| concept | concept | |
| breed | breed | Homid / Lupus |
| auspice | auspice | Ragabash, Theurge, Philodox, Galliard, Ahroun |
| tribe | tribe | |
| pack_name | pack_name | |
| pack_totem | pack_totem | |
| rank | rank | |
| pc | pc | 0 = NPC, 1 = PC |
| appearance | appearance | |
| biography | biography | |
| notes | notes | |
| equipment | equipment | |
| character_image | character_image | |
| status | status | active, inactive, archived, dead, missing |
| rage_permanent | rage_permanent | INT |
| rage_current | rage_current | INT |
| gnosis_permanent | gnosis_permanent | INT |
| gnosis_current | gnosis_current | INT |
| willpower_permanent | willpower_permanent | INT, default 5 |
| willpower_current | willpower_current | INT, default 5 |
| attributes | attributes | JSON (Physical/Social/Mental) |
| abilities | abilities | JSON |
| forms | forms | JSON (Homid, Glabro, Crinos, Hispo, Lupus) |
| gifts | gifts | JSON |
| rites | rites | JSON |
| renown | renown | JSON (Glory, Honor, Wisdom) |
| backgrounds | backgrounds | JSON |
| backgroundDetails | backgroundDetails | JSON |
| merits_flaws | merits_flaws | JSON |
| touchstones | touchstones | JSON |
| harano_hauglosk | harano_hauglosk | JSON |
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

- **Admin panel:** Add a werewolf admin panel (e.g. `admin/werewolf_admin_panel.php`) that queries `werewolf_characters` if you want a UI like the wraith/ghoul panels.
- **View/Edit:** Use werewolf-specific view/edit endpoints reading from `werewolf_characters` only.
- **Template file:** The importer skips `werewolf_character_template.json` when using `?all=1`.

**Note:** The import script currently reads from `reference/Characters/Werewolves/`. To import files from this folder (`reference/Characters/Werewolf/`), run the script with the full path to your JSON file, e.g. `php .../import_werewolf_characters.php reference/Characters/Werewolf/YourCharacter.json`.
