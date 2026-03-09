<?php
/**
 * Paths JSON Import Script
 *
 * Imports Necromancy and Thaumaturgy paths from JSON files into Supabase.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/../../includes/supabase_client.php';

$stats = [
    'paths_inserted' => 0,
    'paths_updated' => 0,
    'powers_inserted' => 0,
    'powers_updated' => 0,
    'errors' => []
];

$initial_paths = count(supabase_table_get('paths_master', ['select' => 'id']));
$initial_powers = count(supabase_table_get('path_powers', ['select' => 'id']));

function importPathsFromFile(string $filepath, string $type, array &$stats): bool
{
    if (!is_file($filepath)) {
        $stats['errors'][] = "File not found: {$filepath}";
        return false;
    }

    $jsonContent = file_get_contents($filepath);
    if ($jsonContent === false) {
        $stats['errors'][] = "Failed to read file: {$filepath}";
        return false;
    }

    $data = json_decode($jsonContent, true);
    if (!is_array($data) || !isset($data['paths']) || !is_array($data['paths'])) {
        $stats['errors'][] = "Invalid JSON structure in {$filepath}: missing 'paths' array";
        return false;
    }

    if (count($data['paths']) === 0) {
        $stats['errors'][] = "No paths found in {$filepath}";
        return false;
    }

    $filename = basename($filepath);

    foreach ($data['paths'] as $path) {
        if (!isset($path['name']) || !isset($path['powers']) || !is_array($path['powers'])) {
            $stats['errors'][] = "Invalid path structure in {$filename}: missing 'name' or 'powers'";
            continue;
        }

        $pathName = trim((string) $path['name']);
        $pathDescription = isset($path['description']) ? trim((string) $path['description']) : null;

        $existingRows = supabase_table_get('paths_master', [
            'select' => 'id,description,source',
            'type' => 'eq.' . $type,
            'name' => 'eq.' . $pathName,
            'limit' => '1'
        ]);

        $existing = $existingRows[0] ?? null;
        $pathId = null;

        if ($existing === null) {
            $insertResult = supabase_rest_request(
                'POST',
                '/rest/v1/paths_master',
                [],
                [
                    'name' => $pathName,
                    'type' => $type,
                    'description' => $pathDescription,
                    'source' => $filename
                ],
                ['Prefer: return=representation']
            );

            if ($insertResult['error'] !== null || !is_array($insertResult['data']) || empty($insertResult['data'][0]['id'])) {
                $stats['errors'][] = "Failed to insert path '{$pathName}': " . ($insertResult['error'] ?? 'unknown error');
                continue;
            }

            $pathId = (int) $insertResult['data'][0]['id'];
            $stats['paths_inserted']++;
        } else {
            $pathId = (int) $existing['id'];
            $needsUpdate = ($existing['description'] ?? null) !== $pathDescription || ($existing['source'] ?? null) !== $filename;

            if ($needsUpdate) {
                $updateResult = supabase_rest_request(
                    'PATCH',
                    '/rest/v1/paths_master',
                    ['id' => 'eq.' . $pathId],
                    [
                        'description' => $pathDescription,
                        'source' => $filename
                    ],
                    ['Prefer: return=minimal']
                );

                if ($updateResult['error'] !== null) {
                    $stats['errors'][] = "Failed to update path '{$pathName}': " . $updateResult['error'];
                    continue;
                }

                $stats['paths_updated']++;
            }
        }

        foreach ($path['powers'] as $levelKey => $powerName) {
            if (!preg_match('/^level_(\d+)$/i', (string) $levelKey, $matches)) {
                $stats['errors'][] = "Invalid power level key format for '{$pathName}': {$levelKey}";
                continue;
            }

            $level = (int) $matches[1];
            if ($level < 1 || $level > 5) {
                $stats['errors'][] = "Invalid power level for '{$pathName}': {$level} (must be 1-5)";
                continue;
            }

            $cleanPowerName = trim((string) $powerName);
            $existingPowerRows = supabase_table_get('path_powers', [
                'select' => 'id,power_name',
                'path_id' => 'eq.' . $pathId,
                'level' => 'eq.' . $level,
                'limit' => '1'
            ]);

            $existingPower = $existingPowerRows[0] ?? null;

            if ($existingPower === null) {
                $insertPower = supabase_rest_request(
                    'POST',
                    '/rest/v1/path_powers',
                    [],
                    [
                        'path_id' => $pathId,
                        'level' => $level,
                        'power_name' => $cleanPowerName,
                        'system_text' => null,
                        'challenge_type' => 'unknown',
                        'challenge_notes' => null
                    ],
                    ['Prefer: return=representation']
                );

                if ($insertPower['error'] !== null) {
                    $stats['errors'][] = "Failed to insert power for '{$pathName}' level {$level}: " . $insertPower['error'];
                    continue;
                }

                $stats['powers_inserted']++;
                continue;
            }

            if (($existingPower['power_name'] ?? '') !== $cleanPowerName) {
                $updatePower = supabase_rest_request(
                    'PATCH',
                    '/rest/v1/path_powers',
                    ['id' => 'eq.' . (int) $existingPower['id']],
                    ['power_name' => $cleanPowerName],
                    ['Prefer: return=minimal']
                );

                if ($updatePower['error'] !== null) {
                    $stats['errors'][] = "Failed to update power for '{$pathName}' level {$level}: " . $updatePower['error'];
                    continue;
                }

                $stats['powers_updated']++;
            }
        }
    }

    return true;
}

$baseDir = __DIR__ . '/../../reference/mechanics/paths/';
if (!is_dir($baseDir)) {
    $stats['errors'][] = "Directory not found: {$baseDir}";
}

$files = [
    ['file' => $baseDir . 'Necromancy_Paths.json', 'type' => 'Necromancy'],
    ['file' => $baseDir . 'Thaumaturgy_Paths.json', 'type' => 'Thaumaturgy']
];

foreach ($files as $fileInfo) {
    importPathsFromFile($fileInfo['file'], $fileInfo['type'], $stats);
}

$final_paths = count(supabase_table_get('paths_master', ['select' => 'id']));
$final_powers = count(supabase_table_get('path_powers', ['select' => 'id']));

if ($is_cli) {
    echo "Paths Import Summary\n";
    echo "====================\n";
    echo "Paths inserted: {$stats['paths_inserted']}\n";
    echo "Paths updated: {$stats['paths_updated']}\n";
    echo "Powers inserted: {$stats['powers_inserted']}\n";
    echo "Powers updated: {$stats['powers_updated']}\n";
    echo "\nTotal paths in database: {$final_paths} (was {$initial_paths})\n";
    echo "Total powers in database: {$final_powers} (was {$initial_powers})\n";
    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    return;
}
?>
<!DOCTYPE html>
<html>
<head><title>Paths Import</title></head>
<body>
<h1>Paths Import Summary</h1>
<p>Paths inserted: <strong><?php echo htmlspecialchars((string) $stats['paths_inserted']); ?></strong></p>
<p>Paths updated: <strong><?php echo htmlspecialchars((string) $stats['paths_updated']); ?></strong></p>
<p>Powers inserted: <strong><?php echo htmlspecialchars((string) $stats['powers_inserted']); ?></strong></p>
<p>Powers updated: <strong><?php echo htmlspecialchars((string) $stats['powers_updated']); ?></strong></p>
<p><em>Total paths in database: <?php echo htmlspecialchars((string) $final_paths); ?> (was <?php echo htmlspecialchars((string) $initial_paths); ?>)</em></p>
<p><em>Total powers in database: <?php echo htmlspecialchars((string) $final_powers); ?> (was <?php echo htmlspecialchars((string) $initial_powers); ?>)</em></p>
<?php if (!empty($stats['errors'])): ?>
<h2>Errors</h2>
<ul>
<?php foreach ($stats['errors'] as $error): ?>
<li><?php echo htmlspecialchars($error); ?></li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p><em>No errors encountered.</em></p>
<?php endif; ?>
</body>
</html>

