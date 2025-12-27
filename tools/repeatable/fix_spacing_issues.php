<?php
/**
 * Fix spacing issues in markdown files
 * - Adds spaces after punctuation (; : , .)
 * - Adds spaces before common words after punctuation
 * - Fixes specific concatenated phrases
 * - Removes excessive spaces
 * - Preserves markdown formatting
 */

$books_dir = __DIR__ . '/../../reference/Books_md_ready';

if (!is_dir($books_dir)) {
    die("Directory not found: {$books_dir}\n");
}

function fixSpacing(string $text): string {
    $lines = explode("\n", $text);
    $fixed_lines = [];
    
    foreach ($lines as $line) {
        // Skip markdown headers (lines starting with #)
        if (preg_match('/^#+\s/', $line)) {
            $fixed_lines[] = $line;
            continue;
        }
        
        // Skip empty lines
        if (trim($line) === '') {
            $fixed_lines[] = $line;
            continue;
        }
        
        $fixed = $line;
        
        // 1. Add space after punctuation if missing (but preserve existing spaces)
        // Only add space if there's a letter immediately after punctuation
        $fixed = preg_replace('/([;:,.])([a-zA-Z])/', '$1 $2', $fixed);
        
        // 2. Fix specific common concatenated phrases (be very specific)
        $specific_fixes = [
            // Common game phrases
            '/onasuccessfulattack/i' => 'on a successful attack',
            '/onasuccessful/i' => 'on a successful',
            '/takeanextraaction/i' => 'take an extra action',
            '/takeanextra/i' => 'take an extra',
            '/duringachallenge/i' => 'during a challenge',
            '/makeafollow-upattack/i' => 'make a follow-up attack',
            '/makeafollow-up/i' => 'make a follow-up',
            '/healoneHealth/i' => 'heal one Health',
            '/healone/i' => 'heal one',
            '/afterbecoming/i' => 'after becoming',
            '/powercertain/i' => 'power certain',
            '/aslisted/i' => 'as listed',
            '/givesthem/i' => 'gives them',
            '/the minto/i' => 'them into',
            '/the m /i' => 'them ',
            '/for m /i' => 'form ',
            '/for m\b/i' => 'form',
            '/onemonth/i' => 'one month',
            '/onceliving/i' => 'once living',
            '/the ir/i' => 'their',
            '/sufficientto:/i' => 'sufficient to:',
            '/sufficientto/i' => 'sufficient to',
            '/amortal/i' => 'a mortal',
            '/avampire/i' => 'a vampire',
            '/sustainavampire/i' => 'sustain a vampire',
            '/sustain a vampirefor/i' => 'sustain a vampire for',
            '/adda/i' => 'add a',
            '/oncepergame/i' => 'once per game',
            '/onceper/i' => 'once per',
            '/pergame/i' => 'per game',
            '/Attributecategory/i' => 'Attribute category',
            '/woundpenalties/i' => 'wound penalties',
            '/specialabilities/i' => 'special abilities',
            '/Health Levelof/i' => 'Health Level of',
            '/Levelof/i' => 'Level of',
            '/Corpus Levelof/i' => 'Corpus Level of',
            '/anotherform/i' => 'another form',
            '/another for m/i' => 'another form',
            '/anotherformimmediately/i' => 'another form immediately',
            '/Giftsaslisted/i' => 'Gifts as listed',
            '/Giftsaslistedin/i' => 'Gifts as listed in',
            '/powercertain/i' => 'power certain',
            '/powercertain Gifts/i' => 'power certain Gifts',
            '/enforcingone\'s/i' => 'enforcing one\'s',
            '/enforcingone/i' => 'enforcing one',
            // Additional common patterns
            '/throughthememory/i' => 'through the memory',
            '/holdonto/i' => 'hold onto',
            '/materialworld/i' => 'material world',
            '/powerin/i' => 'power in',
            '/canonly/i' => 'can only',
            '/wr a iths/i' => 'wraiths',
            '/wr a ithscanonly/i' => 'wraiths can only',
            '/wraithscanonly/i' => 'wraiths can only',
            '/wraithscan only/i' => 'wraiths can only',
            '/actionduring/i' => 'action during',
            '/afollow-upattack/i' => 'a follow-up attack',
            '/achallenge/i' => 'a challenge',
            '/ona successful/i' => 'on a successful',
            '/Giftsas listed/i' => 'Gifts as listed',
            '/incredibledestructive/i' => 'incredible destructive',
        ];
        
        foreach ($specific_fixes as $pattern => $replacement) {
            $fixed = preg_replace($pattern, $replacement, $fixed);
        }
        
        // 3. Fix spacing around colons in common patterns (but be careful)
        // Only fix if there's no space after colon and it's followed by a capital letter
        $fixed = preg_replace('/([A-Za-z]+):([A-Z][a-z])/', '$1: $2', $fixed);
        
        // 4. Remove excessive spaces (3+ spaces -> 1 space, but preserve markdown)
        $fixed = preg_replace('/[ \t]{3,}/', ' ', $fixed);
        
        // 5. Fix double spaces (but not in markdown tables or code)
        $fixed = preg_replace('/(?<!\|)\s{2,}(?!\|)/', ' ', $fixed);
        
        // 6. Ensure space after semicolons and colons when followed by text
        $fixed = preg_replace('/([;:])([a-zA-Z])/', '$1 $2', $fixed);
        
        $fixed_lines[] = $fixed;
    }
    
    return implode("\n", $fixed_lines);
}

// Process all .md files (excluding .bak files)
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($books_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$processed = 0;
$errors = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md' && !str_ends_with($file->getFilename(), '.bak')) {
        $filepath = $file->getPathname();
        
        echo "Processing: " . basename($filepath) . "\n";
        
        try {
            $content = file_get_contents($filepath);
            if ($content === false) {
                $errors[] = "Failed to read: {$filepath}";
                continue;
            }
            
            $fixed_content = fixSpacing($content);
            
            // Only write if content changed
            if ($fixed_content !== $content) {
                if (file_put_contents($filepath, $fixed_content) === false) {
                    $errors[] = "Failed to write: {$filepath}";
                } else {
                    $processed++;
                    echo "  ✓ Fixed\n";
                }
            } else {
                echo "  - No changes\n";
            }
        } catch (Exception $e) {
            $errors[] = "Error processing {$filepath}: " . $e->getMessage();
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: {$processed}\n";
if (count($errors) > 0) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "No errors.\n";
}
