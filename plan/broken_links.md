# Broken Links

- `includes/header.php:120` – Nav points to `/logout.php`, but no `logout.php` exists.
- `index.php:268` and `js/questionnaire.js:509` – Character links go to `character_sheet.php`, which isn’t in the repo.
- `agents/laws_agent/index.php:43` – Loads `/css/header.css`, but that stylesheet is missing.
- `js/admin_items.js` – Calls missing endpoints: `api_items.php` (line 67), `api_admin_items_crud.php` (278/572/595), and `api_admin_add_equipment.php` (511) not found under `admin/`.
- `js/admin_locations.js` – Calls missing endpoints: `api_admin_location_assignments.php` (536/692/815) and `api_delete_location_simple.php` (842), which are absent.
- `js/admin_npc_briefing.js` – Posts to `api_update_npc_notes.php` (137/351), but that file isn’t present.
- `js/modules/core/DataManager.js` – Requests `admin/api_disciplines.php` (283), `load_character.php` (321), `get_characters.php` (339), `delete_character.php` (357), and `upload.php` (375); none exist.
- `js/character_image.js:159` – Posts to `remove_character_image.php`, which isn’t in the repo.

Next steps: add/restore missing pages/APIs or repoint to existing equivalents, remove or reroute logout until a handler exists, re-run broken-link check after fixes.
