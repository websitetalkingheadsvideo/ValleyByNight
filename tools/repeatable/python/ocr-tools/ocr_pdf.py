#!/usr/bin/env python3
"""
Extract text from image-based PDFs using Tesseract OCR.

This tool performs actual OCR on PDFs that contain scanned images rather than
selectable text. It converts PDF pages to images and runs Tesseract OCR on each page.

Usage: python ocr_pdf.py <pdf_file> [output_file] [--lang=LANG] [--dpi=DPI]
"""

import sys
import subprocess
import shutil
import io
from pathlib import Path
from typing import Optional

def check_tesseract() -> bool:
    """Check if Tesseract is installed and accessible."""
    tesseract_path = shutil.which('tesseract')
    if tesseract_path:
        return True
    
    # Try common Windows installation path
    windows_path = Path("C:/Program Files/Tesseract-OCR/tesseract.exe")
    if windows_path.exists():
        return True
    
    return False

def get_tesseract_path() -> Optional[str]:
    """Get the path to Tesseract executable."""
    tesseract_path = shutil.which('tesseract')
    if tesseract_path:
        return tesseract_path
    
    # Try common Windows installation path
    windows_path = Path("C:/Program Files/Tesseract-OCR/tesseract.exe")
    if windows_path.exists():
        return str(windows_path)
    
    return None

def check_pymupdf() -> bool:
    """Check if PyMuPDF is available."""
    try:
        import fitz  # PyMuPDF
        return True
    except ImportError:
        return False

def _get_temp_dir() -> Path:
    """Return a single temp dir for this run (reused for one page at a time)."""
    temp_base = Path.cwd() / "tmp_ocr_images"
    temp_base.mkdir(exist_ok=True)
    return temp_base

def ocr_image(image_path: Path, lang: str = "eng") -> str:
    """Run Tesseract OCR on an image and return the text."""
    tesseract_path = get_tesseract_path()
    if not tesseract_path:
        print("Error: Tesseract not found. Please install Tesseract OCR.", file=sys.stderr)
        sys.exit(1)
    
    try:
        # Run Tesseract: tesseract image.png output -l lang
        # Use errors='replace' to handle encoding issues
        result = subprocess.run(
            [tesseract_path, str(image_path), "stdout", "-l", lang],
            capture_output=True,
            text=False,  # Get bytes first
            check=True
        )
        # Decode with error handling
        text = result.stdout.decode('utf-8', errors='replace')
        return text if text else ""
    except subprocess.CalledProcessError as e:
        error_msg = e.stderr.decode('utf-8', errors='replace') if e.stderr else "Unknown error"
        print(f"Error running Tesseract on {image_path.name}: {error_msg}", file=sys.stderr)
        return ""
    except FileNotFoundError:
        print(f"Error: Tesseract executable not found at {tesseract_path}", file=sys.stderr)
        sys.exit(1)

def cleanup_temp_images(image_paths: list[Path]):
    """Remove temporary image files."""
    for image_path in image_paths:
        try:
            image_path.unlink()
        except Exception:
            pass
    
    # Remove temp directory if empty
    if image_paths:
        temp_dir = image_paths[0].parent
        try:
            temp_dir.rmdir()
        except Exception:
            pass

def ocr_pdf(pdf_path: Path, output_path: Optional[Path] = None, lang: str = "eng", dpi: int = 300) -> str:
    """
    Perform OCR on a PDF file.
    Processes one page at a time (create image -> OCR -> delete image) to avoid
    temp files going missing on long runs and to limit disk use.
    """
    if not pdf_path.exists():
        print(f"Error: PDF file not found: {pdf_path}", file=sys.stderr)
        sys.exit(1)

    try:
        import fitz  # PyMuPDF
        from PIL import Image
    except ImportError:
        print("Error: PyMuPDF not installed. Install with: pip install pymupdf", file=sys.stderr)
        sys.exit(1)

    try:
        doc = fitz.open(str(pdf_path))
    except Exception as e:
        print(f"Error opening PDF: {e}", file=sys.stderr)
        sys.exit(1)

    total_pages = len(doc)
    zoom = dpi / 72.0
    mat = fitz.Matrix(zoom, zoom)
    temp_dir = _get_temp_dir()
    # Single temp file reused each page so only one image on disk at a time
    temp_image = temp_dir / "_ocr_current_page.png"

    print(f"Running OCR on {total_pages} pages (one page at a time, DPI={dpi})...", file=sys.stderr)
    full_text = []

    try:
        for page_num in range(total_pages):
            i = page_num + 1
            if i % 10 == 0 or i == 1:
                print(f"Processing page {i}/{total_pages}...", file=sys.stderr)
            page = doc[page_num]
            pix = page.get_pixmap(matrix=mat)
            img_data = pix.tobytes("png")
            img = Image.open(io.BytesIO(img_data))
            img.save(temp_image, "PNG")
            page_text = ocr_image(temp_image, lang=lang)
            if page_text and page_text.strip():
                full_text.append(f"=== PAGE {i} ===\n{page_text}\n")
            try:
                temp_image.unlink()
            except Exception:
                pass
    finally:
        doc.close()
        try:
            if temp_image.exists():
                temp_image.unlink()
        except Exception:
            pass

    combined_text = "\n".join(full_text)

    if output_path:
        output_path.parent.mkdir(parents=True, exist_ok=True)
        output_path.write_text(combined_text, encoding='utf-8')
        print(f"\nOCR text saved to: {output_path}", file=sys.stderr)

    return combined_text

def main():
    """Main entry point."""
    if len(sys.argv) < 2:
        print("Usage: python ocr_pdf.py <pdf_file> [output_file] [--lang=LANG] [--dpi=DPI]", file=sys.stderr)
        print("\nOptions:", file=sys.stderr)
        print("  --lang=LANG    Tesseract language code (default: eng)", file=sys.stderr)
        print("  --dpi=DPI      DPI for image conversion (default: 300)", file=sys.stderr)
        print("\nExample:", file=sys.stderr)
        print("  python ocr_pdf.py book.pdf output.txt", file=sys.stderr)
        print("  python ocr_pdf.py book.pdf output.txt --lang=eng --dpi=300", file=sys.stderr)
        sys.exit(1)
    
    # Check dependencies
    if not check_tesseract():
        print("Error: Tesseract OCR not found.", file=sys.stderr)
        print("Please install Tesseract OCR:", file=sys.stderr)
        print("  Windows: Download from https://github.com/UB-Mannheim/tesseract/wiki", file=sys.stderr)
        sys.exit(1)
    
    if not check_pymupdf():
        print("Error: PyMuPDF not installed.", file=sys.stderr)
        print("Install with: pip install pymupdf", file=sys.stderr)
        sys.exit(1)
    
    # Parse arguments
    pdf_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2]) if len(sys.argv) > 2 and not sys.argv[2].startswith('--') else None
    
    lang = "eng"
    dpi = 300
    
    for arg in sys.argv[2:]:
        if arg.startswith('--lang='):
            lang = arg.split('=', 1)[1]
        elif arg.startswith('--dpi='):
            try:
                dpi = int(arg.split('=', 1)[1])
            except ValueError:
                print(f"Warning: Invalid DPI value: {arg.split('=', 1)[1]}, using default 300", file=sys.stderr)
    
    # Perform OCR
    text = ocr_pdf(pdf_path, output_path, lang=lang, dpi=dpi)
    
    if not output_path:
        print(text)

if __name__ == "__main__":
    main()
