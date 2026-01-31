# Plan: Find and Fix Inline Keywords in LotNR-formatted.md

**Goal:** Power names (Hand of Flame, Wall of Fire, Firestorm, etc.) are **keywords** for RAG/chunking and search. They must be identified in the document and marked as `#### **Keyword**` so they are structural subheaders and explicitly marked as keywords (keywords go inside `**`).

---

## What Was Done (758–764)

Added three keywords as `#### **Keyword**` subheaders (keyword = power name; **bold** = keyword marker):

- **Hand of Flame** → `#### **Hand of Flame**` before "A dancing puff…"
- **Wall of Fire** → `#### **Wall of Fire**` before "A column of flame…"
- **Firestorm** → `#### **Firestorm**` before "When you call…"

Keywords are inside `**` so they are explicitly marked for RAG/chunking.

---

## Pattern to Find Elsewhere

1. **Structure:** A line is a `### Basic X` / `### Intermediate X` / `### Advanced X` header (discipline level).
2. **Problem:** The next line (or same paragraph) starts with a **keyword** (power name) immediately followed by the description, with no line break or `####` before it.
3. **Example:**  
   `Force Bolt Your concentrated will…` → should be  
   `#### **Force Bolt**`  
   `Your concentrated will…`  
   (Force Bolt is the **keyword**; we add `#### **Force Bolt**` so it’s a subheader with the keyword in bold.)

We are looking for: **keyword phrase (Title Case, 2–5 words) + space + sentence start (e.g. Your, With, By, When, You, A, Complicated)** with no `#### **Keyword**` before it. When we add it, use `#### **Keyword**` (keyword inside `**`).

---

## How to Find Dozens More

### 1. Scan by section

- **Grep for lines that follow a ### Basic/Intermediate/Advanced line**  
  - In the file, each “line” is a long paragraph. So the “next line” is the next element in `content.split('\n')`.
- For each such paragraph, **split on sentence starts** after a likely keyword:  
  - Look for pattern: `Word Word [Word …] ` + one of `(Your|With|By|When|You |A |The |Complicated|In |To )`.  
  - The segment before that is the candidate **keyword** (e.g. "Force Bolt", "Eyes of the Beast").

### 2. Build a keyword list, then search

- **Source keywords from:**
  - Existing `####` lines (each is already a keyword).
  - TOC / index lines that list power names (e.g. "Hand of Flame 178", "Wall of Fire").
  - Known discipline power names (e.g. from reference: Animalism, Auspex, Celerity, … and their powers: Eyes of the Beast, Feral Claws, Flame Bolt, etc.).
- **Search the doc:** for each keyword, grep for  
  `Keyword + space + [A-Z]`  
  (e.g. `Force Bolt Your`, `Eyes of the Beast With`, `Cauldron of Blood The`).  
  Where that appears at the start of a paragraph (right after a `###` header or at start of a long line after a period), that line is a candidate for inserting `#### **Keyword**` and a newline.

### 3. Heuristic: “Title Case phrase then sentence start”

- After each `### Basic` / `### Intermediate` / `### Advanced` line, take the next line (paragraph).
- Split the paragraph by a regex that matches: **space + capital letter** when the capital starts a typical sentence (e.g. `Your`, `With`, `By`, `When`, `You `, `A `, `The `, `Complicated`, `In `, `To `).
- The **first segment** (before that split) is a candidate keyword if:
  - It’s 2–5 words.
  - It’s in Title Case (or close).
  - It’s not already preceded by `####` on the previous line.
- Manually or via script: insert `#### **Keyword**` and a newline before that sentence.

### 4. Script outline

1. Read `LotNR-formatted.md`; split into lines (paragraphs).
2. For each line index `i`:
   - If line `i` matches `^### (Basic|Intermediate|Advanced) `:
     - Consider line `i+1` (next paragraph).
     - On line `i+1`, find all positions matching:  
       ` (Your|With|By|When|You |A |The |Complicated|In |To )`  
       (or a broader list of sentence starters).
     - Take the first such match; text before it = candidate keyword. Trim trailing space/punctuation.
     - If candidate is 2–5 words and Title Case:  
       - Replace line `i+1` with:  
         `#### **Candidate**\n` + rest of paragraph.
3. Optionally: run the same logic for **multiple** keywords in the same paragraph (e.g. "Flame Bolt By" later in the same block) and split into multiple `#### **Keyword**` blocks.
4. Write the file back; diff/review.

### 5. Manual fallback

- Grep for:  
  `(### Basic |### Intermediate |### Advanced ).+\n[A-Z][a-z]+ [a-z]+.* [A-Z]`  
  (multiline) to list sections where the next paragraph starts with “Word Word …” then a capital (sentence start).
- Open each hit, and wherever a power name is directly followed by the description, add `#### **Power Name**` and a newline.

---

## Implementation

**Script:** `V:\reference\Books\add_keyword_subheaders.py`

- Reads `LotNR-formatted.md`; for each paragraph that follows a `### Basic/Intermediate/Advanced` line (or is long and contains the pattern), finds "Keyword SentenceStarter" via regex.
- **Keyword:** 1–4 Title Case words. Single-word keywords allowed only if in `POWER_WHITELIST` (e.g. Possession, Control, Flight, Repulse, Manipulate, Fortitude, Resilience, Resistance, Aegis, Engulf).
- **Sentence starters:** Your, With, By, When, You, A, The, Complicated, In, To, Over, Like, Just, After, Once, Gripping, Creating, etc. (see script).
- **Blacklist:** single-word false positives (Man, Clan, Fe, Cy, Butes, Ches, Bilities, One, Ct, Thin, etc.).
- Replaces each "Keyword SentenceStarter" with `\n#### **Keyword**\nSentenceStarter`; output is line-based (splits on inserted newlines).

**Run:** `python V:\reference\Books\add_keyword_subheaders.py V:\reference\Books\LotNR-formatted.md`

**Done:** Script was run; 125 paragraphs modified. Some false positives (OCR fragments, Ability names) were reverted manually (Gaining, Cophony, Man, Clan, Butes, Ches, Fe, Bilities, Cy, One, Ct, Thin). Remaining `#### **Keyword**` lines include both discipline power names (e.g. Forgetful Mind, Conditioning, Possession, Hand of Flame, Flame Bolt, Force Bolt, Manipulate, Flight, Repulse, Control) and some Ability/section names; review and remove any unwanted ones.

---

## RAG preparation (done)

- **TOC:** Fixed "Discplines" → "Disciplines".
- **False-positive #### removed/merged:** Chetypes → Archetypes; Ces → Resources; Three (Blood Traits) merged into paragraph; Ciplines → Learning Disciplines; Assign merged into Step Four; Quell the Beast (was "Beast" only) corrected; Obeah fragments (Beah Sense Vitality → Sense Vitality, Beah Corpore Sano → Corpore Sano); Obtenebration/Potence section headers (oBtene/Bration → Obtenebration, poten/Ce → Potence); Bration Shadow Play → Shadow Play; Shroud of Night, Mask of a Thousand Faces, Vanish from the Mind's Eye, Cloak the Gathering fixed.
- **Structure:** Clear hierarchy (# chapter, ## section, ### discipline level, #### power keyword) for chunking; power names as `#### **Keyword**` for retrieval.

## Task Checklist

- [x] Fix Lure of Flames block: add keywords Hand of Flame, Wall of Fire, Firestorm as `#### **Keyword**` subheaders.
- [x] Run script to insert `#### **Keyword**` for inline power names across the document.
- [x] Add script `reference/Books/add_keyword_subheaders.py` with blacklist/whitelist.
- [x] Revert obvious false positives (OCR fragments).
- [x] RAG prep: fix TOC typo, merge fragment #### lines, normalize section/power headers.
- [ ] Review remaining `#### **Keyword**` lines; remove any that are Ability names or unwanted.
- [ ] Re-run header capitalization script on new `#### **Keyword**` lines if needed (script should leave `**` intact).

---

## Files

- **Source/target:** `V:\reference\Books\LotNR-formatted.md`
- **Plan (this file):** `V:\reference\Books\LotNR_subheader_keyword_plan.md`
- **Header capitalization script:** `V:\reference\Books\fix_header_capitalization.py`
- **Keyword subheader script:** `V:\reference\Books\add_keyword_subheaders.py`
