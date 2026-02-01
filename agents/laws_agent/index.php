<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$result = db_fetch_one(
    $conn,
    'SELECT email_verified, username FROM users WHERE id = ?',
    'i',
    [$_SESSION['user_id']]
);

if (!$result) {
    $conn->close();
    die('User not found');
}

if (!$result['email_verified']) {
    $conn->close();
    die('Email verification required. Please check your email and verify your account before using the Laws Agent.');
}

$username = $result['username'];

// Fetch available books
require_once __DIR__ . '/rag_functions.php';
$books = get_all_books($conn);

$conn->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laws Agent - VbN</title>
    <link rel="stylesheet" href="/css/global.css">
    <style>
        .laws-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }

        .chat-container {
            background: rgba(26, 15, 15, 0.8);
            border-radius: 8px;
            padding: 0;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .chat-header {
            text-align: center;
            padding: 30px 20px;
            border-bottom: 2px solid #8b0000;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px 8px 0 0;
        }

        .chat-header h1 {
            color: #8b0000;
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .chat-header p {
            color: #999;
            margin: 0;
            font-size: 14px;
        }

        .status-bar {
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.5);
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #666;
        }

        .status-dot.active {
            background: #0f0;
            box-shadow: 0 0 10px #0f0;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            min-height: 400px;
            max-height: 600px;
        }

        .messages-container::-webkit-scrollbar {
            width: 8px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: #8b0000;
            border-radius: 4px;
        }

        .message {
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            text-align: right;
        }

        .message-content {
            display: inline-block;
            max-width: 80%;
            padding: 15px 20px;
            border-radius: 12px;
            text-align: left;
            line-height: 1.6;
        }

        .message.user .message-content {
            background: rgba(139, 0, 0, 0.3);
            color: #fff;
            border: 1px solid #8b0000;
        }

        .message.assistant .message-content {
            background: rgba(0, 0, 0, 0.5);
            color: #ccc;
            border: 1px solid #444;
        }

        .message-content strong {
            color: #8b0000;
        }

        .sources {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #444;
            font-size: 13px;
            line-height: 1.8;
        }

        .sources-title {
            color: #8b0000;
            font-weight: bold;
        }

        .source-link {
            color: #8b0000;
            font-weight: bold;
            cursor: pointer;
            text-decoration: underline;
            text-decoration-color: rgba(139, 0, 0, 0.5);
            transition: all 0.3s;
        }

        .source-link:hover {
            color: #ff0000;
            text-decoration-color: #ff0000;
            background: rgba(139, 0, 0, 0.15);
            padding: 2px 4px;
            border-radius: 3px;
        }

        .model-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }

        .model-badge.lm-studio {
            background: rgba(0, 150, 0, 0.3);
            color: #0f0;
            border: 1px solid #0f0;
        }

        .model-badge.claude {
            background: rgba(139, 0, 139, 0.3);
            color: #ff69b4;
            border: 1px solid #ff69b4;
        }

        .input-container {
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 0 0 8px 8px;
            border-top: 1px solid #444;
        }

        .input-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .question-input {
            flex: 1;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #8b0000;
            color: #fff;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
        }

        .question-input:focus {
            outline: none;
            border-color: #a00000;
            box-shadow: 0 0 5px rgba(139, 0, 0, 0.5);
        }

        .send-button {
            padding: 15px 30px;
            background: #8b0000;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .send-button:hover:not(:disabled) {
            background: #a00000;
        }

        .send-button:disabled {
            background: #555;
            cursor: not-allowed;
        }

        .filters-row {
            display: flex;
            gap: 10px;
            font-size: 14px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #666;
            color: #ccc;
            border-radius: 4px;
            cursor: pointer;
            flex: 1;
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #8b0000;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-button {
            padding: 8px 16px;
            background: rgba(139, 0, 0, 0.3);
            color: #ccc;
            border: 1px solid #8b0000;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .action-button:hover {
            background: rgba(139, 0, 0, 0.5);
            color: #fff;
        }

        .suggestions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .suggestion-chip {
            padding: 8px 16px;
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8b0000;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            color: #ccc;
            transition: all 0.3s;
        }

        .suggestion-chip:hover {
            background: rgba(139, 0, 0, 0.4);
            color: #fff;
            transform: translateY(-2px);
        }

        .thinking {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #999;
            font-style: italic;
        }

        .thinking::after {
            content: '...';
            animation: ellipsis 1.5s infinite;
        }

        @keyframes ellipsis {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .welcome-message h2 {
            color: #8b0000;
            margin-bottom: 20px;
        }

        .error-message {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8b0000;
            padding: 15px;
            border-radius: 8px;
            color: #ff6b6b;
            margin: 10px 0;
        }

        .source-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .source-modal.active {
            display: flex;
        }

        .source-modal-content {
            background: rgba(26, 15, 15, 0.95);
            border: 2px solid #8b0000;
            border-radius: 8px;
            padding: 30px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            color: #ccc;
        }

        .modal-close {
            float: right;
            font-size: 28px;
            color: #8b0000;
            cursor: pointer;
            line-height: 20px;
        }

        .modal-close:hover {
            color: #ff0000;
        }
    </style>
</head>
<body>

<div class="laws-container">
    <div class="chat-container">
        <div class="chat-header">
            <h1>🧛 Laws Agent</h1>
            <p>Ask me anything about VTM/MET rules, disciplines, clans, mechanics, or lore</p>
            <p style="font-size: 12px; margin-top: 10px; color: #666;">
                Powered by RAG with LM Studio + Claude AI Fallback
            </p>
        </div>

        <div class="status-bar">
            <div class="status-indicator">
                <span class="status-dot" id="statusDot"></span>
                <span id="statusText">Checking systems...</span>
            </div>
            <div>
                <span style="color: #666;">Session:</span>
                <span style="color: #8b0000;" id="sessionId">New</span>
            </div>
        </div>

        <div class="messages-container" id="messages">
            <div class="welcome-message" id="welcome">
                <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <p>I'm your personal Laws Agent with access to official VTM/MET rulebooks.</p>
                <p style="margin-top: 20px; color: #666;">Current capabilities:</p>
                <ul style="text-align: left; max-width: 500px; margin: 20px auto; color: #999;">
                    <li>✓ Hybrid semantic + keyword search</li>
                    <li>✓ Context-aware answers from <?php echo count($books); ?> book(s)</li>
                    <li>✓ LM Studio (local) with Claude fallback</li>
                    <li>✓ Conversation memory within sessions</li>
                    <li>✓ Source citations with page numbers</li>
                </ul>
                
                <p style="margin-top: 30px; color: #666;">Try asking:</p>
                
                <div class="suggestions" style="justify-content: center; margin-top: 20px;">
                    <div class="suggestion-chip" onclick="askQuestion('How does Celerity work in MET?')">How does Celerity work?</div>
                    <div class="suggestion-chip" onclick="askQuestion('What are the Camarilla Traditions?')">Camarilla Traditions</div>
                    <div class="suggestion-chip" onclick="askQuestion('Explain combat resolution')">Combat Resolution</div>
                    <div class="suggestion-chip" onclick="askQuestion('What disciplines do Toreador have?')">Toreador Disciplines</div>
                    <div class="suggestion-chip" onclick="askQuestion('How does the Blood Bond work?')">Blood Bonds</div>
                </div>
            </div>
        </div>

        <div class="input-container">
            <div class="filters-row">
                <select id="bookFilter" class="filter-select">
                    <option value="all">All Books</option>
                    <?php foreach ($books as $book): ?>
                    <option value="<?php echo htmlspecialchars($book['book_code']); ?>">
                        <?php echo htmlspecialchars($book['book_name']); ?>
                        (<?php echo $book['total_pages']; ?> pages)
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="action-buttons">
                    <button class="action-button" onclick="resetSession()" title="Clear conversation history">
                        🔄 New Session
                    </button>
                </div>
            </div>
            
            <div class="input-row">
                <input 
                    type="text" 
                    id="questionInput" 
                    class="question-input" 
                    placeholder="Ask about rules, disciplines, clans, lore..."
                    onkeypress="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); askQuestion(); }"
                >
                <button class="send-button" id="sendButton" onclick="askQuestion()">
                    Ask
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Source Modal -->
<div class="source-modal" id="sourceModal" onclick="closeModal(event)">
    <div class="source-modal-content" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<script>
let conversationHistory = [];
let sessionId = 'New';

// Check system status on load
checkStatus();

function checkStatus() {
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');
    
    fetch('api.php?action=get_books')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDot.classList.add('active');
                statusText.textContent = 'System ready • ' + data.books.length + ' book(s) loaded';
            } else {
                statusText.textContent = 'System error';
            }
        })
        .catch(error => {
            statusText.textContent = 'Connection error';
        });
}

function askQuestion(predefinedQuestion = null) {
    const input = document.getElementById('questionInput');
    const question = predefinedQuestion || input.value.trim();
    
    if (!question) return;
    
    const welcome = document.getElementById('welcome');
    if (welcome) welcome.style.display = 'none';
    
    if (!predefinedQuestion) {
        input.value = '';
    }
    
    addMessage('user', escapeHtml(question));
    
    const thinkingId = addMessage('assistant', '<div class="thinking">Searching rulebooks and consulting AI</div>', true);
    
    setInputEnabled(false);
    
    const bookFilter = document.getElementById('bookFilter').value;
    
    let url = `api.php?action=ask&question=${encodeURIComponent(question)}`;
    if (bookFilter && bookFilter !== 'all') {
        url += `&book=${encodeURIComponent(bookFilter)}`;
    }
    
    const requestStart = Date.now();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            removeMessage(thinkingId);
            
            if (data.success) {
                let answer = data.answer || 'I found some relevant information in the rulebooks.';
                answer = formatAnswer(answer);
                
                // Add model badge
                if (data.model) {
                    const modelClass = data.model.includes('studio') ? 'lm-studio' : 'claude';
                    const modelName = data.model.includes('studio') ? 'LM Studio' : 'Claude';
                    answer += `<span class="model-badge ${modelClass}">${modelName}</span>`;
                }
                
                // Add sources
                if (data.sources && data.sources.length > 0) {
                    answer += '<div class="sources">';
                    answer += '<span class="sources-title">📚 Sources: </span>';
                    const sourceLinks = data.sources.map((source, index) => {
                        const sourceData = JSON.stringify(source).replace(/"/g, '&quot;');
                        return `<span class="source-link" onclick='viewSource(${sourceData})'>${escapeHtml(source.book)} (p.${source.page})</span>`;
                    });
                    answer += sourceLinks.join(', ');
                    answer += '</div>';
                }
                
                addMessage('assistant', answer);
                
                // Update session ID
                if (sessionId === 'New') {
                    sessionId = 'Active';
                    document.getElementById('sessionId').textContent = sessionId;
                }
                
                setTimeout(() => {
                    const messagesContainer = document.getElementById('messages');
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 100);
                
                conversationHistory.push({ 
                    question, 
                    answer: data.answer,
                    sources: data.sources 
                });
            } else {
                addMessage('assistant', `<div class="error-message">❌ Error: ${escapeHtml(data.error || 'Unknown error occurred')}</div>`);
            }
        })
        .catch(error => {
            removeMessage(thinkingId);
            addMessage('assistant', `<div class="error-message">⚠️ Connection error: ${escapeHtml(error.message)}</div>`);
        })
        .finally(() => {
            setInputEnabled(true);
            input.focus();
            
            setTimeout(() => {
                const messagesContainer = document.getElementById('messages');
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 50);
        });
}

function addMessage(type, content, isTemporary = false) {
    const messagesContainer = document.getElementById('messages');
    const messageDiv = document.createElement('div');
    const messageId = 'msg-' + Date.now() + '-' + Math.random();
    
    messageDiv.id = messageId;
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `<div class="message-content">${content}</div>`;
    
    messagesContainer.appendChild(messageDiv);
    
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, 10);
    
    return messageId;
}

function removeMessage(messageId) {
    const message = document.getElementById(messageId);
    if (message) {
        message.remove();
    }
}

function setInputEnabled(enabled) {
    const input = document.getElementById('questionInput');
    const button = document.getElementById('sendButton');
    
    input.disabled = !enabled;
    button.disabled = !enabled;
    
    button.textContent = enabled ? 'Ask' : 'Thinking...';
}

function viewSource(source) {
    const modal = document.getElementById('sourceModal');
    const content = document.getElementById('modalContent');
    
    const metadata = JSON.parse(source.metadata || '{}');
    
    content.innerHTML = `
        <h2 style="color: #8b0000; margin-top: 0;">${escapeHtml(source.book)}</h2>
        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #444;">
            <strong>Page:</strong> ${source.page}<br>
            <strong>Type:</strong> ${escapeHtml(source.content_type)}<br>
            <strong>Category:</strong> ${escapeHtml(source.category)}<br>
            <strong>System:</strong> ${escapeHtml(source.system)}<br>
            <strong>Relevance:</strong> ${(source.score * 100).toFixed(1)}%
        </div>
        <div style="line-height: 1.8;">
            <strong style="color: #8b0000;">Excerpt:</strong><br>
            ${escapeHtml(source.excerpt)}
        </div>
    `;
    
    modal.classList.add('active');
}

function closeModal(event) {
    if (!event || event.target.id === 'sourceModal') {
        document.getElementById('sourceModal').classList.remove('active');
    }
}

function resetSession() {
    if (!confirm('Clear conversation history and start fresh?')) {
        return;
    }
    
    fetch('api.php?action=reset_session')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                conversationHistory = [];
                sessionId = 'New';
                document.getElementById('sessionId').textContent = sessionId;
                document.getElementById('messages').innerHTML = '';
                document.getElementById('welcome').style.display = 'block';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatAnswer(text) {
    // If text is already HTML, return as-is
    if (text.trim().startsWith('<')) {
        return text;
    }
    
    // Convert markdown-style formatting
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
    text = text.replace(/\n\n/g, '</p><p>');
    text = text.replace(/\n/g, '<br>');
    
    return '<p>' + text + '</p>';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
