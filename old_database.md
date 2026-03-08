# Legacy MySQL Usage Audit

Generated on 2026-03-06.

**Status (post-migration):** MySQL has been removed. `includes/connect.php` is a stub (loads Supabase only; `$conn = null`; `db_*` throw). All application and admin critical paths use `includes/supabase_client.php`. Scripts under `database/`, `tools/repeatable/`, and some admin/agent files still `require connect.php` and will throw if they call `$conn` or `db_*` until migrated to Supabase.

This file lists PHP files that were using legacy MySQL patterns; many have since been migrated.

## Connection files already patched in this pass

- `agents/style_agent/db.php` (removed hardcoded legacy DB credentials, now uses `includes/connect.php`)
- `agents/character_agent/db.php` (removed hardcoded legacy DB credentials, now uses `includes/connect.php`)

## Runtime files migrated to Supabase REST (this session)

- `includes/supabase_client.php` (new shared Supabase REST client)
- `includes/verify_role.php` (role check now uses Supabase `users` table)
- `includes/api_get_character_names.php`
- `api_get_characters.php`
- `api_items.php`
- `load_character.php`
- `admin/view_character_api.php`
- `includes/save_character.php`
- `includes/save_wraith_character.php`
- `includes/login_process.php`
- `includes/register_process.php`
- `includes/update_account.php`
- `includes/camarilla_positions_helper.php`
- `delete_character.php`
- `export_havens.php`
- `debug_login.php`
- `connect_to_style_agent_mcp.php`
- `index.php`
- `lotn_char_create.php`
- `questionnaire.php`
- `wraith_char_create.php`
- `phoenix_map.php`
- `query_rulebooks.php`
- `query_rulebooks_simple.php`
- `reset_admin_password.php`
- `rituals_view.php`
- `upload.php`
- `admin/list_positions.php`
- `admin/view_position_api.php`
- `admin/api_add_position.php`
- `admin/update_position_api.php`
- `admin/delete_position_api.php`
- `admin/view_demon_character_api.php`
- `admin/view_mage_character_api.php`
- `admin/view_mortal_character_api.php`
- `admin/view_supernatural_entity_api.php`
- `admin/view_werewolf_character_api.php`
- `admin/view_wraith_character_api.php`
- `admin/delete_character_api.php`
- `admin/delete_demon_character_api.php`
- `admin/delete_mage_character_api.php`
- `admin/delete_mortal_character_api.php`
- `admin/delete_supernatural_entity_api.php`
- `admin/delete_werewolf_character_api.php`
- `admin/demon_admin_panel.php`
- `admin/mage_admin_panel.php`
- `admin/mortal_admin_panel.php`
- `admin/supernatural_entities_admin_panel.php`
- `admin/werewolf_admin_panel.php`
- `admin/wraith_admin_panel.php`
- `admin/ghoul_admin_panel.php`
- `admin/api_equipment.php`
- `admin/api_locations.php`
- `admin/questionnaire_admin.php`

## Still using MySQL in app/admin/includes/agents

### Root-level app pages

### `includes/`

- `includes/connect.php`

### `admin/`

- `admin/add_alistaire_nosferatu_primogen.php`
- `admin/add_eddy_valiant_scourge.php`
- `admin/add_lesser_harpy_position.php`
- `admin/add_missing_positions.php`
- `admin/admin_equipment.php`
- `admin/admin_items.php`
- `admin/admin_npc_briefing.php`
- `admin/admin_sire_childe.php`
- `admin/admin_sire_childe_enhanced.php`
- `admin/api_admin_add_equipment.php`
- `admin/api_admin_equipment_assignments.php`
- `admin/api_admin_equipment_crud.php`
- `admin/api_admin_location_assignments.php`
- `admin/api_admin_locations_crud.php`
- `admin/api_analyze_sire_relationships.php`
- `admin/api_boons.php`
- `admin/api_delete_location_simple.php`
- `admin/api_npc_briefing.php`
- `admin/api_sire_childe.php`
- `admin/api_update_map_position.php`
- `admin/api_update_npc_notes.php`
- `admin/boon_agent_viewer.php`
- `admin/boon_ledger.php`
- `admin/cleanup_duplicate_positions.php`
- `admin/cleanup_duplicate_primogen.php`
- `admin/debug_misfortune_link.php`
- `admin/find_alexandra_chen.php`
- `admin/find_misfortune_broad.php`
- `admin/find_misfortune_character.php`
- `admin/fix_item_names_case.php`
- `admin/import_prop_deck_items.php`
- `admin/migrate_item_categories.php`
- `admin/update_item_images.php`

### `agents/`

- `agents/ability_agent/tests/test_integration.php`
- `agents/boon_agent/src/BoonAgent.php`
- `agents/boon_agent/src/BoonAnalyzer.php`
- `agents/boon_agent/src/BoonValidator.php`
- `agents/boon_agent/src/ReportGenerator.php`
- `agents/character_agent/characters.php`
- `agents/character_agent/server.php`
- `agents/coterie_agent/api_coterie_crud.php`
- `agents/influence_agent/index.php`
- `agents/laws_agent/import_books.php`
- `agents/laws_agent/setup_api.php`
- `agents/laws_agent/setup_rag_database.php`
- `agents/music_agent/import_music_assets.php`
- `agents/paths_agent/import_paths_json.php`
- `agents/paths_agent/src/CharacterPathsRepository.php`
- `agents/paths_agent/src/PathPowersRepository.php`
- `agents/paths_agent/src/PathRepository.php`
- `agents/paths_agent/tests/PathsAgentTest.php`
- `agents/paths_agent/tests/test_integration.php`
- `agents/rituals_agent/src/CharacterRitualsRepository.php`
- `agents/rituals_agent/src/RitualRepository.php`
- `agents/rituals_agent/src/RulesRepository.php`
- `agents/rituals_agent/tests/RitualsAgentTest.php`
- `agents/style_agent/server.php`

## Still using MySQL in migration/utility scripts

### `database/` (migration + maintenance scripts)

All of these still use MySQL (`mysqli_*` / `mysql_*`):  
`add_character_rituals_fk.php`, `add_coterie_history_reason_columns.php`, `add_location_blueprint_moodboard_fields.php`, `add_none_derangement.php`, `add_pc_haven_field.php`, `add_primogen_assignments.php`, `add_rumor_name_column.php`, `add_wraith_attributes_column.php`, `assign_helena_crowly_primogen.php`, `audit_paths_completion.php`, `audit_rituals_agent_spotcheck.php`, `audit_rituals_checklist.php`, `audit_rituals_completeness.php`, `audit_rituals_inventory.php`, `audit_rituals_qa_gates.php`, `backfill_abilities_from_grapevine.php`, `check_blueprint_moodboard_columns.php`, `check_boons_table.php`, `check_character_images.php`, `check_location_types.php`, `check_missing_haven_files.php`, `check_missing_locations.php`, `check_ward_spirits.php`, `create_abilities_table.php`, `create_backgrounds_master_table.php`, `create_demon_characters_table.php`, `create_derangements_table.php`, `create_influence_effects_table.php`, `create_influences_table.php`, `create_location_ownership_table.php`, `create_mage_characters_table.php`, `create_mcp_style_packs_table.php`, `create_merits_flaws_tables.php`, `create_mortal_characters_table.php`, `create_nature_demeanor_table.php`, `create_supernatural_entities_table.php`, `create_werewolf_characters_table.php`, `create_wraith_characters_table.php`, `diagnose_character_rituals_matching.php`, `extract_and_update_powers.php`, `extract_paths_content.php`, `find_similar_ritual_names.php`, `find_tailored_dreams_owner.php`, `fix_derangement_display_order.php`, `fix_eddy_roland_relationship.php`, `fix_mage_characters_add_id_user_id.php`, `fix_mcp_path_to_agents.php`, `fix_player_names.php`, `fix_victoria_player_name.php`, `generate_character_summary.php`, `generate_cw_whitford_boons.php`, `generate_misfortune_boons.php`, `generate_npc_haven_coverage.php`, `generate_paths_update_data.php`, `generate_world_summaries.php`, `import_giovanni_npcs.php`, `import_new_characters.php`, `import_primogen_characters.php`, `import_rumors.php`, `import_trace_element.php`, `list_all_havens.php`, `merge_history_into_biography.php`, `replace_mage_characters_table.php`, `research_challenge_types.php`, `restore_main_locations.php`, `show_mage_characters_columns.php`, `update_adrian_image.php`, `update_character_images.php`, `update_dorikhan_image.php`, `update_from_wikidot.php`, `update_jennifer_image.php`, `update_lila_image.php`, `update_mcp_path_to_agent.php`, `update_merits_flaws_costs.php`, `update_new_character_images.php`, `update_sarah_image.php`, `update_trace_evan_lila_images.php`, `update_travis_image.php`, `update_victoria_appearance.php`, `update_victoria_image.php`, `verify_character_rituals_fk.php`, `verify_image_files.php`, `verify_wikidot_updates.php`.

### `tools/` (repeatable + sync scripts)

All of these still use MySQL:  
`tools/compare_abilities_json_to_db.php`, `tools/sync_abilities_from_xml.php`, `tools/sync_archetypes_from_xml.php`, `tools/repeatable/add_lesser_harpy_position.php`, `tools/repeatable/analyze_character_images.php`, `tools/repeatable/analyze_nosferatu_for_harpy_team.php`, `tools/repeatable/assign_lesser_harpy_positions.php`, `tools/repeatable/backfill_character_abilities.php`, `tools/repeatable/backfill_character_appearance.php`, `tools/repeatable/backfill_character_backgrounds.php`, `tools/repeatable/backfill_character_biography.php`, `tools/repeatable/backfill_character_field.php`, `tools/repeatable/backfill_character_merits_flaws.php`, `tools/repeatable/backfill_character_nature.php`, `tools/repeatable/backfill_character_notes.php`, `tools/repeatable/backfill_character_traits.php`, `tools/repeatable/backfill_disciplines.php`, `tools/repeatable/character-data/index.php`, `tools/repeatable/character-data/quick-edit.php`, `tools/repeatable/character-data/sync_abilities_from_json.php`, `tools/repeatable/check_ability_categories.php`, `tools/repeatable/check_core_phreak.php`, `tools/repeatable/check_disciplines.php`, `tools/repeatable/check_ghouls_table.php`, `tools/repeatable/check_julien.php`, `tools/repeatable/check_phreak.php`, `tools/repeatable/check_roxanne.php`, `tools/repeatable/export_character_json.php`, `tools/repeatable/extract_character_json.php`, `tools/repeatable/fix_ability_categories.php`, `tools/repeatable/generate_abilities_for_character.php`, `tools/repeatable/generate_character_traits.php`, `tools/repeatable/generate_traits_simple.php`, `tools/repeatable/get_roxanne_abilities.php`, `tools/repeatable/import_character.php`, `tools/repeatable/insert_julien_disciplines.php`, `tools/repeatable/insert_layla_disciplines.php`, `tools/repeatable/php/data-tools/check_books_when_ready.php`, `tools/repeatable/php/data-tools/generate_project_summary.php`, `tools/repeatable/php/database-tools/audit_rituals_duplicates.php`, `tools/repeatable/php/database-tools/audit_rituals_sources.php`, `tools/repeatable/php/database-tools/db_cleanup_0863.php`, `tools/repeatable/php/database-tools/export_npcs.php`, `tools/repeatable/php/database-tools/import_characters.php`, `tools/repeatable/php/database-tools/import_demon_characters.php`, `tools/repeatable/php/database-tools/import_locations.php`, `tools/repeatable/php/database-tools/import_mage_characters.php`, `tools/repeatable/php/database-tools/import_mortal_characters.php`, `tools/repeatable/php/database-tools/import_supernatural_entities.php`, `tools/repeatable/php/database-tools/import_werewolf_characters.php`, `tools/repeatable/php/database-tools/import_wraith_characters.php`, `tools/repeatable/php/database-tools/update_paths_completion.php`, `tools/repeatable/remove_optional_abilities.php`, `tools/repeatable/remove_talon_position.php`, `tools/repeatable/sire.php`, `tools/repeatable/test_import_simple.php`, `tools/repeatable/test_roland_api.php`, `tools/repeatable/test_trait_insert.php`, `tools/repeatable/update_abilities_from_json.php`, `tools/repeatable/update_character_abilities.php`, `tools/repeatable/update_marisol_vega.php`, `tools/repeatable/verify_abilities.php`.

### root tests using MySQL

- `test_actual_connection.php`
- `test_connect_logic.php`
- `test_exact_connection.php`
- `test_local_setup.php`
- `test_mcp_loading.php`
- `test_old_password.php`
- `test_password_read.php`

## Summary

- Direct hardcoded old MySQL credentials were removed from:
  - `agents/style_agent/db.php`
  - `agents/character_agent/db.php`
- The rest of the files above still need full Supabase migration work.
