<?php
/**
 * Location Assignments API (Supabase)
 * GET: fetch assignments for location; POST: assign characters; DELETE: remove assignment
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $location_id = isset($_GET['location_id']) ? (int) $_GET['location_id'] : 0;
        if ($location_id <= 0) {
            throw new Exception('Invalid location ID');
        }
        $rows = supabase_table_get('location_ownership', [
            'select' => 'id,character_id,ownership_type,notes',
            'location_id' => 'eq.' . $location_id
        ]);
        $charIds = array_unique(array_filter(array_column($rows, 'character_id')));
        $characters = [];
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', [
                'select' => 'id,character_name,clan,player_name',
                'id' => 'in.(' . implode(',', array_map('intval', $charIds)) . ')'
            ]);
            foreach ($chars as $c) {
                $characters[(int) $c['id']] = $c;
            }
        }
        $assignments = [];
        foreach ($rows as $r) {
            $cid = (int) ($r['character_id'] ?? 0);
            $c = $characters[$cid] ?? null;
            $assignments[] = [
                'id' => (int) ($r['id'] ?? 0),
                'character_id' => $cid,
                'character_name' => $c['character_name'] ?? '',
                'clan' => $c['clan'] ?? '',
                'player_name' => $c['player_name'] ?? '',
                'ownership_type' => $r['ownership_type'] ?? '',
                'notes' => $r['notes'] ?? ''
            ];
        }
        usort($assignments, static function ($a, $b) {
            return strcasecmp($a['character_name'], $b['character_name']);
        });
        echo json_encode(['success' => true, 'assignments' => $assignments, 'count' => count($assignments)]);
    } elseif ($method === 'POST') {
        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $location_id = (int) ($input['location_id'] ?? 0);
        $character_ids = $input['character_ids'] ?? [];
        $ownership_type = trim((string) ($input['ownership_type'] ?? 'Resident'));
        if ($location_id <= 0 || empty($character_ids)) {
            throw new Exception('Invalid location_id or character_ids');
        }
        supabase_rest_request('DELETE', '/rest/v1/location_ownership', ['location_id' => 'eq.' . $location_id], null, ['Prefer: return=minimal']);
        $now = date('Y-m-d H:i:s');
        foreach ($character_ids as $character_id) {
            $char_id = (int) $character_id;
            if ($char_id > 0) {
                supabase_rest_request('POST', '/rest/v1/location_ownership', [], [
                    'location_id' => $location_id,
                    'character_id' => $char_id,
                    'ownership_type' => $ownership_type,
                    'created_at' => $now
                ], ['Prefer: return=minimal']);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Characters assigned successfully', 'count' => count($character_ids)]);
    } elseif ($method === 'DELETE') {
        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $assignment_id = (int) ($input['assignment_id'] ?? 0);
        if ($assignment_id <= 0) {
            throw new Exception('Invalid assignment ID');
        }
        $res = supabase_rest_request('DELETE', '/rest/v1/location_ownership', ['id' => 'eq.' . $assignment_id], null, ['Prefer: return=minimal']);
        if ($res['error'] !== null) {
            throw new Exception($res['error']);
        }
        echo json_encode(['success' => true, 'message' => 'Assignment removed successfully']);
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
