# Apply content_type corrections to LotNR.json (audit-verified mismatches only).
# doc_17: glossary + fiction -> general (not rules). doc_118: keep discipline_info.

import json

PATH = "LotNR.json"

CORRECTIONS = [
    ("doc_7", "storytelling_guide"),
    ("doc_11", "general"),
    ("doc_12", "general"),
    ("doc_13", "general"),
    ("doc_15", "general"),
    ("doc_17", "general"),
    ("doc_19", "clan_info"),
    ("doc_20", "general"),
    ("doc_21", "general"),
    ("doc_23", "clan_info"),
    ("doc_25", "clan_info"),
    ("doc_27", "clan_info"),
    ("doc_29", "clan_info"),
    ("doc_30", "clan_info"),
    ("doc_31", "clan_info"),
    ("doc_33", "clan_info"),
    ("doc_34", "clan_info"),
    ("doc_35", "clan_info"),
    ("doc_36", "clan_info"),
    ("doc_37", "clan_info"),
    ("doc_39", "clan_info"),
    ("doc_40", "clan_info"),
    ("doc_41", "clan_info"),
    ("doc_43", "clan_info"),
    ("doc_44", "clan_info"),
    ("doc_45", "clan_info"),
    ("doc_46", "clan_info"),
    ("doc_47", "clan_info"),
    ("doc_49", "clan_info"),
    ("doc_50", "clan_info"),
    ("doc_51", "character_creation"),
    ("doc_52", "character_creation"),
    ("doc_53", "clan_info"),
    ("doc_54", "clan_info"),
    ("doc_56", "character_creation"),
    ("doc_61", "character_creation"),
    ("doc_84", "general"),
    ("doc_113", "character_creation"),
    ("doc_115", "character_creation"),
    ("doc_116", "character_creation"),
    ("doc_117", "character_creation"),
    ("doc_208", "storytelling_guide"),
    ("doc_209", "storytelling_guide"),
    ("doc_211", "storytelling_guide"),
    ("doc_213", "storytelling_guide"),
    ("doc_214", "storytelling_guide"),
]

def main():
    with open(PATH, "r", encoding="utf-8") as f:
        data = json.load(f)

    by_id = {obj["id"]: obj for obj in data}
    applied = 0
    for doc_id, new_type in CORRECTIONS:
        if doc_id in by_id and by_id[doc_id].get("content_type") != new_type:
            by_id[doc_id]["content_type"] = new_type
            applied += 1

    with open(PATH, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

    print(f"Applied {applied} content_type corrections to {PATH}")


if __name__ == "__main__":
    main()
