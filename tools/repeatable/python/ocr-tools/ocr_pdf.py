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

def convert_pdf_to_images(pdf_path: Path, dpi: int = 300) -> list[Path]:
    """
    Convert PDF pages to images using PyMuPDF.
    
    Returns list of temporary image file paths.
    """
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
    
    # Calculate zoom factor from DPI (default 72 DPI, so 300/72 = ~4.17)
    zoom = dpi / 72.0
    mat = fitz.Matrix(zoom, zoom)
    
    # Save images to temporary files
    temp_dir = Path.cwd() / "tmp_ocr_images"
    temp_dir.mkdir(exist_ok=True)
    
    image_paths = []
    for page_num in range(len(doc)):
        page = doc[page_num]
        pix = page.get_pixmap(matrix=mat)
        
        # Convert to PIL Image
        img_data = pix.tobytes("png")
        img = Image.open(io.BytesIO(img_data))
        
        # Save to temp file
        image_path = temp_dir / f"page_{page_num + 1:04d}.png"
        img.save(image_path, "PNG")
        image_paths.append(image_path)
    
    doc.close()
    return image_paths

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
    
    Args:
        pdf_path: Path to the PDF file
        output_path: Optional path to save output (if None, returns text)
        lang: Tesseract language code (default: "eng")
        dpi: DPI for image conversion (default: 300)
    
    Returns:
        Extracted text
    """
    if not pdf_path.exists():
        print(f"Error: PDF file not found: {pdf_path}", file=sys.stderr)
        sys.exit(1)
    
    print(f"Converting PDF to images (DPI: {dpi})...", file=sys.stderr)
    image_paths = convert_pdf_to_images(pdf_path, dpi=dpi)
    
    print(f"Running OCR on {len(image_paths)} pages...", file=sys.stderr)
    full_text = []
    
    for i, image_path in enumerate(image_paths, 1):
        print(f"Processing page {i}/{len(image_paths)}...", file=sys.stderr)
        page_text = ocr_image(image_path, lang=lang)
        if page_text and page_text.strip():
            full_text.append(f"=== PAGE {i} ===\n{page_text}\n")
    
    # Cleanup temporary images
    cleanup_temp_images(image_paths)
    
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
