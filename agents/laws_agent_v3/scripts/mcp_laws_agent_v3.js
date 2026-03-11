#!/usr/bin/env node

/**
 * Laws Agent MCP Server v3
 * Uses the Cloudflare AI Search REST API for retrieval.
 */

const fs = require('fs');
const https = require('https');
const path = require('path');

// Load project .env so MCP has tokens when Cursor does not inject them
const projectRoot = path.resolve(__dirname, '..', '..', '..');
const envPath = path.join(projectRoot, '.env');
try {
    if (fs.existsSync(envPath)) {
        const content = fs.readFileSync(envPath, 'utf8');
        for (const line of content.split('\n')) {
            const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
            if (match && !process.env[match[1]]) {
                process.env[match[1]] = match[2].replace(/^["']|["']$/g, '').trim();
            }
        }
    }
} catch (_) {}

function requireEnv(name) {
    const value = process.env[name];

    if (!value || String(value).trim() === '') {
        throw new Error(`Missing required environment variable: ${name}`);
    }

    return value;
}

function extractExcerpt(text, maxChars) {
    const normalized = String(text || '').replace(/\s+/g, ' ').trim();

    if (normalized.length <= maxChars) {
        return normalized;
    }

    const excerpt = normalized.substring(0, maxChars);
    const lastPeriod = excerpt.lastIndexOf('.');

    if (lastPeriod !== -1 && lastPeriod > maxChars * 0.7) {
        return normalized.substring(0, lastPeriod + 1);
    }

    return `${excerpt}...`;
}

function buildCloudflareFilters(category, system) {
    const filters = [];

    if (category) {
        filters.push({
            type: 'eq',
            key: 'category',
            value: category,
        });
    }

    if (system) {
        filters.push({
            type: 'eq',
            key: 'system_type',
            value: system,
        });
    }

    if (filters.length === 0) {
        return null;
    }

    return {
        type: 'and',
        filters,
    };
}

function sendCloudflareSearchRequest(accountId, ragName, token, body) {
    const payload = JSON.stringify(body);
    const options = {
        hostname: 'api.cloudflare.com',
        port: 443,
        path: `/client/v4/accounts/${accountId}/autorag/rags/${encodeURIComponent(ragName)}/search`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(payload),
            Authorization: `Bearer ${token}`,
        },
    };

    return new Promise((resolve, reject) => {
        const req = https.request(options, (res) => {
            let responseBody = '';

            res.on('data', (chunk) => {
                responseBody += chunk;
            });

            res.on('end', () => {
                try {
                    const parsed = JSON.parse(responseBody);

                    if (res.statusCode !== 200 || !parsed.success || !parsed.result) {
                        throw new Error(parsed.errors?.[0]?.message || `Cloudflare AI Search request failed with HTTP ${res.statusCode}`);
                    }

                    resolve(parsed.result);
                } catch (error) {
                    reject(new Error(`Cloudflare AI Search request failed: ${error.message}`));
                }
            });
        });

        req.on('error', (error) => {
            reject(new Error(`Cloudflare AI Search request failed: ${error.message}`));
        });

        req.setTimeout(30000, () => {
            req.destroy();
            reject(new Error('Cloudflare AI Search request timed out'));
        });

        req.write(payload);
        req.end();
    });
}

function normalizeCloudflareSearchRows(rows) {
    return (rows || []).map((item) => {
        const attributes = item.attributes || item.metadata || {};
        const textParts = Array.isArray(item.content)
            ? item.content
                  .filter((contentItem) => contentItem.type === 'text' && contentItem.text)
                  .map((contentItem) => contentItem.text)
            : [];
        const text = textParts.join('\n').trim() || String(item.text || item.page_text || item.content || '');
        const rawPageNumber = attributes.page_number ?? attributes.page ?? item.page_number ?? item.page ?? 0;
        const pageNumber = Number.parseInt(String(rawPageNumber), 10);

        return {
            rulebook_id: item.file_id || item.id || '',
            book_title:
                attributes.book_title ||
                attributes.title ||
                item.filename ||
                item.title ||
                item.file_id ||
                'Unknown',
            category: attributes.category || item.category || 'Other',
            system_type: attributes.system_type || item.system_type || 'Unknown',
            page_number: Number.isFinite(pageNumber) ? pageNumber : 0,
            page_text: text,
            relevance: typeof item.score === 'number' ? item.score : 0,
        };
    });
}

async function searchRulebooksCloudflare(question, category, system, limit) {
    const accountId = requireEnv('CF_ACCOUNT_ID');
    const ragName = requireEnv('CF_AUTORAG_NAME');
    const token = requireEnv('CF_FUCKING_7th_API');
    const filters = buildCloudflareFilters(category, system);
    const body = {
        query: question,
        rewrite_query: true,
        max_num_results: Math.min(limit, 50),
        ranking_options: {
            score_threshold: 0.1,
        },
    };

    if (filters) {
        body.filters = filters;
    }

    const result = await sendCloudflareSearchRequest(accountId, ragName, token, body);
    return {
        response: typeof result.response === 'string' ? result.response : '',
        rows: normalizeCloudflareSearchRows(result.data || []).slice(0, limit),
    };
}

async function askLawsAgent(question, category, system) {
    try {
        const { response: cloudflareAnswer, rows: searchResults } = await searchRulebooksCloudflare(
            question,
            category,
            system,
            5
        );

        const answer =
            typeof cloudflareAnswer === 'string' && cloudflareAnswer.trim() !== ''
                ? cloudflareAnswer.trim()
                : "I couldn't find any relevant information in the rulebooks to answer that question. Please try rephrasing or being more specific.";

        const sources = searchResults.map((result) => ({
            book: result.book_title,
            page: result.page_number,
            category: result.category,
            system: result.system_type,
            excerpt: extractExcerpt(result.page_text, 300),
            relevance: Number(result.relevance),
        }));

        return {
            success: true,
            question,
            answer,
            sources,
            ai_model: 'Cloudflare AI Search',
            searched: true,
            results_found: searchResults.length,
        };
    } catch (error) {
        return {
            success: false,
            error: error.message,
            question,
        };
    }
}

class LawsAgentMCPServer {
    constructor() {
        this.tools = [
            {
                name: 'query_laws_agent',
                description: 'Ask VTM/MET rules questions to the Laws Agent. Uses Cloudflare AI Search for retrieval.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        question: {
                            type: 'string',
                            description: 'The rules question to ask.',
                        },
                        category: {
                            type: 'string',
                            enum: ['Core', 'Faction', 'Supplement', 'Blood Magic', 'Journal', 'Other'],
                            description: 'Optional category filter.',
                        },
                        system: {
                            type: 'string',
                            enum: ['MET-VTM', 'MET', 'VTM', 'MTA', 'WOD', 'Wraith'],
                            description: 'Optional game system filter.',
                        },
                    },
                    required: ['question'],
                },
            },
        ];
    }

    async handleToolCall(toolName, args) {
        if (toolName !== 'query_laws_agent') {
            return {
                content: [
                    {
                        type: 'text',
                        text: `Unknown tool: ${toolName}`,
                    },
                ],
                isError: true,
            };
        }

        try {
            const response = await askLawsAgent(args.question, args.category || null, args.system || null);

            if (!response.success) {
                return {
                    content: [
                        {
                            type: 'text',
                            text: `Error: ${response.error || 'Unknown error'}`,
                        },
                    ],
                    isError: true,
                };
            }

            let formattedText = `**Question:** ${response.question}\n\n`;
            formattedText += `**Answer:**\n${response.answer}\n\n`;

            if (response.sources.length > 0) {
                formattedText += '**Sources:**\n';
                response.sources.forEach((source, index) => {
                    formattedText += `${index + 1}. ${source.book} (Page ${source.page}) - ${source.category}, ${source.system}\n`;
                });
            }

            formattedText += `\n*Powered by ${response.ai_model}*`;

            return {
                content: [
                    {
                        type: 'text',
                        text: formattedText,
                    },
                ],
                isError: false,
            };
        } catch (error) {
            return {
                content: [
                    {
                        type: 'text',
                        text: `Failed to query Laws Agent: ${error.message}`,
                    },
                ],
                isError: true,
            };
        }
    }

    async handleMessage(message) {
        if (!Object.prototype.hasOwnProperty.call(message, 'id')) {
            return null;
        }

        if (message.method === 'initialize') {
            return {
                jsonrpc: '2.0',
                id: message.id,
                result: {
                    protocolVersion: '2024-11-05',
                    capabilities: {
                        tools: {},
                    },
                    serverInfo: {
                        name: 'laws-agent-v3',
                        version: '3.0.0',
                    },
                },
            };
        }

        if (message.method === 'tools/list') {
            return {
                jsonrpc: '2.0',
                id: message.id,
                result: {
                    tools: this.tools,
                },
            };
        }

        if (message.method === 'tools/call') {
            const result = await this.handleToolCall(message.params.name, message.params.arguments || {});

            return {
                jsonrpc: '2.0',
                id: message.id,
                result,
            };
        }

        return {
            jsonrpc: '2.0',
            id: message.id,
            error: {
                code: -32601,
                message: `Method not found: ${message.method}`,
            },
        };
    }

    async run() {
        process.stdin.setEncoding('utf8');
        let buffer = '';

        process.stdin.on('data', async (chunk) => {
            buffer += chunk;
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (!line.trim()) {
                    continue;
                }

                try {
                    const message = JSON.parse(line);
                    const response = await this.handleMessage(message);

                    if (response) {
                        process.stdout.write(`${JSON.stringify(response)}\n`);
                    }
                } catch (error) {
                    console.error('Error processing message:', error);
                }
            }
        });

        process.stdin.on('end', () => {
            process.exit(0);
        });
    }
}

if (require.main === module) {
    const args = process.argv.slice(2);
    if (args[0] === '--query') {
        let input = '';
        process.stdin.setEncoding('utf8');
        process.stdin.on('data', (chunk) => {
            input += chunk;
        });
        process.stdin.on('end', async () => {
            try {
                const payload = input.trim() ? JSON.parse(input) : {};
                const question = String(payload.question || '').trim();
                if (!question) {
                    process.stdout.write(
                        JSON.stringify({
                            success: false,
                            error: 'Question is required',
                            question: '',
                        }) + '\n'
                    );
                    process.exit(1);
                    return;
                }
                const result = await askLawsAgent(
                    question,
                    payload.category || null,
                    payload.system || null
                );
                process.stdout.write(JSON.stringify(result) + '\n');
            } catch (err) {
                let question = '';
                try {
                    const p = input.trim() ? JSON.parse(input) : {};
                    question = p.question || '';
                } catch (_) {}
                process.stdout.write(
                    JSON.stringify({
                        success: false,
                        error: err.message || 'Unknown error',
                        question,
                    }) + '\n'
                );
                process.exit(1);
            }
        });
        return;
    }
    const server = new LawsAgentMCPServer();
    server.run().catch((error) => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}

module.exports = LawsAgentMCPServer;
