#!/usr/bin/env python3
"""
Build glossary from corpus: extract terms, detect variants, create glossary.yml.
"""
import json
import re
import yaml
from pathlib import Path
from typing import Dict, List, Set, Tuple, Any
from collections import Counter, defaultdict

REPO_ROOT = Path(__file__).parent.parent.parent
SOURCE_DIR = REPO_ROOT / "reference" / "Books_md_ready_fixed_cleaned"
RAG_DIR = REPO_ROOT / "rag"


def extract_headings_from_files(source_dir: Path) -> List[str]:
    """Extract all headings from markdown files."""
    headings = []
    
    for filepath in sorted(source_dir.glob("*.md")):
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Extract headings
        heading_matches = re.findall(r'^#{1,3}\s+(.+)$', content, re.MULTILINE)
        headings.extend([h.strip() for h in heading_matches])
    
    return headings


def extract_bold_terms(content: str) -> List[str]:
    """Extract terms from bold markdown (**, __)."""
    terms = []
    
    # Bold text patterns: **text** or __text__
    bold_matches = re.findall(r'\*\*([^*]+)\*\*|__([^_]+)__', content)
    for match in bold_matches:
        term = match[0] or match[1]
        term = term.strip()
        if len(term) > 2 and len(term) < 50:  # Reasonable term length
            terms.append(term)
    
    return terms


def extract_capitalized_phrases(content: str, min_length: int = 3) -> List[str]:
    """Extract capitalized phrases that might be game terms."""
    # Find sequences of capitalized words
    pattern = r'\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b'
    matches = re.findall(pattern, content)
    
    # Filter by length and common patterns
    phrases = []
    for match in matches:
        words = match.split()
        if len(words) >= min_length and len(words) <= 5:  # Reasonable phrase length
            phrases.append(match)
    
    return phrases


def normalize_term(term: str) -> str:
    """Normalize term for comparison (lowercase, remove extra spaces)."""
    term = term.lower()
    term = re.sub(r'\s+', ' ', term)
    return term.strip()


def detect_ocr_variants(term: str) -> List[str]:
    """Detect potential OCR artifacts in a term."""
    variants = []
    normalized = normalize_term(term)
    
    # Detect spacing splits (e.g., "Cam arilla" -> "Camarilla")
    # Look for patterns like "word word" where they might be one word
    if ' ' in normalized:
        # Check if removing space creates a known term pattern
        no_space = normalized.replace(' ', '')
        variants.append(no_space)
    
    # Detect hyphenation issues
    if '-' in normalized:
        no_hyphen = normalized.replace('-', '')
        variants.append(no_hyphen)
    
    # Case variants
    variants.append(normalized.lower())
    variants.append(normalized.capitalize())
    variants.append(normalized.upper())
    
    return variants


def find_term_variants(term_freq: Counter) -> Dict[str, List[str]]:
    """Find variant forms of terms (OCR artifacts, case differences)."""
    variant_groups = defaultdict(list)
    
    # Normalize all terms and group variants
    normalized_to_terms = defaultdict(list)
    for term, count in term_freq.items():
        normalized = normalize_term(term)
        normalized_to_terms[normalized].append((term, count))
    
    # For each normalized form, pick the most common as canonical
    for normalized, term_list in normalized_to_terms.items():
        # Sort by frequency
        term_list.sort(key=lambda x: x[1], reverse=True)
        canonical = term_list[0][0]
        
        # Collect all variants
        variants = [t[0] for t in term_list]
        variant_groups[canonical] = variants
    
    return dict(variant_groups)


def extract_terms_from_corpus(source_dir: Path) -> Tuple[Counter, Dict[str, int]]:
    """Extract candidate terms from entire corpus."""
    term_frequency = Counter()
    term_sources = defaultdict(int)  # Track where terms appear
    
    print("Extracting terms from corpus...")
    
    # First pass: extract headings
    headings = extract_headings_from_files(source_dir)
    for heading in headings:
        # Split heading into potential terms
        words = heading.split()
        for i in range(len(words)):
            # 1-word terms (if capitalized)
            if words[i][0].isupper() and len(words[i]) > 2:
                term_frequency[words[i]] += 1
            # 2-word phrases
            if i < len(words) - 1:
                phrase = f"{words[i]} {words[i+1]}"
                if words[i][0].isupper():
                    term_frequency[phrase] += 1
            # 3-word phrases
            if i < len(words) - 2:
                phrase = f"{words[i]} {words[i+1]} {words[i+2]}"
                if words[i][0].isupper():
                    term_frequency[phrase] += 1
    
    # Second pass: extract from content
    for filepath in sorted(source_dir.glob("*.md")):
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Extract bold terms
        bold_terms = extract_bold_terms(content)
        for term in bold_terms:
            term_frequency[term] += 1
            term_sources[term] += 1
        
        # Extract capitalized phrases
        phrases = extract_capitalized_phrases(content)
        for phrase in phrases:
            term_frequency[phrase] += 1
            term_sources[phrase] += 1
    
    return term_frequency, dict(term_sources)


def build_glossary_entries(term_frequency: Counter, min_frequency: int = 3) -> List[Dict[str, Any]]:
    """Build glossary entries from term frequency data."""
    # Filter by frequency
    frequent_terms = {term: count for term, count in term_frequency.items() 
                      if count >= min_frequency and len(term) > 2}
    
    # Find variant groups
    variant_groups = find_term_variants(Counter(frequent_terms))
    
    # Build glossary entries
    entries = []
    processed_terms = set()
    
    # Common game terms (high priority)
    priority_terms = [
        'Camarilla', 'Sabbat', 'Anarch', 'Masquerade', 'Prince', 'Primogen',
        'Vaulderie', 'Discipline', 'Trait', 'Merit', 'Flaw', 'Clan', 'Sect',
        'Kindred', 'Kine', 'Elysium', 'Haven', 'Ghoul', 'Embrace',
        'Generation', 'Blood', 'Vitae', 'Humanity', 'Beast', 'Frenzy',
        'Rötschreck', 'Auspex', 'Celerity', 'Fortitude', 'Obfuscate',
        'Potence', 'Presence', 'Protean', 'Dominate', 'Animalism',
        'Thaumaturgy', 'Necromancy', 'Quietus', 'Serpentis', 'Vicissitude',
        'Tzimisce', 'Nosferatu', 'Toreador', 'Brujah', 'Gangrel',
        'Ventrue', 'Tremere', 'Malkavian', 'Assamite', 'Giovanni',
    ]
    
    # Add priority terms first
    for term in priority_terms:
        variants = variant_groups.get(term, [term])
        if term not in processed_terms:
            entry = {
                'term': term,
                'aliases': list(set(variants + [term.lower(), term.upper(), term])),
                'short_definition': None,
                'related_terms': [],
                'tags': [],
                'source_examples': [],
            }
            entries.append(entry)
            processed_terms.update(variants)
    
    # Add other frequent terms
    for term, variants in variant_groups.items():
        if term in processed_terms:
            continue
        
        # Skip if too generic
        generic_terms = {'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use'}
        if term.lower() in generic_terms:
            continue
        
        entry = {
            'term': term,
            'aliases': list(set(variants + [term.lower(), term.upper()])),
            'short_definition': None,
            'related_terms': [],
            'tags': [],
            'source_examples': [],
        }
        entries.append(entry)
        processed_terms.update(variants)
    
    # Sort by term name
    entries.sort(key=lambda x: x['term'].lower())
    
    return entries


def detect_ocr_patterns_in_corpus(source_dir: Path) -> Dict[str, List[str]]:
    """Detect common OCR patterns across corpus."""
    ocr_patterns = defaultdict(list)
    
    print("Detecting OCR patterns...")
    
    for filepath in sorted(source_dir.glob("*.md")):
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Look for suspicious spacing patterns
        # Words split incorrectly: "Cam arilla", "Vauld erie"
        suspicious_spacing = re.findall(r'\b\w{1,4}\s+\w{4,}\b', content)
        for match in suspicious_spacing[:10]:  # Limit per file
            words = match.split()
            if len(words) == 2 and len(words[0]) <= 4 and len(words[1]) >= 4:
                combined = ''.join(words)
                if combined not in ocr_patterns:
                    ocr_patterns[combined] = []
                if match not in ocr_patterns[combined]:
                    ocr_patterns[combined].append(match)
    
    return dict(ocr_patterns)


def main():
    """Build glossary from corpus."""
    print("Building glossary from corpus...")
    
    # Extract terms
    term_frequency, term_sources = extract_terms_from_corpus(SOURCE_DIR)
    print(f"Found {len(term_frequency)} unique terms")
    
    # Build glossary entries
    glossary_entries = build_glossary_entries(term_frequency, min_frequency=3)
    print(f"Created {len(glossary_entries)} glossary entries")
    
    # Detect OCR patterns
    ocr_patterns = detect_ocr_patterns_in_corpus(SOURCE_DIR)
    
    # Write glossary.yml
    glossary_file = RAG_DIR / "glossary.yml"
    glossary_data = {'terms': glossary_entries}
    
    with open(glossary_file, 'w', encoding='utf-8') as f:
        yaml.dump(glossary_data, f, default_flow_style=False, allow_unicode=True, sort_keys=False)
    
    print(f"Glossary written to {glossary_file}")
    
    # Write OCR patterns report
    reports_dir = RAG_DIR / "reports"
    reports_dir.mkdir(parents=True, exist_ok=True)
    
    variants_file = reports_dir / "term_variants.md"
    with open(variants_file, 'w', encoding='utf-8') as f:
        f.write("# Term Variants and OCR Artifacts\n\n")
        f.write("## OCR Spacing Patterns Detected\n\n")
        for canonical, variants in sorted(ocr_patterns.items()):
            f.write(f"### {canonical}\n")
            for variant in variants:
                f.write(f"- `{variant}`\n")
            f.write("\n")
    
    print(f"OCR patterns report written to {variants_file}")
    
    return glossary_entries


if __name__ == "__main__":
    main()
