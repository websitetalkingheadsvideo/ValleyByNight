<?php
/**
 * Position View Modal Include
 * Provides reusable modal HTML and JavaScript for viewing/editing position details
 * 
 * @param string $apiEndpoint - Path to the position API endpoint (default: '/admin/view_position_api.php')
 * @param string $modalId - ID for the modal element (default: 'viewPositionModal')
 */

// Set defaults if not provided
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : '/admin/view_position_api.php';
$modalId = isset($modalId) ? $modalId : 'viewPositionModal';

// Calculate path prefix for CSS (same logic as header.php)
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);
if ($script_dir === '/') {
    $path_prefix = '';
} else {
    $path_segments = trim($script_dir, '/');
    $segment_count = $path_segments === '' ? 0 : substr_count($path_segments, '/') + 1;
    $path_prefix = str_repeat('../', $segment_count);
}
?>

<!-- Position View Modal -->
<div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1" aria-labelledby="viewPositionName" aria-hidden="true" data-fullscreen="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content vbn-modal-content character-view-modal">
            <div class="modal-header vbn-modal-header align-items-start flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title vbn-modal-title d-flex align-items-center gap-2" id="viewPositionName">
                        <span aria-hidden="true">👑</span>
                        <span>Position Details</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body vbn-modal-body">
                <form id="positionForm">
                    <input type="hidden" id="positionId" name="position_id">
                    
                    <!-- Position Name -->
                    <div class="mb-3">
                        <label for="positionName" class="form-label">Position Name</label>
                        <input type="text" class="form-control bg-dark text-light border-danger" id="positionName" name="name" readonly>
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-3">
                        <label for="positionCategory" class="form-label">Category</label>
                        <input type="text" class="form-control bg-dark text-light border-danger" id="positionCategory" name="category" readonly>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="positionDescription" class="form-label">Description</label>
                        <textarea class="form-control bg-dark text-light border-danger" id="positionDescription" name="description" rows="4" readonly></textarea>
                    </div>
                    
                    <!-- Importance Rank -->
                    <div class="mb-3">
                        <label for="positionImportanceRank" class="form-label">Importance Rank</label>
                        <input type="number" class="form-control bg-dark text-light border-danger" id="positionImportanceRank" name="importance_rank" readonly>
                    </div>
                    
                    <!-- Current Holder -->
                    <div class="mb-3">
                        <label class="form-label">Current Holder</label>
                        <div id="currentHolderInfo" class="p-3 bg-dark border border-danger rounded">
                            <p class="text-muted mb-0">Loading...</p>
                        </div>
                        <!-- Dropdown for edit mode (hidden in view mode) -->
                        <div id="currentHolderDropdown" class="d-none">
                            <select id="currentHolderSelect" name="current_holder" class="form-select bg-dark text-light border-danger">
                                <option value="">-- Vacant --</option>
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="isActingCheck" name="is_acting" value="1">
                                <label class="form-check-label text-light" for="isActingCheck">
                                    Acting (temporary assignment)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assignment History (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Assignment History</label>
                        <div id="positionHistory" class="p-3 bg-dark border border-danger rounded">
                            <p class="text-muted mb-0">Loading...</p>
                        </div>
                    </div>
                    
                    <!-- Form Actions (hidden in view mode) -->
                    <div id="positionFormActions" class="d-none">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer vbn-modal-footer" id="positionModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // Configuration
    const API_ENDPOINT = <?php echo json_encode($apiEndpoint); ?>;
    const MODAL_ID = <?php echo json_encode($modalId); ?>;
    const UPDATE_ENDPOINT = '/admin/update_position_api.php';
    const ALL_CHARACTERS = <?php echo json_encode($modal_characters ?? []); ?>;
    
    // State
    let currentPositionData = null;
    let currentMode = 'view'; // 'view' or 'edit'
    let positionModalInstance = null;
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePositionView);
    } else {
        initializePositionView();
    }
    
    function initializePositionView() {
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Position view modal not found. Modal ID: ' + MODAL_ID);
            return;
        }
        
        // Initialize form submission
        const form = document.getElementById('positionForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
        
        // Initialize modal instance
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            positionModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: true,
                focus: true
            });
            
            // Reset on close
            modalEl.addEventListener('hidden.bs.modal', () => {
                currentPositionData = null;
                currentMode = 'view';
                resetForm();
            });
        }
    }
    
    // Global function to open position view
    window.viewPosition = function(positionId, mode = 'view') {
        if (!positionId) return;
        
        const modalEl = document.getElementById(MODAL_ID);
        if (!modalEl) {
            console.error('Position view modal not found. Modal ID: ' + MODAL_ID);
            return;
        }
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error('Bootstrap modal runtime not loaded; cannot open position view.');
            return;
        }
        
        currentMode = mode;
        
        if (!positionModalInstance) {
            positionModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: true,
                focus: true
            });
        }
        
        // Reset form
        resetForm();
        
        // Set loading state
        setLoadingState();
        
        // Show modal
        positionModalInstance.show();
        
        // Load position data
        const requestUrl = API_ENDPOINT + '?id=' + encodeURIComponent(positionId) + '&_t=' + Date.now();
        
        fetch(requestUrl)
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    currentPositionData = data;
                    renderPositionView(mode);
                } else {
                    showError(data && data.message ? data.message : 'Unknown error.');
                }
            })
            .catch(error => {
                console.error('view_position_api error', error);
                showError('Error loading position.');
            });
    };
    
    function setLoadingState() {
        const nameEl = document.getElementById('viewPositionName');
        if (nameEl) nameEl.textContent = 'Loading...';
        
        const formInputs = document.querySelectorAll('#positionForm input, #positionForm textarea');
        formInputs.forEach(input => {
            if (input.id !== 'positionId') {
                input.value = '';
            }
        });
        
        const holderInfo = document.getElementById('currentHolderInfo');
        if (holderInfo) holderInfo.innerHTML = '<p class="text-muted mb-0">Loading...</p>';
        
        const history = document.getElementById('positionHistory');
        if (history) history.innerHTML = '<p class="text-muted mb-0">Loading...</p>';
    }
    
    function renderPositionView(mode) {
        if (!currentPositionData || !currentPositionData.position) {
            showError('Invalid position data.');
            return;
        }
        
        const pos = currentPositionData.position;
        currentMode = mode;
        
        // Update title
        const nameEl = document.getElementById('viewPositionName');
        if (nameEl) {
            nameEl.textContent = pos.name || 'Position Details';
        }
        
        // Populate form fields
        const positionIdEl = document.getElementById('positionId');
        if (positionIdEl) positionIdEl.value = pos.position_id || '';
        
        const nameInput = document.getElementById('positionName');
        if (nameInput) {
            nameInput.value = pos.name || '';
            nameInput.readOnly = (mode === 'view');
        }
        
        const categoryInput = document.getElementById('positionCategory');
        if (categoryInput) {
            categoryInput.value = pos.category || '';
            categoryInput.readOnly = (mode === 'view');
        }
        
        const descInput = document.getElementById('positionDescription');
        if (descInput) {
            descInput.value = pos.description || '';
            descInput.readOnly = (mode === 'view');
        }
        
        const rankInput = document.getElementById('positionImportanceRank');
        if (rankInput) {
            rankInput.value = pos.importance_rank || '';
            rankInput.readOnly = (mode === 'view');
        }
        
        // Render current holder (different for view vs edit mode)
        renderCurrentHolder(mode);
        
        // Render history
        renderHistory();
        
        // Show/hide form actions based on mode
        const formActions = document.getElementById('positionFormActions');
        const modalFooter = document.getElementById('positionModalFooter');
        if (mode === 'edit') {
            if (formActions) formActions.classList.remove('d-none');
            if (modalFooter) modalFooter.style.display = 'none';
        } else {
            if (formActions) formActions.classList.add('d-none');
            if (modalFooter) modalFooter.style.display = '';
        }
    }
    
    function renderCurrentHolder(mode) {
        const holderInfo = document.getElementById('currentHolderInfo');
        const holderDropdown = document.getElementById('currentHolderDropdown');
        const holderSelect = document.getElementById('currentHolderSelect');
        const isActingCheck = document.getElementById('isActingCheck');
        
        if (!holderInfo) return;
        
        const holder = currentPositionData.current_holder;
        
        if (mode === 'edit') {
            // Show dropdown, hide info display
            if (holderInfo) holderInfo.style.display = 'none';
            if (holderDropdown) holderDropdown.classList.remove('d-none');
            
            // Populate dropdown with all characters
            if (holderSelect && ALL_CHARACTERS) {
                holderSelect.innerHTML = '<option value="">-- Vacant --</option>';
                ALL_CHARACTERS.forEach(function(char) {
                    const option = document.createElement('option');
                    option.value = char.id;
                    option.textContent = char.character_name + (char.clan ? ' (' + char.clan + ')' : '');
                    // Select current holder if exists
                    if (holder && holder.character_id && holder.character_id == char.id) {
                        option.selected = true;
                    }
                    holderSelect.appendChild(option);
                });
            }
            
            // Set acting checkbox
            if (isActingCheck && holder) {
                isActingCheck.checked = holder.is_acting ? true : false;
            } else if (isActingCheck) {
                isActingCheck.checked = false;
            }
        } else {
            // Show info display, hide dropdown
            if (holderInfo) holderInfo.style.display = '';
            if (holderDropdown) holderDropdown.classList.add('d-none');
            
            if (!holder) {
                holderInfo.innerHTML = '<p class="text-muted mb-0">Position is vacant</p>';
                return;
            }
            
            const holderName = holder.character_name || holder.assignment_character_id || 'Unknown';
            const holderClan = holder.clan || 'Unknown';
            const isActing = holder.is_acting ? 'Acting' : 'Permanent';
            const startDate = holder.start_night ? new Date(holder.start_night).toLocaleDateString() : 'Unknown';
            const characterId = holder.character_id;
            
            let html = '<div class="d-flex flex-column gap-2">';
            html += '<div><strong>Name:</strong> ';
            if (characterId) {
                html += `<a href="../lotn_char_create.php?id=${characterId}" class="text-light">${escapeHtml(holderName)}</a>`;
            } else {
                html += escapeHtml(holderName);
            }
            html += '</div>';
            html += `<div><strong>Clan:</strong> ${escapeHtml(holderClan)}</div>`;
            html += `<div><strong>Status:</strong> <span class="badge ${holder.is_acting ? 'badge-acting' : 'badge-permanent'}">${isActing}</span></div>`;
            html += `<div><strong>Since:</strong> ${startDate}</div>`;
            html += '</div>';
            
            holderInfo.innerHTML = html;
        }
    }
    
    function renderHistory() {
        const historyEl = document.getElementById('positionHistory');
        if (!historyEl) return;
        
        const history = currentPositionData.history || [];
        
        if (history.length === 0) {
            historyEl.innerHTML = '<p class="text-muted mb-0">No assignment history</p>';
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm table-dark mb-0">';
        html += '<thead><tr><th>Holder</th><th>Start</th><th>End</th><th>Status</th></tr></thead>';
        html += '<tbody>';
        
        history.forEach(assignment => {
            const holderName = assignment.character_name || assignment.character_id || 'Unknown';
            const startDate = assignment.start_night ? new Date(assignment.start_night).toLocaleDateString() : '—';
            const endDate = assignment.end_night ? new Date(assignment.end_night).toLocaleDateString() : 'Current';
            const isActing = assignment.is_acting ? 'Acting' : 'Permanent';
            const characterId = assignment.character_id;
            
            html += '<tr>';
            html += '<td>';
            if (characterId) {
                html += `<a href="../lotn_char_create.php?id=${characterId}" class="text-light">${escapeHtml(holderName)}</a>`;
            } else {
                html += escapeHtml(holderName);
            }
            html += '</td>';
            html += `<td>${startDate}</td>`;
            html += `<td>${endDate}</td>`;
            html += `<td><span class="badge ${assignment.is_acting ? 'badge-acting' : 'badge-permanent'}">${isActing}</span></td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        historyEl.innerHTML = html;
    }
    
    function handleFormSubmit(event) {
        event.preventDefault();
        
        if (currentMode !== 'edit') return;
        
        const form = document.getElementById('positionForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Get current holder selection
        const holderSelect = document.getElementById('currentHolderSelect');
        const isActingCheck = document.getElementById('isActingCheck');
        if (holderSelect) {
            data.current_holder = holderSelect.value || '';
        }
        if (isActingCheck) {
            data.is_acting = isActingCheck.checked ? 1 : 0;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }
        
        fetch(UPDATE_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Close modal and refresh page
                if (positionModalInstance) {
                    positionModalInstance.hide();
                }
                // Reload page to show updated data
                window.location.reload();
            } else {
                showError(result.message || 'Error saving position.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Changes';
                }
            }
        })
        .catch(error => {
            console.error('Update position error', error);
            showError('Error saving position.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Changes';
            }
        });
    }
    
    function resetForm() {
        const form = document.getElementById('positionForm');
        if (form) form.reset();
        
        const formInputs = document.querySelectorAll('#positionForm input, #positionForm textarea');
        formInputs.forEach(input => {
            input.readOnly = true;
        });
        
        const formActions = document.getElementById('positionFormActions');
        if (formActions) formActions.classList.add('d-none');
        
        const modalFooter = document.getElementById('positionModalFooter');
        if (modalFooter) modalFooter.style.display = '';
        
        // Reset holder display
        const holderInfo = document.getElementById('currentHolderInfo');
        const holderDropdown = document.getElementById('currentHolderDropdown');
        if (holderInfo) holderInfo.style.display = '';
        if (holderDropdown) holderDropdown.classList.add('d-none');
    }
    
    function showError(message) {
        const holderInfo = document.getElementById('currentHolderInfo');
        if (holderInfo) {
            holderInfo.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(message)}</div>`;
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Global function to open position edit
    window.editPosition = function(positionId) {
        window.viewPosition(positionId, 'edit');
    };
})();
</script>

