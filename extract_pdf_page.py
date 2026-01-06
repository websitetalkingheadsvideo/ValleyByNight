#!/usr/bin/env python3
"""
Extract a single page from a PDF file and output as text.
Usage: python extract_pdf_page.py <pdf_file> <page_number>
"""
import sys
import os

try:
    import PyPDF2
    HAS_PYPDF2 = True
except ImportError:
    HAS_PYPDF2 = False

try:
    import pdfplumber
    HAS_PDFPLUMBER = True
except ImportError:
    HAS_PDFPLUMBER = False

def extract_page_pypdf2(pdf_path, page_num):
    """Extract text from a specific page using PyPDF2."""
    with open(pdf_path, 'rb') as file:
        reader = PyPDF2.PdfReader(file)
        if page_num < 1 or page_num > len(reader.pages):
            return None, f"Page {page_num} not found. PDF has {len(reader.pages)} pages."
        page = reader.pages[page_num - 1]  # 0-indexed
        text = page.extract_text()
        return text, None

def extract_page_pdfplumber(pdf_path, page_num):
    """Extract text from a specific page using pdfplumber (better formatting)."""
    with pdfplumber.open(pdf_path) as pdf:
        if page_num < 1 or page_num > len(pdf.pages):
            return None, f"Page {page_num} not found. PDF has {len(pdf.pages)} pages."
        page = pdf.pages[page_num - 1]  # 0-indexed
        text = page.extract_text()
        return text, None

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python extract_pdf_page.py <pdf_file> <page_number>")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    try:
        page_num = int(sys.argv[2])
    except ValueError:
        print("Error: Page number must be an integer")
        sys.exit(1)
    
    if not os.path.exists(pdf_path):
        print(f"Error: File not found: {pdf_path}")
        sys.exit(1)
    
    # Try pdfplumber first (better formatting), then PyPDF2
    if HAS_PDFPLUMBER:
        text, error = extract_page_pdfplumber(pdf_path, page_num)
    elif HAS_PYPDF2:
        text, error = extract_page_pypdf2(pdf_path, page_num)
    else:
        print("Error: No PDF library available. Please install pdfplumber or PyPDF2:")
        print("  pip install pdfplumber  # Recommended")
        print("  pip install PyPDF2       # Alternative")
        sys.exit(1)
    
    if error:
        print(error)
        sys.exit(1)
    
    if text:
        print(text)
    else:
        print(f"Warning: No text extracted from page {page_num}")
