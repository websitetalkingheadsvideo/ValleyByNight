# Cursor Prompt for Error Analysis and Remediation Plan

You are a website development specialist wroking on a PHP, JavaScript, and MySQL web application repository for the “Valley by Night” application. Your task is to read the existing testing log in `errors.md`, extract and organize the errors, and then create a new remediation document called `errors_plan.md` that summarizes each error and provides a concrete step-by-step plan to fix it.

You MUST:
- Use **Taskmaster (MCP)** to first analyze this request and generate a clear, numbered plan of actions.
- Present that plan to me for review.
- **Do not create or edit any files until I explicitly approve the plan.**
- After approval, use **Plan Mode** to create and populate `errors_plan.md` according to the plan.

---

## Context

- Tech stack: **PHP**, **JavaScript**, **MySQL**.
- Source of truth for issues: `errors.md` (Chrome UI testing log for Valley by Night).
- Error entries follow the structured format already in `errors.md` (Error ID, Page, Severity, Status, Description, etc.).
- Focus categories I care about grouping:
  - HTTP **404 Not Found** issues
  - HTTP **403 Forbidden** issues
  - HTTP **500 Internal Server Error** issues
  - JavaScript errors (runtime, syntax, DOM/selector issues)
  - JSON/AJAX/data loading errors
  - Permissions / server configuration problems
  - UI/styling/UX issues

Only use information from `errors.md` as the canonical list of errors. Treat it as the single source of truth for what needs to be analyzed and planned.

---

## Overall Goal

1. **Analyze** all error entries in `errors.md` (ERR-001, ERR-002, …).
2. **Extract** each error into a normalized internal representation.
3. **Classify and group** similar errors (404s together, 403s together, “Element not found” JS issues together, etc.).
4. **Estimate difficulty to fix** each error as **Easy**, **Medium**, or **Hard**.
5. **Create `errors_plan.md`** containing:
   - Only the errors and their summaries
   - Errors grouped by similarity/type
   - Errors sorted by **ease of fix** (Easy → Hard)
   - A **step-by-step fix plan under each error**

---

## Step 1 – Use Taskmaster (MCP) to Plan

Use **Taskmaster (MCP)** to generate a numbered plan including:

1. Parse `errors.md`.
2. Iterate over each structured error entry.
3. Extract all fields (Error ID, Page, Severity, Status, HTTP code, JS error text, etc.).
4. Add additional derived fields:
   - Category
   - DifficultyToFix (Easy/Medium/Hard)
   - SimilarErrorIds
5. Apply difficulty heuristics.
6. Group errors by similarity and underlying cause.
7. Order groups and errors (easy → hard).
8. Define the `errors_plan.md` structure.
9. Present the completed plan to me.
10. Wait for explicit approval.

Stop here. Do not create files yet.

---

## Step 2 – Desired `errors_plan.md` Structure

After approval, create `errors_plan.md` with:

```markdown
# Valley by Night – Errors Remediation Plan

This document summarizes the open issues from `errors.md` and provides a step-by-step remediation plan for each error. Errors are grouped by type and ordered from easiest to hardest.

## Legend
- Difficulty: Easy / Medium / Hard
- Severity: Low / Medium / High / Critical
- Status: From errors.md

---

## Group 1 – Example Group (e.g., “JavaScript ‘Element not found’ @ line 412”)

### [ERR-XXX] Short Human-Friendly Title (Page Path)

- Severity: High
- Status: Open
- Difficulty to Fix: Easy
- Category: JavaScript Runtime
- Summary: 2–3 sentence explanation.
- Similar Errors: ERR-YYY, ERR-ZZZ

**Fix Plan (Step-by-step)**  
1. Identify the DOM element…  
2. Verify it exists in the HTML…  
3. Update selector if needed…  
4. etc.

---

Repeat for all groups and errors.
```

---

## Step 3 – Execution Rules

1. After presenting the plan, wait for my approval.
2. Only then enter **Plan Mode**.
3. In Plan Mode:
   - Create `errors_plan.md`
   - Populate it as specified
   - Do **not** modify any code in this run
4. Show me the final diff or output.

---

This is the complete Cursor prompt. Save it and reuse whenever analyzing new `errors.md` logs.
