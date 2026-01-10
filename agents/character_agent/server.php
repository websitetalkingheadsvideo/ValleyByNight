<?php
// server.php - Valley by Night Character MCP (PHP + MySQL)
// Minimal STDIN/STDOUT loop exposing tools defined in mcp.json.

// Suppress any output that might interfere with JSON-RPC
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
 * Tool: listCharacters
 * Simple listing with optional clan / sect filters.
 */
function tool_listCharacters(array $input): array {
    $conn  = vbn_get_connection();
    $clan  = $input['clan'] ?? null;
    $sect  = $input['sect'] ?? null;
    $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
    if ($limit <= 0) {
        $limit = 50;
    }

    $sql = "SELECT id, character_name, clan, sect, status, title
            FROM characters
            WHERE 1=1";
    $types = '';
    $params = [];

    if (!empty($clan)) {
        $sql .= " AND clan = ?";
        $types .= 's';
        $params[] = $clan;
    }
    if (!empty($sect)) {
        $sql .= " AND sect = ?";
        $types .= 's';
        $params[] = $sect;
    }

    $sql .= " ORDER BY character_name ASC LIMIT ?";
    $types .= 'i';
    $params[] = $limit;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)];
    }

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => 'Execute failed: ' . mysqli_stmt_error($stmt)];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return [
        'ok' => true,
        'results' => $rows
    ];
}

/**
 * Tool: searchCharacters
 * Flexible search:
 *  - specific fields: character_name, clan, sect, status, title
 *  - q: free-text across name, clan, sect, status, title, character_json
 */
function tool_searchCharacters(array $input): array {
    $conn  = vbn_get_connection();
    $limit = isset($input['limit']) ? (int)$input['limit'] : 100;
    if ($limit <= 0) {
        $limit = 100;
    }

    $sql = "SELECT id, character_name, clan, sect, status, title
            FROM characters
            WHERE 1=1";
    $types = '';
    $params = [];

    // Specific field filters (LIKE for fuzzy matching).
    $fieldMap = [
        'character_name' => 'character_name',
        'clan'           => 'clan',
        'sect'           => 'sect',
        'status'         => 'status',
        'title'          => 'title'
    ];

    foreach ($fieldMap as $inputKey => $column) {
        if (!empty($input[$inputKey])) {
            $pattern = like_pattern($input[$inputKey]);
            $sql .= " AND {$column} LIKE ?";
            $types .= 's';
            $params[] = $pattern;
        }
    }

    // Free-text search across common fields + JSON.
    if (!empty($input['q'])) {
        $qPattern = like_pattern($input['q']);
        $sql .= " AND (character_name LIKE ?
                   OR clan LIKE ?
                   OR sect LIKE ?
                   OR status LIKE ?
                   OR title LIKE ?
                   OR character_json LIKE ?)";
        $types .= 'ssssss';
        $params[] = $qPattern;
        $params[] = $qPattern;
        $params[] = $qPattern;
        $params[] = $qPattern;
        $params[] = $qPattern;
        $params[] = $qPattern;
    }

    $sql .= " ORDER BY character_name ASC LIMIT ?";
    $types .= 'i';
    $params[] = $limit;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)];
    }

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => 'Execute failed: ' . mysqli_stmt_error($stmt)];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return [
        'ok'      => true,
        'results' => $rows
    ];
}

/**
 * Tool: getCharacter
 * Returns a decoded character_json plus some DB metadata.
 */
function tool_getCharacter(array $input): array {
    if (!isset($input['id'])) {
        return ['ok' => false, 'error' => 'Missing required field: id'];
    }

    $id   = (int)$input['id'];
    $conn = vbn_get_connection();

    $stmt = mysqli_prepare($conn, "SELECT id, character_name, clan, sect, status, title, character_json
                                    FROM characters
                                    WHERE id = ?");
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => 'Execute failed: ' . mysqli_stmt_error($stmt)];
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

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
        'id'             => $row['id'],
        'character_name' => $row['character_name'],
        'clan'           => $row['clan'],
        'sect'           => $row['sect'],
        'status'         => $row['status'],
        'title'          => $row['title'],
    ];

    return [
        'ok'        => true,
        'character' => $character
    ];
}

/**
 * Tool: updateCharacterJson
 * Overwrites the character_json column for a given id.
 */
function tool_updateCharacterJson(array $input): array {
    if (!isset($input['id'], $input['character']) || !is_array($input['character'])) {
        return ['ok' => false, 'error' => 'Missing id or character object'];
    }

    $id        = (int)$input['id'];
    $character = $input['character'];

    $json = json_encode($character, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $conn = vbn_get_connection();
    $stmt = mysqli_prepare($conn, "UPDATE characters
                                    SET character_json = ?
                                    WHERE id = ?");
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Prepare failed: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'si', $json, $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => 'Execute failed: ' . mysqli_stmt_error($stmt)];
    }

    mysqli_stmt_close($stmt);

    return [
        'ok' => true,
        'id' => $id
    ];
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
