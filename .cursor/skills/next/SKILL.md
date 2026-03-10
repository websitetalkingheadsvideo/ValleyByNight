---
name: next
description: Runs the Next Git Sync workflow: bump patch version (if clear), stage all with git add -A, commit, push to current branch upstream, then report commit/push status, version change, and a short summary of what shipped. Use when the user says "next", "/next", or asks to sync, ship, or push changes.
---

# Next (Git Sync)

Run this workflow as a single sequence. Do not create or switch branches; use the current branch only.

## 1. Verify

- Ensure you are at the Git repository root.
- Use the current branch for all steps.

## 2. Version bump (optional)

- Prefer (in order): `package.json`, `pyproject.toml`, `composer.json`, `setup.cfg`, or a dedicated version file (e.g. `VERSION`, `includes/version.php`).
- If **one** clear version field exists: increment **patch** (e.g. `1.2.3` → `1.2.4`). Update only that file.
- If none or multiple conflicting sources: **skip** and note in the final summary.

## 3. Inspect

- Run `git status -sb` and `git diff --stat`.
- Know what is new, modified, or deleted.

## 4. Stage

- Run `git add -A`. Stage everything; do not cherry-pick. Respect `.gitignore`.
- Verify with `git diff --cached --stat`. Fix push-blockers (e.g. secrets) before continuing.

## 5. Commit

- **No staged changes:** Stop before push; still do step 7 for the summary.
- **Has staged changes:** Commit with a short imperative message (~72 chars), e.g. `chore: bump version and sync project changes`.

## 6. Push

- `git push` if upstream exists; otherwise `git push -u origin <current-branch>`.
- Do not create tags or new branches.

## 7. Summary since last push

- With upstream: `git log --oneline --stat @{u}..HEAD` and `git diff --stat @{u}..HEAD`.
- No upstream / first push: `git log --oneline --stat` and `git diff --stat` on current branch.

## 8. Final response

Do **not** dump raw commands. Reply with:

- **Commit & push status:** New commit yes/no; pushed yes/no; branch name.
- **Version info:** Bumped (from → to) or skipped (reason).
- **Summary since last push:** 3–7 bullet points (new/edited files, features, fixes, config). High-signal only.
