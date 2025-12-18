/**
 * Admin Agents - JavaScript
 * Handles agent configuration modal
 */

async function openConfigModal(agentSlug) {
    const modalElement = document.getElementById('agentConfigModal');
    if (!modalElement) {
        console.error('agentConfigModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete');
        return;
    }
    
    // Set title and show loading state
    modalTitle.textContent = '⚙️ Agent Configuration';
    modalBody.setAttribute('aria-busy', 'true');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-light">Loading configuration...</p></div>';
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    
    // Show modal
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
    
    try {
        // Fetch config data
        const response = await fetch(`../agents/${agentSlug}/api_get_config.php`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load configuration');
        }
        
        // Build modal content
        let content = '';
        
        if (!data.config_exists) {
            content = `
                <div class="alert alert-warning mb-0">
                    <h5 class="alert-heading">Configuration File Not Found</h5>
                    <p class="mb-0">The configuration file <code>settings.json</code> does not exist yet. The agent will use default settings.</p>
                </div>
            `;
        } else if (data.config_error) {
            content = `
                <div class="alert alert-danger mb-0">
                    <h5 class="alert-heading">Configuration Error</h5>
                    <p class="mb-0">${escapeHtml(data.config_error)}</p>
                </div>
            `;
        } else {
            content = `
                <div class="mb-4">
                    <h3 class="text-light mb-3">Current Configuration</h3>
                    <pre class="bg-dark border border-danger rounded p-3 text-light" style="max-height: 400px; overflow-y: auto; font-size: 0.85em;"><code>${escapeHtml(JSON.stringify(data.config_data, null, 2))}</code></pre>
                </div>
                <div class="card bg-dark border-danger">
                    <div class="card-body">
                        <h3 class="text-light mb-3">Configuration Information</h3>
                        <ul class="text-light mb-0">
                            <li><strong>Config File Path:</strong> <code>${escapeHtml(data.config_file)}</code></li>
                            <li><strong>File Status:</strong> <span class="text-success">Exists</span></li>
                            <li><strong>Last Modified:</strong> ${escapeHtml(data.file_info.last_modified)}</li>
                            <li><strong>File Size:</strong> ${escapeHtml(data.file_info.file_size_formatted)}</li>
                        </ul>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = content;
        modalBody.setAttribute('aria-busy', 'false');
        
    } catch (error) {
        console.error('Error loading configuration:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger mb-0">
                <h5 class="alert-heading">Error Loading Configuration</h5>
                <p class="mb-0">${escapeHtml(error.message)}</p>
            </div>
        `;
        modalBody.setAttribute('aria-busy', 'false');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Event delegation for config modal buttons
    document.addEventListener('click', function(e) {
        const configBtn = e.target.closest('[data-agent-slug]');
        if (configBtn && configBtn.hasAttribute('data-agent-slug')) {
            const agentSlug = configBtn.getAttribute('data-agent-slug');
            openConfigModal(agentSlug);
        }
    });
});

