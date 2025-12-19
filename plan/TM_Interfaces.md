# TM Interfaces Reference

This document provides links to the interface/viewer files for each Task Master plan (TM-01 through TM-09) that was worked on today.

## TM-01 — Rituals Agent: Core Integration

**Interface:** [`agents/rituals_agent/rituals_display.php`](http://localhost/agents/rituals_agent/rituals_display.php)

Displays all rituals from the `rituals_master` table in a sortable table format. Shows key attributes: type, level, name, description, source.

---

## TM-02 — Paths Database Completion

**Interface:** ❌ No interface available

This task involved database completion scripts and audit tools. No user-facing interface was created. Files created include:
- `database/audit_paths_completion.php`
- `database/update_paths_completion.php`
- `database/extract_and_update_powers.php`

---

## TM-03 — Paths Agent Core Implementation

**Interface:** [`agents/paths_agent/paths_display.php`](http://localhost/agents/paths_agent/paths_display.php)

**API Endpoint:** [`agents/paths_agent/api_view_path.php`](http://localhost/agents/paths_agent/api_view_path.php)

Displays all paths from the `paths_master` table in a sortable table format. Shows key attributes: type, name, description, source. Features include:
- Sortable columns (type, name, description, source)
- Real-time search across all fields
- Statistics cards showing total paths and counts by type
- Detailed path view modal with all powers organized by level
- Power details include system text, challenge type, and challenge notes

---

## TM-04 — Character Rituals FK Migration

**Interface:** ❌ No interface available

This task involved database migration scripts to add foreign key relationships. No user-facing interface was created. Files include:
- `database/add_character_rituals_fk.php`
- `database/verify_character_rituals_fk.php`

---

## TM-05 — Ability Agent Implementation

**Interface:** [`agents/ability_agent/abilities_display.php`](http://localhost/agents/ability_agent/abilities_display.php)

**API Endpoint:** [`agents/ability_agent/api_view_ability.php`](http://localhost/agents/ability_agent/api_view_ability.php)

Displays all abilities from the `abilities` table in a sortable table format. Shows key attributes: category, name, description, min_level, max_level. Features include:
- Sortable columns (category, name, description, min_level, max_level)
- Real-time search across all fields
- Statistics cards showing total abilities and counts by category
- Detailed ability view modal with all ability information

---

## TM-06 — Discipline Agent Implementation

**Interface:** [`agents/discipline_agent/discipline_test.php`](http://localhost/agents/discipline_agent/discipline_test.php)

Test page that shows a list of disciplines for a character. This is a test/debugging interface for the Discipline Agent.

---

## TM-07 — Ritual Data Audit

**Interface:** [`agents/rituals_agent/rituals_display.php`](http://localhost/agents/rituals_agent/rituals_display.php)

Uses the same display interface as TM-01. The audit process created several database audit scripts:
- `database/audit_rituals_inventory.php`
- `database/audit_rituals_completeness.php`
- `database/audit_rituals_sources.php`
- `database/audit_rituals_duplicates.php`
- `database/audit_rituals_qa_gates.php`
- `database/audit_rituals_agent_spotcheck.php`
- `database/audit_rituals_checklist.php`

---

## TM-08 — Phoenix-localized Clanbooks and Viewer

**Interface:** [`reference/docs/clanbook_viewer.php`](http://localhost/reference/docs/clanbook_viewer.php)

Allows selection and viewing of Phoenix-localized clanbooks. Provides a viewer for clan-specific documentation.

---

## TM-09 — Music System for NPCs & Locations

**Interface:** [`agents/music_agent/index.php`](http://localhost/agents/music_agent/index.php)

Main interface for the Music Registry Admin system. Additional UI components include:
- `agents/music_agent/ui_browse.php` - Browse music assets
- `agents/music_agent/ui_add_asset.php` - Add new music assets
- `agents/music_agent/ui_add_cue.php` - Add playback cues
- `agents/music_agent/ui_add_binding.php` - Add music bindings
- `agents/music_agent/ui_settings.php` - Settings management

---

## Summary

- **With Interfaces:** TM-01, TM-03, TM-05, TM-06, TM-07, TM-08, TM-09
- **No Interface:** TM-02, TM-04

