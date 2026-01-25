#!/usr/bin/env python3
"""
Scan reference/Books for PDFs, compare to reference/Books_summaries.
For each PDF without a matching summary, run extract_full_pdf_text and record
whether it needs OCR (very little text extracted = image-based).
Writes reference/Books/books_ocr.md with findings.
"""
from __future__ import annotations

import re
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path


def _repo_root() -> Path:
    return Path(__file__).resolve().parents[4]


def _norm_stem(name: str, ext: str) -> str:
    base = name[: -len(ext)] if name.lower().endswith(ext.lower()) else name
    base = re.sub(r"\([^)]*\)", "", base)
    base = re.sub(r"[^a-zA-Z0-9]+", "_", base).strip("_").lower()
    return base


def _word_set(s: str) -> frozenset[str]:
    return frozenset(w for w in s.split("_") if len(w) > 1)


def _pdf_matches_summary(pdf_norm: str, summary_keys: set[str], summary_word_sets: dict[str, frozenset[str]]) -> bool:
    if pdf_norm in summary_keys:
        return True
    ws = _word_set(pdf_norm)
    for k, kw in summary_word_sets.items():
        if ws == kw or (len(ws) >= 3 and ws <= kw) or (len(kw) >= 3 and kw <= ws):
            return True
    return False


def _collect_summary_keys(summaries_dir: Path) -> tuple[set[str], dict[str, frozenset[str]]]:
    keys: set[str] = set()
    word_sets: dict[str, frozenset[str]] = {}
    for f in summaries_dir.glob("*.md"):
        n = _norm_stem(f.name, ".md")
        keys.add(n)
        word_sets[n] = _word_set(n)
    return keys, word_sets


def _check_pdf_needs_ocr(pdf_path: Path, extract_script: Path, tmp_out: Path) -> bool:
    proc = subprocess.run(
        [sys.executable, str(extract_script), str(pdf_path), str(tmp_out)],
        capture_output=True,
        text=True,
        timeout=300,
    )
    err = (proc.stderr or "") + (proc.stdout or "")
    if "Warning: Very little text extracted" in err:
        return True
    if tmp_out.exists() and tmp_out.stat().st_size < 100:
        return True
    return False


def main(
    books_dir: Path,
    summaries_dir: Path,
    output_path: Path,
    extract_script: Path,
    root: Path,
) -> None:
    summary_keys, summary_word_sets = _collect_summary_keys(summaries_dir)
    pdfs = sorted(books_dir.rglob("*.pdf"))
    no_summary: list[Path] = []
    for p in pdfs:
        n = _norm_stem(p.name, ".pdf")
        if not _pdf_matches_summary(n, summary_keys, summary_word_sets):
            no_summary.append(p)

    tmp = root / "tmp" / "scan_books_ocr_tmp.txt"
    needs_ocr: list[Path] = []
    extract_ok: list[Path] = []
    for i, p in enumerate(no_summary, 1):
        rel = p.relative_to(books_dir)
        print(f"[{i}/{len(no_summary)}] {rel}", flush=True)
        try:
            if _check_pdf_needs_ocr(p, extract_script, tmp):
                needs_ocr.append(p)
                print("  -> needs OCR", flush=True)
            else:
                extract_ok.append(p)
                print("  -> extract OK", flush=True)
        except Exception as e:
            print(f"  -> error: {e}", flush=True)
    if tmp.exists():
        tmp.unlink()

    stamp = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")
    lines = [
        "# Books: Missing Summaries and OCR Status",
        "",
        f"**Last run:** {stamp}",
        "",
        "PDFs under `reference/Books` that have **no matching summary** in `reference/Books_summaries`.",
        "For each, we ran `extract_full_pdf_text.py`; **needs OCR** = very little text extracted (image-based).",
        "",
        "---",
        "",
        f"**Total PDFs in Books:** {len(pdfs)}",
        f"**With matching summary:** {len(pdfs) - len(no_summary)}",
        f"**Without summary:** {len(no_summary)}",
        f"**Of those, need OCR:** {len(needs_ocr)}",
        f"**Of those, extract OK:** {len(extract_ok)}",
        "",
        "---",
        "",
        "## Needs OCR (no summary)",
        "",
    ]
    for p in needs_ocr:
        r = p.relative_to(books_dir)
        lines.append(f"- `{r.as_posix()}`")
    lines.extend(["", "## Extract OK (no summary)", ""])
    for p in extract_ok:
        r = p.relative_to(books_dir)
        lines.append(f"- `{r.as_posix()}`")
    lines.append("")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text("\n".join(lines), encoding="utf-8")
    print(f"\nReport written to {output_path}", flush=True)


if __name__ == "__main__":
    import argparse

    root = _repo_root()
    ap = argparse.ArgumentParser(description="Scan Books vs Books_summaries, check OCR for PDFs without summary.")
    ap.add_argument("--books-dir", type=Path, default=root / "reference" / "Books", help="Books root")
    ap.add_argument("--summaries-dir", type=Path, default=root / "reference" / "Books_summaries", help="Books_summaries root")
    ap.add_argument("--output", type=Path, default=root / "reference" / "Books" / "books_ocr.md", help="Output markdown path")
    ap.add_argument(
        "--extract-script",
        type=Path,
        default=root / "tools" / "repeatable" / "python" / "extract_full_pdf_text.py",
        help="Path to extract_full_pdf_text.py",
    )
    args = ap.parse_args()

    main(args.books_dir, args.summaries_dir, args.output, args.extract_script, root)
