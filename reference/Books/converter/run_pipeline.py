#!/usr/bin/env python3
"""
Batch PDF → RAG conversion pipeline.

Discover PDFs under reference/Books; output to agents/laws_agent/Books.

Usage:
  python run_pipeline.py --list
      List all PDFs under --books-dir (default: V:\\reference\\Books).

  python run_pipeline.py --pdf "V:\\reference\\Books\\MET - VTM - Liber des Goules (5006).pdf"
      Run extract, inspect, clean, convert, post-process for one book.
      Output: _rag.json in Books/; _raw.txt, _artifact_report.txt, _final.txt in Books/backups/.

  python run_pipeline.py --pdf path/to/book.pdf --skip-clean
      Extract and inspect only (no clean, no convert).

  python run_pipeline.py --pdf path/to/book.pdf --skip-convert
      Extract, inspect, clean only (no JSON).

Options:
  --books-dir DIR    Root directory to search for PDFs (default: repo reference/Books).
  --output-dir DIR   Where to write _rag.json (default: agents/laws_agent/Books). Intermediate files go to output-dir/backups/.
  --patterns FILE    Per-book artifact patterns for clean step.
  --config FILE      Book config JSON for convert step (source_title, book_code, page_ranges).
"""

import re
import subprocess
import sys
from pathlib import Path

# Default paths (absolute)
SCRIPT_DIR = Path(__file__).resolve().parent
REPO_ROOT = SCRIPT_DIR.parent.parent.parent  # reference/Books/converter -> reference/Books -> reference -> repo root
DEFAULT_BOOKS_DIR = REPO_ROOT / "reference" / "Books"
DEFAULT_OUTPUT_DIR = REPO_ROOT / "agents" / "laws_agent" / "Books"
CONVERTER_DIR = SCRIPT_DIR


def slug_from_pdf_path(pdf_path: Path) -> str:
    stem = pdf_path.stem
    # Alphanumeric, underscore, keep one space -> underscore
    s = re.sub(r"[^\w\s-]", "", stem)
    s = re.sub(r"[-\s]+", "_", s).strip("_").lower()
    if not s:
        s = "book"
    return s


def discover_pdfs(books_dir: Path) -> list[Path]:
    out: list[Path] = []
    for p in books_dir.rglob("*.pdf"):
        if p.is_file():
            out.append(p)
    return sorted(out)


def run_step(cmd: list[str], step_name: str) -> bool:
    print(f"[{step_name}] {' '.join(cmd)}")
    r = subprocess.run(cmd)
    if r.returncode != 0:
        print(f"[{step_name}] failed with exit code {r.returncode}")
        return False
    return True


def run_pipeline_one(
    pdf_path: Path,
    output_dir: Path,
    backups_dir: Path,
    patterns_file: Path | None,
    config_file: Path | None,
    skip_clean: bool,
    skip_convert: bool,
) -> None:
    slug = slug_from_pdf_path(pdf_path)
    raw_path = backups_dir / f"{slug}_raw.txt"
    report_path = backups_dir / f"{slug}_artifact_report.txt"
    final_path = backups_dir / f"{slug}_final.txt"
    json_path = output_dir / f"{slug}_rag.json"

    output_dir.mkdir(parents=True, exist_ok=True)
    backups_dir.mkdir(parents=True, exist_ok=True)

    # Step 1: Extract
    if not run_step(
        [sys.executable, str(CONVERTER_DIR / "extract_pdf_with_markers.py"), str(pdf_path), str(raw_path)],
        "Extract",
    ):
        return

    # Step 2: Inspect (report for manual review)
    cmd_inspect = [sys.executable, str(CONVERTER_DIR / "inspect_artifacts.py"), str(raw_path), str(report_path)]
    if not run_step(cmd_inspect, "Inspect"):
        return
    print(f"  Review: {report_path}")

    if skip_clean and skip_convert:
        return

    # Step 4: Clean
    if not skip_clean:
        cmd_clean = [
            sys.executable,
            str(CONVERTER_DIR / "clean_artifacts_and_rejoin.py"),
            str(raw_path),
            str(final_path),
        ]
        if patterns_file and patterns_file.exists():
            cmd_clean.extend(["--patterns", str(patterns_file)])
        if not run_step(cmd_clean, "Clean"):
            return

    if skip_convert:
        return

    # Step 5: Convert to JSON (auto-detect config by slug if not provided)
    cmd_convert = [
        sys.executable,
        str(CONVERTER_DIR / "convert_to_rag_json.py"),
        str(final_path),
        str(json_path),
    ]
    if config_file and config_file.exists():
        cmd_convert.extend(["--config", str(config_file)])
    else:
        auto_config = CONVERTER_DIR / "config" / f"{slug}.json"
        if auto_config.exists():
            cmd_convert.extend(["--config", str(auto_config)])
    if not run_step(cmd_convert, "Convert"):
        return

    # Step 6: Post-process (OCR + spelling/caps fixes from Books)
    if not run_step(
        [sys.executable, str(CONVERTER_DIR / "post_process_rag_json.py"), str(json_path)],
        "Post-process",
    ):
        return

    print(f"Done: {json_path}")


def main() -> None:
    import argparse
    parser = argparse.ArgumentParser(description="PDF to RAG conversion pipeline")
    parser.add_argument("--list", action="store_true", help="List all PDFs under --books-dir")
    parser.add_argument("--pdf", type=str, help="Path to one PDF to process")
    parser.add_argument("--books-dir", type=Path, default=DEFAULT_BOOKS_DIR, help="Root dir to search for PDFs")
    parser.add_argument("--output-dir", type=Path, default=DEFAULT_OUTPUT_DIR, help="Output directory for raw/final/json")
    parser.add_argument("--patterns", type=Path, help="Per-book artifact patterns file for clean step")
    parser.add_argument("--config", type=Path, help="Book config JSON for convert step")
    parser.add_argument("--skip-clean", action="store_true", help="Only extract and inspect")
    parser.add_argument("--skip-convert", action="store_true", help="Do not run convert to JSON")
    args = parser.parse_args()

    if args.list:
        books_dir = args.books_dir.resolve()
        if not books_dir.exists():
            print(f"Books dir not found: {books_dir}")
            sys.exit(1)
        pdfs = discover_pdfs(books_dir)
        for p in pdfs:
            print(p)
        print(f"Total: {len(pdfs)} PDFs")
        return

    if not args.pdf:
        parser.print_help()
        print("\nUse --list to see PDFs, or --pdf path/to/book.pdf to process one book.")
        sys.exit(1)

    pdf_path = Path(args.pdf).resolve()
    if not pdf_path.exists():
        print(f"PDF not found: {pdf_path}")
        sys.exit(1)

    output_dir = args.output_dir.resolve()
    backups_dir = output_dir / "backups"
    run_pipeline_one(
        pdf_path,
        output_dir,
        backups_dir,
        args.patterns,
        args.config,
        args.skip_clean,
        args.skip_convert,
    )


if __name__ == "__main__":
    main()
