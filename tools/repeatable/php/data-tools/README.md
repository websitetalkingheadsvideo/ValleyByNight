# Data Tools

Tools for generating reports, summaries, and data analysis from the database.

## Tools

### check_books_when_ready.php

**Purpose:** Checks the rulebooks database and generates a detailed report.

**Usage:**
```bash
php tools/repeatable/php/data-tools/check_books_when_ready.php
```

**Output:**
- Creates `books_database_report.txt` in project root
- Displays summary in browser/CLI

**Report Contents:**
- Total books count
- Books with/without extracted content
- Extracted pages statistics
- List of all books with details

**Dependencies:**
- Database connection (via `includes/connect.php`)
- `rulebooks` table
- `rulebook_pages` table

---

### generate_project_summary.php

**Purpose:** Generates a comprehensive HTML project summary document.

**Usage:**
```bash
php tools/repeatable/php/data-tools/generate_project_summary.php
```

**Output:**
- Creates `PROJECT_SUMMARY.html` in project root
- HTML document showcasing:
  - Game content richness (characters, locations, items)
  - Technical achievements (agents, systems)
  - Current status and roadmap
  - Historical context

**Features:**
- Beautiful Gothic-themed HTML styling
- Statistics and visualizations
- Character examples
- Agent system descriptions
- Status tracking

**Dependencies:**
- Database connection (via `includes/connect.php`)
- Various database tables (characters, locations, items)
- Character JSON files (optional, for detailed examples)

**Target Audience:** Storytellers/GMs familiar with Laws of the Night Revised

---

## Common Use Cases

1. **Project documentation:** Run `generate_project_summary.php` to create an up-to-date project overview
2. **Database health check:** Run `check_books_when_ready.php` to verify rulebook data integrity
3. **Reporting:** Both tools generate comprehensive reports for project status and data quality
