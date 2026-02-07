"""
Spell-check pass: dictionary + WoD vocabulary.
- Fix single-word misspellings (high confidence: edit distance 1-2)
- Split fused words when both parts are valid (never split known-good words)
- Uses standard English (pyspellchecker) + V:\\reference\\wod_vocabulary.md
"""
from __future__ import annotations

import re
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent.parent.parent
WOD_VOCAB_PATH = REPO_ROOT / "reference" / "wod_vocabulary.md"

# Lazy-loaded
_spell_checker = None
_valid_words: frozenset[str] = frozenset()


def _load_wod_vocabulary() -> frozenset[str]:
    out: set[str] = set()
    if not WOD_VOCAB_PATH.exists():
        return frozenset()
    text = WOD_VOCAB_PATH.read_text(encoding="utf-8")
    for line in text.splitlines():
        line = line.strip()
        if line.startswith("- ") and not line.startswith("- ("):
            term = line[2:].strip()
            if term:
                lower = term.lower()
                out.add(lower)
                # Add no-space form so we never split known compounds
                out.add(re.sub(r"\s+", "", lower))
    return frozenset(out)


def _load_spellchecker():
    global _spell_checker
    if _spell_checker is None:
        try:
            from spellchecker import SpellChecker
            _spell_checker = SpellChecker(language="en", distance=1)
        except ImportError:
            _spell_checker = False  # type: ignore[assignment]
    return _spell_checker


def _valid_words_set() -> frozenset[str]:
    global _valid_words
    if not _valid_words:
        wod = _load_wod_vocabulary()
        sp = _load_spellchecker()
        if sp:
            eng = set(sp)  # SpellChecker is iterable over known words
            _valid_words = frozenset(eng | wod)
        else:
            _valid_words = wod
    return _valid_words


def _is_valid(word: str) -> bool:
    if not word or not word.isalpha():
        return True  # Skip non-alpha tokens
    lower = word.lower()
    return lower in _valid_words_set()


def _try_split(word: str) -> str | None:
    """If word can be split into 2+ valid words, return spaced version; else None."""
    if len(word) < 5:  # Too short to split meaningfully
        return None
    lower = word.lower()
    if _is_valid(lower):
        return None  # Never split known-good word
    # Must allow left part as short as 2 chars. Common fused phrases start with
    # 2-letter words: of, in, to, be, it, do. E.g. bothofwhichmustbe needs
    # "of" as left to get "of which must be". Min 3 would miss these.
    for i in range(len(word) - 2, 1, -1):  # left min 2, right min 2
        left, right = lower[:i], lower[i:]
        if _is_valid(left) and _is_valid(right):
            cap = word[0].isupper()
            return f"{left.capitalize() if cap else left} {right}"
    # Try recursive split (3+ words), prefer longer left
    for i in range(len(word) - 2, 1, -1):
        left, right = lower[:i], lower[i:]
        if _is_valid(left):
            rest = _try_split(right)
            if rest:
                cap = word[0].isupper()
                return f"{left.capitalize() if cap else left} {rest}"
    return None


def _try_correct(word: str) -> str | None:
    """High-confidence single-word correction (edit distance 1). Returns fix or None."""
    sp = _load_spellchecker()
    if not sp:
        return None
    if _is_valid(word):
        return None
    lower = word.lower()
    best = sp.correction(lower)
    if not best or best == lower:
        return None
    if word[0].isupper():
        best = best[0].upper() + best[1:] if len(best) > 1 else best.upper()
    return best


def _process_word(word: str) -> str:
    if not word or not re.match(r"^[A-Za-z]+$", word):
        return word
    # First try split (fused words)
    split = _try_split(word)
    if split:
        return split
    # Then try single-word correction
    fix = _try_correct(word)
    if fix:
        return fix
    return word


def clean_content(text: str) -> str:
    """Apply spell-check + word-split pass. Runs after fix_ocr_artifacts and fix_spelling_and_caps."""
    if not text or not isinstance(text, str):
        return text
    words = re.split(r"(\W+)", text)
    out = []
    for w in words:
        if w:
            out.append(_process_word(w))
        else:
            out.append(w)
    return "".join(out)
