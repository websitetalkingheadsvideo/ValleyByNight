<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$extra_css = ['css/laws-agent.css'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="laws-container">
    <div class="chat-container">
        <div class="chat-header">
            <h1>🧛 Laws Agent</h1>
            <p>Ask me anything about VTM/MET rules, disciplines, clans, mechanics, or lore</p>
            <p class="chat-header-sub">
                Powered by RAG with LM Studio + Claude AI Fallback
            </p>
        </div>

        <div class="status-bar">
            <div class="status-indicator">
                <span class="status-dot" id="statusDot"></span>
                <span id="statusText">Checking systems...</span>
            </div>
            <div>
                <a href="import_books.php" class="status-bar-import-link">Import books</a>
                <span class="status-bar-label">Session:</span>
                <span class="status-bar-session" id="sessionId">New</span>
            </div>
        </div>

        <div class="messages-container" id="messages">
            <div class="welcome-message" id="welcome">
                <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <p>I'm your personal Laws Agent with access to official VTM/MET rulebooks.</p>
                <p class="welcome-capabilities-title">Current capabilities:</p>
                <ul class="welcome-capabilities-list">
                    <li>✓ Hybrid semantic + keyword search</li>
                    <li>✓ Context-aware answers from <?php echo count($books); ?> book(s)</li>
                    <li>✓ LM Studio (local) with Claude fallback</li>
                    <li>✓ Conversation memory within sessions</li>
                    <li>✓ Source citations with page numbers</li>
                </ul>
                
                <p class="welcome-try-title">Try asking:</p>
                
                <div class="suggestions welcome-suggestions">
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
let askInProgress = false;

const ASK_TIMEOUT_MS = 320000;  // 320s; LM Studio can take 2–3 min, don't abort before we read response

// Check system status on load
checkStatus();

function checkStatus() {
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');
    
    fetch('api.php?action=get_books')
    .then(response => response.text())
.then(text => {
    console.log('Raw response:', text);
    return JSON.parse(text);
})
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
    if (askInProgress) return;
    const input = document.getElementById('questionInput');
    const question = predefinedQuestion || input.value.trim();
    
    if (!question) return;

    askInProgress = true;
    const welcome = document.getElementById('welcome');
    if (welcome) welcome.style.display = 'none';
    
    if (!predefinedQuestion) {
        input.value = '';
    }
    
    addMessage('user', escapeHtml(question));
    
    const thinkingId = addMessage('assistant', '<div class="thinking">Consulting rulebooks and AI — this can take up to 3 minutes.</div>', true);
    
    setInputEnabled(false);
    
    const bookFilter = document.getElementById('bookFilter').value;
    
    let url = `api.php?action=ask&question=${encodeURIComponent(question)}`;
    if (bookFilter && bookFilter !== 'all') {
        url += `&book=${encodeURIComponent(bookFilter)}`;
    }
    
    const requestStart = Date.now();
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), ASK_TIMEOUT_MS);

    fetch(url, { signal: controller.signal })
        .then(response => response.text().then(text => ({ response, text })))
        .then(({ response, text }) => {
            clearTimeout(timeoutId);
            if (!text || !text.trim()) {
                if (response.status === 500) {
                    throw new Error('Server error (500). Check server logs.');
                }
                if (response.status === 504) {
                    throw new Error('Gateway timeout (504). The server took too long to respond.');
                }
                throw new Error('Server returned an empty response (status ' + (response.status || '') + '). Check server logs.');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Server did not return valid JSON (status ' + response.status + '). Response may be an error page.');
            }
        })
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
                
                // Add sources (store by ref so onclick avoids broken quotes in excerpt/book)
                if (data.sources && data.sources.length > 0) {
                    window._sourcesStore = window._sourcesStore || [];
                    const storeIndex = window._sourcesStore.length;
                    window._sourcesStore.push(data.sources);
                    answer += '<div class="sources">';
                    answer += '<span class="sources-title">📚 Sources: </span>';
                    const sourceLinks = data.sources.map((source, index) => {
                        return `<span class="source-link" data-store="${storeIndex}" data-index="${index}" onclick="viewSourceByRef(this)">${escapeHtml(source.book)} (p.${source.page})</span>`;
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
            clearTimeout(timeoutId);
            removeMessage(thinkingId);
            const msg = error.name === 'AbortError'
                ? 'Request timed out after ' + (ASK_TIMEOUT_MS / 1000) + ' seconds.'
                : error.message;
            addMessage('assistant', `<div class="error-message">⚠️ ${escapeHtml(msg)}</div>`);
        })
        .finally(() => {
            askInProgress = false;
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

function viewSourceByRef(el) {
    const storeIndex = parseInt(el.getAttribute('data-store'), 10);
    const index = parseInt(el.getAttribute('data-index'), 10);
    if (isNaN(storeIndex) || isNaN(index) || !window._sourcesStore || !window._sourcesStore[storeIndex]) return;
    const source = window._sourcesStore[storeIndex][index];
    if (!source) return;
    viewSource(source);
}

function viewSource(source) {
    const modal = document.getElementById('sourceModal');
    const content = document.getElementById('modalContent');
    
    const typeLine = (source.content_type && source.content_type.trim()) ? `<strong>Type:</strong> ${escapeHtml(source.content_type)}<br>` : '';
    const categoryLine = (source.category && source.category.trim()) ? `<strong>Category:</strong> ${escapeHtml(source.category)}<br>` : '';
    const systemLine = (source.system && source.system.trim()) ? `<strong>System:</strong> ${escapeHtml(source.system)}<br>` : '';
    content.innerHTML = `
        <h2 style="color: #8b0000; margin-top: 0;">${escapeHtml(source.book)}</h2>
        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #444;">
            <strong>Page:</strong> ${escapeHtml(String(source.page))}<br>
            ${typeLine}${categoryLine}${systemLine}
            <strong>Relevance:</strong> ${(source.score != null ? (source.score * 100).toFixed(1) : '—')}%
        </div>
        <div style="line-height: 1.8;">
            <strong style="color: #8b0000;">Excerpt:</strong><br>
            ${escapeHtml(source.excerpt || '')}
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
        .then(response => response.text())
        .then(text => {
            if (!text || !text.trim()) return { success: false };
            try { return JSON.parse(text); } catch (e) { return { success: false }; }
        })
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
