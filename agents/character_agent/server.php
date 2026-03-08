<?php
// server.php - Valley by Night Character MCP (PHP + Supabase)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/supabase_client.php';
require __DIR__ . '/db.php';

/**
 * Write a JSON-RPC 2.0 response to STDOUT.
 */
function mcp_respond($id, $result = null, $error = null): void {
    $response = [
        'jsonrpc' => '2.0',
        'id' => $id
    ];
    
    if ($error !== null) {
        $response['error'] = $error;
    } else {
        $response['result'] = $result;
    }
    
    fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_UNICODE) . "\n");
    fflush(STDOUT);
}

/**
 * Helper to apply wildcards for LIKE searches.
 */
function like_pattern(?string $value): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    return '%' . $value . '%';
}

/**
 * Tool: listCharacters (Supabase)
 */
function tool_listCharacters(array $input): array {
    $clan  = $input['clan'] ?? null;
    $sect  = $input['sect'] ?? null;
    $limit = isset($input['limit']) ? (int) $input['limit'] : 50;
    if ($limit <= 0) {
        $limit = 50;
    }
    $query = [
        'select' => 'id,character_name,clan,sect,status,title',
        'order' => 'character_name.asc',
        'limit' => (string) $limit
    ];
    if (!empty($clan)) {
        $query['clan'] = 'eq.' . $clan;
    }
    if (!empty($sect)) {
        $query['sect'] = 'eq.' . $sect;
    }
    $rows = supabase_table_get('characters', $query);
    return ['ok' => true, 'results' => $rows];
}

/**
 * Tool: searchCharacters (Supabase) – field filters and optional q (ilike across columns).
 */
function tool_searchCharacters(array $input): array {
    $limit = isset($input['limit']) ? (int) $input['limit'] : 100;
    if ($limit <= 0) {
        $limit = 100;
    }
    $query = [
        'select' => 'id,character_name,clan,sect,status,title',
        'order' => 'character_name.asc',
        'limit' => (string) $limit
    ];
    $fieldMap = [
        'character_name' => 'character_name',
        'clan' => 'clan',
        'sect' => 'sect',
        'status' => 'status',
        'title' => 'title'
    ];
    foreach ($fieldMap as $inputKey => $column) {
        if (!empty($input[$inputKey])) {
            $term = '%' . $input[$inputKey] . '%';
            $query[$column] = 'ilike.' . $term;
        }
    }
    if (!empty($input['q'])) {
        $q = $input['q'];
        $pat = '*' . $q . '*'; // PostgREST * = % for ilike
        $query['or'] = '(character_name.ilike.' . $pat . ',clan.ilike.' . $pat . ',sect.ilike.' . $pat . ',status.ilike.' . $pat . ',title.ilike.' . $pat . ',character_json.ilike.' . $pat . ')';
    }
    $rows = supabase_table_get('characters', $query);
    return ['ok' => true, 'results' => $rows];
}

/**
 * Tool: getCharacter (Supabase)
 */
function tool_getCharacter(array $input): array {
    if (!isset($input['id'])) {
        return ['ok' => false, 'error' => 'Missing required field: id'];
    }
    $id = (int) $input['id'];
    $rows = supabase_table_get('characters', [
        'select' => 'id,character_name,clan,sect,status,title,character_json',
        'id' => 'eq.' . $id,
        'limit' => '1'
    ]);
    $row = $rows[0] ?? null;
    if (!$row) {
        return ['ok' => false, 'error' => 'Character not found'];
    }
    $character = [];
    if (!empty($row['character_json'])) {
        $decoded = json_decode($row['character_json'], true);
        if (is_array($decoded)) {
            $character = $decoded;
        }
    }
    $character['_db_meta'] = [
        'id' => $row['id'],
        'character_name' => $row['character_name'] ?? '',
        'clan' => $row['clan'] ?? '',
        'sect' => $row['sect'] ?? '',
        'status' => $row['status'] ?? '',
        'title' => $row['title'] ?? '',
    ];
    return ['ok' => true, 'character' => $character];
}

/**
 * Tool: updateCharacterJson (Supabase)
 */
function tool_updateCharacterJson(array $input): array {
    if (!isset($input['id'], $input['character']) || !is_array($input['character'])) {
        return ['ok' => false, 'error' => 'Missing id or character object'];
    }
    $id = (int) $input['id'];
    $json = json_encode($input['character'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $res = supabase_rest_request('PATCH', '/rest/v1/characters', ['id' => 'eq.' . $id], ['character_json' => $json], ['Prefer: return=minimal']);
    if ($res['error'] !== null) {
        return ['ok' => false, 'error' => $res['error']];
    }
    return ['ok' => true, 'id' => $id];
}

/**
 * Main MCP loop:
 *  - reads one JSON object per line from STDIN
 *  - dispatches to the requested tool
 *  - responds with { id, result } JSON
 */
while (!feof(STDIN)) {
    $line = fgets(STDIN);
    if ($line === false) {
        break;
    }
    $line = trim($line);
    if ($line === '') {
        continue;
    }

    $request = json_decode($line, true);
    if (!is_array($request)) {
        mcp_respond(null, null, ['code' => -32700, 'message' => 'Parse error']);
        continue;
    }

    // Handle MCP protocol initialization
    if (isset($request['method'])) {
        $method = $request['method'];
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;
        
        // Handle initialize request
        if ($method === 'initialize') {
            mcp_respond($id, [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => []
                ],
                'serverInfo' => [
                    'name' => 'vbn-character-mcp',
                    'version' => '1.1.0'
                ]
            ]);
            continue;
        }
        
        // Handle tools/list request
        if ($method === 'tools/list') {
            mcp_respond($id, [
                'tools' => [
                    ['name' => 'listCharacters', 'description' => 'List characters with optional filters'],
                    ['name' => 'searchCharacters', 'description' => 'Search characters by specific fields'],
                    ['name' => 'getCharacter', 'description' => 'Get a character by ID'],
                    ['name' => 'updateCharacterJson', 'description' => 'Update character JSON data']
                ]
            ]);
            continue;
        }
        
        // Handle tools/call request
        if ($method === 'tools/call') {
            $toolName = $params['name'] ?? null;
            $input = $params['arguments'] ?? [];
            
            try {
                switch ($toolName) {
                    case 'listCharacters':
                        $result = tool_listCharacters($input);
                        break;
                    case 'searchCharacters':
                        $result = tool_searchCharacters($input);
                        break;
                    case 'getCharacter':
                        $result = tool_getCharacter($input);
                        break;
                    case 'updateCharacterJson':
                        $result = tool_updateCharacterJson($input);
                        break;
                    default:
                        mcp_respond($id, null, ['code' => -32601, 'message' => "Method not found: {$toolName}"]);
                        continue 2;
                }
                
                mcp_respond($id, $result);
            } catch (Throwable $e) {
                mcp_respond($id, null, ['code' => -32603, 'message' => 'Internal error: ' . $e->getMessage()]);
            }
            continue;
        }
        
        // Unknown method
        mcp_respond($id, null, ['code' => -32601, 'message' => "Method not found: {$method}"]);
        continue;
    }

    // Fallback for non-MCP format (legacy support)
    $toolName = $request['tool']['name'] ?? null;
    $input = $request['input'] ?? [];
    $id = $request['id'] ?? null;

    if (!$toolName) {
        mcp_respond($id, null, ['code' => -32600, 'message' => 'Invalid request']);
        continue;
    }

    try {
        switch ($toolName) {
            case 'listCharacters':
                $result = tool_listCharacters($input);
                break;
            case 'searchCharacters':
                $result = tool_searchCharacters($input);
                break;
            case 'getCharacter':
                $result = tool_getCharacter($input);
                break;
            case 'updateCharacterJson':
                $result = tool_updateCharacterJson($input);
                break;
            default:
                mcp_respond($id, null, ['code' => -32601, 'message' => "Unknown tool: {$toolName}"]);
                continue;
        }
        
        mcp_respond($id, $result);
    } catch (Throwable $e) {
        mcp_respond($id, null, ['code' => -32603, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}
