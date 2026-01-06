/**
 * Admin Boon Agent Viewer - JavaScript
 * Extracted from admin/boon_agent_viewer.php
 */

let boonGraphNetwork = null;

/**
 * Handle graph resize when modal fullscreen is toggled
 */
function handleBoonGraphResize(modalEl, isFullscreen) {
    const containerEl = document.getElementById('boonGraphContainer');
    if (!containerEl || !boonGraphNetwork) return;
    
    setTimeout(() => {
        const newHeight = isFullscreen 
            ? (containerEl.offsetHeight || window.innerHeight - 200)
            : 600;
        containerEl.style.height = newHeight + 'px';
        boonGraphNetwork.setSize('100%', newHeight + 'px');
    }, 100);
}

function runAction(action, actionName) {
    showActionModal(action, actionName);
}

function generateReport(action, reportName) {
    showActionModal(action, reportName);
}

function showActionModal(action, actionName) {
    // Show loading state
    const modalEl = document.getElementById('reportResultModal');
    const modalTitle = document.getElementById('reportResultModalLabel');
    const modalContent = document.getElementById('reportResultContent');
    
    if (modalTitle) {
        modalTitle.textContent = 'Running ' + actionName + '...';
    }
    if (modalContent) {
        modalContent.innerHTML = '<div class="text-center text-light"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Please wait...</p></div>';
    }
    
    // Show modal
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true,
            keyboard: true
        });
        modalInstance.show();
    }
    
    // Make AJAX request
    fetch('?action=' + encodeURIComponent(action), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (modalTitle) {
            modalTitle.textContent = actionName + ' Result';
        }
        if (modalContent) {
            modalContent.innerHTML = formatJsonAsHtml(data);
        }
    })
    .catch(error => {
        if (modalTitle) {
            modalTitle.textContent = 'Error';
        }
        if (modalContent) {
            modalContent.innerHTML = '<div class="alert alert-danger">Error: ' + escapeHtml(error.message) + '</div>';
        }
    });
}

function formatJsonAsHtml(data, level = 0, key = '') {
    if (data === null) {
        return '<span class="opacity-75 fst-italic">null</span>';
    }
    
    if (data === undefined) {
        return '<span class="opacity-75 fst-italic">undefined</span>';
    }
    
    const type = typeof data;
    
    if (type === 'boolean') {
        return '<span class="text-warning">' + (data ? 'true' : 'false') + '</span>';
    }
    
    if (type === 'number') {
        return '<span class="text-info">' + data + '</span>';
    }
    
    if (type === 'string') {
        let displayValue = data;
        // Clean up paths by removing server path prefix
        if (key.toLowerCase().includes('path') || data.includes('/usr/home/working/public_html/')) {
            displayValue = data.replace(/^\/usr\/home\/working\/public_html\//, '');
        }
        return '<span class="text-success">"' + escapeHtml(displayValue) + '"</span>';
    }
    
    if (Array.isArray(data)) {
        if (data.length === 0) {
            return '<span class="opacity-75">[]</span>';
        }
        
        let html = '<ul class="list-unstyled mb-0" style="margin-left: ' + (level * 20) + 'px;">';
        data.forEach((item, index) => {
            html += '<li class="mb-2">';
            html += '<span class="opacity-75">[' + index + ']:</span> ';
            html += formatJsonAsHtml(item, level + 1, '');
            html += '</li>';
        });
        html += '</ul>';
        return html;
    }
    
    if (type === 'object') {
        const keys = Object.keys(data);
        if (keys.length === 0) {
            return '<span class="opacity-75">{}</span>';
        }
        
        let html = '<div class="mb-2" style="margin-left: ' + (level * 20) + 'px;">';
        keys.forEach(keyName => {
            const value = data[keyName];
            html += '<div class="mb-2 pb-2 border-bottom border-secondary" style="border-width: 1px !important;">';
            html += '<strong class="text-danger">' + escapeHtml(keyName) + ':</strong> ';
            
            if (typeof value === 'object' && value !== null) {
                html += '<div class="mt-1">' + formatJsonAsHtml(value, level + 1, keyName) + '</div>';
            } else {
                html += formatJsonAsHtml(value, level + 1, keyName);
            }
            html += '</div>';
        });
        html += '</div>';
        return html;
    }
    
    return escapeHtml(String(data));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showBoonRelationshipsGraph() {
    const modalEl = document.getElementById('boonRelationshipsModal');
    const loadingEl = document.getElementById('boonGraphLoading');
    const errorEl = document.getElementById('boonGraphError');
    const containerEl = document.getElementById('boonGraphContainer');
    const legendEl = document.getElementById('boonGraphLegend');
    
    if (!modalEl || !loadingEl || !errorEl || !containerEl || !legendEl) {
        console.error('Required elements not found for boon graph');
        return;
    }
    
    // Reset UI
    loadingEl.classList.remove('d-none');
    errorEl.classList.add('d-none');
    containerEl.style.display = 'none';
    legendEl.innerHTML = '';
    
    // Destroy existing network if it exists
    if (boonGraphNetwork) {
        boonGraphNetwork.destroy();
        boonGraphNetwork = null;
    }
    
    // Show modal
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            focus: true,
            keyboard: true
        });
        modalInstance.show();
    }
    
    // Fetch relationship data
    fetch('?action=get_relationships', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        loadingEl.classList.add('d-none');
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load relationships');
        }
        
        if (!data.nodes || data.nodes.length === 0) {
            containerEl.style.display = 'block';
            containerEl.innerHTML = '<div class="text-center text-light py-5"><p>No active boon relationships found.</p></div>';
            return;
        }
        
        // Prepare nodes with styling
        const nodes = new vis.DataSet(data.nodes.map(node => ({
            id: node.id,
            label: node.label || node.name,
            title: node.name,
            color: {
                background: '#8B0000',
                border: '#f5e6d3',
                highlight: {
                    background: '#B22222',
                    border: '#f5e6d3'
                }
            },
            font: {
                color: '#f5e6d3',
                size: 14
            },
            shape: 'box',
            borderWidth: 2
        })));
        
        // Prepare edges with styling
        const edges = new vis.DataSet(data.edges.map(edge => ({
            from: edge.from,
            to: edge.to,
            label: edge.label,
            title: edge.title,
            color: edge.color,
            width: edge.width,
            arrows: {
                to: {
                    enabled: true,
                    scaleFactor: 1.2
                }
            },
            font: {
                color: '#f5e6d3',
                size: 12,
                align: 'middle'
            }
        })));
        
        // Create network
        const container = containerEl;
        const graphData = {
            nodes: nodes,
            edges: edges
        };
        
        const options = {
            nodes: {
                shape: 'box',
                font: {
                    color: '#f5e6d3',
                    size: 14
                }
            },
            edges: {
                arrows: {
                    to: {
                        enabled: true
                    }
                },
                smooth: {
                    type: 'curvedCW',
                    roundness: 0.2
                }
            },
            physics: {
                enabled: true,
                barnesHut: {
                    gravitationalConstant: -2000,
                    centralGravity: 0.3,
                    springLength: 200,
                    springConstant: 0.04,
                    damping: 0.09
                }
            },
            interaction: {
                hover: true,
                tooltipDelay: 200,
                zoomView: true,
                dragView: true
            },
            layout: {
                improvedLayout: true
            }
        };
        
        boonGraphNetwork = new vis.Network(container, graphData, options);
        containerEl.style.display = 'block';
        
        // Add legend
        legendEl.innerHTML = `
            <div class="row g-2">
                <div class="col-12">
                    <h6 class="text-light mb-2">Boon Type Legend:</h6>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 40px; height: 2px; background: #666666;"></div>
                        <span class="text-light small">Trivial</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 40px; height: 3px; background: #8B6508;"></div>
                        <span class="text-light small">Minor</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 40px; height: 4px; background: #8B0000;"></div>
                        <span class="text-light small">Major</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 40px; height: 5px; background: #1a0f0f;"></div>
                        <span class="text-light small">Life</span>
                    </div>
                </div>
            </div>
            <p class="opacity-75 small mt-2 mb-0">Arrows indicate the direction of debt: Creditor → Debtor. Hover over nodes and edges for details.</p>
        `;
    })
    .catch(error => {
        loadingEl.classList.add('d-none');
        errorEl.classList.remove('d-none');
        errorEl.textContent = 'Error loading boon relationships: ' + escapeHtml(error.message);
    });
    
    // Clean up network when modal is closed
    modalEl.addEventListener('hidden.bs.modal', function cleanup() {
        if (boonGraphNetwork) {
            boonGraphNetwork.destroy();
            boonGraphNetwork = null;
        }
        modalEl.removeEventListener('hidden.bs.modal', cleanup);
    }, { once: true });
}

// Initialize event listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    // Action buttons - using event delegation
    document.body.addEventListener('click', function(event) {
        const button = event.target.closest('button[onclick^="runAction("]');
        if (button) {
            const onclickAttr = button.getAttribute('onclick');
            const match = onclickAttr.match(/runAction\(['"]([^'"]+)['"],\s*['"]([^'"]+)['"]\)/);
            if (match) {
                button.removeAttribute('onclick');
                runAction(match[1], match[2]);
            }
        }
        
        const reportButton = event.target.closest('button[onclick^="generateReport("]');
        if (reportButton) {
            const onclickAttr = reportButton.getAttribute('onclick');
            const match = onclickAttr.match(/generateReport\(['"]([^'"]+)['"],\s*['"]([^'"]+)['"]\)/);
            if (match) {
                reportButton.removeAttribute('onclick');
                generateReport(match[1], match[2]);
            }
        }
        
        const graphButton = event.target.closest('button[onclick="showBoonRelationshipsGraph()"]');
        if (graphButton) {
            graphButton.removeAttribute('onclick');
            showBoonRelationshipsGraph();
        }
    });
});

