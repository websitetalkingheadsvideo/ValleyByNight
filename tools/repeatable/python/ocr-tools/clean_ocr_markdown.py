#!/usr/bin/env python3
"""
Clean OCR hard-wrapped line breaks in Markdown files.

Merges lines that belong to the same paragraph while preserving
legitimate structural line breaks (headers, lists, blockquotes, tables, code fences).
"""

import os
import re
import sys
from pathlib import Path
from typing import List, Tuple


class MarkdownCleaner:
    """Cleans OCR-derived Markdown by merging hard-wrapped lines."""
    
    # Patterns for structural elements
    HEADER_PATTERN = re.compile(r'^#{1,2}\s+')  # Only # and ##
    LIST_PATTERN = re.compile(r'^(\s*)([-*+]|\d+\.)\s+')
    BLOCKQUOTE_PATTERN = re.compile(r'^\s*>')
    TABLE_PATTERN = re.compile(r'^\s*\|.*\|\s*$')
    HR_PATTERN = re.compile(r'^(\s*[-*_]{3,}\s*)$')
    CODE_FENCE_START = re.compile(r'^```')
    CODE_FENCE_END = re.compile(r'^```')
    PAGE_BREAK_PATTERN = re.compile(r'^<div style="page-break-after: always;"></div>$')
    PAGE_NUMBER_PATTERN = re.compile(r'^\[Page \d+\]$')
    SENTENCE_END_PATTERN = re.compile(r'[.!?]["\']?\s*$')
    
    def __init__(self, dry_run: bool = False):
        self.dry_run = dry_run
        self.stats = {
            'files_processed': 0,
            'files_changed': 0,
            'total_merges': 0,
            'total_hyphen_joins': 0
        }
    
    def is_code_fence(self, line: str) -> bool:
        """Check if line is a code fence delimiter."""
        return bool(self.CODE_FENCE_START.match(line.strip()))
    
    def is_header(self, line: str) -> bool:
        """Check if line is a header (only # and ##)."""
        return bool(self.HEADER_PATTERN.match(line))
    
    def is_list(self, line: str) -> bool:
        """Check if line is a list item."""
        return bool(self.LIST_PATTERN.match(line))
    
    def is_blockquote(self, line: str) -> bool:
        """Check if line is a blockquote."""
        return bool(self.BLOCKQUOTE_PATTERN.match(line))
    
    def is_table(self, line: str) -> bool:
        """Check if line is a table row."""
        return bool(self.TABLE_PATTERN.match(line))
    
    def is_horizontal_rule(self, line: str) -> bool:
        """Check if line is a horizontal rule."""
        return bool(self.HR_PATTERN.match(line))
    
    def is_structural(self, line: str) -> bool:
        """Check if line is a structural element that should be preserved."""
        return (
            self.is_header(line) or
            self.is_list(line) or
            self.is_blockquote(line) or
            self.is_table(line) or
            self.is_horizontal_rule(line)
        )
    
    def is_page_break(self, line: str) -> bool:
        """Check if line is a page break marker."""
        return bool(self.PAGE_BREAK_PATTERN.match(line.strip()))
    
    def is_page_number(self, line: str) -> bool:
        """Check if line is a page number marker."""
        return bool(self.PAGE_NUMBER_PATTERN.match(line.strip()))
    
    def ends_with_sentence_ending(self, text: str) -> bool:
        """Check if text ends with sentence-ending punctuation."""
        return bool(self.SENTENCE_END_PATTERN.search(text.rstrip()))
    
    def clean_file(self, input_path: Path, output_path: Path) -> Tuple[bool, int, int]:
        """
        Clean a single Markdown file.
        
        Returns:
            (changed, merges, hyphen_joins)
        """
        try:
            content = input_path.read_text(encoding='utf-8', errors='ignore')
        except Exception as e:
            print(f"  ERROR reading {input_path.name}: {e}")
            return False, 0, 0
        
        # Normalize line endings
        lines = content.replace('\r\n', '\n').replace('\r', '\n').split('\n')
        
        cleaned_lines = []
        in_code_block = False
        paragraph_buffer = []
        file_merges = 0
        file_hyphen_joins = 0
        
        i = 0
        while i < len(lines):
            line = lines[i]
            stripped = line.strip()
            
            # Handle code fences
            if self.is_code_fence(line):
                # Flush any pending paragraph
                if paragraph_buffer:
                    result, merges, hyphen_joins = self.flush_paragraph(paragraph_buffer)
                    cleaned_lines.extend(result)
                    file_merges += merges
                    file_hyphen_joins += hyphen_joins
                    paragraph_buffer = []
                
                if not in_code_block:
                    in_code_block = True
                    cleaned_lines.append(line)
                else:
                    in_code_block = False
                    cleaned_lines.append(line)
                i += 1
                continue
            
            # Inside code block: preserve everything as-is
            if in_code_block:
                cleaned_lines.append(line)
                i += 1
                continue
            
            # Empty line: always a paragraph boundary, but check if sentence continues across page breaks
            if not stripped:
                if paragraph_buffer:
                    last_line_text = paragraph_buffer[-1].rstrip()
                    # Only merge across page breaks if sentence doesn't end AND next line continues it
                    if not self.ends_with_sentence_ending(last_line_text):
                        # Look ahead past empty lines and page markers
                        j = i + 1
                        page_markers = []
                        while j < len(lines):
                            if not lines[j].strip():
                                j += 1
                            elif self.is_page_break(lines[j]) or self.is_page_number(lines[j]):
                                page_markers.append(lines[j])
                                j += 1
                            else:
                                break
                        
                        # Check if next line continues the sentence
                        if j < len(lines) and not self.is_structural(lines[j]):
                            next_line_text = lines[j].strip()
                            # Merge if next line starts lowercase or with quote (continues sentence)
                            if next_line_text and (next_line_text[0].islower() or 
                                                  next_line_text.startswith('"') or
                                                  next_line_text.startswith("'")):
                                # Add page markers to output
                                cleaned_lines.extend(page_markers)
                                # Skip the empty lines and page markers, continue to next line
                                i = j
                                continue
                
                # Normal paragraph boundary - flush buffer and add empty line
                if paragraph_buffer:
                    result, merges, hyphen_joins = self.flush_paragraph(paragraph_buffer)
                    cleaned_lines.extend(result)
                    file_merges += merges
                    file_hyphen_joins += hyphen_joins
                    paragraph_buffer = []
                cleaned_lines.append('')
                i += 1
                continue
            
            # Page break and page number markers: preserve but don't break paragraph
            if self.is_page_break(line) or self.is_page_number(line):
                # These will be handled when we encounter empty lines
                # For now, just add them and continue (they'll be positioned correctly)
                cleaned_lines.append(line)
                i += 1
                continue
            
            # Structural elements: preserve as-is
            if self.is_structural(line):
                if paragraph_buffer:
                    result, merges, hyphen_joins = self.flush_paragraph(paragraph_buffer)
                    cleaned_lines.extend(result)
                    file_merges += merges
                    file_hyphen_joins += hyphen_joins
                    paragraph_buffer = []
                cleaned_lines.append(line)
                i += 1
                continue
            
            # Regular paragraph line
            paragraph_buffer.append(line)
            i += 1
        
        # Flush any remaining paragraph
        if paragraph_buffer:
            result, merges, hyphen_joins = self.flush_paragraph(paragraph_buffer)
            cleaned_lines.extend(result)
            file_merges += merges
            file_hyphen_joins += hyphen_joins
        
        # Join lines and normalize
        cleaned_content = '\n'.join(cleaned_lines)
        
        # Count changes
        original_content = '\n'.join(lines)
        changed = cleaned_content != original_content
        
        # Update global stats
        self.stats['total_merges'] += file_merges
        self.stats['total_hyphen_joins'] += file_hyphen_joins
        
        # Write output (unless dry run)
        if not self.dry_run:
            try:
                output_path.parent.mkdir(parents=True, exist_ok=True)
                output_path.write_text(cleaned_content, encoding='utf-8')
            except Exception as e:
                print(f"  ERROR writing {output_path.name}: {e}")
                return False, 0, 0
        
        return changed, file_merges, file_hyphen_joins
    
    def flush_paragraph(self, buffer: List[str]) -> Tuple[List[str], int, int]:
        """
        Merge paragraph lines from buffer, handling hyphen joins.
        
        Returns:
            (cleaned_lines, merges_count, hyphen_joins_count)
        """
        if not buffer:
            return [], 0, 0
        
        merged = []
        i = 0
        hyphen_joins = 0
        
        while i < len(buffer):
            line = buffer[i].rstrip()
            
            # Check for hyphenated line wrap
            if i < len(buffer) - 1 and line.endswith('-'):
                next_line = buffer[i + 1].lstrip()
                # Join: remove hyphen and merge
                merged.append(line[:-1] + next_line)
                i += 2
                hyphen_joins += 1
            else:
                merged.append(line)
                i += 1
        
        # Join all parts with single space
        result = ' '.join(merged)
        
        # Count merges: if buffer had multiple lines, we merged them
        merges = len(buffer) - 1 if len(buffer) > 1 else 0
        
        return ([result] if result.strip() else []), merges, hyphen_joins
    
    def process_folder(self, input_folder: Path, output_folder: Path):
        """Process all .md files in the input folder."""
        if not input_folder.exists():
            print(f"ERROR: Input folder does not exist: {input_folder}")
            return
        
        # Get all .md files (non-recursive)
        md_files = sorted([f for f in input_folder.iterdir() 
                          if f.is_file() and f.suffix == '.md'])
        
        if not md_files:
            print(f"No .md files found in {input_folder}")
            return
        
        print(f"Processing {len(md_files)} file(s) from {input_folder}")
        if self.dry_run:
            print("DRY RUN MODE - No files will be modified\n")
        else:
            output_folder.mkdir(parents=True, exist_ok=True)
            print(f"Output folder: {output_folder}\n")
        
        for md_file in md_files:
            output_file = output_folder / md_file.name
            print(f"Processing: {md_file.name}")
            
            changed, merges, hyphen_joins = self.clean_file(md_file, output_file)
            
            self.stats['files_processed'] += 1
            if changed:
                self.stats['files_changed'] += 1
                print(f"  [CHANGED] {merges} merges, {hyphen_joins} hyphen joins")
            else:
                print(f"  [NO CHANGES]")
        
        # Print summary
        print(f"\n{'='*60}")
        print("SUMMARY")
        print(f"{'='*60}")
        print(f"Files processed: {self.stats['files_processed']}")
        print(f"Files changed: {self.stats['files_changed']}")
        print(f"Total merges: {self.stats['total_merges']}")
        print(f"Total hyphen joins: {self.stats['total_hyphen_joins']}")
        if self.dry_run:
            print("\n(DRY RUN - No files were modified)")


def main():
    """Main entry point."""
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Clean OCR hard-wrapped line breaks in Markdown files',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Process files (write to output folder)
  python clean_ocr_markdown.py
  
  # Dry run (show what would change)
  python clean_ocr_markdown.py --dry-run
  
  # Custom input/output folders
  python clean_ocr_markdown.py --input custom_input --output custom_output
        """
    )
    
    parser.add_argument(
        '--input',
        type=str,
        default='reference/Books_md_ready_fixed',
        help='Input folder containing .md files (default: reference/Books_md_ready_fixed)'
    )
    
    parser.add_argument(
        '--output',
        type=str,
        default='reference/Books_md_ready_fixed_cleaned',
        help='Output folder for cleaned files (default: reference/Books_md_ready_fixed_cleaned)'
    )
    
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Show what would change without writing files'
    )
    
    args = parser.parse_args()
    
    input_folder = Path(args.input)
    output_folder = Path(args.output)
    
    cleaner = MarkdownCleaner(dry_run=args.dry_run)
    cleaner.process_folder(input_folder, output_folder)


if __name__ == '__main__':
    main()
