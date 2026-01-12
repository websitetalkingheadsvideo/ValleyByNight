#!/usr/bin/env python3
"""
Game Design Document Analyzer for Valley by Night
Analyzes game design documents and extracts VbN-relevant elements.
"""

import os
import re
from pathlib import Path
from typing import Dict, List, Tuple
from datetime import datetime

# VbN system keywords for relevance detection
VBN_SYSTEMS = [
    'rumor', 'boon', 'status', 'influence', 'prestation', 'harpy',
    'elysium', 'camarilla', 'anarch', 'clan', 'sect', 'masquerade',
    'humanity', 'hunger', 'blood bond', 'discipline', 'challenge',
    'social', 'political', 'reputation', 'nemesis', 'dialogue',
    'encounter', 'agent', 'npc', 'character', 'faction'
]

def extract_system_description(content: str) -> str:
    """Extract minimal system description from document."""
    # Look for overview section
    overview_match = re.search(r'##\s+Overview\s*\n\n(.*?)(?=\n##|\Z)', content, re.DOTALL)
    if overview_match:
        overview = overview_match.group(1).strip()
        # Take first paragraph or first 200 chars
        sentences = re.split(r'[.!?]\s+', overview)
        if sentences:
            return sentences[0] + '.'
    
    # Fallback: first paragraph after title
    first_para = re.search(r'^#.*?\n\n(.*?)(?=\n\n|\Z)', content, re.DOTALL)
    if first_para:
        text = first_para.group(1).strip()
        sentences = re.split(r'[.!?]\s+', text)
        if sentences:
            return sentences[0] + '.'
    
    return "System description not found."

def extract_vbn_relevant_parts(content: str) -> List[str]:
    """Extract parts that would work for VbN."""
    relevant_parts = []
    
    # Look for sections with VbN keywords
    sections = re.split(r'\n##\s+', content)
    
    for section in sections:
        section_lower = section.lower()
        # Check if section mentions VbN or relevant systems
        if any(keyword in section_lower for keyword in VBN_SYSTEMS):
            # Extract section title
            title_match = re.match(r'^([^\n]+)', section)
            if title_match:
                title = title_match.group(1).strip()
                # Extract first paragraph or key points
                body = re.sub(r'^[^\n]+\n', '', section, count=1)
                # Get first meaningful paragraph
                para_match = re.search(r'([^\n]+(?:\n[^\n]+)*?)(?=\n\n|\n###|\Z)', body)
                if para_match:
                    relevant_parts.append(f"**{title}**: {para_match.group(1).strip()[:200]}")
    
    # Also look for explicit "Application to VbN" sections
    vbn_app_sections = re.findall(r'###?\s+Application to VbN[^\n]*\n(.*?)(?=\n##|\Z)', content, re.DOTALL | re.IGNORECASE)
    for section in vbn_app_sections:
        # Extract bullet points or first paragraph
        bullets = re.findall(r'[-•*]\s+([^\n]+)', section)
        if bullets:
            relevant_parts.extend([f"• {b.strip()}" for b in bullets[:5]])
        else:
            para = re.search(r'([^\n]+(?:\n[^\n]+)*?)(?=\n\n|\Z)', section)
            if para:
                relevant_parts.append(para.group(1).strip()[:200])
    
    return relevant_parts[:10]  # Limit to top 10

def assess_transferability(content: str) -> Tuple[str, str]:
    """Assess if system is fully, partially, or not transferable."""
    content_lower = content.lower()
    
    # Check for explicit transferability statements
    if re.search(r'(fully|completely|directly).*transfer', content_lower):
        return "Fully Transferable", "System can be directly implemented in VbN."
    
    if re.search(r'(partially|somewhat|adapted|reframed).*transfer', content_lower):
        return "Partially Transferable", "System requires adaptation for VbN context."
    
    if re.search(r'(not|doesn\'t|won\'t).*transfer', content_lower):
        return "Not Transferable", "System is not suitable for VbN."
    
    # Heuristic: count VbN keyword mentions
    vbn_mentions = sum(1 for keyword in VBN_SYSTEMS if keyword in content_lower)
    
    if vbn_mentions >= 5:
        return "Partially Transferable", f"System has {vbn_mentions} VbN-relevant concepts that can be adapted."
    elif vbn_mentions >= 2:
        return "Partially Transferable", f"System has some VbN-relevant elements ({vbn_mentions} concepts found)."
    else:
        return "Needs Assessment", "Transferability unclear from document content."

def analyze_document(file_path: Path) -> Dict:
    """Analyze a single game design document."""
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extract title
    title_match = re.search(r'^#\s+(.+?)(?:\n|$)', content)
    title = title_match.group(1).strip() if title_match else file_path.stem
    
    return {
        'title': title,
        'filename': file_path.name,
        'description': extract_system_description(content),
        'vbn_parts': extract_vbn_relevant_parts(content),
        'transferability': assess_transferability(content)
    }

def generate_master_index(analyses: List[Dict], output_path: Path):
    """Generate master index markdown file."""
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    
    md_content = f"""# Game Design Analysis Index

**Generated:** {timestamp}  
**Purpose:** Extract game design elements from external documents that may help with VbN development.

---

"""
    
    for analysis in analyses:
        md_content += f"""## {analysis['title']}

**Source:** `{analysis['filename']}`

### System Description
{analysis['description']}

### VbN-Relevant Elements

"""
        if analysis['vbn_parts']:
            for part in analysis['vbn_parts']:
                md_content += f"{part}\n\n"
        else:
            md_content += "*No specific VbN-relevant elements identified.*\n\n"
        
        transfer_status, transfer_note = analysis['transferability']
        md_content += f"""### Transferability Assessment

**Status:** {transfer_status}

{transfer_note}

---

"""
    
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(md_content)

def main():
    """Main analysis function."""
    script_dir = Path(__file__).parent
    game_design_dir = script_dir
    
    # Find all markdown files (excluding this script and output)
    md_files = [f for f in game_design_dir.glob('*.md') 
                if f.name not in ['analyze_game_design.py', 'Game Design Analysis Index.md']]
    
    if not md_files:
        print("No markdown files found in game_design directory.")
        return
    
    print(f"Analyzing {len(md_files)} document(s)...")
    
    analyses = []
    for md_file in sorted(md_files):
        print(f"  - {md_file.name}")
        try:
            analysis = analyze_document(md_file)
            analyses.append(analysis)
        except Exception as e:
            print(f"    ERROR: {e}")
    
    # Generate master index
    output_path = game_design_dir / 'Game Design Analysis Index.md'
    generate_master_index(analyses, output_path)
    
    print(f"\nAnalysis complete. Output: {output_path}")

if __name__ == '__main__':
    main()
