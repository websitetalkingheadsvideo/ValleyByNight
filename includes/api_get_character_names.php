<?php
/**
 * Get Character Names API
 * Returns list of all character names for dropdowns
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/supabase_client.php';

$response = ['success' => false, 'characters' => [], 'error' => ''];

try {
    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name',
        'order' => 'character_name.asc',
    ]);

    $characters = [];
    foreach ($rows as $row) {
        $characters[] = [
            'id' => $row['id'] ?? null,
            'name' => $row['character_name'] ?? '',
        ];
    }
    
    $response['success'] = true;
    $response['characters'] = $characters;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
