# Rules violations tracker

Tracks files that violate:
- **Rule 1**: [php-explicit-error-handling-no-silent-fail.mdc](../.cursor/rules/php-explicit-error-handling-no-silent-fail.mdc)
- **Rule 2**: [ui-task-vbn-style-check-before-done.mdc](../.cursor/rules/ui-task-vbn-style-check-before-done.mdc)
- **Rule 3**: [api-json-response-envelope.mdc](../.cursor/rules/api-json-response-envelope.mdc)

Status: `Open` | `Resolved`  
**Last compliance pass:** All listed items below resolved (charset, error key, envelope, visible errors, form-text/opacity removed).

---

## Rule 1 – PHP error handling (no silent fail)

| File | Location | Issue | Status |
|------|----------|--------|--------|
| admin/admin_items.php | 48–50 | `catch` – now logs + shows `$item_categories_error` notice | Resolved |
| admin/admin_panel.php | 201–203, 251–253, 350–352 | `catch` – now logs + shows `$stats_error` / `$questionnaire_error` / `$characters_error` | Resolved |
| admin/mortal_admin_panel.php | 36–38 | `catch` – now logs + shows `$rows_error` in table | Resolved |
| admin/demon_admin_panel.php | 37–39 | Same | Resolved |
| admin/supernatural_entities_admin_panel.php | 37–39 | Same | Resolved |
| admin/questionnaire_admin.php | 65–67 | `catch` – now logs + shows `$questions_error` notice | Resolved |
| agents/music_agent/ui_add_asset.php | 138–140, 146–149 | `catch` logs; `$error` already displayed in UI | Resolved |
| agents/music_agent/ui_add_binding.php | 135–137, 143–147 | Same | Resolved |
| agents/music_agent/ui_browse.php | 78–80, 128–133 | Same | Resolved |
| agents/music_agent/ui_settings.php | 120–122, 128–132 | Same | Resolved |
| index.php | 146–148, 325–327 | `catch` – now sets `$stats_error` / `$player_chars_error` and shows alert | Resolved |

*Note: API endpoints using `message` for failure are listed under Rule 3; fixing envelope satisfies Rule 1 for those.*

---

## Rule 2 – UI (no form-text, no opacity on text)

| File | Line(s) | Issue | Status |
|------|---------|--------|--------|
| admin/admin_equipment.php | 191 | form-text + opacity-75 → mt-1 small text-light | Resolved |
| admin/admin_items.php | 312, 347 | form-text + opacity-75 → mt-1 small text-light | Resolved |
| admin/admin_panel.php | 403 | opacity-75 → text-light | Resolved |
| admin/boon_ledger.php | 52, 120 | opacity-75 → text-light | Resolved |
| admin/boon_agent_viewer.php | 345, 351, 357, 363, 370 | opacity-75 → text-light small | Resolved |
| admin/music_debug.php | 180 | opacity-75 → text-light | Resolved |
| admin/rumor_viewer.php | (multiple) | opacity-75 → text-light | Resolved |
| agents/character_agent/view_reports.php | 66, 76, 115, 125 | opacity-75 → text-light | Resolved |
| agents/character_agent/generate_reports.php | 509, 516, 523 | form-text removed, kept text-danger | Resolved |
| agents/rituals_agent/rituals_display.php | 398 | opacity-75 → text-light small | Resolved |
| agents/ability_agent/abilities_display.php | 325 | opacity-75 → text-light | Resolved |
| agents/paths_agent/paths_display.php | 373 | opacity-75 → text-light | Resolved |
| agents/music_agent/index.php | 57, 65, 73, 81 | opacity-75 → text-light | Resolved |
| agents/music_agent/ui_add_asset.php | 188, 196, 266, 284 | form-text → mt-1 small text-light | Resolved |
| agents/music_agent/ui_add_binding.php | 218, 272, 285 | form-text → mt-1 small text-light | Resolved |
| agents/music_agent/ui_browse.php | (multiple) | opacity-75 → text-light | Resolved |
| agents/music_agent/ui_settings.php | 175, 195, 221, 232, 243 | form-text + opacity-75 → text-light | Resolved |
| agents/boon_agent/config/index.php | 80 | opacity-75 → text-light small | Resolved |
| includes/character_view_modal.php | 1197, 1202 | opacity-75 → text-light | Resolved |
| includes/position_view_modal.php | (multiple) | opacity-75 → text-light | Resolved |
| questionnaire.php | 108, 120 | opacity-75 → text-light | Resolved |
| register.php | 41 | opacity-75 → text-light | Resolved |
| reference/world/index.php | (multiple) | opacity-75 → text-light | Resolved |
| phoenix_map.php | 108 | opacity-75 → text-light small | Resolved |
| index.php | 263 | card opacity-50 kept (card state, not text) | Note only |
| css/admin-agents.css | 176 | .alert-blood .opacity-75 rule removed | Resolved |
| css/global.css | 13 | Comment updated: no opacity on text | Resolved |
| css/bootstrap-overrides.css | 237 | .opacity-50 on card (non-text; allowed) | Note only |

---

## Rule 3 – API JSON envelope

| File | Issue | Status |
|------|--------|--------|
| **Content-Type** (all api_*.php, api_items, api_get_characters, includes/api_get_character_names) | charset=utf-8 added | Resolved |
| admin/api_add_position.php | Failure `message` → `error`; charset | Resolved |
| admin/api_paths.php | Failure `message` → `error`; charset | Resolved |
| admin/api_boons.php | Failure `message` → `error`; charset | Resolved |
| admin/api_sire_childe.php | Failure `message` → `error`; charset | Resolved |
| admin/api_npc_briefing.php | Failure `message` → `error`; charset | Resolved |
| admin/api_character_images_audit.php | Failure `message` → `error`; charset | Resolved |
| admin/api_admin_locations_crud.php | outputJson() charset + JSON_UNESCAPED_UNICODE | Resolved |
| agents/ability_agent/api_view_ability.php | Failure `message` → `error`; charset | Resolved |
| agents/paths_agent/api_view_path.php | Failure `message` → `error`; charset | Resolved |
| agents/rituals_agent/api_view_ritual.php | Failure `message` → `error`; charset | Resolved |
| agents/boon_agent/api_get_boon_report.php | Success wrapped in `success`+`data`; failures `success: false`+`error`; charset | Resolved |
| agents/blood_bonds_agent/api_get_bond_context.php | Success wrapped in `success`+`data`; failures `success: false`; charset; exit | Resolved |
| includes/api_get_character_names.php | charset; exit after echo; JSON_UNESCAPED_UNICODE | Resolved |

---

## Resolved

All items from the last compliance pass are marked Resolved in the sections above.
