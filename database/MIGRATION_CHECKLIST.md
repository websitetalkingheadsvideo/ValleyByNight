# Migration Checklist: Biography as Single Source of Truth

## SQL Migration Script
**File:** `v:\database\merge_history_into_biography.php`

**Status:** ✅ Created

**Migration Rules:**
- If biography is non-empty, preserve it (do not overwrite)
- If biography is empty/null and history has content, copy history to biography
- If both have content, append history to biography with separator `\n\n---\n\n`
- Script is idempotent - safe to run multiple times

**Execution:**
- Via browser: `database/merge_history_into_biography.php`
- Via CLI: `php database/merge_history_into_biography.php`

---

## File Edits Required

### 1. `v:\database\generate_world_summaries.php`

**Changes needed:**
- Remove `$has_history` check (line 166)
- Remove `$has_history_summary` check (line 167) 
- Remove history field extraction logic (lines 396-407)
- Replace history output with biography-only logic
- Remove `$missing_histories` tracking (line 258 and related usage)
- Update quality issues reporting to remove history references

**Specific edits:**
- Line 166: Remove `$has_history = isset($char_columns['history']) || isset($char_columns['full_history']);`
- Line 167: Remove `$has_history_summary = isset($char_columns['history_summary']);`
- Lines 396-407: Replace history field logic with biography-only:
  ```php
  // Biography (canonical field)
  $biography_text = handleNarrativeField($char['biography'] ?? null);
  
  if ($biography_text === 'MISSING') {
      $missing_biographies[] = $char_name;
  } elseif (strpos($biography_text, '[TRUNCATED IN SOURCE]') !== false) {
      $truncated_narratives[] = "{$char_name} (biography)";
  }
  
  $output .= "- **Biography**: {$biography_text}\n";
  ```
- Line 258: Change `$missing_histories = [];` to `$missing_biographies = [];`
- Update all references to `$missing_histories` to `$missing_biographies`
- Update quality issues section to report missing biographies instead of missing histories

---

### 2. `v:\tools\repeatable\backfill_character_biography.php`

**Changes needed:**
- Remove history extraction patterns from markdown patterns array
- Keep only biography-related patterns
- Update comments to remove history references

**Specific edits:**
- Lines 241-248: Remove history-specific patterns, keep only biography patterns:
  ```php
  $patterns = [
      // Pattern 1: "# Character History: Name\n\nContent" (keep - extracts biography content)
      ['pattern' => '/^#\s*Character\s+History:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims', 'group' => 1],
      // Pattern 2-4: Biography/Backstory sections only
      ['pattern' => '/^##\s*Biography\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims', 'group' => 1],
      ['pattern' => '/^##\s*Backstory\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims', 'group' => 1],
      ['pattern' => '/^#\s*Biography\s*\n+?(.*?)(?=\n#|\Z)/ims', 'group' => 1]
  ];
  ```
- Remove lines 244 and 247 (history-specific patterns)
- Update file header comment (line 6) to remove "history/biography" and use "biography" only
- Update line 22 comment to remove history references

---

### 3. `v:\tools\repeatable\backfill_character_field.php`

**Changes needed:**
- Remove history extraction patterns from biography field config
- Keep only biography-related patterns

**Specific edits:**
- Lines 46-51: Remove history-specific patterns from `biography` field config:
  ```php
  'markdown_patterns' => [
      '/^#\s*Character\s+History:\s*[^\n]+\n+?(.*?)(?=\n#|\Z)/ims',  // Keep - extracts biography content
      '/^##\s*Biography\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
      '/^##\s*Backstory\s*\n+?(.*?)(?=\n##|\n#|\Z)/ims',
      '/^#\s*Biography\s*\n+?(.*?)(?=\n#|\Z)/ims'
  ],
  ```
- Remove lines 47 and 50 (history-specific patterns)

---

### 4. `v:\tools\repeatable\character-data\index.php`

**Status:** ✅ No changes needed - file does not reference history field

**Verification:**
- File only checks `biography` field (line 43, 85, 287, 443, 593)
- No history field references found

---

### 5. `v:\tools\repeatable\character-data\quick-edit.php`

**Status:** ✅ No changes needed - file does not reference history field

**Verification:**
- File only checks `biography` field (line 80, 133, 153, 196)
- No history field references found

---

## Verification Steps

After migration:

1. ✅ Run SQL migration script: `php database/merge_history_into_biography.php`
2. ✅ Verify biography contains merged result: Check sample characters in database
3. ✅ Run character data quality tools: `tools/repeatable/character-data/index.php`
4. ✅ Verify scripts pass with history absent/ignored
5. ✅ Check that no tests reference history field
6. ✅ Verify biography is the only field used for character narrative content

---

## Notes

- The `history` column will still exist in the database after migration
- A separate migration can drop the column if desired
- All extraction patterns that look for "Character History:" sections are kept because they extract biography content, not a separate history field
- The migration is idempotent and can be run multiple times safely
