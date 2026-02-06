# Audit content_type for LotNR.json. Only report clear mismatches.
# Run: python audit_content_types.py

import json
import re

PATH = "LotNR.json"

def classify(content: str, doc_id: str, page: int) -> str:
    c = content[:8000]  # enough for signals
    c_lower = c.lower()

    # Introduction: fiction (Rendezvous, Peter, Donata, Kevin, Coleman, SCENEBREAK) or credits/contents
    if re.search(r"\b(SCENEBREAK|Rendezvous|Donata|Coleman|Kevin)\b", c) and re.search(r"\"(Hush|We have|Okay)\"|said (Donata|Peter|Coleman)", c):
        return "introduction"
    if "Credits" in c and "Written by:" in c and "Mind's Eye Theatre" in c:
        return "introduction"
    if "Contents" in c and "Chapter One: Introduction" in c and "Rendezvous:" in c:
        return "introduction"

    # Chapter header: CHAPTER X: TITLE or "Contents" as section
    if re.match(r"^(CHAPTER [A-Z]+:|Contents\s)", c.strip()):
        return "chapter_header"
    if "CHAPTER SIX: STORYTELLING" in c and "are you a storyteller?" in c_lower:
        return "chapter_header"
    if "CHAPTER FOUR: DISCIPLINES" in c and "Disciplines" in c:
        return "chapter_header"

    # Storytelling guide: how to use book, Storyteller role, theme, mood, plot, Building stories, key story ingredients
    if "how to use this Book" in c_lower or "how to use this book" in c_lower:
        return "storytelling_guide"
    if "the storyteller is the one who creates" in c_lower and "chapter one" in c_lower:
        return "storytelling_guide"
    if "elegantly simple" in c_lower and "how to use this Book" in c_lower:
        return "storytelling_guide"
    if "Building stories" in c and "Storytelling is less like engineering" in c:
        return "storytelling_guide"
    if "key story ingredients" in c_lower or "key story ingredients" in c:
        return "storytelling_guide"
    if "theme" in c_lower and "The theme is the organizing principle" in c:
        return "storytelling_guide"
    if "mood" in c_lower and "Mood is the general tone of the story" in c:
        return "storytelling_guide"
    if "plot" in c_lower and "If the theme and mood are the story's heart" in c and "plot is its body" in c_lower:
        return "storytelling_guide"
    if "setting" in c_lower and "The setting of your story is as much a character" in c:
        return "storytelling_guide"
    if "paCing" in c or "pacing" in c_lower and "The value of pacing becomes clear" in c:
        return "storytelling_guide"
    if "sCale" in c and "The scale of your story" in c:
        return "storytelling_guide"
    if "sCope" in c and "Your story's scope describes" in c:
        return "storytelling_guide"
    if "player input" in c_lower and "ask your players what they want" in c_lower:
        return "storytelling_guide"
    if "the stages" in c_lower and "Good stories have definitive stages" in c:
        return "storytelling_guide"
    if "Opening" in c and "Lavish plenty of attention on the opening" in c:
        return "storytelling_guide"
    if "Climax" in c and "When the characters have discovered all they need" in c:
        return "storytelling_guide"
    if "Denouement" in c and "The story winds down after its climax" in c:
        return "storytelling_guide"
    if "something For everyone" in c or "something for everyone" in c_lower:
        return "storytelling_guide"
    if "Blast From the past" in c and "character histories" in c_lower:
        return "storytelling_guide"
    if "main plots" in c_lower and "The central plot of your story" in c:
        return "storytelling_guide"
    if "taking up the mantle" in c_lower and "Storytelling is the toughest gig" in c:
        return "storytelling_guide"

    # Rules: thou shalt never Break, no touching, Challenge, Static Test, Health Level, combat
    if "the rules thou shalt never Break" in c_lower or "the rules thou shalt never break" in c_lower:
        return "rules"
    if "no touChing. no stunts" in c or "no touching. no stunts" in c_lower:
        return "rules"
    if "Challenge —" in c and "Rock-PaperScissors" in c and "Static Test" in c:
        return "rules"

    # General: World of Darkness, Kindred overview, Embrace, Camarilla/Sabbat overview, lexicon, generation spread
    if "the world oF darkness" in c or "the world of darkness" in c_lower:
        if "On the surface" in c and "Gothic-Punk" in c:
            return "general"
    if "the kindred" in c_lower and "Vampires have been fixtures" in c:
        return "general"
    if "the emBraCe" in c or "the Embrace" in c and "The Embrace is the process" in c:
        return "general"
    if "the Camarilla" in c and "dates back to the years of the Inquisition" in c and "six clans hold full membership" not in c:
        if "justicars" in c and "the traditions" in c_lower and "the First tradition" in c:
            return "general"  # Camarilla + Traditions on same page
    if "conclave" in c_lower and "Justicars are the only ones" in c and "the saBBat" in c:
        return "general"
    if "the generation spread" in c_lower or "Second Generation" in c and "Third Generation" in c:
        return "general"
    if "ghouls — those who serve" in c_lower or "who hunts the hunters" in c_lower:
        return "general"
    if "lexiCon" in c or "lexicon" in c_lower and "Ancilla —" in c and "Anarch —" in c:
        return "general"
    if "mind's eye theatre terms" in c_lower and "Attribute —" in c and "Ability —" in c:
        return "general"
    if "in these nights" in c_lower and "The modern nights" in c:
        return "general"
    # Sect-only pages (Camarilla/Sabbat structure, no clan writeups)
    if "the Camarilla" in c and "Founded in the late Dark Ages" in c and "the saBBat" in c and "For centuries" in c:
        return "general"
    if "the independents" in c_lower and "The Camarilla claims the allegiance of six" in c:
        return "general"

    # Clan info: clan name + (Roleplaying Hints OR "Disciplines: X, Y, Z" OR Advantage: OR Disadvantage: OR Bloodlines:)
    # or full clan description (Brujah, Malkavian, Setites, Giovanni, Lasombra, etc.)
    clan_fluff = (
        "Roleplaying Hints:" in c or "Roleplaying Hints :" in c or
        "Disciplines: Celerity" in c or "Disciplines: Auspex" in c or "Disciplines: Animalism" in c or
        "Disciplines: Dominate" in c or "Disciplines: Obfuscate" in c or "Disciplines: Potence" in c or
        "Disciplines: Fortitude" in c or "Disciplines: Presence" in c or "Disciplines: Protean" in c or
        "Advantage:" in c or "Advantage :" in c or "Disadvantage:" in c or "Bloodlines:" in c
    )
    clan_names = (
        "Brujah" in c or "Malkavian" in c or "Nosferatu" in c or "Toreador" in c or "Tremere" in c or
        "Ventrue" in c or "Lasombra" in c or "Tzimisce" in c or "Assamite" in c or "Followers of Set" in c or
        "Setites" in c or "Gangrel" in c or "Giovanni" in c or "Ravnos" in c or "Daughters of Cacophony" in c or
        "Salubri" in c or "Samedi" in c
    )
    if clan_fluff and clan_names:
        return "clan_info"
    # Clan description without mechanics (first half of clan chapter)
    if "make up the ranks of the Brujah" in c or "the clan of seers" in c_lower or "Hideous deformities" in c:
        return "clan_info"
    if "Artists, dilettantes and degenerates" in c or "Once a cabal of mortal wizards" in c:
        return "clan_info"
    if "While the other clans play at games" in c and "Ventrue" in c:
        return "clan_info"
    if "Master manipulators" in c and "Lasombra" in c:
        return "clan_info"
    if "Potent sorceries, crumbling castles" in c and "Tzimisce" in c:
        return "clan_info"
    if "From hidden fortresses in the Middle East" in c and "Assamites" in c:
        return "clan_info"
    if "The desert sands of Africa" in c and "Setites" in c:
        return "clan_info"
    if "From the frozen northlands" in c and "Gangrel" in c:
        return "clan_info"
    if "The upstart Giovanni clan" in c:
        return "clan_info"
    if "Once a great clan" in c and "Ravnos" in c:
        return "clan_info"
    # Bloodrights, allegiances (clan chapter intro)
    if "Bloodrights" in c and "Carrying the strengths of the founders" in c:
        return "clan_info"
    # Traditions + Camarilla structure on one page -> general
    if "the traditions" in c_lower and "The Traditions are considered" in c and "the First tradition: the masQuerade" in c:
        return "general"
    # Fiction in middle of book (Elysium had closed, Peter, Donata)
    if "Elysium had closed" in c and "Warburton" in c and "Kevin" in c:
        return "introduction"

    # Character creation: Character Creation, Converting, step one, Free Trait, sample character
    if "Character Creation" in c or "Character Creation & Traits" in c:
        if "Converting" in c and "Converting the last touChes" in c:
            return "character_creation"
        if "Converting taBletop" in c or "Converting tabletop" in c_lower:
            return "character_creation"
        if "sample CharaCter Creation" in c or "step one:" in c_lower or "step two:" in c_lower:
            return "character_creation"
    if "step one: ConCept" in c or "step two: attriButes" in c or "step three: advantages" in c_lower:
        return "character_creation"
    if "step Four: Finishing touChes" in c or "step Five: spark oF liFe" in c:
        return "character_creation"
    if "Alyson is looking to join" in c and "create a character" in c_lower:
        return "character_creation"

    # Discipline info: CHAPTER FOUR, learning disciplines, level 1/2/3 power text
    if "CHAPTER FOUR: DISCIPLINES" in c:
        return "discipline_info"
    if "learning disCiplines" in c_lower or "learning disciplines" in c_lower:
        return "discipline_info"
    if "Though vampirism is a curse" in c and "Disciplines" in c and "blood of Caine" in c:
        return "discipline_info"
    # Actual discipline power text (Level One, Level Two, challenge rules for a power)
    if re.search(r"•\s*Level (One|Two|Three|Four|Five)\s*•", c) or "Level One •" in c:
        return "discipline_info"
    if "Static Test" in c and "Simple Test" in c and "Extended Challenge" in c and "Discipline" in c:
        return "discipline_info"

    return ""  # unknown, don't change


def main():
    with open(PATH, "r", encoding="utf-8") as f:
        data = json.load(f)

    mismatches = []
    for i, obj in enumerate(data):
        doc_id = obj.get("id", "")
        current = obj.get("content_type", "")
        content = obj.get("content", "")
        page = obj.get("page", 0)
        suggested = classify(content, doc_id, page)
        if suggested and suggested != current:
            mismatches.append((doc_id, current, suggested, page))

    for doc_id, current, suggested, page in mismatches:
        print(f"{doc_id} (p.{page}): {current} -> {suggested}")

    print(f"\nTotal mismatches: {len(mismatches)}")
    return mismatches


if __name__ == "__main__":
    main()
