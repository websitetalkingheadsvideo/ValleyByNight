# Valley By Night: Clanbook Processing Guide

## Purpose
Standardize extraction and processing of clanbooks for RAG and agent pipelines.

## Pipeline
1. Extract PDF → MD through Docling.
2. Identify canonical sections.
3. Normalize headings.
4. Build Tone Bible.
5. Extract mechanical notes (tabletop-tagged).
6. Generate NPC seeds.
7. Create JSON Clan Profile.
8. Store anchors in Clan Index.

## Canonical Sections
- Opening Fiction
- History
- Culture
- Philosophy
- Factions
- Politics
- Relations
- Character Creation
- Archetypes
- Templates
- ST Notes

## Normalized Heading Scheme
Use consistent headings across all clanbooks.

## Mechanics Handling
- Retain all rules.
- Tag as `"tabletop"`.
- Map to LARP later using conversion agent.

## Output Files per Clan
- raw.md
- cleaned.md
- clan_profile.json
- tone_bible.md

## Chunking Strategy
- ≤ 2000 tokens per chunk.
- Chunk at headings and paragraph breaks.

## Future Integration
Attach this guide to the project repository for automated agent use.
