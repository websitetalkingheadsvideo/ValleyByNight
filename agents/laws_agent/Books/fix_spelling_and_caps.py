"""
Fix RAG JSON content in agents/laws_agent/Books:
1. Mid-word uppercase: e.g. saBBat -> Sabbat, masQuerade -> Masquerade (only first letter of each word may be uppercase).
2. Spelling: apply a small list of common OCR/spelling corrections; game terms are left unchanged.

Run from Books/ or project root. Then re-run import_books.php to update the RAG database.
"""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

# WoD/MET terms that must not be "corrected" by spelling (lowercase for lookup)
WOD_WHITELIST = frozenset({
    "camarilla", "sabbat", "kindred", "toreador", "ventrue", "brujah", "malkavian",
    "nosferatu", "tremere", "gangrel", "assamite", "setite", "giovanni", "ravnos",
    "tzimisce", "lasombra", "caine", "cainite", "elysium", "justicar", "justicars",
    "primogen", "diablerie", "masquerade", "progeny", "childe", "childer", "sire",
    "antitribu", "gehenna", "antediluvian", "methuselah", "neonate", "ancillae",
    "prestation", "boon", "lextalionis", "archon", "archons", "conclave", "praxis",
    "nosferatu", "obfuscate", "auspex", "dominate", "fortitude", "potence", "celerity",
    "protean", "animalism", "bloodline", "caitiff", "embrace", "ghoul", "regnant",
    "retainers", "numina", "salubri", "samedi", "antideluvian", "inconnu", "harpy",
    "harpies", "seneschal", "sheriff", "keeper", "met", "vtm", "nod", "noddy",
    "tremere", "tzimisce", "lasombra", "antitribu", "baali", "salubri", "daughters",
    "cacophony", "bloodlines", "inner circle", "elysium",
})

# Common spelling/OCR corrections (lowercase key -> correct form; apply only to whole words)
SPELLING_FIXES = {
    "teh": "the",
    "thier": "their",
    "recieve": "receive",
    "occured": "occurred",
    "occurence": "occurrence",
    "seperately": "separately",
    "definately": "definitely",
    "accomodate": "accommodate",
    "refered": "referred",
    "occuring": "occurring",
    "guage": "gauge",
    "acheive": "achieve",
    "beleive": "believe",
    "wierd": "weird",
    "tounge": "tongue",
    "truely": "truly",
    "goverment": "government",
    "enviornment": "environment",
    "sucess": "success",
    "sucessful": "successful",
    "untill": "until",
    "thier": "their",
    "relevent": "relevant",
    "occassion": "occasion",
    "commited": "committed",
    "comitted": "committed",
    "aparent": "apparent",
    "aparrent": "apparent",
    "across": "across",
    "acros": "across",
    "becuase": "because",
    "begining": "beginning",
    "beleive": "believe",
    "benefitted": "benefited",
    "calender": "calendar",
    "carefull": "careful",
    "concious": "conscious",
    "dependance": "dependence",
    "desparate": "desperate",
    "develope": "develop",
    "dissappear": "disappear",
    "embarass": "embarrass",
    "existance": "existence",
    "futher": "further",
    "goverment": "government",
    "grammer": "grammar",
    "happend": "happened",
    "harrass": "harass",
    "independant": "independent",
    "occured": "occurred",
    "persistant": "persistent",
    "recieve": "receive",
    "refered": "referred",
    "relevent": "relevant",
    "seperate": "separate",
    "sucessful": "successful",
    "tommorrow": "tomorrow",
    "tounge": "tongue",
    "truely": "truly",
    "untill": "until",
    "wierd": "weird",
}


def fix_mid_word_uppercase(text: str) -> str:
    """Lowercase any uppercase letter that is not the first character of a word."""
    if not text:
        return text

    def fix_word(match: re.Match) -> str:
        word = match.group(0)
        if len(word) <= 1:
            return word
        return word[0] + word[1:].lower()

    return re.sub(r"[A-Za-z]+", fix_word, text)


def fix_spelling_in_text(text: str) -> str:
    """Apply SPELLING_FIXES to whole words, skipping WOD_WHITELIST."""
    if not text:
        return text
    words = re.split(r"(\W+)", text)
    out = []
    for w in words:
        if not w:
            out.append(w)
            continue
        if re.match(r"^[A-Za-z]+$", w):
            lower = w.lower()
            if lower in WOD_WHITELIST:
                out.append(w)
            elif lower in SPELLING_FIXES:
                repl = SPELLING_FIXES[lower]
                if w[0].isupper():
                    repl = repl[0].upper() + repl[1:] if repl else repl
                out.append(repl)
            else:
                out.append(w)
        else:
            out.append(w)
    return "".join(out)


def clean_content(text: str) -> str:
    """Apply mid-word caps fix, then spelling fixes."""
    if not text or not isinstance(text, str):
        return text
    text = fix_mid_word_uppercase(text)
    text = fix_spelling_in_text(text)
    return text


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
        if "content" in item and item["content"]:
            old = item["content"]
            new = clean_content(old)
            if new != old:
                item["content"] = new
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
                print(f"{path.name}: {n_updates} content fields updated")
        except Exception as e:
            print(f"{path.name}: ERROR {e}", file=sys.stderr)
    print(f"Done. {len(json_files)} files, {total_entries} entries, {total_updates} fields updated.")
    print("Re-run import_books.php (web or CLI) to update the RAG database with the fixes.")


if __name__ == "__main__":
    main()
