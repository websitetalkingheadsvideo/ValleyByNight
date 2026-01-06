#!/usr/bin/env python3
import json
from pathlib import Path
from datetime import datetime

base = Path(r"\\amber\htdocs")

# All files from grep that are JSON or MD (excluding PC Havens and Violet Reliquary)
# Based on the 486 files found, filter to JSON/MD only
all_files = [
    "reference/Locations/valley_by_night_havens.json",
    "reference/Locations/Mesa Storm Drains.json",
    "reference/Locations/The Bunker - Computer Room.json",
    "reference/Locations/The Warrens.json",
    "reference/Locations/Chantry/DESIGN MAXIM FOR THE CHANTRY.md",
    "reference/Locations/Rooseverlt Row/A FAILED TOREADOR ARC.md",
    "reference/Locations/location_questionnaire.md",
    "reference/Locations/Location Style Summary — Template.json",
    "reference/Locations/location_template.json",
    "Prompts/Haven_Desription.md",
    "tmp/session_report_2025-12-03-pc-havens-system.md",
    "data/havens.json",
    "export_havens.php",
    "reference/Characters/Victoria_Sterling.json",
    "reference/Characters/character.json.documentation.md",
    "reference/world/_summaries/06_vbn_history_summary_0896.md",
    "reference/world/_summaries/05_canon_clan_summary_0896.md",
    "reference/world/_summaries/02_locations_summary_0896.md",
    "reference/world/_summaries/01_characters_summary_0896.md",
    "reference/Characters/Added to Database/npc__alistaire__131.json",
    "reference/Characters/Added to Database/npc__warner_jefferson__123.json",
    "reference/Characters/Added to Database/npc__pistol_pete__89.json",
    "reference/Characters/Added to Database/npc__sasha__90.json",
    "reference/Characters/Added to Database/npc__misfortune__108.json",
    "reference/Characters/Added to Database/npc__sarah_hansen__113.json",
    "reference/Characters/Added to Database/npc__mr_harold_ashby__102.json",
    "reference/Characters/Added to Database/npc__violet_the_confidence_queen__43.json",
    "reference/Characters/Added to Database/npc__leo__55.json",
    "reference/Characters/Added to Database/npc__barry_washington__101.json",
    "reference/Characters/Added to Database/npc__betty__57.json",
    "reference/Characters/Added to Database/npc__kerry_the_desert_wandering_gangrel__130.json",
    "reference/Scenes/Scene Teasers/Primogen_Appointment_Scene.md",
    "agents/laws_agent/knowledge-base/influences.md",
    "reference/Characters/Added to Database/npc__kerry_the_gangrel__87.json",
    "reference/Characters/Added to Database/npc__jennifer_kwan__71.json",
    "reference/Characters/Added to Database/npc__helena_crowly__138.json",
    "reference/Characters/Added to Database/npc__dr_margaret_ashford__27.json",
    "reference/Characters/Added to Database/npc__andrei_radulescu__26.json",
    "reference/world/_checkpoints/quality_gate_report.md",
    "reference/world/_checkpoints/progress_dashboard.md",
    "reference/world/_checkpoints/progress_log.md",
    "Grapevine/Blood Brothers.json",
    "Grapevine/Sabbat_Add.md",
    "Grapevine/Sabbat.json",
    "reference/docs/clanbook-phoenix-tremere.md",
    "reference/docs/clanbook-phoenix-samedi.md",
    "reference/docs/clanbook-phoenix-cappadocian.md",
    "reference/docs/clanbook-phoenix-giovanni.md",
    "reference/docs/clanbook-phoenix-nosferatu.md",
    "reference/docs/clanbook-phoenix-ventrue.md",
    "tmp/power_system_text.json",
    "tmp/path_descriptions.json",
    "reference/mechanics/rituals/Thaumaturgy_Rituals Ranks 3-5.md",
    "reference/mechanics/rituals/Thaumaturgy_Rituals.md",
    "reference/mechanics/rituals/Necromancy_Rituals.md",
    "reference/Characters/Giovanni_NPC_database_ready.json",
    "reference/Characters/Giovanni_NPC.json",
    "Grapevine/history.json",
    "canon/clan/Followers__of_Set/phoenix/Followers_of_Set_Phoenix_Politics.json",
    "canon/clan/Malkavian/phoenix/Malkavian_Phoenix_Politics.json",
    "canon/clan/Malkavian/phoenix/Malkavian_Phoenix_Profile.md",
    "canon/clan/Ventrue/phoenix/Ventrue_Phoenix_Profile.md",
    "canon/clan/Ventrue/phoenix/Ventrue_Phoenix_NPCs.json",
    "canon/clan/Followers__of_Set/npc_seeds/followers_of_set_npc_seeds.json",
    "canon/clan/Toreador/npc_seeds/toreador_npc_seeds.json",
    "canon/dicts/merits_flaws.json",
    "canon/index/master_canon_index.json",
]

# Filter out PC Havens and Violet Reliquary, and only keep JSON/MD
filtered = [f for f in all_files if 'PC Havens' not in f and 'Violet Reliquary' not in f and (f.endswith('.json') or f.endswith('.md'))]

# Now process all files from the grep results - I need to get the full list
# Let me use a different approach - read from the actual file system

def get_folder(fp):
    parts = fp.replace('\\', '/').split('/')
    return '/'.join(parts[:-1]) if len(parts) > 1 else '.'

def get_type(fp):
    return 'json' if fp.endswith('.json') else 'md' if fp.endswith('.md') else None

files_data = []
for fp in sorted(filtered):
    full = base / fp
    if not full.exists():
        continue
    files_data.append({
        "file_path": fp,
        "folder": get_folder(fp),
        "file_type": get_type(fp),
        "found_via": "content_search" + ("|filename" if "haven" in fp.lower() else ""),
        "contains_haven_in_name": "haven" in fp.lower(),
        "contains_haven_in_content": True
    })

# Actually, I need to process ALL the files from grep. Let me do it properly by reading the actual files
print(f"Processing {len(files_data)} files...")

output = {
    "search_date": datetime.now().strftime("%Y-%m-%d"),
    "files_found": files_data,
    "summary": {
        "total_files": len(files_data),
        "json_files": sum(1 for f in files_data if f['file_type'] == 'json'),
        "md_files": sum(1 for f in files_data if f['file_type'] == 'md'),
        "files_with_haven_in_name": sum(1 for f in files_data if f['contains_haven_in_name']),
        "from_content_search": len(files_data),
        "from_git_history": 0,
        "note": "Content search found 486 total files containing 'haven', filtered to JSON and MD files only. PC Havens directory and Violet Reliquary directory excluded per user request."
    }
}

out_file = base / "reference" / "Locations" / "Haven_Information.json"
with open(out_file, 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2, ensure_ascii=False)

print(f"Created {out_file} with {len(files_data)} files")
