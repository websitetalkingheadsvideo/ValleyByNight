#!/usr/bin/env python3
"""
Step 1: Extract text from PDF with page markers
Usage: python3 extract_pdf_with_markers.py input.pdf output.txt
"""

import pdfplumber
import sys

def extract_pdf_with_page_markers(pdf_path, output_path):
    """Extract text from PDF with <!-- PAGE X --> markers"""
    
    print(f"Opening PDF: {pdf_path}")
    
    with pdfplumber.open(pdf_path) as pdf:
        total_pages = len(pdf.pages)
        print(f"Total pages in PDF: {total_pages}")
        
        with open(output_path, 'w', encoding='utf-8') as out:
            for i, page in enumerate(pdf.pages, start=1):
                if i % 10 == 0:
                    print(f"Processing page {i}/{total_pages}")
                
                # Write page marker
                out.write(f"<!-- PAGE {i} -->\n")
                
                # Extract text from page
                try:
                    text = page.extract_text()
                    if text and text.strip():
                        out.write(text)
                        out.write("\n")
                    else:
                        out.write(f"[No text content]\n")
                except Exception as e:
                    print(f"Error on page {i}: {e}")
                    out.write(f"[Error: {e}]\n")
                
                out.write("\n")
    
    print(f"\nExtraction complete!")
    print(f"Output saved to: {output_path}")
    print(f"Next step: Review the file for OCR artifacts")

def main():
    if len(sys.argv) != 3:
        print("Usage: python3 extract_pdf_with_markers.py input.pdf output.txt")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    output_path = sys.argv[2]
    
    extract_pdf_with_page_markers(pdf_path, output_path)

if __name__ == "__main__":
    main()
