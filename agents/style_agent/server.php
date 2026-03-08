<?php
// server.php - Valley by Night Style Agent MCP (PHP + MySQL)
// Minimal STDIN/STDOUT loop exposing tools for Art Bible access.

// Suppress any output that might interfere with JSON-RPC
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('output_buffering', 0);

// Ensure no output before we start
if (ob_get_level() > 0) {
    ob_end_clean();
}

try {
    require_once __DIR__ . '/../../includes/supabase_client.php';
    require __DIR__ . '/db.php';
} catch (Throwable $e) {
    // If db.php fails, we can't continue - but we need to output JSON-RPC error format
    fwrite(STDERR, "Fatal error loading db.php: " . $e->getMessage() . "\n");
    exit(1);
}

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
 * Wrap tool result for MCP tools/call: result must have content[] and isError.
 * For ok results that contain a 'content' string (e.g. getChapter, getRules, getPrompts), use that as text.
 */
function mcp_tool_result(array $raw): array {
    $isError = !isset($raw['ok']) || $raw['ok'] !== true;
    if ($isError) {
        $text = $raw['error'] ?? json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif (isset($raw['content']) && is_string($raw['content'])) {
        $text = $raw['content'];
    } else {
        $text = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    return [
        'content' => [
            ['type' => 'text', 'text' => $text]
        ],
        'isError' => $isError
    ];
}

/**
 * Get MCP base path from Supabase
 */
function get_mcp_path(): string {
    $rows = supabase_table_get('mcp_style_packs', [
        'select' => 'filesystem_path',
        'slug' => 'eq.style_agent_mcp',
        'enabled' => 'eq.1',
        'limit' => '1'
    ]);
    if (empty($rows)) {
        throw new Exception('Style Agent MCP not found in database');
    }
    $project_root = dirname(__DIR__, 2);
    return $project_root . '/' . ($rows[0]['filesystem_path'] ?? '');
}

/**
 * Tool: getMCPInfo
 * Get Style Agent MCP metadata from Supabase
 */
function tool_getMCPInfo(array $input): array {
    $rows = supabase_table_get('mcp_style_packs', [
        'select' => '*',
        'slug' => 'eq.style_agent_mcp',
        'enabled' => 'eq.1',
        'limit' => '1'
    ]);
    $mcp = $rows[0] ?? null;
    
    if (!$mcp) {
        return ['ok' => false, 'error' => 'Style Agent MCP not found or disabled'];
    }
    
    return [
        'ok' => true,
        'mcp' => $mcp
    ];
}

/**
 * Tool: getChapter
 * Load a specific Art Bible chapter file
 */
function tool_getChapter(array $input): array {
    if (!isset($input['chapter_file'])) {
        return ['ok' => false, 'error' => 'Missing required field: chapter_file'];
    }
    
    $chapter_file = basename($input['chapter_file']); // Security: prevent directory traversal
    $mcp_path = get_mcp_path();
    $file_path = $mcp_path . '/docs/' . $chapter_file;
    
    if (!file_exists($file_path)) {
        return ['ok' => false, 'error' => "Chapter file not found: {$chapter_file}"];
    }
    
    $content = file_get_contents($file_path);
    if ($content === false) {
        return ['ok' => false, 'error' => 'Failed to read chapter file'];
    }
    
    return [
        'ok' => true,
        'chapter_file' => $chapter_file,
        'content' => $content,
        'size' => strlen($content)
    ];
}

/**
 * Tool: listChapters
 * List all available Art Bible chapters
 */
function tool_listChapters(array $input): array {
    $mcp_path = get_mcp_path();
    $docs_path = $mcp_path . '/docs';
    
    if (!is_dir($docs_path)) {
        return ['ok' => false, 'error' => 'docs directory not found'];
    }
    
    $files = glob($docs_path . '/*.md');
    $chapters = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        $chapters[] = [
            'filename' => $filename,
            'size' => filesize($file)
        ];
    }
    
    $metadata = [];
    $mcpRows = supabase_table_get('mcp_style_packs', ['select' => 'id', 'slug' => 'eq.style_agent_mcp', 'limit' => '1']);
    if (!empty($mcpRows)) {
        $mcpId = $mcpRows[0]['id'] ?? null;
        if ($mcpId !== null) {
            $metaRows = supabase_table_get('mcp_style_chapters', [
                'select' => 'chapter_name,chapter_file,chapter_number,description,tags',
                'mcp_pack_id' => 'eq.' . $mcpId,
                'order' => 'display_order.asc'
            ]);
            foreach ($metaRows as $row) {
                $metadata[$row['chapter_file'] ?? ''] = $row;
            }
        }
    }
    
    // Merge metadata with file list
    foreach ($chapters as &$chapter) {
        if (isset($metadata[$chapter['filename']])) {
            $chapter['metadata'] = $metadata[$chapter['filename']];
        }
    }
    
    return [
        'ok' => true,
        'chapters' => $chapters
    ];
}

/**
 * Tool: getRules
 * Load the distilled RULES.md file
 */
function tool_getRules(array $input): array {
    $mcp_path = get_mcp_path();
    $file_path = $mcp_path . '/RULES.md';
    
    if (!file_exists($file_path)) {
        return ['ok' => false, 'error' => 'RULES.md not found'];
    }
    
    $content = file_get_contents($file_path);
    if ($content === false) {
        return ['ok' => false, 'error' => 'Failed to read RULES.md'];
    }
    
    return [
        'ok' => true,
        'content' => $content,
        'size' => strlen($content)
    ];
}

/**
 * Tool: getPrompts
 * Load the PROMPTS.md file
 */
function tool_getPrompts(array $input): array {
    $mcp_path = get_mcp_path();
    $file_path = $mcp_path . '/PROMPTS.md';
    
    if (!file_exists($file_path)) {
        return ['ok' => false, 'error' => 'PROMPTS.md not found'];
    }
    
    $content = file_get_contents($file_path);
    if ($content === false) {
        return ['ok' => false, 'error' => 'Failed to read PROMPTS.md'];
    }
    
    return [
        'ok' => true,
        'content' => $content,
        'size' => strlen($content)
    ];
}

/**
 * Tool: searchArtBible
 * Search across Art Bible chapter files
 */
function tool_searchArtBible(array $input): array {
    if (!isset($input['query']) || empty($input['query'])) {
        return ['ok' => false, 'error' => 'Missing required field: query'];
    }
    
    $query = strtolower($input['query']);
    $mcp_path = get_mcp_path();
    $docs_path = $mcp_path . '/docs';
    
    if (!is_dir($docs_path)) {
        return ['ok' => false, 'error' => 'docs directory not found'];
    }
    
    $files = glob($docs_path . '/*.md');
    $results = [];
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $lower_content = strtolower($content);
        
        if (strpos($lower_content, $query) !== false) {
            $filename = basename($file);
            $lines = explode("\n", $content);
            $matches = [];
            
            foreach ($lines as $line_num => $line) {
                if (stripos($line, $query) !== false) {
                    $matches[] = [
                        'line' => $line_num + 1,
                        'content' => trim($line)
                    ];
                }
            }
            
            $results[] = [
                'chapter_file' => $filename,
                'matches' => $matches,
                'match_count' => count($matches)
            ];
        }
    }
    
    return [
        'ok' => true,
        'query' => $input['query'],
        'results' => $results,
        'total_matches' => count($results)
    ];
}

/**
 * Main MCP loop:
 *  - reads one JSON object per line from STDIN
 *  - dispatches to the requested tool
 *  - responds with { id, result } JSON
 */
try {
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
                    'tools' => new \stdClass()
                ],
                'serverInfo' => [
                    'name' => 'vbn-style-agent-mcp',
                    'version' => '1.0.0'
                ]
            ]);
            continue;
        }
        
        // Handle initialized notification (no response needed)
        if ($method === 'initialized') {
            // Notification - no response
            continue;
        }
        
        // Handle tools/list request
        if ($method === 'tools/list') {
            mcp_respond($id, [
                'tools' => [
                    [
                        'name' => 'getMCPInfo',
                        'description' => 'Get Style Agent MCP metadata from database',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new \stdClass()
                        ]
                    ],
                    [
                        'name' => 'getChapter',
                        'description' => 'Load a specific Art Bible chapter file',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'chapter_file' => ['type' => 'string', 'description' => 'Filename of the chapter (e.g. Colors.md)']
                            ],
                            'required' => ['chapter_file']
                        ]
                    ],
                    [
                        'name' => 'listChapters',
                        'description' => 'List all available Art Bible chapters',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new \stdClass()
                        ]
                    ],
                    [
                        'name' => 'getRules',
                        'description' => 'Load the RULES.md file',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new \stdClass()
                        ]
                    ],
                    [
                        'name' => 'getPrompts',
                        'description' => 'Load the PROMPTS.md file',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => new \stdClass()
                        ]
                    ],
                    [
                        'name' => 'searchArtBible',
                        'description' => 'Search across Art Bible chapter files',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'query' => ['type' => 'string', 'description' => 'Search query string']
                            ],
                            'required' => ['query']
                        ]
                    ]
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
                    case 'getMCPInfo':
                        $result = tool_getMCPInfo($input);
                        break;
                    case 'getChapter':
                        $result = tool_getChapter($input);
                        break;
                    case 'listChapters':
                        $result = tool_listChapters($input);
                        break;
                    case 'getRules':
                        $result = tool_getRules($input);
                        break;
                    case 'getPrompts':
                        $result = tool_getPrompts($input);
                        break;
                    case 'searchArtBible':
                        $result = tool_searchArtBible($input);
                        break;
                    default:
                        mcp_respond($id, null, ['code' => -32601, 'message' => "Method not found: {$toolName}"]);
                        continue 2;
                }
                
                mcp_respond($id, mcp_tool_result($result));
            } catch (Throwable $e) {
                mcp_respond($id, mcp_tool_result(['ok' => false, 'error' => 'Internal error: ' . $e->getMessage()]));
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
            case 'getMCPInfo':
                $result = tool_getMCPInfo($input);
                break;
            case 'getChapter':
                $result = tool_getChapter($input);
                break;
            case 'listChapters':
                $result = tool_listChapters($input);
                break;
            case 'getRules':
                $result = tool_getRules($input);
                break;
            case 'getPrompts':
                $result = tool_getPrompts($input);
                break;
            case 'searchArtBible':
                $result = tool_searchArtBible($input);
                break;
            default:
                mcp_respond($id, null, ['code' => -32601, 'message' => "Unknown tool: {$toolName}"]);
                continue 2;
        }
        
        mcp_respond($id, mcp_tool_result($result));
    } catch (Throwable $e) {
        mcp_respond($id, mcp_tool_result(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]));
    }
    }
} catch (Throwable $e) {
    // Fatal error in main loop - log to stderr and exit
    fwrite(STDERR, "Fatal error in MCP server: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}

