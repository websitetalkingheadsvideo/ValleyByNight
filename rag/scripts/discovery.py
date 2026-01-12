#!/usr/bin/env python3
"""
Discovery phase: Inventory source documents and analyze structure.
"""
import os
import json
from pathlib import Path
from typing import List, Dict, Any
import re

REPO_ROOT = Path(__file__).parent.parent.parent
SOURCE_DIR = REPO_ROOT / "reference" / "Books_md_ready_fixed_cleaned"
RAG_DIR = REPO_ROOT / "rag"


def get_markdown_files(source_dir: Path) -> List[Path]:
    """Get all markdown files in source directory."""
    return sorted(source_dir.glob("*.md"))


def analyze_file_structure(filepath: Path) -> Dict[str, Any]:
    """Analyze a markdown file's structure."""
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        lines = content.split('\n')
    
    stats = {
        'filename': filepath.name,
        'filepath': str(filepath.relative_to(REPO_ROOT)),
        'line_count': len(lines),
        'char_count': len(content),
        'has_page_breaks': bool(re.search(r'<div style="page-break', content)),
        'has_page_markers': bool(re.search(r'\[Page \d+\]', content)),
        'heading_count': {
            'h1': len(re.findall(r'^#\s+', content, re.MULTILINE)),
            'h2': len(re.findall(r'^##\s+', content, re.MULTILINE)),
            'h3': len(re.findall(r'^###\s+', content, re.MULTILINE)),
        },
        'has_credits': bool(re.search(r'##?\s*[Cc]redits?', content)),
        'has_toc': bool(re.search(r'##?\s*[Tt]able\s+of\s+[Cc]ontents?', content)),
        'systems_mentioned': [],
        'editors_mentioned': [],
    }
    
    # Detect system tags
    system_patterns = [
        r"Mind'?s?\s+Eye\s+Theatre",
        r"World\s+of\s+Darkness",
        r"Vampire.*Masquerade",
        r"Werewolf.*Apocalypse",
        r"Wraith.*Oblivion",
        r"Mage.*Ascension",
    ]
    for pattern in system_patterns:
        matches = re.findall(pattern, content, re.IGNORECASE)
        if matches:
            stats['systems_mentioned'].extend(matches)
    
    # Detect credits section
    credits_match = re.search(r'##?\s*[Cc]redits?\s*\n(.*?)(?=\n##|\n#|$)', content, re.DOTALL)
    if credits_match:
        credits_text = credits_match.group(1)
        # Look for "Written by:", "Edited by:", "Developed by:" patterns
        written_match = re.search(r'Written\s+by[:\s]+(.*?)(?:\n|Additional|Developed|Edited)', credits_text, re.IGNORECASE | re.DOTALL)
        edited_match = re.search(r'Edited\s+by[:\s]+(.*?)(?:\n|Written|Developed|Additional)', credits_text, re.IGNORECASE | re.DOTALL)
        if written_match:
            stats['editors_mentioned'].append(written_match.group(1).strip())
        if edited_match:
            stats['editors_mentioned'].append(edited_match.group(1).strip())
    
    # Remove duplicates
    stats['systems_mentioned'] = list(set(stats['systems_mentioned']))
    
    return stats


def create_book_slug(filename: str) -> str:
    """Create a book ID slug from filename."""
    # Remove extension
    slug = filename.replace('.md', '')
    # Replace spaces and special chars with underscores
    slug = re.sub(r'[^\w\s-]', '', slug)
    slug = re.sub(r'[-\s]+', '_', slug)
    slug = slug.lower()
    return slug


def main():
    """Run discovery phase."""
    print("Starting discovery phase...")
    
    # Create reports directory
    reports_dir = RAG_DIR / "reports"
    reports_dir.mkdir(parents=True, exist_ok=True)
    
    # Get all markdown files
    md_files = get_markdown_files(SOURCE_DIR)
    print(f"Found {len(md_files)} markdown files")
    
    # Analyze each file
    file_stats = []
    for filepath in md_files:
        print(f"Analyzing {filepath.name}...")
        stats = analyze_file_structure(filepath)
        file_stats.append(stats)
    
    # Create corpus audit report
    audit_report = {
        'discovery_date': str(Path(__file__).stat().st_mtime),
        'source_directory': str(SOURCE_DIR.relative_to(REPO_ROOT)),
        'total_files': len(file_stats),
        'total_lines': sum(s['line_count'] for s in file_stats),
        'total_characters': sum(s['char_count'] for s in file_stats),
        'files_with_page_breaks': sum(1 for s in file_stats if s['has_page_breaks']),
        'files_with_page_markers': sum(1 for s in file_stats if s['has_page_markers']),
        'files_with_credits': sum(1 for s in file_stats if s['has_credits']),
        'files_with_toc': sum(1 for s in file_stats if s['has_toc']),
        'total_headings': {
            'h1': sum(s['heading_count']['h1'] for s in file_stats),
            'h2': sum(s['heading_count']['h2'] for s in file_stats),
            'h3': sum(s['heading_count']['h3'] for s in file_stats),
        },
        'file_details': file_stats,
        'findings': {
            'book_boundaries': 'One file per book confirmed',
            'structure_issues': [
                'Page break markers present in most files',
                'Page markers [Page N] present in many files',
                'OCR artifacts visible (spacing issues)',
            ],
            'metadata_availability': {
                'credits_sections': f"{sum(1 for s in file_stats if s['has_credits'])}/{len(file_stats)} files",
                'system_tags': 'Present in copyright notices',
            }
        },
        'risks': [
            'OCR artifacts may affect term matching',
            'Inconsistent heading levels may affect chunking',
            'Page markers may interfere with content structure',
        ],
    }
    
    # Write audit report
    audit_file = reports_dir / "corpus_audit.md"
    with open(audit_file, 'w', encoding='utf-8') as f:
        f.write("# Corpus Audit Report\n\n")
        f.write(f"**Discovery Date**: {audit_report['discovery_date']}\n\n")
        f.write(f"**Source Directory**: `{audit_report['source_directory']}`\n\n")
        f.write(f"**Total Files**: {audit_report['total_files']}\n\n")
        f.write(f"**Total Lines**: {audit_report['total_lines']:,}\n\n")
        f.write(f"**Total Characters**: {audit_report['total_characters']:,}\n\n")
        f.write("## Structure Analysis\n\n")
        f.write(f"- Files with page breaks: {audit_report['files_with_page_breaks']}\n")
        f.write(f"- Files with page markers: {audit_report['files_with_page_markers']}\n")
        f.write(f"- Files with credits sections: {audit_report['files_with_credits']}\n")
        f.write(f"- Files with table of contents: {audit_report['files_with_toc']}\n\n")
        f.write("## Heading Statistics\n\n")
        f.write(f"- H1 headings: {audit_report['total_headings']['h1']}\n")
        f.write(f"- H2 headings: {audit_report['total_headings']['h2']}\n")
        f.write(f"- H3 headings: {audit_report['total_headings']['h3']}\n\n")
        f.write("## Findings\n\n")
        for finding in audit_report['findings'].get('structure_issues', []):
            f.write(f"- {finding}\n")
        f.write("\n## Risks\n\n")
        for risk in audit_report['risks']:
            f.write(f"- {risk}\n")
        f.write("\n## File Details\n\n")
        f.write("| Filename | Lines | H1 | H2 | H3 | Credits | Page Breaks |\n")
        f.write("|----------|-------|----|----|----|---------|-------------|\n")
        for stat in sorted(file_stats, key=lambda x: x['filename']):
            f.write(f"| {stat['filename']} | {stat['line_count']} | {stat['heading_count']['h1']} | {stat['heading_count']['h2']} | {stat['heading_count']['h3']} | {'Yes' if stat['has_credits'] else 'No'} | {'Yes' if stat['has_page_breaks'] else 'No'} |\n")
    
    print(f"Discovery complete. Report written to {audit_file}")
    return file_stats


if __name__ == "__main__":
    main()
