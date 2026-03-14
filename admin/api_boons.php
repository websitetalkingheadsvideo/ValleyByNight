<?php
/**
 * API for Boon Management
 * Handles CRUD operations for boons table (Supabase)
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../includes/supabase_client.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($action) {
    case 'list':
        handleListBoons();
        break;
    case 'get':
        handleGetBoon();
        break;
    default:
        if ($method === 'POST') {
            handleCreateBoon();
        } elseif ($method === 'PUT') {
            handleUpdateBoon();
        } elseif ($method === 'DELETE') {
            handleDeleteBoon();
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
        }
        break;
}

function handleListBoons(): void {
    $status = $_GET['status'] ?? 'all';
    $query = ['select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date,fulfilled_date,due_date,notes,created_by,updated_at,registered_with_harpy,date_registered,harpy_notes', 'order' => 'created_date.desc'];
    if ($status !== 'all') {
        $statusMap = ['Owed' => 'active', 'Called' => 'active', 'Paid' => 'fulfilled', 'Broken' => 'disputed'];
        $dbStatus = $statusMap[$status] ?? strtolower($status);
        $query['status'] = 'eq.' . $dbStatus;
    }
    $boons = supabase_table_get('boons', $query);
    $charIds = [];
    foreach ($boons as $b) {
        if (!empty($b['creditor_id'])) {
            $charIds[(int) $b['creditor_id']] = true;
        }
        if (!empty($b['debtor_id'])) {
            $charIds[(int) $b['debtor_id']] = true;
        }
    }
    $charIds = array_keys($charIds);
    $nameMap = [];
    if (!empty($charIds)) {
        $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', $charIds) . ')']);
        foreach ($chars as $c) {
            $nameMap[(int) $c['id']] = $c['character_name'] ?? '';
        }
    }
    $statusMap = ['active' => 'Owed', 'fulfilled' => 'Paid', 'cancelled' => 'Broken', 'disputed' => 'Broken'];
    $out = [];
    foreach ($boons as $row) {
        $row['giver_name'] = $nameMap[(int) ($row['creditor_id'] ?? 0)] ?? '';
        $row['receiver_name'] = $nameMap[(int) ($row['debtor_id'] ?? 0)] ?? '';
        $row['boon_id'] = $row['id'];
        $row['date_created'] = $row['created_date'] ?? null;
        $row['status'] = $statusMap[strtolower((string) ($row['status'] ?? ''))] ?? $row['status'];
        $row['boon_type'] = ucfirst(strtolower((string) ($row['boon_type'] ?? '')));
        $out[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $out]);
}

function handleGetBoon(): void {
    $boon_id = (int) ($_GET['id'] ?? 0);
    if (!$boon_id) {
        echo json_encode(['success' => false, 'error' => 'Boon ID required']);
        return;
    }
    $rows = supabase_table_get('boons', ['select' => '*', 'id' => 'eq.' . $boon_id, 'limit' => '1']);
    $boon = $rows[0] ?? null;
    if (!$boon) {
        echo json_encode(['success' => false, 'error' => 'Boon not found']);
        return;
    }
    $creditor_id = (int) ($boon['creditor_id'] ?? 0);
    $debtor_id = (int) ($boon['debtor_id'] ?? 0);
    $nameMap = [];
    if ($creditor_id || $debtor_id) {
        $ids = array_filter([$creditor_id, $debtor_id]);
        $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', $ids) . ')']);
        foreach ($chars as $c) {
            $nameMap[(int) $c['id']] = $c['character_name'] ?? '';
        }
    }
    $boon['giver_name'] = $nameMap[$creditor_id] ?? '';
    $boon['receiver_name'] = $nameMap[$debtor_id] ?? '';
    $boon['boon_id'] = $boon['id'];
    $boon['date_created'] = $boon['created_date'] ?? null;
    $boon['status'] = ['active' => 'Owed', 'fulfilled' => 'Paid', 'cancelled' => 'Broken', 'disputed' => 'Broken'][strtolower((string) ($boon['status'] ?? ''))] ?? $boon['status'];
    $boon['boon_type'] = ucfirst(strtolower((string) ($boon['boon_type'] ?? '')));
    echo json_encode(['success' => true, 'data' => $boon]);
}

function handleCreateBoon(): void {
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        return;
    }
    $creditor_id = null;
    $debtor_id = null;
    if (isset($input['creditor_id']) && is_numeric($input['creditor_id'])) {
        $creditor_id = (int) $input['creditor_id'];
    } elseif (!empty($input['giver_name'])) {
        $creditor_id = getCharacterIdByName($input['giver_name']);
        if (!$creditor_id) {
            echo json_encode(['success' => false, 'error' => 'Creditor (giver) not found in database']);
            return;
        }
    }
    if (isset($input['debtor_id']) && is_numeric($input['debtor_id'])) {
        $debtor_id = (int) $input['debtor_id'];
    } elseif (!empty($input['receiver_name'])) {
        $debtor_id = getCharacterIdByName($input['receiver_name']);
        if (!$debtor_id) {
            echo json_encode(['success' => false, 'error' => 'Debtor (receiver) not found in database']);
            return;
        }
    }
    if (!$creditor_id || !$debtor_id) {
        echo json_encode(['success' => false, 'error' => 'Creditor and debtor IDs or names are required']);
        return;
    }
    $boon_type = strtolower(trim((string) ($input['boon_type'] ?? '')));
    if (!in_array($boon_type, ['trivial', 'minor', 'major', 'life'], true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid boon type. Must be: trivial, minor, major, life']);
        return;
    }
    $status = strtolower(trim((string) ($input['status'] ?? 'active')));
    $statusMap = ['owed' => 'active', 'called' => 'active', 'paid' => 'fulfilled', 'broken' => 'disputed'];
    $status = $statusMap[$status] ?? 'active';
    $payload = [
        'creditor_id' => $creditor_id,
        'debtor_id' => $debtor_id,
        'boon_type' => $boon_type,
        'status' => $status,
        'description' => trim((string) ($input['description'] ?? '')),
        'notes' => trim((string) ($input['notes'] ?? '')),
        'due_date' => !empty($input['due_date']) ? $input['due_date'] : null,
        'created_by' => $_SESSION['user_id'] ?? null,
        'registered_with_harpy' => !empty($input['registered_with_harpy']) ? trim((string) $input['registered_with_harpy']) : null,
        'harpy_notes' => !empty($input['harpy_notes']) ? trim((string) $input['harpy_notes']) : null,
        'created_date' => date('Y-m-d H:i:s'),
    ];
    $res = supabase_rest_request('POST', '/rest/v1/boons', [], $payload, ['Prefer: return=representation']);
    if ($res['error'] !== null) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $res['error']]);
        return;
    }
    $data = is_array($res['data']) ? $res['data'] : (isset($res['data'][0]) ? $res['data'][0] : null);
    $boon_id = $data['id'] ?? null;
    echo json_encode(['success' => true, 'message' => 'Boon created successfully', 'boon_id' => $boon_id]);
}

function handleUpdateBoon(): void {
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        return;
    }
    $boon_id = (int) ($input['boon_id'] ?? $input['id'] ?? 0);
    if (!$boon_id) {
        echo json_encode(['success' => false, 'error' => 'Boon ID required']);
        return;
    }
    $payload = [];
    if (isset($input['creditor_id']) && is_numeric($input['creditor_id'])) {
        $payload['creditor_id'] = (int) $input['creditor_id'];
    } elseif (!empty($input['giver_name'])) {
        $cid = getCharacterIdByName($input['giver_name']);
        if ($cid) {
            $payload['creditor_id'] = $cid;
        }
    }
    if (isset($input['debtor_id']) && is_numeric($input['debtor_id'])) {
        $payload['debtor_id'] = (int) $input['debtor_id'];
    } elseif (!empty($input['receiver_name'])) {
        $did = getCharacterIdByName($input['receiver_name']);
        if ($did) {
            $payload['debtor_id'] = $did;
        }
    }
    if (!empty($input['boon_type'])) {
        $bt = strtolower(trim((string) $input['boon_type']));
        if (in_array($bt, ['trivial', 'minor', 'major', 'life'], true)) {
            $payload['boon_type'] = $bt;
        }
    }
    if (!empty($input['status'])) {
        $st = strtolower(trim((string) $input['status']));
        $statusMap = ['owed' => 'active', 'called' => 'active', 'paid' => 'fulfilled', 'broken' => 'disputed'];
        $st = $statusMap[$st] ?? $st;
        if (in_array($st, ['active', 'fulfilled', 'cancelled', 'disputed'], true)) {
            $payload['status'] = $st;
            if ($st === 'fulfilled') {
                $payload['fulfilled_date'] = date('Y-m-d H:i:s');
            }
        }
    }
    if (isset($input['description'])) {
        $payload['description'] = trim((string) $input['description']);
    }
    if (isset($input['notes'])) {
        $payload['notes'] = trim((string) $input['notes']);
    }
    if (isset($input['registered_with_harpy'])) {
        $payload['registered_with_harpy'] = !empty($input['registered_with_harpy']) ? trim((string) $input['registered_with_harpy']) : null;
        if (!empty($input['registered_with_harpy'])) {
            $payload['date_registered'] = date('Y-m-d H:i:s');
        }
    }
    if (isset($input['harpy_notes'])) {
        $payload['harpy_notes'] = !empty($input['harpy_notes']) ? trim((string) $input['harpy_notes']) : null;
    }
    $payload['updated_at'] = date('Y-m-d H:i:s');
    if (empty($payload)) {
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    $res = supabase_rest_request('PATCH', '/rest/v1/boons', ['id' => 'eq.' . $boon_id], $payload, ['Prefer: return=minimal']);
    if ($res['error'] !== null) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $res['error']]);
        return;
    }
    echo json_encode(['success' => true, 'message' => 'Boon updated successfully']);
}

function handleDeleteBoon(): void {
    $boon_id = (int) ($_GET['id'] ?? 0);
    if (!$boon_id) {
        echo json_encode(['success' => false, 'error' => 'Boon ID required']);
        return;
    }
    $res = supabase_rest_request('DELETE', '/rest/v1/boons', ['id' => 'eq.' . $boon_id], null, ['Prefer: return=minimal']);
    if ($res['error'] !== null) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $res['error']]);
        return;
    }
    echo json_encode(['success' => true, 'message' => 'Boon deleted successfully']);
}

function getCharacterIdByName(string $name): ?int {
    $rows = supabase_table_get('characters', ['select' => 'id', 'character_name' => 'eq.' . $name, 'limit' => '1']);
    $row = $rows[0] ?? null;
    return $row ? (int) $row['id'] : null;
}
