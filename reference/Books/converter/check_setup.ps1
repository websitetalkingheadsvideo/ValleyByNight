# Diagnostic Script - Check Setup for Clan Books Processing
# Run this first to verify everything is ready
# Usage: .\check_setup.ps1

Write-Host "=== PDF Processing System Diagnostic ===" -ForegroundColor Cyan
Write-Host ""

$allGood = $true

# Check 1: Python
Write-Host "[1/8] Checking Python..." -ForegroundColor Yellow
try {
    $pythonVersion = python --version 2>&1
    Write-Host "  ✓ Python found: $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Python not found!" -ForegroundColor Red
    Write-Host "    Install: https://www.python.org/downloads/" -ForegroundColor Yellow
    $allGood = $false
}

# Check 2: pdfplumber
Write-Host "[2/8] Checking pdfplumber library..." -ForegroundColor Yellow
$pdfplumber = python -c "import pdfplumber; print('installed')" 2>&1
if ($pdfplumber -eq "installed") {
    Write-Host "  ✓ pdfplumber installed" -ForegroundColor Green
} else {
    Write-Host "  ✗ pdfplumber not installed!" -ForegroundColor Red
    Write-Host "    Install: pip install pdfplumber" -ForegroundColor Yellow
    $allGood = $false
}

# Check 3: pyspellchecker
Write-Host "[3/8] Checking pyspellchecker library..." -ForegroundColor Yellow
$pyspellchecker = python -c "import spellchecker; print('installed')" 2>&1
if ($pyspellchecker -eq "installed") {
    Write-Host "  ✓ pyspellchecker installed" -ForegroundColor Green
} else {
    Write-Host "  ✗ pyspellchecker not installed!" -ForegroundColor Red
    Write-Host "    Install: pip install pyspellchecker" -ForegroundColor Yellow
    $allGood = $false
}

# Check 4: Converter scripts
Write-Host "[4/8] Checking converter scripts..." -ForegroundColor Yellow
$requiredScripts = @(
    "extract_pdf_with_markers.py",
    "inspect_artifacts.py",
    "clean_artifacts_and_rejoin.py",
    "convert_to_rag_json.py",
    "post_process_rag_json.py",
    "run_pipeline.py"
)

$converterDir = "V:\reference\Books\converter"
if (Test-Path $converterDir) {
    Write-Host "  ✓ Converter directory found: $converterDir" -ForegroundColor Green
    
    foreach ($script in $requiredScripts) {
        $scriptPath = Join-Path $converterDir $script
        if (Test-Path $scriptPath) {
            Write-Host "    ✓ $script" -ForegroundColor Green
        } else {
            Write-Host "    ✗ $script missing!" -ForegroundColor Red
            $allGood = $false
        }
    }
} else {
    Write-Host "  ✗ Converter directory not found: $converterDir" -ForegroundColor Red
    Write-Host "    Create it and place the scripts there" -ForegroundColor Yellow
    $allGood = $false
}

# Check 5: Clan Books directory
Write-Host "[5/8] Checking Clan Books directory..." -ForegroundColor Yellow
$clanBooksDir = "V:\reference\Books\Clan Books"
if (Test-Path $clanBooksDir) {
    $pdfCount = (Get-ChildItem "$clanBooksDir\*.pdf").Count
    Write-Host "  ✓ Clan Books directory found" -ForegroundColor Green
    Write-Host "    Found $pdfCount PDF files" -ForegroundColor Cyan
    
    if ($pdfCount -eq 0) {
        Write-Host "    ⚠ No PDF files found!" -ForegroundColor Yellow
        Write-Host "      Place Clan Book PDFs in: $clanBooksDir" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ✗ Clan Books directory not found: $clanBooksDir" -ForegroundColor Red
    Write-Host "    Create it and place PDFs there" -ForegroundColor Yellow
    $allGood = $false
}

# Check 6: Output directory
Write-Host "[6/8] Checking output directory..." -ForegroundColor Yellow
$outputDir = "V:\agents\laws_agent\Books"
if (Test-Path $outputDir) {
    Write-Host "  ✓ Output directory exists: $outputDir" -ForegroundColor Green
} else {
    Write-Host "  ⚠ Output directory doesn't exist: $outputDir" -ForegroundColor Yellow
    Write-Host "    Will be created automatically" -ForegroundColor Cyan
}

# Check 7: Test PDF extraction (if PDFs exist)
Write-Host "[7/8] Testing PDF extraction..." -ForegroundColor Yellow
if ((Test-Path $clanBooksDir) -and (Get-ChildItem "$clanBooksDir\*.pdf").Count -gt 0) {
    $testPdf = (Get-ChildItem "$clanBooksDir\*.pdf" | Select-Object -First 1).FullName
    $testOutput = Join-Path $env:TEMP "pdf_test.txt"
    
    try {
        Set-Location $converterDir
        python extract_pdf_with_markers.py $testPdf $testOutput 2>&1 | Out-Null
        
        if (Test-Path $testOutput) {
            $noTextCount = (Select-String -Path $testOutput -Pattern "\[No text content\]" -AllMatches).Matches.Count
            $pageCount = (Select-String -Path $testOutput -Pattern "<!-- PAGE" -AllMatches).Matches.Count
            $percentNoText = [math]::Round(($noTextCount / $pageCount) * 100, 1)
            
            Remove-Item $testOutput -ErrorAction SilentlyContinue
            
            if ($percentNoText -lt 50) {
                Write-Host "  ✓ PDF extraction working ($percentNoText% blank pages)" -ForegroundColor Green
            } else {
                Write-Host "  ⚠ PDFs may need OCR ($percentNoText% blank pages)" -ForegroundColor Yellow
                Write-Host "    Most pages have no extractable text" -ForegroundColor Yellow
                Write-Host "    These appear to be image-based PDFs" -ForegroundColor Yellow
                Write-Host "    Recommendation: OCR them with Adobe Acrobat or OCRmyPDF" -ForegroundColor Cyan
            }
        }
    } catch {
        Write-Host "  ✗ PDF extraction test failed!" -ForegroundColor Red
        Write-Host "    Error: $_" -ForegroundColor Red
        $allGood = $false
    }
} else {
    Write-Host "  ⊘ Skipped (no PDFs to test)" -ForegroundColor Gray
}

# Check 8: PHP for importing (optional)
Write-Host "[8/8] Checking PHP (for import step)..." -ForegroundColor Yellow
try {
    $phpVersion = php --version 2>&1
    if ($phpVersion -match "PHP") {
        Write-Host "  ✓ PHP found" -ForegroundColor Green
    } else {
        Write-Host "  ⊘ PHP not found (optional, needed for import step)" -ForegroundColor Gray
    }
} catch {
    Write-Host "  ⊘ PHP not found (optional, needed for import step)" -ForegroundColor Gray
}

# Summary
Write-Host ""
Write-Host "=== Diagnostic Summary ===" -ForegroundColor Cyan
if ($allGood) {
    Write-Host "✓ All critical checks passed!" -ForegroundColor Green
    Write-Host "  You're ready to process Clan Books" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  1. Test one book: .\process_clan_books.ps1 -TestOne" -ForegroundColor White
    Write-Host "  2. Process all: .\process_clan_books.ps1" -ForegroundColor White
    Write-Host "  3. Import JSONs: see QUICK_REFERENCE.md" -ForegroundColor White
} else {
    Write-Host "✗ Some issues found - fix them before processing" -ForegroundColor Red
    Write-Host "  See messages above for details" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "For detailed help: see Windows_PDF_Processing_Guide.md" -ForegroundColor Cyan
