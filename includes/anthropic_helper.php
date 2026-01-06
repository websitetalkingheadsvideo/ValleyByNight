<?php
declare(strict_types=1);

/**
 * AI Helper Functions (LM Studio/Anthropic)
 * 
 * Provides functions for interacting with AI APIs
 * Uses LM Studio (local, OpenAI-compatible) as the primary AI service
 */

/**
 * Load OpenAI API key from environment
 * 
 * @return string|null API key or null if not set
 */
function load_openai_api_key(): ?string
{
    $key = getenv('OPENAI_API_KEY');
    if ($key === false || $key === '') {
        return null;
    }
    return $key;
}

/**
 * Load Anthropic API key from environment (for backward compatibility)
 * 
 * @return string|null API key or null if not set
 */
function load_anthropic_api_key(): ?string
{
    $key = getenv('ANTHROPIC_API_KEY');
    if ($key === false || $key === '') {
        return null;
    }
    return $key;
}

/**
 * Call LM Studio API (OpenAI-compatible local server)
 * 
 * @param string $prompt User prompt/question
 * @param string $systemPrompt System prompt/instructions
 * @param int $maxTokens Maximum tokens in response
 * @return array{success:bool,content?:string,model?:string,error?:string}
 */
function call_openai(string $prompt, string $systemPrompt, int $maxTokens = 1500): array
{
    // LM Studio base URL (configurable via environment variable)
    $lmBase = getenv('LM_STUDIO_BASE_URL');
    if ($lmBase === false || $lmBase === '') {
        $lmBase = 'http://192.168.0.217:1234';
    }

    // Model ID (configurable via environment variable)
    $modelId = getenv('LM_STUDIO_MODEL_ID');
    if ($modelId === false || $modelId === '') {
        $modelId = 'mistralai/mistral-7b-instruct-v0.3';
    }

    $url = rtrim($lmBase, '/') . '/v1/chat/completions';
    
    // LM Studio models typically don't support "system" role
    // Combine system prompt with user prompt
    $combinedPrompt = $systemPrompt . "\n\n" . $prompt;
    
    $data = [
        'model' => $modelId,
        'messages' => [
            [
                'role' => 'user',
                'content' => $combinedPrompt,
            ],
        ],
        'temperature' => 0.3,
        'max_tokens' => $maxTokens,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $error,
        ];
    }

    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Failed to get response from LM Studio API',
        ];
    }

    $responseData = json_decode($response, true);
    if ($responseData === null) {
        return [
            'success' => false,
            'error' => 'Failed to parse API response: ' . json_last_error_msg() . ' (HTTP ' . $httpCode . ')',
        ];
    }

    if ($httpCode !== 200) {
        // Try to extract error message from response
        $errorMessage = 'Unknown API error';
        if (isset($responseData['error']['message'])) {
            $errorMessage = $responseData['error']['message'];
        } elseif (isset($responseData['error'])) {
            if (is_string($responseData['error'])) {
                $errorMessage = $responseData['error'];
            } elseif (is_array($responseData['error'])) {
                $errorMessage = json_encode($responseData['error']);
            }
        } elseif (!empty($responseData)) {
            // Response exists but doesn't match expected format
            $errorMessage = 'Unexpected response format';
        } else {
            // No response data at all
            $errorMessage = 'No response data';
        }
        
        return [
            'success' => false,
            'error' => 'API error (HTTP ' . $httpCode . '): ' . $errorMessage,
        ];
    }

    if (!isset($responseData['choices'][0]['message']['content'])) {
        return [
            'success' => false,
            'error' => 'Unexpected API response format',
        ];
    }

    return [
        'success' => true,
        'content' => $responseData['choices'][0]['message']['content'],
        'model' => $responseData['model'] ?? $modelId,
    ];
}

/**
 * Call Anthropic API (for backward compatibility, but prefers LM Studio)
 * 
 * @param string $prompt User prompt/question
 * @param string $systemPrompt System prompt/instructions
 * @param int $maxTokens Maximum tokens in response
 * @return array{success:bool,content?:string,model?:string,error?:string}
 */
function call_anthropic(string $prompt, string $systemPrompt, int $maxTokens = 1500): array
{
    // Try LM Studio first (via call_openai which now uses LM Studio)
    return call_openai($prompt, $systemPrompt, $maxTokens);

    // Fall back to Anthropic if OpenAI not available
    $apiKey = load_anthropic_api_key();
    if ($apiKey === null) {
        return [
            'success' => false,
            'error' => 'OPENAI_API_KEY or ANTHROPIC_API_KEY not configured',
        ];
    }

    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => $maxTokens,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'system' => $systemPrompt,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $error,
        ];
    }

    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Failed to get response from Anthropic API',
        ];
    }

    $responseData = json_decode($response, true);
    if ($responseData === null) {
        return [
            'success' => false,
            'error' => 'Failed to parse API response: ' . json_last_error_msg(),
        ];
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['error']['message'] ?? 'Unknown API error';
        return [
            'success' => false,
            'error' => 'API error: ' . $errorMessage,
        ];
    }

    if (!isset($responseData['content'][0]['text'])) {
        return [
            'success' => false,
            'error' => 'Unexpected API response format',
        ];
    }

    return [
        'success' => true,
        'content' => $responseData['content'][0]['text'],
        'model' => $responseData['model'] ?? 'claude-3-5-sonnet-20241022',
    ];
}
