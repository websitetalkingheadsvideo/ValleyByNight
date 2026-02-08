"""
Fix OCR artifacts in RAG JSON content and section_title fields.
- Collapse doubled/tripled consecutive letters (e.g. NNoobblleessssee -> Noblesse)
- Collapse spaced-out single letters at start (B L O O D L I N E -> BLOODLINE)
- Fix common word fusions (handor -> hand or, knownonly -> known only, etc.)
- Strip trailing garbage (WW2357 TM, TM TM, isolated caps/numbers)
"""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

def _collapse_doubled_word(word: str) -> str:
    """If word is entirely doubled (pairs of same letter, e.g. NNoobblleessssee), collapse to single letters."""
    if len(word) < 2 or len(word) % 2 != 0:
        return word
    for i in range(0, len(word), 2):
        if word[i] != word[i + 1]:
            return word
    return word[::2]


def _collapse_doubled_letters_at_start(text: str, max_chars: int = 900) -> str:
    """Collapse doubled letters only in words that are fully doubled, in the first max_chars."""
    head, rest = text[:max_chars], text[max_chars:]
    words = re.split(r"(\s+)", head)
    out = []
    for w in words:
        if not w:
            out.append(w)
            continue
        # Allow trailing punctuation (e.g. "CCaappiittaalliissttss,,")
        lead_alpha = re.match(r"^([A-Za-z]+)(.*)$", w)
        if lead_alpha:
            alpha, suffix = lead_alpha.group(1), lead_alpha.group(2)
            if alpha and _collapse_doubled_word(alpha) != alpha:
                out.append(_collapse_doubled_word(alpha) + suffix)
            else:
                out.append(w)
        else:
            out.append(w)
    return "".join(out) + rest


def _collapse_spaced_out_letters(text: str) -> str:
    """Collapse sequences of single letter + space (e.g. B L O O D L I N E -> BLOODLINE)."""
    # Pattern: (letter space)+ letter, then (space or punctuation or end) so we don't merge "B O O K:" with preceding "B L O O D L I N E "
    def replace(m):
        s = m.group(0)
        letters = "".join(c for c in s if c.isalpha())
        if len(letters) >= 2:
            return letters + " "
        return s

    return re.sub(
        r"(?:[A-Za-z] )+[A-Za-z](?=\s|$|[.,:;)])",
        lambda m: "".join(c for c in m.group(0) if c.isalpha()) + " ",
        text,
    )


def _fix_word_fusions(text: str) -> str:
    """Fix common OCR word fusions (missing space before only, or, etc.)."""
    # Order matters: longer phrases first; avoid breaking "for", "and" as words
    fusions = [
        (r"(\w)only\b", r"\1 only"),
        # Word+the / the+word fusions
        (r"\bofthe\b", "of the"),
        (r"\binthe\b", "in the"),
        (r"\btothe\b", "to the"),
        (r"\bforthe\b", "for the"),
        (r"\bandthe\b", "and the"),
        (r"\bthe(family|game|last|time|end|way|same|first|other|role|world|book|point|name|fact|rest|whole)\b", r"the \1"),
        # and+pronoun / had+verb / phrase fusions
        (r"\bFora\b", "For a"),
        (r"\bitexists\b", "it exists"),
        (r"\bandhe\b", "and he"),
        (r"\bhadcome\b", "had come"),
        (r"\bbothofwhichmustbe\b", "both of which must be"),
        (r"\binthesamecondition\b", "in the same condition"),
        (r"\btheywereinwhen\b", "they were in when"),
        (r"\bclanstilldonot\b", "clan still do not"),
        (r"\bhadcomeprepared\b", "had come prepared"),
        (r"\bthesecondstakedownwhere\b", "the second stake down where"),
        (r"\bhimselfpinned\b", "himself pinned"),
        (r"\bhandover\b", "hand over"),
        (r"\bandmouthed\b", "and mouthed"),
        (r"Ama~nglyt,he\b", "Amazingly, the"),
        (r"\bandhefalteredinshock\b", "and he faltered in shock"),
        (r"\bthesecondstakedown\b", "the second stake down"),
        (r"\bInaninstant\b", "In an instant"),
        (r"\bThehesitationwasall\b", "The hesitation was all"),
        (r"\bCarwelneeded\b", "Carvel needed"),
        (r"\bYuseffound\b", "Yusef found"),
        (r"\bYusefs\b", "Yusef's"),
        (r"\bsolidtgold\b", "solid gold"),
        (r"\bamenta1\b", "a mental"),
        (r"\bminglestake\b", "single stake"),
        (r"\bbeenin\b", "been in"),
        (r"\bmindandstart\b", "mind and start"),
        (r"I'ma\s+lmost\b", "I'm almost"),
        (r"\bswenting\b", "sweating"),
        (r"\bpmtcomes\b", "part comes"),
        (r"\bFor dus to\b", "For this to"),
        (r"\bI'we\b", "I've"),
        (r"\byou'all\b", "you'll"),
        (r"\bwe'all\b", "we'll"),
        (r"\bWe'we\b", "We've"),
        (r"\byou'we\b", "you've"),
        # Bastet/Backgrounds/Heritage
        (r"\bcabter\b", "caster"),
        (r"\bBastetb\b", "Bastet"),
        (r"\bAckgrounds\b", "Backgrounds"),
        (r"\beritage\b", "Heritage"),
        # Garbled headers
        (r"~Eneral~\s*Iets", "General Gifts"),
        (r"~Iets\b", "Gifts"),
        (r"\d+\s*Maste\s+E-?\s*Leve\s+L[\"']?\s*Tha\s+Um\s+Atur\s+Cjy\s+Rit\s*ua\s*Is\s*ed", "Master E-Level Thaumaturgy Rituals"),
        # Orphaned leading fragments
        (r"\bnt facets\b", "different facets"),
        ("handor ", "hand or "),
        ("centuryor ", "century or "),
        ("longor ", "long or "),
        ("Sooneror ", "Sooner or "),
        ("vigorous motionor ", "vigorous motion or "),
        ("clothingor ", "clothing or "),
        ("jewelryor ", "jewelry or "),
        ("high-collaredor ", "high-collared or "),
        ("sitquietly ", "sit quietly "),
        ("remove himself from the activity as quickly andquietly ", "remove himself from the activity as quickly and quietly "),
        (r"(\w)purely\b", r"\1 purely"),
        (r"(\w)rightly\b", r"\1 rightly"),
        (r"(\w)firmly\b", r"\1 firmly"),
        (r"(\w)rarely\b", r"\1 rarely"),
        (r"(\w)tradition\b", r"\1 tradition"),
        (r"(\w)creation\b", r"\1 creation"),
        (r"(\w)selection\b", r"\1 selection"),
        (r"(\w)rely\b", r"\1 rely"),
        ("personal useonly", "personal use only"),
        ("useonly", "use only"),
        ("tonightonly", "tonight only"),
        ("theonly", "the only"),
        ("thatfirmly", "that firmly"),
        ("bellishedor", "embellished or"),
        ("governrightly", "govern rightly"),
        ("Ventruerely", "Ventrue rely"),
        ("Charactercreation", "Character creation"),
        ("BackgroundNearly", "Background Nearly"),
        ("Gaining Bloodline PrestigeOnly", "Gaining Bloodline Prestige Only"),
        ("theunruly", "the unruly"),
        ("stemspurely", "stems purely"),
        ("TheCreation", "The Creation"),
        ("knownonly", "known only"),
        ("owndirection", "own direction"),
        ("became knownonly", "became known only"),
        ("unwantedattention", "unwanted attention"),
        ("trainedeasily", "trained easily"),
        ("createonly", "create only"),
        ("theyfully", "they fully"),
        ("involveddaily", "involved daily"),
        ("theirfamily", "their family"),
        ("withoutquestion", "without question"),
        ("willlikely", "will likely"),
        ("lesslikely", "less likely"),
        ("morelikely", "more likely"),
        ("exactlocation", "exact location"),
        ("arefiction", "are fiction"),
        ("companyor product", "company or product"),
        ("Themention", "The mention"),
        ("trademarkor", "trademark or"),
        ("companyor prod", "company or prod"),
        ("Mageonly", "Mage only"),
        ("theonly way", "the only way"),
        ("Samedirarely", "Samedi rarely"),
        ("Dr. Samuel Stankiewicz has disassociated himself from his brethren, and his progeny have followed suit. Gaining Bloodline PrestigeOnly", "Dr. Samuel Stankiewicz has disassociated himself from his brethren, and his progeny have followed suit. Gaining Bloodline Prestige Only"),
    ]
    for pat, repl in fusions:
        text = re.sub(pat, repl, text, flags=re.IGNORECASE)
    return text


def _fix_missing_space_before_a(text: str) -> str:
    """Fix OCR 'worda  ' -> 'word a ' (missing space before 'a')."""
    text = re.sub(r"(\w)a  ", r"\1 a ", text)
    # Restore words that were incorrectly split
    text = text.replace("ide a ", "idea ").replace("are a ", "area ")
    return text


def _fix_bloodline_book_typo(text: str) -> str:
    """Fix 'BLOODLINEBOO  K:' -> 'BLOODLINE BOOK:', 'SAMEDIA  ' -> 'SAMEDI ', name+initial."""
    text = text.replace("BLOODLINEBOO  K:", "BLOODLINE BOOK:")
    text = text.replace("SAMEDIA  ", "SAMEDI ")
    text = text.replace("The TygerI  am", "The Tyger I am")
    text = text.replace("JasonC . ", "Jason C. ")
    text = text.replace("RichardE . ", "Richard E. ")
    text = text.replace("Dan sky", "Dansky")
    text = text.replace("dan sky", "Dansky")
    return text


def _strip_trailing_garbage(text: str) -> str:
    """Remove OCR garbage at end: WW digits, TM TM, isolated caps/numbers."""
    # Strip trailing whitespace first
    text = text.rstrip()
    # Remove trailing patterns: " WW2357 TM CLANBOOK: WW2357 TM" etc.
    text = re.sub(r"\s+WW\d+(\s+TM)*(\s+CLANBOOK[:\s]*WW\d+\s*TM)*\s*$", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s+WW\d+\s*TM\s*TM\s*TM\s*$", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s+WW\d+\s*TM\s*BRUJAH\s*TM\s*TM\s*$", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s+TM\s*TM\s*TM\s*$", "", text)
    text = re.sub(r"\s+TM\s*TM\s*$", "", text)
    # "CL C A L NB A OO N K B : OOK: CLANBOOK W : W 2 358 WW2W3W528358" and variants
    text = re.sub(r"\s+CL\s+C\s+A\s+L\s+NB\s+A\s+OO\s+N\s+K\s+B\s*:\s*OOK:.*$", "", text)
    text = re.sub(r"\s+CLCAL\s+NBA\s+OONKB\s*:\s*OOK:.*$", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s+W\s*:\s*W\s*2\s*\d+\s*WW[\dW]+\s*$", "", text)
    # "WH I I I W 01 F", "CA MI S T 0 1110"
    text = re.sub(r"\s+WH\s+I+\s+W\s*0*\d+\s+F\s*$", "", text)
    text = re.sub(r"\s+CA\s+MI\s+S\s+T\s*0\s*\d+\s*$", "", text)
    # "0 PRINTED IN CANADA-"
    text = re.sub(r"\s+0\s+PRINTED IN CANADA-\s*$", "", text)
    return text.rstrip()


def _fix_faith_and_fire_names(text: str) -> str:
    """Fix name OCR splits: Ka len -> Kalen, Pau lo -> Paulo (any whitespace)."""
    text = re.sub(r"\bKa\s+len\b", "Kalen", text)
    text = re.sub(r"\bPau\s+lo\b", "Paulo", text)
    return text


def _fix_leading_liber(text: str) -> str:
    """Fix 'be r Li Credits' / 'ber  Li Credits' -> 'Liber Credits' at start."""
    text = re.sub(r"^be\s*r\s*Li\s+", "Liber ", text, flags=re.IGNORECASE)
    text = re.sub(r"^ber\s+Li\s+", "Liber ", text, flags=re.IGNORECASE)
    return text


def _fix_leading_credits(text: str) -> str:
    """Fix 'C redits' -> 'Credits', 'C s t , redits' -> 'Credits' at start."""
    text = re.sub(r"^C\s+redits\b", "Credits", text, flags=re.IGNORECASE)
    text = re.sub(r"^C\s+s\s+t\s*,\s*redits\b", "Credits", text, flags=re.IGNORECASE)
    return text


def _fix_encoding_artifacts(text: str) -> str:
    """Fix OCR encoding glitches (e.g. Euro sign misread as E)."""
    text = text.replace("\u20ac", "E")  # EURO SIGN -> E
    return text


# Known leading junk: short lines or OCR fragments that often appear at start of content
_LEADING_JUNK_LINES = frozenset({
    "boy", "Fa", "et", "Sis", "0", "tests", "Au Retune Acis",
    "Gear Poi ls", "Poi ls", "lucky cable", "car waaanetrnat",
})


def _strip_leading_ocr_junk(text: str) -> str:
    """Strip leading lines that look like OCR junk until we hit real prose."""
    lines = text.split("\n")
    first_real = 0
    for i, line in enumerate(lines):
        stripped = line.strip()
        if not stripped:
            first_real = i + 1
            continue
        # Known junk line
        if stripped in _LEADING_JUNK_LINES:
            first_real = i + 1
            continue
        # Very short line (likely fragment): 1–3 chars or single short word
        if len(stripped) <= 4 and (stripped.isalpha() or stripped.isdigit() or stripped in ("0", "O")):
            first_real = i + 1
            continue
        # Line looks like start of sentence: reasonable length or common starters
        if len(stripped) >= 15:
            break
        if stripped.startswith(("If ", "The ", "He ", "She ", "And ", "When ", "So ", "But ", "It ", "This ", "They ", "We ", "I ", "You ", "As ", "For ", "In ", "On ", "At ", "Madeline", "Salvatini", "Paulo", "Hauknefr", "Perhaps")):
            break
        # Short line but not clearly junk; treat as potential junk if we haven't seen long line yet
        if len(stripped) <= 10:
            first_real = i + 1
            continue
        break
    if first_real == 0:
        return text
    return "\n".join(lines[first_real:]).lstrip("\n")


def _fix_leading_journal_fragments(text: str) -> str:
    """Fix leading OCR junk and garbled text in MET Journal content."""
    text = _strip_leading_ocr_junk(text)
    # Strip page-number remnant "; 4 " at very start
    text = re.sub(r"^;\s*\d+\s+", "", text)
    # TOC line: "Worderomtdheev Eloper Anotefrommeonthepurposeandgoalsofthismagazineaswellaswhatto expect"
    text = re.sub(
        r"^Worderomtdheev Eloper Anotefrommeonthepurposeandgoalsofthismagazineaswellaswhatto expect",
        "A Word From The Developer - A note on the purpose and goals of this magazine as well as what to expect",
        text,
        count=1,
    )
    # "r's Broke I It" at start (orphaned from "Laws Is Broke")
    text = re.sub(r"^r's Broke I It\s+", "Laws Is Broke. It ", text)
    return text


def _get_learned_replacements_path() -> Path:
    """Path to converter's learned_ocr_replacements.txt."""
    books_dir = Path(__file__).resolve().parent
    repo_root = books_dir.parent.parent.parent.parent
    return repo_root / "reference" / "Books" / "converter" / "learned_ocr_replacements.txt"


def _apply_learned_replacements(text: str) -> str:
    """Apply learned OCR replacements from converter/learned_ocr_replacements.txt."""
    path = _get_learned_replacements_path()
    if not path.exists():
        return text
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        if "|" in line:
            old, new = line.split("|", 1)
            text = text.replace(old, new)
    return text


def _collapse_extra_spaces(text: str) -> str:
    """Collapse runs of 2+ spaces/tabs to a single space. Normalize nbsp and tab to space first."""
    text = text.replace("\u00a0", " ")  # non-breaking space
    text = text.replace("\t", " ")  # tab
    return re.sub(r" {2,}", " ", text)


def clean_content(s: str) -> str:
    """Apply all OCR cleanup steps to a content string."""
    if not s or not isinstance(s, str):
        return s
    s = _fix_encoding_artifacts(s)
    s = _fix_leading_journal_fragments(s)
    s = _collapse_spaced_out_letters(s)
    s = _collapse_doubled_letters_at_start(s)
    s = _fix_word_fusions(s)
    s = _apply_learned_replacements(s)
    s = _fix_faith_and_fire_names(s)
    s = _fix_leading_liber(s)
    s = _fix_leading_credits(s)
    s = _fix_bloodline_book_typo(s)
    s = _fix_missing_space_before_a(s)
    s = _collapse_extra_spaces(s)
    s = _strip_trailing_garbage(s)
    return s


def process_file(path: Path) -> tuple[int, int]:
    """Process one JSON file; return (entries_processed, fields_updated)."""
    raw = path.read_text(encoding="utf-8")
    data = json.loads(raw)
    if not isinstance(data, list):
        return 0, 0
    updated = 0
    for item in data:
        if not isinstance(item, dict):
            continue
        for key in ("content",):
            if key in item and item[key]:
                old = item[key]
                new = clean_content(old)
                if new != old:
                    item[key] = new
                    updated += 1
        meta = item.get("metadata")
        if isinstance(meta, dict) and "section_title" in meta and meta["section_title"]:
            old = meta["section_title"]
            new = clean_content(old)
            if new != old:
                meta["section_title"] = new
                updated += 1
    if updated > 0:
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    return len(data), updated


def main() -> None:
    books_dir = Path(__file__).resolve().parent
    json_files = sorted(books_dir.glob("*.json"))
    total_entries = 0
    total_updates = 0
    for path in json_files:
        try:
            n_entries, n_updates = process_file(path)
            total_entries += n_entries
            total_updates += n_updates
            if n_updates > 0:
                print(f"{path.name}: {n_updates} fields updated")
        except Exception as e:
            print(f"{path.name}: ERROR {e}", file=sys.stderr)
    print(f"Done. {len(json_files)} files, {total_entries} entries, {total_updates} fields updated.")


if __name__ == "__main__":
    main()
