<?php
/**
 * Combine all missing_x_report.json files into a single missing.md file
 */

$reports = [
    'missing_abilities_report.json' => 'Abilities',
    'missing_appearance_report.json' => 'Appearance',
    'missing_backgrounds_report.json' => 'Backgrounds',
    'missing_concept_report.json' => 'Concept',
    'missing_demeanor_report.json' => 'Demeanor',
    'missing_disciplines_report.json' => 'Disciplines',
    'missing_history_report.json' => 'History',
    'missing_merits_flaws_report.json' => 'Merits/Flaws',
    'missing_nature_report.json' => 'Nature',
    'missing_notes_report.json' => 'Notes',
    'missing_traits_report.json' => 'Traits'
];

$characters = [];

foreach ($reports as $file => $field) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (isset($data['characters'])) {
            foreach ($data['characters'] as $char) {
                $id = $char['id'];
                $name = $char['character_name'];
                if (!isset($characters[$id])) {
                    $characters[$id] = ['name' => $name, 'missing' => []];
                }
                $characters[$id]['missing'][] = $field;
            }
        }
    }
}

ksort($characters);

$output = "# Character Missing Data Report\n\n";
$output .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "This report combines all missing data reports into a single consolidated list.\n\n";
$output .= "## Summary\n\n";
$output .= "Total characters with missing data: " . count($characters) . "\n\n";
$output .= "## Characters Missing Data\n\n";

foreach ($characters as $id => $char) {
    $output .= "### ID: {$id} - {$char['name']}\n\n";
    $output .= "**Missing Fields:**\n";
    foreach ($char['missing'] as $field) {
        $output .= "- {$field}\n";
    }
    $output .= "\n";
}

file_put_contents('missing.md', $output);
echo "Generated missing.md with " . count($characters) . " characters\n";

