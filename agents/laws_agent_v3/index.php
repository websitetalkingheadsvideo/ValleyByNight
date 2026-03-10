<?php
/**
 * Laws Agent v3 - VTM/MET Rules Q&A Interface
 *
 * Users ask legal/rules questions and receive AI-generated answers from the same
 * process as MCP ai_search: Cloudflare AI Search (AutoRAG) rag_id "laws-agent", query = question.
 * Supports "Ask for More Information" follow-up with conversation context.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $base = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    if ($base === '/' || $base === '\\' || $base === '.') {
        $base = '/';
    }
    header('Location: ' . rtrim($base, '/') . '/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/version.php';
$extra_css = ['css/admin-agents.css', 'css/laws-agent.css'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="display-5 text-light fw-bold mb-1">⚖️ Laws Agent v3</h1>
            <p class="lead fst-italic mb-0">Ask VTM/MET rules questions — powered by Laws-agent-v3 AI search</p>
        </div>
        <a href="../../admin/agents.php" class="btn btn-outline-danger btn-lg">← Back to Agents</a>
    </div>

    <div class="card bg-dark border-danger laws-agent-card">
        <div class="card-body">
            <form id="lawsAgentForm" class="mb-4">
                <label for="questionInput" class="form-label text-light">Your question</label>
                <textarea
                    id="questionInput"
                    name="question"
                    class="form-control bg-darker text-light border-danger"
                    rows="3"
                    placeholder="e.g. What are the Traditions? How does the blood bond work?"
                    required
                ></textarea>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button type="submit" id="submitBtn" class="btn btn-danger">
                        <span class="btn-text">Ask</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <button type="button" id="moreInfoBtn" class="btn btn-outline-danger d-none">
                        Ask for More Information
                    </button>
                </div>
            </form>

            <div id="resultSection" class="d-none">
                <h3 class="h5 text-light mb-2">Answer</h3>
                <div id="answerDisplay" class="laws-agent-answer bg-darker border border-danger rounded-3 p-3 mb-3"></div>
                <div id="sourcesDisplay" class="laws-agent-sources mb-3"></div>
                <div id="metaDisplay" class="laws-agent-meta text-light"></div>
            </div>

            <div id="errorDisplay" class="alert alert-danger d-none" role="alert"></div>
            <div id="loadingDisplay" class="d-none">
                <div class="spinner-border text-danger" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="ms-2 text-light">Searching rulebooks and generating answer…</span>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = ['js/laws_agent_v3.js:defer'];
require_once __DIR__ . '/../../includes/footer.php';
?>
