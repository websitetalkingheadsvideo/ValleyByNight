#!/usr/bin/env python3
"""
Extract full text content from a PDF file.
Usage: python extract_full_pdf_text.py <pdf_file> [output_file]
"""
import sys
import os
from pathlib import Path

try:
    import pdfplumber
    HAS_PDFPLUMBER = True
except ImportError:
    HAS_PDFPLUMBER = False

try:
    import PyPDF2
    HAS_PYPDF2 = True
except ImportError:
    HAS_PYPDF2 = False

def extract_text_pdfplumber(pdf_path):
    """Extract text from entire PDF using pdfplumber (better formatting)."""
    full_text = []
    try:
        with pdfplumber.open(pdf_path) as pdf:
            total_pages = len(pdf.pages)
            for page_num, page in enumerate(pdf.pages, 1):
                text = page.extract_text()
                if text:
                    full_text.append(f"=== PAGE {page_num} ===\n{text}\n")
                if page_num % 50 == 0:
                    print(f"Processed {page_num}/{total_pages} pages...", file=sys.stderr)
        return "\n".join(full_text), None
    except Exception as e:
        return None, str(e)

def extract_text_pypdf2(pdf_path):
    """Extract text from entire PDF using PyPDF2."""
    full_text = []
    try:
        with open(pdf_path, 'rb') as file:
            reader = PyPDF2.PdfReader(file)
            total_pages = len(reader.pages)
            for page_num, page in enumerate(reader.pages, 1):
                text = page.extract_text()
                if text:
                    full_text.append(f"=== PAGE {page_num} ===\n{text}\n")
                if page_num % 50 == 0:
                    print(f"Processed {page_num}/{total_pages} pages...", file=sys.stderr)
        return "\n".join(full_text), None
    except Exception as e:
        return None, str(e)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python extract_full_pdf_text.py <pdf_file> [output_file]")
        sys.exit(1)
    
    pdf_path = Path(sys.argv[1])
    output_file = sys.argv[2] if len(sys.argv) > 2 else None
    
    if not pdf_path.exists():
        print(f"Error: File not found: {pdf_path}", file=sys.stderr)
        sys.exit(1)
    
    print(f"Extracting text from: {pdf_path.name}", file=sys.stderr)
    
    # Try pdfplumber first (better formatting), then PyPDF2
    text, error = None, None
    if HAS_PDFPLUMBER:
        text, error = extract_text_pdfplumber(str(pdf_path))
    elif HAS_PYPDF2:
        text, error = extract_text_pypdf2(str(pdf_path))
    else:
        print("Error: No PDF library available. Please install pdfplumber or PyPDF2:", file=sys.stderr)
        print("  pip install pdfplumber  # Recommended", file=sys.stderr)
        print("  pip install PyPDF2       # Alternative", file=sys.stderr)
        sys.exit(1)
    
    if error:
        print(f"Error: {error}", file=sys.stderr)
        sys.exit(1)
    
    if not text or len(text.strip()) < 100:
        print(f"Warning: Very little text extracted from PDF (may be image-based)", file=sys.stderr)
    
    if output_file:
        output_path = Path(output_file)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(text)
        print(f"Text extracted to: {output_path}", file=sys.stderr)
    else:
        print(text)
