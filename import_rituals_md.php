<?php
/**
 * import_rituals_md.php
 *
 * Laws Agent – Ritual Importer
 *
 * Imports ritual definitions from:
 *   reference/mechanics/rituals/*.md
 *
 * INTO:
 *   rituals_master
 *
 * REQUIREMENTS:
 * - Must be run inside VbN codebase
 * - Uses existing mysqli connection from includes/connect.php
 * - NO environment variables
 * - NO alternate DB connections
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* =========================================================
   Bootstrap VbN DB connection
   ========================================================= */

require_once __DIR__ . '/includes/connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not available. connect.php must define \$conn (mysqli).\n");
}

/* =========================================================
   Config
   ========================================================= */

$ritualDir = __DIR__ . '/reference/mechanics/rituals';

if (!is_dir($ritualDir)) {
    die("Ritual directory not found: {$ritualDir}\n");
}

$files = glob($ritualDir . '/*.md');
if (!$files) {
    echo "No ritual markdown files found.\n";
    exit;
}

/* =========================================================
   Load existing rituals to avoid duplicates
   ========================================================= */

$existingRituals = [];
$checkStmt = $conn->prepare("SELECT name FROM rituals_master");
if ($checkStmt) {
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Unique constraint is on name only, so use name as key (case-insensitive)
        $key = strtolower(trim($row['name']));
        $existingRituals[$key] = true;
    }
    $checkStmt->close();
}

/* =========================================================
   Prepared statement (INSERT only)
   ========================================================= */

$sql = "
INSERT INTO rituals_master
  (name, type, level, description, system_text, requirements, ingredients, source)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

/* =========================================================
   Import loop
   ========================================================= */

// Sort stable import order (index first, then rank split files)
usort($files, function ($a, $b) {
    $pa = priorityForFile(basename($a));
    $pb = priorityForFile(basename($b));
    return $pa <=> $pb ?: strcasecmp($a, $b);
});

$inserted = 0;
$skipped  = 0;

foreach ($files as $path) {
    $filename = basename($path);
    $markdown = file_get_contents($path);

    if (!$markdown || trim($markdown) === '') {
        continue;
    }

    $rituals = parseRitualsFromMarkdown($filename, $markdown);

    foreach ($rituals as $r) {
        // Ensure required fields
        if (($r['type'] ?? '') === '' || ($r['name'] ?? '') === '' || !isset($r['level'])) {
            $skipped++;
            continue;
        }

        $name = $r['name'];
        $type = $r['type'];
        $level = (int)$r['level'];
        
        // Validate level range (check constraint likely limits to 1-5)
        if ($level < 1 || $level > 5) {
            echo "SKIPPED {$name} (Level {$level}): Level must be between 1-5 due to database constraint\n";
            $skipped++;
            continue;
        }
        
        // Check if ritual already exists (unique constraint is on name only)
        $key = strtolower(trim($name));
        if (isset($existingRituals[$key])) {
            $skipped++;
            continue; // Skip existing rituals
        }
        
        $description = nullIfEmpty($r['description'] ?? '');
        $system_text = nullIfEmpty($r['system_text'] ?? '');
        $requirements = nullIfEmpty($r['requirements'] ?? '');
        $ingredients = nullIfEmpty($r['ingredients'] ?? '');
        $source = nullIfEmpty($r['source'] ?? '');

        $stmt->bind_param(
            'ssisssss',
            $name,
            $type,
            $level,
            $description,
            $system_text,
            $requirements,
            $ingredients,
            $source
        );

        if (!$stmt->execute()) {
            echo "ERROR inserting {$name} (Level {$level}): {$stmt->error}\n";
            $skipped++;
            continue;
        }

        if ($stmt->affected_rows === 1) {
            $inserted++;
            // Add to existing set to avoid duplicates in same import run
            $existingRituals[$key] = true;
        }
    }

    echo "Processed {$filename}: " . count($rituals) . " rituals\n";
}

$stmt->close();

echo "Import complete. Inserted: {$inserted}, Skipped: {$skipped}\n";

function priorityForFile(string $name): int
{
    $n = strtolower($name);
    if (str_contains($n, 'thaumaturgy_rituals.md')) return 10;               // Rank 1–2 index
    if (str_contains($n, 'thaumaturgy_rituals ranks 3-5')) return 20;
    if (str_contains($n, 'thaumaturgy_rituals ranks 6-8')) return 30;
    if (str_contains($n, 'necromancy')) return 40;
    if (str_contains($n, 'assamite')) return 50;
    return 100;
}

function parseRitualsFromMarkdown(string $filename, string $md): array
{
    $lower = strtolower($filename);

    if (str_contains($lower, 'necromancy')) {
        return parseNecromancyPacket($filename, $md);
    }

    if (str_contains($lower, 'assamite')) {
        return parseAssamiteRituals($filename, $md);
    }

    if (str_contains($lower, 'thaumaturgy')) {
        // Rank 1–2 live in Thaumaturgy_Rituals.md (later ranks are a note in that file)
        // Rank 3–5 and 6–8 live in the split files.
        return parseThaumaturgyRituals($filename, $md);
    }

    // Fallback: try Thaumaturgy-style blocks (Ingredients/Process/Effect)
    $try = parseThaumaturgyRituals($filename, $md);
    return $try ?: [];
}

/* =========================================================
   THAUMATURGY PARSER
   ========================================================= */

function parseThaumaturgyRituals(string $filename, string $md): array
{
    $type = 'Thaumaturgy';

    // Normalize line endings
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    // Split into Rank sections
    $rankSections = splitSections($md, '/^##\s*Rank\s+(\d+)\s+Rituals\s*$/mi');

    $rituals = [];
    foreach ($rankSections as $sec) {
        $rank = (int)($sec['match'][1] ?? 0);
        if ($rank === 0) continue;

        // In the main index file, ranks 3+ often contain only a note (no entries).
        // So, only accept if it actually has ritual headers.
        if (!preg_match('/^###\s+/m', $sec['body'])) {
            continue;
        }

        $blocks = splitRitualBlocksByH3($sec['body']);
        foreach ($blocks as $b) {
            $titleRaw = trim($b['title']);
            if ($titleRaw === '') continue;

            $name = preg_replace('/\s*\[\s*\d+\s*\]\s*$/', '', $titleRaw);
            $name = trim($name);

            $fields = parseBoldFieldLines($b['body']);

            $ingredients = $fields['Ingredients'] ?? '';
            $process     = $fields['Process'] ?? '';
            $effect      = $fields['Effect'] ?? '';
            $developer   = $fields['Developer'] ?? '';

            // Requirements: process + developer (if useful)
            $requirements = trim(joinNonEmpty([
                $process ? "Process: {$process}" : '',
                $developer ? "Developer: {$developer}" : '',
            ]));

            $description = $effect !== '' ? $effect : firstSentence($b['body']);

            // System text: keep full block for fidelity
            $system_text = trim(cleanBlockText($b['body']));

            $rituals[] = [
                'name'         => $name,
                'type'         => $type,
                'level'        => $rank,
                'description'  => $description,
                'ingredients'  => $ingredients,
                'requirements' => $requirements,
                'system_text'  => $system_text,
                'source'       => "MD: {$filename}",
            ];
        }
    }

    return $rituals;
}

function splitRitualBlocksByH3(string $text): array
{
    $text = trim($text) . "\n";
    $lines = explode("\n", $text);

    $blocks = [];
    $currentTitle = null;
    $currentBody  = [];

    foreach ($lines as $line) {
        if (preg_match('/^###\s+(.*)$/', $line, $m)) {
            if ($currentTitle !== null) {
                $blocks[] = [
                    'title' => $currentTitle,
                    'body'  => trim(join("\n", $currentBody)),
                ];
            }
            $currentTitle = trim($m[1]);
            $currentBody  = [];
            continue;
        }
        $currentBody[] = $line;
    }

    if ($currentTitle !== null) {
        $blocks[] = [
            'title' => $currentTitle,
            'body'  => trim(join("\n", $currentBody)),
        ];
    }

    // Remove empty/noise blocks
    $blocks = array_values(array_filter($blocks, fn($b) => trim($b['title']) !== ''));
    return $blocks;
}

function parseBoldFieldLines(string $body): array
{
    // Captures lines like:
    // **Ingredients:** ...
    // **Process:** ...
    // **Effect:** ...
    // **Developer:** ...
    $out = [];

    $lines = explode("\n", $body);
    foreach ($lines as $line) {
        if (preg_match('/^\*\*(.+?)\:\*\*\s*(.*)\s*$/', trim($line), $m)) {
            $key = trim($m[1]);
            $val = trim($m[2]);

            // If the value continues on next lines (common), append until next bold key or blank rule separator
            $out[$key] = $val;
        }
    }

    // Improve multi-line capturing for common keys by regex blocks
    foreach (['Ingredients', 'Process', 'Effect', 'Developer'] as $k) {
        $block = captureBoldBlock($body, $k);
        if ($block !== null) $out[$k] = $block;
    }

    return $out;
}

function captureBoldBlock(string $body, string $label): ?string
{
    // Match **Label:** ... until next **OtherLabel:** or separator line
    $pattern = '/\*\*' . preg_quote($label, '/') . '\:\*\*\s*(.+?)(?=\n\*\*[\w\s]+\:\*\*|\n---|\n##\s|\n###\s|$)/is';
    if (preg_match($pattern, $body, $m)) {
        return trim(preg_replace('/\s+/', ' ', trim($m[1])));
    }
    return null;
}

/* =========================================================
   NECROMANCY PARSER (Phoenix Necromancy Packet)
   ========================================================= */

function parseNecromancyPacket(string $filename, string $md): array
{
    $type = 'Necromancy';
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    // Map section -> level number
    $sectionLevel = [
        'Basic Rituals' => 1,
        'Intermediate Rituals' => 2,
        'Advanced Rituals' => 3,
    ];

    $sections = splitSections($md, '/^##\s+(.+?)\s*$/mi');

    $rituals = [];
    foreach ($sections as $sec) {
        $secTitle = trim($sec['match'][1] ?? '');
        if ($secTitle === '' || !isset($sectionLevel[$secTitle])) {
            continue;
        }
        $lvl = $sectionLevel[$secTitle];

        $blocks = splitRitualBlocksByH3($sec['body']);
        foreach ($blocks as $b) {
            $name = trim($b['title']);
            if ($name === '') continue;

            $ingredients = '';
            $requirements = '';
            $description = '';
            $system_text = trim(cleanBlockText($b['body']));

            // Parse bold fields
            $timeReq = captureSimpleBoldLine($b['body'], 'Time Required');
            $duration = captureSimpleBoldLine($b['body'], 'Duration');
            $components = captureSimpleBoldLine($b['body'], 'Components');
            $prereq = captureSimpleBoldLine($b['body'], 'Prerequisites');

            if ($components) $ingredients = $components;

            $requirements = trim(joinNonEmpty([
                $timeReq ? "Time Required: {$timeReq}" : '',
                $duration ? "Duration: {$duration}" : '',
                $prereq ? "Prerequisites: {$prereq}" : '',
            ]));

            // Description: first non-empty paragraph after field lines / adapted line
            $description = firstSentence(stripMechanicsAndFields($b['body']));
            if ($description === '') $description = firstSentence($b['body']);

            // Keep source if present
            $source = captureAdaptedLine($b['body']);
            $source = $source ? "{$source}; MD: {$filename}" : "MD: {$filename}";

            $rituals[] = [
                'name'         => $name,
                'type'         => $type,
                'level'        => $lvl,
                'description'  => $description,
                'ingredients'  => $ingredients,
                'requirements' => $requirements,
                'system_text'  => $system_text,
                'source'       => $source,
            ];
        }
    }

    return $rituals;
}

function captureSimpleBoldLine(string $body, string $label): ?string
{
    // **Label:** value
    $pattern = '/\*\*' . preg_quote($label, '/') . '\:\*\*\s*(.+?)(?=\n|$)/i';
    if (preg_match($pattern, $body, $m)) {
        $val = trim($m[1]);
        // Strip trailing Markdown line breaks (two spaces)
        $val = rtrim($val);
        $val = preg_replace('/\s{2,}$/', '', $val);
        return $val;
    }
    return null;
}

function captureAdaptedLine(string $body): ?string
{
    // *Adapted from ...*
    if (preg_match('/^\*(Adapted from .+?)\*\s*$/mi', $body, $m)) {
        return trim($m[1]);
    }
    return null;
}

function stripMechanicsAndFields(string $body): string
{
    // Remove bold header lines and Mechanics section to get a cleaner description sentence
    $body = preg_replace('/^\*(Adapted from .+?)\*\s*$/mi', '', $body);
    $body = preg_replace('/^\*\*.+?\:\*\*.+$/mi', '', $body);
    $body = preg_replace('/^\*\*Mechanics\:\*\*\s*$/mi', '', $body);

    // If there's a Mechanics list, drop it
    $body = preg_replace('/\*\*Mechanics\:\*\*.*$/is', '', $body);

    // Remove separators
    $body = preg_replace('/^\s*---\s*$/m', '', $body);

    return trim($body);
}

/* =========================================================
   ASSAMITE PARSER (Quick-Reference + some full entries)
   ========================================================= */

function parseAssamiteRituals(string $filename, string $md): array
{
    $type = 'Assamite';
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    $rituals = [];

    // 1) Quick-reference sections: "### Level X Rituals" with "#### Ritual Name"
    $currentLevel = null;
    $lines = explode("\n", $md);

    $buffer = [];
    $currentName = null;

    $flushQuick = function () use (&$rituals, &$buffer, &$currentName, &$currentLevel, $type, $filename) {
        if ($currentName === null || $currentLevel === null) {
            $buffer = [];
            $currentName = null;
            return;
        }
        $body = trim(join("\n", $buffer));

        $costDuration = '';
        if (preg_match('/^\*\*Cost\/Duration\:\*\*\s*(.+)$/mi', $body, $m)) {
            $costDuration = trim($m[1]);
        }

        // Description: first sentence of the body with cost line removed
        $descBody = preg_replace('/^\*\*Cost\/Duration\:\*\*.+$/mi', '', $body);
        $description = firstSentence($descBody);

        $rituals[] = [
            'name'         => $currentName,
            'type'         => $type,
            'level'        => $currentLevel,
            'description'  => $description,
            'ingredients'  => '',
            'requirements' => $costDuration ? "Cost/Duration: {$costDuration}" : '',
            'system_text'  => $body,
            'source'       => "MD: {$filename}",
        ];

        $buffer = [];
        $currentName = null;
    };

    foreach ($lines as $line) {
        if (preg_match('/^###\s+Level\s+(\d+)\s+Rituals/i', $line, $m)) {
            $flushQuick();
            $currentLevel = (int)$m[1];
            continue;
        }

        // Stop quick parsing when we hit Key Rules / other major section
        if (preg_match('/^##\s+Key\s+Rules/i', $line)) {
            $flushQuick();
            $currentLevel = null;
            break;
        }

        if (preg_match('/^####\s+(.+)\s*$/', $line, $m)) {
            $flushQuick();
            $currentName = trim($m[1]);
            $buffer = [];
            continue;
        }

        // Separator flush
        if (trim($line) === '---') {
            $flushQuick();
            continue;
        }

        if ($currentName !== null) {
            $buffer[] = $line;
        }
    }
    $flushQuick();

    // 2) Detailed entries: "### Name [id]" with Ingredients/Process/Effect/Developer
    // These may appear intermingled; parse them too (they will upsert cleanly).
    $detailedSections = splitSections($md, '/^###\s+(.+?)\s*$/mi');
    foreach ($detailedSections as $sec) {
        $title = trim($sec['match'][1] ?? '');
        if ($title === '' || preg_match('/^Level\s+\d+\s+Rituals/i', $title)) {
            continue;
        }
        if (preg_match('/^Key\s+Rules/i', $title)) {
            continue;
        }

        // Heuristic: only accept if it has Ingredients/Process/Effect keys
        if (!preg_match('/\*\*Ingredients\:\*\*/i', $sec['body']) || !preg_match('/\*\*Effect\:\*\*/i', $sec['body'])) {
            continue;
        }

        $name = preg_replace('/\s*\[\s*\d+\s*\]\s*$/', '', $title);
        $name = trim($name);

        $fields = parseBoldFieldLines($sec['body']);

        $ingredients = $fields['Ingredients'] ?? '';
        $process     = $fields['Process'] ?? '';
        $effect      = $fields['Effect'] ?? '';
        $developer   = $fields['Developer'] ?? '';

        $requirements = trim(joinNonEmpty([
            $process ? "Process: {$process}" : '',
            $developer ? "Developer: {$developer}" : '',
        ]));

        $system_text = trim(cleanBlockText($sec['body']));
        $description = $effect !== '' ? $effect : firstSentence($sec['body']);

        // Try to infer level from nearest preceding "Level X Rituals" header by scanning backwards
        $level = inferNearestAssamiteLevel($md, $sec['startPos'] ?? 0);

        $rituals[] = [
            'name'         => $name,
            'type'         => $type,
            'level'        => $level ?? 0,
            'description'  => $description,
            'ingredients'  => $ingredients,
            'requirements' => $requirements,
            'system_text'  => $system_text,
            'source'       => "MD: {$filename}",
        ];
    }

    // Drop any level=0 entries if we failed to infer (optional).
    // Keeping them can be useful if you want to fix levels later in UI.
    return $rituals;
}

function inferNearestAssamiteLevel(string $md, int $pos): ?int
{
    $before = substr($md, 0, max(0, $pos));
    if (preg_match_all('/^###\s+Level\s+(\d+)\s+Rituals\s*$/mi', $before, $m)) {
        $last = end($m[1]);
        return (int)$last;
    }
    return null;
}

/* =========================================================
   GENERIC HELPERS
   ========================================================= */

function splitSections(string $text, string $headerRegex): array
{
    $out = [];
    if (!preg_match_all($headerRegex, $text, $matches, PREG_OFFSET_CAPTURE)) {
        return $out;
    }

    $count = count($matches[0]);
    for ($i = 0; $i < $count; $i++) {
        $start = $matches[0][$i][1];
        $end   = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($text);

        $headerLine = $matches[0][$i][0];

        // Build match array compatible with preg_match groups
        $match = [];
        for ($g = 0; $g < count($matches); $g++) {
            $match[$g] = $matches[$g][$i][0];
        }

        // Body begins after header line
        $bodyStart = $start + strlen($headerLine);
        $body = substr($text, $bodyStart, $end - $bodyStart);

        $out[] = [
            'match'    => $match,
            'header'   => $headerLine,
            'body'     => trim($body),
            'startPos' => $start,
            'endPos'   => $end,
        ];
    }
    return $out;
}

function cleanBlockText(string $body): string
{
    // Remove trailing/leading separators but keep internal formatting
    $body = preg_replace('/^\s*---\s*$/m', '', $body);
    $body = preg_replace('/\n{3,}/', "\n\n", $body);
    return trim($body);
}

function firstSentence(string $text): string
{
    $t = trim($text);
    if ($t === '') return '';

    // Remove common markdown noise
    $t = preg_replace('/^\s*---\s*$/m', '', $t);
    $t = preg_replace('/^\s*\*{1,2}.+?\*{1,2}\s*$/m', '', $t); // italic-only lines
    $t = preg_replace('/^\s*\*\*.+?\*\*\s*$/m', '', $t);       // bold-only lines
    $t = preg_replace('/\s+/', ' ', $t);
    $t = trim($t);

    // First sentence heuristic
    if (preg_match('/^(.+?[\.!\?])\s+/', $t, $m)) {
        return trim($m[1]);
    }
    // Otherwise first ~160 chars
    return mb_substr($t, 0, 160);
}

function joinNonEmpty(array $parts, string $sep = "\n"): string
{
    $parts = array_values(array_filter(array_map('trim', $parts), fn($p) => $p !== ''));
    return join($sep, $parts);
}

function nullIfEmpty(string $s): ?string
{
    $s = trim($s);
    return $s === '' ? null : $s;
}


