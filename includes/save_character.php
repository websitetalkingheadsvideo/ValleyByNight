<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

set_error_handler(function ($severity, $message, $file, $line): void {
    if (error_reporting() & $severity) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "PHP Error: $message in $file on line $line",
        ]);
        exit();
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/supabase_client.php';

function cleanString($value) {
    if (is_string($value)) {
        return trim($value);
    }
    return $value;
}

function cleanInt($value): int {
    return (int) $value;
}

function cleanJsonData($value): ?string {
    if (empty($value) || (is_string($value) && trim($value) === '')) {
        return null;
    }

    if (is_array($value) || is_object($value)) {
        $encoded = json_encode($value);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode JSON payload.');
        }
        return $encoded;
    }

    if (!is_string($value)) {
        return null;
    }

    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $trimmed;
    }

    $wrapped = json_encode(['text' => $trimmed]);
    if ($wrapped === false) {
        throw new RuntimeException('Failed to normalize text JSON payload.');
    }
    return $wrapped;
}

function assertSupabaseResult(array $result, string $context): array {
    if ($result['error'] !== null) {
        throw new RuntimeException($context . ': ' . $result['error']);
    }
    return $result;
}

function supabaseDeleteByCharacterId(string $table, int $characterId, array $extraQuery): void {
    $query = array_merge(['character_id' => 'eq.' . (string) $characterId], $extraQuery);
    $result = supabase_rest_request('DELETE', '/rest/v1/' . $table, $query, null, ['Prefer: return=minimal']);
    assertSupabaseResult($result, 'Delete failed for ' . $table);
}

function supabaseInsertRows(string $table, array $rows): void {
    if (empty($rows)) {
        return;
    }
    $result = supabase_rest_request('POST', '/rest/v1/' . $table, [], $rows, ['Prefer: return=minimal']);
    assertSupabaseResult($result, 'Insert failed for ' . $table);
}

try {
    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new RuntimeException('Failed to read request body.');
    }
    error_log('Save character raw input: ' . $input);

    $data = json_decode($input, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }

    $cleanData = [
        'character_name' => cleanString($data['character_name'] ?? ''),
        'player_name' => cleanString($data['player_name'] ?? ''),
        'chronicle' => cleanString($data['chronicle'] ?? 'Valley by Night'),
        'nature' => cleanString($data['nature'] ?? ''),
        'demeanor' => cleanString($data['demeanor'] ?? ''),
        'derangement' => cleanString($data['derangement'] ?? ''),
        'concept' => cleanString($data['concept'] ?? ''),
        'clan' => cleanString($data['clan'] ?? ''),
        'generation' => cleanInt($data['generation'] ?? 13),
        'sire' => cleanString($data['sire'] ?? ''),
        'pc' => cleanInt($data['pc'] ?? $data['is_pc'] ?? 1),
        'appearance' => cleanString($data['appearance'] ?? ''),
        'biography' => cleanString($data['biography'] ?? ''),
        'notes' => cleanString($data['notes'] ?? ''),
        'custom_data' => cleanJsonData($data['custom_data'] ?? ''),
        'character_image' => cleanString($data['imagePath'] ?? $data['character_image'] ?? ''),
        'status' => cleanString($data['status'] ?? $data['current_state'] ?? ($data['status_details']['current_state'] ?? ($data['status']['current_state'] ?? 'active'))),
        'camarilla_status' => cleanString($data['camarilla_status'] ?? ($data['status_details']['camarilla_status'] ?? ($data['status']['camarilla_status'] ?? 'Unknown'))),
    ];

    $validStates = ['active', 'inactive', 'archived'];
    $cleanData['status'] = strtolower((string) ($cleanData['status'] ?? 'active'));
    if (!in_array($cleanData['status'], $validStates, true)) {
        $cleanData['status'] = 'active';
    }

    $validCamarilla = ['Camarilla', 'Anarch', 'Independent', 'Sabbat', 'Unknown'];
    $camarillaValue = $cleanData['camarilla_status'] ?? 'Unknown';
    $camarillaValue = $camarillaValue ? ucfirst(strtolower((string) $camarillaValue)) : 'Unknown';
    if (!in_array($camarillaValue, $validCamarilla, true)) {
        $camarillaValue = 'Unknown';
    }
    $cleanData['camarilla_status'] = $camarillaValue;

    $characterId = 0;
    if (isset($data['character_id'])) {
        $characterId = (int) $data['character_id'];
    } elseif (isset($data['id'])) {
        $characterId = (int) $data['id'];
    }

    $userId = (int) $_SESSION['user_id'];

    $characterPayload = [
        'character_name' => $cleanData['character_name'],
        'player_name' => $cleanData['player_name'],
        'chronicle' => $cleanData['chronicle'],
        'nature' => $cleanData['nature'],
        'demeanor' => $cleanData['demeanor'],
        'derangement' => $cleanData['derangement'],
        'concept' => $cleanData['concept'],
        'clan' => $cleanData['clan'],
        'generation' => $cleanData['generation'],
        'sire' => $cleanData['sire'],
        'pc' => $cleanData['pc'],
        'appearance' => $cleanData['appearance'],
        'biography' => $cleanData['biography'],
        'notes' => $cleanData['notes'],
        'custom_data' => $cleanData['custom_data'],
        'status' => $cleanData['status'],
        'camarilla_status' => $cleanData['camarilla_status'],
    ];
    if ($cleanData['character_image'] !== '') {
        $characterPayload['character_image'] = $cleanData['character_image'];
    }

    if ($characterId > 0) {
        $updateResult = supabase_rest_request(
            'PATCH',
            '/rest/v1/characters',
            ['id' => 'eq.' . (string) $characterId],
            $characterPayload,
            ['Prefer: return=minimal']
        );
        assertSupabaseResult($updateResult, 'Failed to update character');
    } else {
        $characterPayload['user_id'] = $userId;
        $insertResult = supabase_rest_request(
            'POST',
            '/rest/v1/characters',
            ['select' => 'id'],
            [$characterPayload],
            ['Prefer: return=representation']
        );
        $insertResult = assertSupabaseResult($insertResult, 'Failed to create character');
        $insertedRows = $insertResult['data'];
        if (!is_array($insertedRows) || empty($insertedRows) || !isset($insertedRows[0]['id'])) {
            throw new RuntimeException('Create character response missing inserted ID.');
        }
        $characterId = (int) $insertedRows[0]['id'];
    }

    if (isset($data['disciplinePowers']) && is_array($data['disciplinePowers'])) {
        supabaseDeleteByCharacterId('character_disciplines', $characterId, []);

        $disciplineRows = [];
        foreach ($data['disciplinePowers'] as $disciplineName => $powerLevels) {
            if (!is_array($powerLevels) || empty($powerLevels)) {
                continue;
            }
            $maxLevel = max(1, min(5, (int) max($powerLevels)));
            $disciplineRows[] = [
                'character_id' => $characterId,
                'discipline_name' => cleanString((string) $disciplineName),
                'level' => $maxLevel,
            ];
        }
        supabaseInsertRows('character_disciplines', $disciplineRows);
    }

    if (isset($data['abilities']) && (is_array($data['abilities']) || is_object($data['abilities']))) {
        supabaseDeleteByCharacterId('character_abilities', $characterId, []);

        $abilities = is_object($data['abilities']) ? (array) $data['abilities'] : $data['abilities'];
        $abilityRows = [];
        foreach ($abilities as $category => $abilityNames) {
            if (!is_array($abilityNames)) {
                continue;
            }

            $abilityCounts = [];
            foreach ($abilityNames as $abilityNameRaw) {
                $abilityName = trim((string) $abilityNameRaw);
                if (strpos($abilityName, ' (') !== false) {
                    $abilityName = substr($abilityName, 0, (int) strpos($abilityName, ' ('));
                }
                if ($abilityName === '') {
                    continue;
                }
                $abilityCounts[$abilityName] = ($abilityCounts[$abilityName] ?? 0) + 1;
            }

            foreach ($abilityCounts as $abilityName => $levelValue) {
                $specialization = null;
                foreach ($abilityNames as $originalNameRaw) {
                    $originalName = (string) $originalNameRaw;
                    if (strpos($originalName, $abilityName . ' (') !== 0) {
                        continue;
                    }
                    $specStart = (int) strpos($originalName, ' (') + 2;
                    $specEnd = (int) strrpos($originalName, ')');
                    if ($specEnd > $specStart) {
                        $specialization = substr($originalName, $specStart, $specEnd - $specStart);
                    }
                    break;
                }

                $abilityRows[] = [
                    'character_id' => $characterId,
                    'ability_name' => $abilityName,
                    'ability_category' => cleanString((string) $category),
                    'level' => max(1, min(5, (int) $levelValue)),
                    'specialization' => $specialization,
                ];
            }
        }
        supabaseInsertRows('character_abilities', $abilityRows);
    }

    if (isset($data['traits']) && is_array($data['traits'])) {
        supabaseDeleteByCharacterId(
            'character_traits',
            $characterId,
            ['or' => '(trait_type.is.null,trait_type.eq.positive)']
        );

        $allowedCategories = ['Physical', 'Social', 'Mental'];
        $positiveRows = [];
        foreach ($data['traits'] as $category => $traitNames) {
            $normalizedCategory = ucfirst(strtolower((string) $category));
            if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
                continue;
            }
            foreach ($traitNames as $traitNameRaw) {
                $traitName = cleanString((string) $traitNameRaw);
                if ($traitName === '') {
                    continue;
                }
                $positiveRows[] = [
                    'character_id' => $characterId,
                    'trait_name' => $traitName,
                    'trait_category' => $normalizedCategory,
                    'trait_type' => 'positive',
                ];
            }
        }
        supabaseInsertRows('character_traits', $positiveRows);
    }

    if (isset($data['negativeTraits']) && is_array($data['negativeTraits'])) {
        supabaseDeleteByCharacterId('character_negative_traits', $characterId, []);

        $allowedCategories = ['Physical', 'Social', 'Mental'];
        $negativeRows = [];
        foreach ($data['negativeTraits'] as $category => $traitNames) {
            $normalizedCategory = ucfirst(strtolower((string) $category));
            if (!in_array($normalizedCategory, $allowedCategories, true) || !is_array($traitNames)) {
                continue;
            }
            foreach ($traitNames as $traitNameRaw) {
                $traitName = cleanString((string) $traitNameRaw);
                if ($traitName === '') {
                    continue;
                }
                $negativeRows[] = [
                    'character_id' => $characterId,
                    'trait_category' => $normalizedCategory,
                    'trait_name' => $traitName,
                ];
            }
        }
        supabaseInsertRows('character_negative_traits', $negativeRows);
    }

    if (isset($data['coteries']) && is_array($data['coteries'])) {
        supabaseDeleteByCharacterId('character_coteries', $characterId, []);

        $coterieRows = [];
        foreach ($data['coteries'] as $coterie) {
            if (!is_array($coterie) || empty($coterie['coterie_name'])) {
                continue;
            }
            $coterieRows[] = [
                'character_id' => $characterId,
                'coterie_name' => cleanString($coterie['coterie_name'] ?? ''),
                'coterie_type' => cleanString($coterie['coterie_type'] ?? ''),
                'role' => cleanString($coterie['role'] ?? ''),
                'description' => cleanString($coterie['description'] ?? ''),
                'notes' => cleanString($coterie['notes'] ?? ''),
            ];
        }
        supabaseInsertRows('character_coteries', $coterieRows);
    }

    if (isset($data['relationships']) && is_array($data['relationships'])) {
        supabaseDeleteByCharacterId('character_relationships', $characterId, []);

        $relationshipRows = [];
        foreach ($data['relationships'] as $relationship) {
            if (!is_array($relationship) || empty($relationship['related_character_name'])) {
                continue;
            }
            $relationshipRows[] = [
                'character_id' => $characterId,
                'related_character_name' => cleanString($relationship['related_character_name'] ?? ''),
                'relationship_type' => cleanString($relationship['relationship_type'] ?? ''),
                'relationship_subtype' => cleanString($relationship['relationship_subtype'] ?? ''),
                'strength' => cleanString($relationship['strength'] ?? ''),
                'description' => cleanString($relationship['description'] ?? ''),
            ];
        }
        supabaseInsertRows('character_relationships', $relationshipRows);
    }

    echo json_encode([
        'success' => true,
        'message' => ($data['id'] ?? $data['character_id'] ?? null) ? 'Character updated successfully!' : 'Character created successfully!',
        'character_id' => $characterId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('save_character.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving character: ' . $e->getMessage(),
    ]);
}