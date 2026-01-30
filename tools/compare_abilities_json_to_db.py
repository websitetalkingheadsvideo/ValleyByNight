"""
Compare abilities in reference/Books/abilities.json to abilities seeded in the database.
Outputs abilities that exist in the JSON file but are not in the abilities table.

Database abilities are those from database/create_abilities_table.php and
database/backfill_abilities_from_grapevine.php (no live DB connection).
"""

from pathlib import Path
import json

# Project root (script lives in tools/)
ROOT = Path(__file__).resolve().parent.parent
JSON_PATH = ROOT / "reference" / "Books" / "abilities.json"

# Ability names currently in DB (from create_abilities_table.php + backfill_abilities_from_grapevine.php)
DB_ABILITY_NAMES = frozenset([
    "Academics", "Alertness", "Animal Ken", "Archery", "Athletics", "Awareness",
    "Blindfighting", "Brawl", "Bureaucracy", "Computer", "Crafts", "Demolitions",
    "Disguise", "Dodge", "Drive", "Empathy", "Enigmas", "Etiquette", "Expression",
    "Firearms", "Firecraft", "Flight", "Intimidation", "Investigation", "Law",
    "Leadership", "Linguistics", "Medicine", "Meditation", "Melee", "Occult",
    "Performance", "Politics", "Repair", "Science", "Security", "Stealth",
    "Streetwise", "Subterfuge", "Survival", "Throwing", "Torture",
])

# Esoteric / deep-lore / setting-specific abilities to exclude from "general" list
# Also duplicate forms (same ability, different spelling/hyphenation) — keep one, drop the other
ESOTERIC = frozenset([
    "Assbeating",  # typo/joke
    "Fast Draw",   # duplicate of Fast-Draw
    "Fortune Telling",   # duplicate of Fortune-Telling
    "Scrounge",          # duplicate of Scrounging
    "Archeology",       # duplicate of Archaeology (UK spelling)
    "Brewing/Distilling",  # duplicate of Brewing
    "Cooking/Baking",     # duplicate of Cooking
    "Demolition",         # duplicate of Demolitions (in DB)
    "Heavy Weaponry",     # duplicate of Heavy Weapons
    "Hypnotism",         # duplicate of Hypnosis
    "Blind Fighting",     # duplicate of Blindfighting (in DB)
    "Ride",               # duplicate of Riding
    "Autumn Lore", "Beast Lore", "Beliefs", "Black Hand Knowledge", "Black Hand Lore",
    "Babel", "Blatancy", "Camarilla Lore", "Chantry Politics", "Chimerical Alchemy",
    "City Secrets", "Clan Impersonation", "Clan Knowledge", "Court Lore",
    "Denizen Lore", "Destruction of Spirits", "Dis Lore", "Dream Lore", "Dreamcraft",
    "Dreaming", "Faerie Lore", "Garou Astrology", "Gematria", "Gremayre",
    "Helldiving", "High Ritual", "Kenning", "Koldunism", "Kuei Lung Ch'uan",
    "Labyrinth Gear", "Lore", "Lore: Arcanum", "Lore: Magical Societies",
    "Lore: Mythology", "Lore: Supernatural", "Lore: Supernatural Creatures",
    "Lupine Lore", "Magus Lore", "Malkavian Time", "Masquerade", "Memories",
    "Metaphysics", "Mythlore", "Nation Lore", "Nunnehi Lore", "Portents", "Powers",
    "Primal-Urge", "Rituals", "Romany Lore", "Rune-Lore", "Sacred Scriptures",
    "Setite Lore", "Shentao (Kenning)", "Soulforging", "Soulshaping", "Spirit Names",
    "Stone Lore", "Subdimensions", "Talith, The", "Tempest Lore", "Temporal Sense",
    "Tolerance", "Traditions", "Tribal Lore", "Underworld Lore", "Underworld Miscellanea",
    "Vamp", "Weather-Eye", "Wild Hunting", "Wood Lore", "Wyrm Lore",
])


def main() -> None:
    if not JSON_PATH.is_file():
        raise SystemExit(f"Cannot read: {JSON_PATH}")

    data = json.loads(JSON_PATH.read_text(encoding="utf-8"))
    json_abilities = set()
    for category in ("Talents", "Skills", "Knowledges"):
        for entry in data.get(category) or []:
            name = entry.get("ability")
            if name and isinstance(name, str):
                name = name.strip()
                if name:
                    json_abilities.add(name)

    missing = sorted(json_abilities - DB_ABILITY_NAMES)
    general_only = [n for n in missing if n not in ESOTERIC]
    print(f"Abilities in reference/Books/abilities.json that are NOT in the database ({len(general_only)} general, {len(missing) - len(general_only)} esoteric excluded):\n")
    for name in general_only:
        print(f"  {name}")


if __name__ == "__main__":
    main()
