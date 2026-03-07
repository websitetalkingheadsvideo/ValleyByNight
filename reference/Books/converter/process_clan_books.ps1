# Process All Clan Books - PowerShell Script
# Place this in: V:\reference\Books\converter\process_clan_books.ps1

param(
    [string]$BooksDir = "V:\reference\Books\Clan Books",
    [string]$OutputDir = "V:\agents\laws_agent\Books",
    [string]$ConverterDir = "V:\reference\Books\converter",
    [switch]$TestOne,
    [switch]$SkipOCRCheck
)

Write-Host "=== Clan Books PDF Processing ===" -ForegroundColor Cyan
Write-Host ""

# Change to converter directory
Set-Location $ConverterDir

# Test if scripts exist
if (!(Test-Path "run_pipeline.py")) {
    Write-Host "ERROR: Scripts not found in $ConverterDir" -ForegroundColor Red
    Write-Host "Current directory: $(Get-Location)"
    exit 1
}

# Get all PDF files
$books = Get-ChildItem "$BooksDir\*.pdf" | Sort-Object Name

if ($books.Count -eq 0) {
    Write-Host "ERROR: No PDF files found in $BooksDir" -ForegroundColor Red
    exit 1
}

Write-Host "Found $($books.Count) books in: $BooksDir" -ForegroundColor Green
Write-Host ""

# Test mode: Just process first book
if ($TestOne) {
    $books = @($books[0])
    Write-Host "TEST MODE: Processing only first book" -ForegroundColor Yellow
    Write-Host ""
}

# OCR Check (optional)
if (!$SkipOCRCheck -and !$TestOne) {
    Write-Host "=== OCR Check ===" -ForegroundColor Cyan
    Write-Host "Checking first book for extractable text..." -ForegroundColor Yellow
    
    $testBook = $books[0]
    $testOutput = Join-Path $env:TEMP "ocr_test.txt"
    
    python extract_pdf_with_markers.py $testBook.FullName $testOutput 2>&1 | Out-Null
    
    $noTextCount = (Select-String -Path $testOutput -Pattern "\[No text content\]" -AllMatches).Matches.Count
    $pageCount = (Select-String -Path $testOutput -Pattern "<!-- PAGE" -AllMatches).Matches.Count
    
    Remove-Item $testOutput -ErrorAction SilentlyContinue
    
    $percentNoText = [math]::Round(($noTextCount / $pageCount) * 100, 1)
    
    Write-Host "  Pages with no text: $noTextCount / $pageCount ($percentNoText%)" -ForegroundColor $(if ($percentNoText -gt 50) { "Red" } else { "Green" })
    
    if ($percentNoText -gt 50) {
        Write-Host ""
        Write-Host "WARNING: Most pages have no extractable text!" -ForegroundColor Red
        Write-Host "These PDFs appear to be image-based and require OCR first." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Options:" -ForegroundColor Cyan
        Write-Host "  1. Use Adobe Acrobat Pro: Tools > Recognize Text > In This File"
        Write-Host "  2. Use OCRmyPDF: ocrmypdf input.pdf output.pdf --language eng"
        Write-Host "  3. Continue anyway (will create empty outputs): -SkipOCRCheck"
        Write-Host ""
        
        $continue = Read-Host "Continue processing anyway? (y/n)"
        if ($continue -ne 'y') {
            Write-Host "Aborted. Please OCR the PDFs first." -ForegroundColor Yellow
            exit 0
        }
    }
    Write-Host ""
}

# Create output directory if needed
if (!(Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
    Write-Host "Created output directory: $OutputDir" -ForegroundColor Green
}

# Process each book
$successful = 0
$failed = 0
$startTime = Get-Date

foreach ($book in $books) {
    Write-Host "=== Processing: $($book.Name) ===" -ForegroundColor Cyan
    Write-Host "Time: $(Get-Date -Format 'HH:mm:ss')" -ForegroundColor Gray
    
    $result = python run_pipeline.py --pdf $book.FullName 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Success: $($book.Name)" -ForegroundColor Green
        $successful++
    } else {
        Write-Host "✗ Failed: $($book.Name)" -ForegroundColor Red
        Write-Host "Error: $result" -ForegroundColor Red
        $failed++
    }
    Write-Host ""
}

# Summary
$endTime = Get-Date
$duration = $endTime - $startTime

Write-Host "=== Processing Complete ===" -ForegroundColor Cyan
Write-Host "Total books: $($books.Count)" -ForegroundColor White
Write-Host "Successful: $successful" -ForegroundColor Green
Write-Host "Failed: $failed" -ForegroundColor $(if ($failed -gt 0) { "Red" } else { "Green" })
Write-Host "Duration: $($duration.ToString('hh\:mm\:ss'))" -ForegroundColor Gray
Write-Host ""
Write-Host "Output location: $OutputDir" -ForegroundColor Cyan
Write-Host ""

# List generated files
$outputFiles = Get-ChildItem "$OutputDir\*" -Include "*_rag.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 10
if ($outputFiles) {
    Write-Host "Recent output files:" -ForegroundColor Cyan
    foreach ($file in $outputFiles) {
        $size = [math]::Round($file.Length / 1MB, 2)
        Write-Host "  $($file.Name) - ${size}MB" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "Next step: Import to Laws Agent" -ForegroundColor Yellow
Write-Host "  cd V:\agents\laws_agent" -ForegroundColor Gray
Write-Host "  php import_rag_data.php Books\bookname_rag.json" -ForegroundColor Gray
