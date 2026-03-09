#!/usr/bin/env node

/**
 * Laws Agent MCP Server v3
 * Uses the Cloudflare AI Search REST API for retrieval.
 */

const fs = require('fs');
const https = require('https');
const path = require('path');

const ANTHROPIC_MODEL = process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-20250514';

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

function readKnowledgeBaseFiles() {
    const knowledgeBasePath = path.join(__dirname, '..', 'knowledge-base');
    let knowledgeContent = '';

    try {
        if (!fs.existsSync(knowledgeBasePath)) {
            return '';
        }

        const files = fs.readdirSync(knowledgeBasePath);
        const textFiles = files.filter((file) =>
            ['.txt', '.md', '.mdx', '.json'].some((ext) => file.endsWith(ext))
        );

        if (textFiles.length === 0) {
            return '';
        }

        knowledgeContent = '\n\n=== Knowledge Base Reference Files ===\n\n';

        textFiles.forEach((file, index) => {
            try {
                const filePath = path.join(knowledgeBasePath, file);
                const content = fs.readFileSync(filePath, 'utf8');
                knowledgeContent += `[Knowledge Base File ${index + 1}] ${file}:\n${content}\n\n`;
            } catch (error) {
                console.error(`Error reading ${file}: ${error.message}`);
            }
        });
    } catch (error) {
        console.error(`Knowledge base error: ${error.message}`);
    }

    return knowledgeContent;
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
    const token = requireEnv('CLOUDFLARE_API_TOKEN');
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
    return normalizeCloudflareSearchRows(result.data).slice(0, limit);
}

function buildContextFromResults(results) {
    if (!results || results.length === 0) {
        return 'No relevant rulebook content found.';
    }

    let context = 'Context from VTM/MET rulebooks:\n\n';

    results.forEach((result, index) => {
        const excerpt = extractExcerpt(result.page_text, 800);
        context += `[Source ${index + 1}] ${result.book_title} (Page ${result.page_number}, Category: ${result.category}, System: ${result.system_type}):\n${excerpt}\n\n`;
    });

    const knowledgeContent = readKnowledgeBaseFiles();

    if (knowledgeContent) {
        context += knowledgeContent;
    }

    return context;
}

function callAnthropicAPI(question, context) {
    const anthropicApiKey = requireEnv('ANTHROPIC_API_KEY');
    const systemPrompt = `You are a helpful assistant answering questions about Vampire: The Masquerade and Mind's Eye Theatre rules. Baseline edition is Laws of the Night Revised. Do not reference V5 or the Second Inquisition. Answer based on the provided context from official rulebooks. Always cite your sources by including [Book Name, Page X] citations in your response.

IMPORTANT: When asked about "Camarilla traditions" or "the Traditions," you should always mention the Six Traditions that govern vampire society:
1. The Masquerade - Conceal vampiric nature from mortals at all times
2. Domain - A Prince (or rightful lord) holds the city; respect granted rights
3. Progeny - Do not Embrace without the Prince's explicit leave
4. Accounting - A sire is responsible for a childe until formal Release
5. Hospitality - Present yourself to the Prince upon entering a city
6. Destruction - Only the Prince (or empowered elder) may grant Final Death

These are fundamental laws of the Camarilla (LotN Revised), even if specific details are not found in the search results.`;

    const payload = JSON.stringify({
        model: ANTHROPIC_MODEL,
        max_tokens: 2000,
        system: systemPrompt,
        messages: [
            {
                role: 'user',
                content: `${question}\n\n${context}`,
            },
        ],
    });

    const options = {
        hostname: 'api.anthropic.com',
        port: 443,
        path: '/v1/messages',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'x-api-key': anthropicApiKey,
            'anthropic-version': '2023-06-01',
            'Content-Length': Buffer.byteLength(payload),
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

                    if (res.statusCode !== 200) {
                        throw new Error(parsed.error?.message || 'Anthropic request failed');
                    }

                    const answer = parsed.content?.[0]?.text;

                    if (!answer) {
                        throw new Error('Unexpected Anthropic response format');
                    }

                    resolve({
                        answer,
                        model: parsed.model,
                    });
                } catch (error) {
                    reject(new Error(`Anthropic request failed: ${error.message}`));
                }
            });
        });

        req.on('error', (error) => {
            reject(new Error(`Anthropic request failed: ${error.message}`));
        });

        req.setTimeout(60000, () => {
            req.destroy();
            reject(new Error('Anthropic request timed out'));
        });

        req.write(payload);
        req.end();
    });
}

async function askLawsAgent(question, category, system) {
    try {
        const searchResults = await searchRulebooksCloudflare(question, category, system, 5);
        const isTraditionQuestion = /\b(traditions?|masquerade|domain|progeny|accounting|hospitality|destruction)\b/i.test(
            question
        );

        if (searchResults.length === 0 && !isTraditionQuestion) {
            return {
                success: true,
                question,
                answer: "I couldn't find any relevant information in the rulebooks to answer that question. Please try rephrasing or being more specific.",
                sources: [],
                ai_model: ANTHROPIC_MODEL,
                searched: true,
                results_found: 0,
            };
        }

        const context =
            searchResults.length > 0
                ? buildContextFromResults(searchResults)
                : 'No specific rulebook excerpts found, but answer based on fundamental knowledge of the Six Traditions.';

        const aiResponse = await callAnthropicAPI(question, context);
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
            answer: aiResponse.answer,
            sources,
            ai_model: aiResponse.model,
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
    const server = new LawsAgentMCPServer();
    server.run().catch((error) => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}

module.exports = LawsAgentMCPServer;
