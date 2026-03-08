#!/usr/bin/env node

/**
 * Laws Agent MCP Server v3
 * Uses Cloudflare AI Search MCP integration (REST API) instead of database search.
 * Same prompts, behavior, and interfaces as v2; only the search backend is replaced.
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

function requireEnv(name) {
    const value = process.env[name];
    if (!value || String(value).trim() === '') {
        throw new Error(`Missing required environment variable: ${name}`);
    }
    return value;
}

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const ANTHROPIC_MODEL = process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-20250514';

// Cloudflare AI Search (AutoRAG) configuration
const CF_ACCOUNT_ID = process.env.CF_ACCOUNT_ID;
const CF_AUTORAG_NAME = process.env.CF_AUTORAG_NAME;
const CF_API_TOKEN = process.env.CF_API_TOKEN;

/**
 * Search rulebooks via Cloudflare AI Search REST API.
 * Replaces the MySQL FULLTEXT search used in v2.
 * @param {string} query - Natural language search query
 * @param {string|null} category - Optional filter (Core, Faction, etc.)
 * @param {string|null} system - Optional filter (MET-VTM, VTM, etc.)
 * @param {number} limit - Max results (default 5)
 * @returns {Promise<Array<{rulebook_id: string, book_title: string, category: string, system_type: string, page_number: number, page_text: string, relevance: number}>>}
 */
async function searchRulebooksCloudflare(query, category = null, system = null, limit = 5) {
    if (!CF_ACCOUNT_ID || !CF_AUTORAG_NAME || !CF_API_TOKEN) {
        throw new Error(
            'Cloudflare AI Search not configured. Set CF_ACCOUNT_ID, CF_AUTORAG_NAME, and CF_API_TOKEN.'
        );
    }

    const body = {
        query: query,
        rewrite_query: true,
        max_num_results: Math.min(limit, 50),
        ranking_options: { score_threshold: 0.1 },
    };

    const filterParts = [];
    if (category) filterParts.push({ type: 'eq', key: 'category', value: category });
    if (system) filterParts.push({ type: 'eq', key: 'system_type', value: system });
    if (filterParts.length > 0) {
        body.filters = { type: 'and', filters: filterParts };
    }

    const postData = JSON.stringify(body);
    const options = {
        hostname: 'api.cloudflare.com',
        port: 443,
        path: `/client/v4/accounts/${CF_ACCOUNT_ID}/autorag/rags/${encodeURIComponent(CF_AUTORAG_NAME)}/search`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(postData),
            Authorization: `Bearer ${CF_API_TOKEN}`,
        },
    };

    return new Promise((resolve, reject) => {
        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', (chunk) => (data += chunk));
            res.on('end', () => {
                try {
                    const parsed = JSON.parse(data);
                    if (!parsed.success || !parsed.result) {
                        reject(
                            new Error(
                                parsed.errors?.[0]?.message || parsed.result?.errors?.[0]?.message || 'Cloudflare AI Search request failed'
                            )
                        );
                        return;
                    }
                    const items = parsed.result.data || [];
                    const normalized = items.map((item) => {
                        const attrs = item.attributes || {};
                        const textParts = (item.content || [])
                            .filter((c) => c.type === 'text' && c.text)
                            .map((c) => c.text);
                        const page_text = textParts.join('\n').trim() || '(no content)';
                        return {
                            rulebook_id: item.file_id || '',
                            book_title: attrs.book_title || item.filename || item.file_id || 'Unknown',
                            category: attrs.category || 'Other',
                            system_type: attrs.system_type || 'VTM',
                            page_number: typeof attrs.page_number === 'number' ? attrs.page_number : 0,
                            page_text,
                            relevance: typeof item.score === 'number' ? item.score : 0,
                        };
                    });
                    resolve(normalized.slice(0, limit));
                } catch (e) {
                    reject(new Error(`Cloudflare search response parse failed: ${e.message}`));
                }
            });
        });
        req.on('error', (e) => reject(new Error(`Cloudflare search request failed: ${e.message}`)));
        req.setTimeout(30000, () => {
            req.destroy();
            reject(new Error('Cloudflare AI Search request timed out'));
        });
        req.write(postData);
        req.end();
    });
}

function extractExcerpt(text, maxChars = 800) {
    text = text.replace(/\s+/g, ' ').trim();
    if (text.length <= maxChars) return text;
    const excerpt = text.substring(0, maxChars);
    const lastPeriod = excerpt.lastIndexOf('.');
    if (lastPeriod !== -1 && lastPeriod > maxChars * 0.7) {
        return text.substring(0, lastPeriod + 1);
    }
    return excerpt + '...';
}

function readKnowledgeBaseFiles() {
    const knowledgeBasePath = path.join(__dirname, '..', 'knowledge-base');
    let knowledgeContent = '';
    try {
        if (!fs.existsSync(knowledgeBasePath)) return '';
        const files = fs.readdirSync(knowledgeBasePath);
        const textFiles = files.filter((file) =>
            ['.txt', '.md', '.mdx', '.json'].some((ext) => file.endsWith(ext))
        );
        if (textFiles.length === 0) return '';
        knowledgeContent = '\n\n=== Knowledge Base Reference Files ===\n\n';
        textFiles.forEach((file, index) => {
            try {
                const filePath = path.join(knowledgeBasePath, file);
                const content = fs.readFileSync(filePath, 'utf8');
                knowledgeContent += `[Knowledge Base File ${index + 1}] ${file}:\n${content}\n\n`;
            } catch (err) {
                console.error(`Error reading ${file}: ${err.message}`);
            }
        });
    } catch (err) {
        console.error(`Knowledge base error: ${err.message}`);
    }
    return knowledgeContent;
}

function buildContextFromResults(results) {
    if (!results || results.length === 0) {
        return 'No relevant rulebook content found.';
    }
    let context = 'Context from VTM/MET rulebooks:\n\n';
    results.forEach((result, i) => {
        const sourceNum = i + 1;
        const excerpt = extractExcerpt(result.page_text, 800);
        context += `[Source ${sourceNum}] ${result.book_title} (Page ${result.page_number}, Category: ${result.category}, System: ${result.system_type}):\n${excerpt}\n\n`;
    });
    const knowledgeContent = readKnowledgeBaseFiles();
    if (knowledgeContent) context += knowledgeContent;
    return context;
}

async function callAnthropicAPI(question, context) {
    if (!ANTHROPIC_API_KEY || String(ANTHROPIC_API_KEY).trim() === '') {
        return Promise.reject(
            new Error('ANTHROPIC_API_KEY is not set. Please configure your API key in the environment.')
        );
    }
    const systemPrompt = `You are a helpful assistant answering questions about Vampire: The Masquerade and Mind's Eye Theatre rules. Baseline edition is Laws of the Night Revised. Do not reference V5 or the Second Inquisition. Answer based on the provided context from official rulebooks and any appended knowledge-base files. Always cite your sources by including [Book Name, Page X] citations in your response.

IMPORTANT: When asked about "Camarilla traditions" or "the Traditions," you should always mention the Six Traditions that govern vampire society:
1. The Masquerade - Conceal vampiric nature from mortals at all times
2. Domain - A Prince (or rightful lord) holds the city; respect granted rights
3. Progeny - Do not Embrace without the Prince's explicit leave
4. Accounting - A sire is responsible for a childe until formal Release
5. Hospitality - Present yourself to the Prince upon entering a city
6. Destruction - Only the Prince (or empowered elder) may grant Final Death

These are fundamental laws of the Camarilla (LotN Revised), even if specific details aren't found in the search results.`;

    const userPrompt = `${question}\n\n${context}`;
    const data = {
        model: ANTHROPIC_MODEL,
        max_tokens: 2000,
        messages: [{ role: 'user', content: userPrompt }],
        system: systemPrompt,
    };

    const options = {
        hostname: 'api.anthropic.com',
        port: 443,
        path: '/v1/messages',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'x-api-key': ANTHROPIC_API_KEY,
            'anthropic-version': '2023-06-01',
        },
    };

    return new Promise((resolve, reject) => {
        const req = https.request(options, (res) => {
            let body = '';
            res.on('data', (chunk) => (body += chunk));
            res.on('end', () => {
                try {
                    const result = JSON.parse(body);
                    if (res.statusCode !== 200) {
                        reject(new Error(result.error?.message || 'Unknown error'));
                        return;
                    }
                    if (result.content?.[0]?.text) {
                        resolve({ answer: result.content[0].text, model: result.model });
                    } else {
                        reject(new Error('Unexpected API response format'));
                    }
                } catch (e) {
                    reject(new Error(`Failed to parse API response: ${e.message}`));
                }
            });
        });
        req.on('error', (e) => reject(new Error(`API request failed: ${e.message}`)));
        req.setTimeout(60000, () => {
            req.abort();
            reject(new Error('API request timed out'));
        });
        req.write(JSON.stringify(data));
        req.end();
    });
}

async function askLawsAgent(question, category = null, system = null) {
    try {
        let searchResults = [];
        const cloudflareConfigured =
            CF_ACCOUNT_ID && CF_AUTORAG_NAME && CF_API_TOKEN;

        if (cloudflareConfigured) {
            searchResults = await searchRulebooksCloudflare(
                question,
                category,
                system,
                5
            );
        }

        const isTraditionQuestion = /\b(traditions?|masquerade|domain|progeny|accounting|hospitality|destruction)\b/i.test(
            question
        );

        if ((!searchResults || searchResults.length === 0) && !isTraditionQuestion) {
            return {
                success: true,
                question,
                answer:
                    "I couldn't find any relevant information in the rulebooks to answer that question. Please try rephrasing or being more specific.",
                sources: [],
                ai_model: ANTHROPIC_MODEL,
                searched: true,
                results_found: 0,
            };
        }

        let context = '';
        if (searchResults && searchResults.length > 0) {
            context = buildContextFromResults(searchResults);
        } else {
            context =
                'No specific rulebook excerpts found, but answer based on fundamental knowledge of the Six Traditions.';
            const knowledgeContent = readKnowledgeBaseFiles();
            if (knowledgeContent) context += '\n\n' + knowledgeContent;
        }

        const aiResponse = await callAnthropicAPI(question, context);

        const sources =
            searchResults && searchResults.length > 0
                ? searchResults.map((result) => ({
                      book: result.book_title,
                      page: result.page_number,
                      category: result.category,
                      system: result.system_type,
                      excerpt: extractExcerpt(result.page_text, 300),
                      relevance: parseFloat(result.relevance),
                  }))
                : [];

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
                description:
                    'Ask VTM/MET rules questions to the Laws Agent (v3). Uses Cloudflare AI Search for retrieval. Same behavior as v2 with semantic search.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        question: {
                            type: 'string',
                            description:
                                'The rules question to ask (e.g., "How does Celerity work?", "What are the Camarilla traditions?")',
                        },
                        category: {
                            type: 'string',
                            enum: ['Core', 'Faction', 'Supplement', 'Blood Magic', 'Journal', 'Other'],
                            description: 'Optional: Filter by book category to narrow search',
                        },
                        system: {
                            type: 'string',
                            enum: ['MET-VTM', 'MET', 'VTM', 'MTA', 'WOD', 'Wraith'],
                            description: 'Optional: Filter by game system to narrow search',
                        },
                    },
                    required: ['question'],
                },
            },
        ];
    }

    async handleToolCall(toolName, args) {
        if (toolName === 'query_laws_agent') {
            try {
                const response = await askLawsAgent(
                    args.question,
                    args.category || null,
                    args.system || null
                );

                if (!response.success) {
                    return {
                        content: [{ type: 'text', text: `Error: ${response.error || 'Unknown error'}` }],
                        isError: true,
                    };
                }

                let formattedText = `**Question:** ${response.question}\n\n`;
                formattedText += `**Answer:**\n${response.answer}\n\n`;
                if (response.sources && response.sources.length > 0) {
                    formattedText += '**Sources:**\n';
                    response.sources.forEach((source, index) => {
                        formattedText += `${index + 1}. ${source.book} (Page ${source.page}) - ${source.category}, ${source.system}\n`;
                    });
                }
                if (response.ai_model) {
                    formattedText += `\n*Powered by ${response.ai_model}*`;
                }

                return {
                    content: [{ type: 'text', text: formattedText }],
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

        return {
            content: [{ type: 'text', text: `Unknown tool: ${toolName}` }],
            isError: true,
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
                if (!line.trim()) continue;
                try {
                    const message = JSON.parse(line);
                    const response = await this.handleMessage(message);
                    if (response) process.stdout.write(JSON.stringify(response) + '\n');
                } catch (err) {
                    console.error('Error processing message:', err);
                }
            }
        });
        process.stdin.on('end', () => process.exit(0));
    }

    async handleMessage(message) {
        if (!message.hasOwnProperty('id')) return null;

        switch (message.method) {
            case 'initialize':
                return {
                    jsonrpc: '2.0',
                    id: message.id,
                    result: {
                        protocolVersion: '2024-11-05',
                        capabilities: { tools: {} },
                        serverInfo: { name: 'laws-agent-v3', version: '3.0.0' },
                    },
                };

            case 'tools/list':
                return {
                    jsonrpc: '2.0',
                    id: message.id,
                    result: { tools: this.tools },
                };

            case 'tools/call':
                const result = await this.handleToolCall(
                    message.params.name,
                    message.params.arguments || {}
                );
                return { jsonrpc: '2.0', id: message.id, result };

            default:
                return {
                    jsonrpc: '2.0',
                    id: message.id,
                    error: { code: -32601, message: `Method not found: ${message.method}` },
                };
        }
    }
}

if (require.main === module) {
    const server = new LawsAgentMCPServer();
    server.run().catch((err) => {
        console.error('Fatal error:', err);
        process.exit(1);
    });
}

module.exports = LawsAgentMCPServer;
