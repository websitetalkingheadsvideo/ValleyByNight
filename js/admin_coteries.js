/**
 * Admin Coteries Management JavaScript
 * Handles table operations, CRUD, filtering for character coterie associations
 */

let allCoteries = [];
let filteredCoteries = [];
let currentPage = 1;
let coteriesPerPage = 20;
let currentSort = { column: 'coterie_name', direction: 'asc' };
let currentCoterieNameFilter = 'all';
let currentCoterieTypeFilter = 'all';
let currentCharacterFilter = 'all';
let currentSearchTerm = '';
let currentCoterieData = null;

// Global data variables (loaded from JSON script tags)
let allCharacters = [];
let coterieNames = [];
let coterieTypes = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load data from JSON script tags
    const allCharactersElement = document.getElementById('allCharactersData');
    const coterieNamesElement = document.getElementById('coterieNamesData');
    const coterieTypesElement = document.getElementById('coterieTypesData');
    
    if (allCharactersElement) {
        try {
            allCharacters = JSON.parse(allCharactersElement.textContent);
        } catch (e) {
            console.error('Failed to parse allCharacters data:', e);
        }
    }
    
    if (coterieNamesElement) {
        try {
            coterieNames = JSON.parse(coterieNamesElement.textContent);
        } catch (e) {
            console.error('Failed to parse coterieNames data:', e);
        }
    }
    
    if (coterieTypesElement) {
        try {
            coterieTypes = JSON.parse(coterieTypesElement.textContent);
        } catch (e) {
            console.error('Failed to parse coterieTypes data:', e);
        }
    }
    
    initializeEventListeners();
    loadCoteries();
});

function initializeEventListeners() {
    // Add Coterie button
    const addCoterieBtn = document.getElementById('addCoterieBtn');
    if (addCoterieBtn) {
        addCoterieBtn.addEventListener('click', openAddCoterieModal);
    }
    
    // Manage Coterie button
    const manageCoterieBtn = document.getElementById('manageCoterieBtn');
    if (manageCoterieBtn) {
        manageCoterieBtn.addEventListener('click', openManageCoterieModal);
    }
    
    // Filters
    document.getElementById('coterieNameFilter').addEventListener('change', function() {
        currentCoterieNameFilter = this.value;
        applyFilters();
    });
    
    document.getElementById('coterieTypeFilter').addEventListener('change', function() {
        currentCoterieTypeFilter = this.value;
        applyFilters();
    });
    
    document.getElementById('characterFilter').addEventListener('change', function() {
        currentCharacterFilter = this.value;
        applyFilters();
    });
    
    document.getElementById('coterieSearch').addEventListener('input', function() {
        currentSearchTerm = this.value.toLowerCase();
        applyFilters();
    });
    
    document.getElementById('pageSize').addEventListener('change', function() {
        coteriesPerPage = parseInt(this.value);
        currentPage = 1;
        updateTable();
    });
}

function loadCoteries() {
    fetch('api_coterie_crud.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allCoteries = data.data;
            applyFilters();
            
            // Refresh manage modal if it's open
            const manageModal = document.getElementById('manageCoterieModal');
            if (manageModal && manageModal.classList.contains('show')) {
                const coterieSelect = document.getElementById('manageCoterieSelect');
                if (coterieSelect && coterieSelect.value) {
                    coterieSelect.dispatchEvent(new Event('change'));
                }
            }
        } else {
            console.error('Failed to load coteries:', data.error);
            alert('Failed to load coteries: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error loading coteries:', error);
        alert('Error loading coteries: ' + error.message);
    });
}

function applyFilters() {
    filteredCoteries = allCoteries.filter(coterie => {
        // Coterie name filter
        if (currentCoterieNameFilter !== 'all' && coterie.coterie_name !== currentCoterieNameFilter) {
            return false;
        }
        
        // Coterie type filter
        if (currentCoterieTypeFilter !== 'all' && coterie.coterie_type !== currentCoterieTypeFilter) {
            return false;
        }
        
        // Character filter
        if (currentCharacterFilter !== 'all' && coterie.character_id != currentCharacterFilter) {
            return false;
        }
        
        // Search filter
        if (currentSearchTerm) {
            const searchLower = currentSearchTerm.toLowerCase();
            const matchesName = coterie.coterie_name?.toLowerCase().includes(searchLower);
            const matchesCharacter = coterie.character_name?.toLowerCase().includes(searchLower);
            const matchesRole = coterie.role?.toLowerCase().includes(searchLower);
            if (!matchesName && !matchesCharacter && !matchesRole) {
                return false;
            }
        }
        
        return true;
    });
    
    // Apply sorting
    sortTable(currentSort.column, currentSort.direction);
    
    // Update pagination
    currentPage = 1;
    updateTable();
}

function sortTable(column, direction) {
    currentSort = { column, direction };
    
    filteredCoteries.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle different data types
        if (column === 'character_id') {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        } else {
            aVal = (aVal || '').toString().toLowerCase();
            bVal = (bVal || '').toString().toLowerCase();
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
    
    updateTable();
}

function updateTable() {
    const tbody = document.querySelector('#coteriesTable tbody');
    const startIndex = (currentPage - 1) * coteriesPerPage;
    const endIndex = startIndex + coteriesPerPage;
    const pageCoteries = filteredCoteries.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageCoteries.map(coterie => {
        const typeBadge = coterie.coterie_type 
            ? `<span class="badge bg-info">${escapeHtml(coterie.coterie_type)}</span>` 
            : '<span class="opacity-75">—</span>';
        const roleText = coterie.role ? escapeHtml(coterie.role) : '<span class="opacity-75">—</span>';
        const descriptionText = coterie.description 
            ? (coterie.description.length > 50 ? escapeHtml(coterie.description.substring(0, 50)) + '...' : escapeHtml(coterie.description))
            : '<span class="opacity-75">—</span>';
        
        return `
        <tr>
            <td><strong>${escapeHtml(coterie.character_name || 'Unknown')}</strong><br>
                <small class="opacity-75">${escapeHtml(coterie.clan || '')} - ${escapeHtml(coterie.player_name || '')}</small></td>
            <td><strong>${escapeHtml(coterie.coterie_name || 'Unknown')}</strong></td>
            <td>${typeBadge}</td>
            <td>${roleText}</td>
            <td>${descriptionText}</td>
            <td class="actions text-center align-top w-150px">
                <div class="btn-group btn-group-sm" role="group" aria-label="Coterie actions">
                    <button class="action-btn view-btn btn btn-primary" 
                            onclick="viewCoterie(${coterie.character_id}, '${escapeHtml(coterie.coterie_name)}')" 
                            title="View Coterie">👁️</button>
                    <button class="action-btn edit-btn btn btn-warning" 
                            onclick="editCoterie(${coterie.character_id}, '${escapeHtml(coterie.coterie_name)}')" 
                            title="Edit Coterie">✏️</button>
                    <button class="action-btn delete-btn btn btn-danger" 
                            onclick="deleteCoterie(${coterie.character_id}, '${escapeHtml(coterie.coterie_name)}', '${escapeHtml(coterie.character_name)}')" 
                            title="Delete Coterie">🗑️</button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
    
    // Add sorting event listeners
    document.querySelectorAll('#coteriesTable th[data-sort]').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.sort;
            const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            
            // Update sort indicators
            document.querySelectorAll('#coteriesTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
            
            sortTable(column, direction);
        });
    });
    
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(filteredCoteries.length / coteriesPerPage);
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    
    // Update info
    const startIndex = (currentPage - 1) * coteriesPerPage + 1;
    const endIndex = Math.min(currentPage * coteriesPerPage, filteredCoteries.length);
    paginationInfo.textContent = `Showing ${startIndex}-${endIndex} of ${filteredCoteries.length} associations`;
    
    // Update buttons
    paginationButtons.innerHTML = '';
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn';
    prevBtn.textContent = '← Previous';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => changePage(currentPage - 1);
    paginationButtons.appendChild(prevBtn);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
        pageBtn.textContent = i;
        pageBtn.onclick = () => changePage(i);
        paginationButtons.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.textContent = 'Next →';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => changePage(currentPage + 1);
    paginationButtons.appendChild(nextBtn);
}

function changePage(page) {
    const totalPages = Math.ceil(filteredCoteries.length / coteriesPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        updateTable();
    }
}

// Generate coterie form HTML
function generateCoterieFormHtml(coterie = null) {
    const characterId = coterie ? coterie.character_id : '';
    const coterieName = coterie ? escapeHtml(coterie.coterie_name) : '';
    const coterieType = coterie ? coterie.coterie_type : '';
    const role = coterie ? escapeHtml(coterie.role || '') : '';
    const description = coterie ? escapeHtml(coterie.description || '') : '';
    const notes = coterie ? escapeHtml(coterie.notes || '') : '';
    const oldCoterieName = coterie ? escapeHtml(coterie.coterie_name) : '';
    
    return `
        <form id="coterieForm" class="needs-validation" novalidate>
            <input type="hidden" id="oldCoterieName" name="old_coterie_name" value="${oldCoterieName}">
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12">
                    <label for="coterieCharacterId" class="form-label">Character *</label>
                    <select id="coterieCharacterId" name="character_id" class="form-select" required>
                        <option value="">Select Character</option>
                        ${allCharacters.map(char => `
                            <option value="${char.id}" ${characterId == char.id ? 'selected' : ''}>
                                ${escapeHtml(char.character_name)} (${escapeHtml(char.clan || 'Unknown')})
                            </option>
                        `).join('')}
                    </select>
                    <div class="invalid-feedback">Please select a character.</div>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="coterieName" class="form-label">Coterie Name *</label>
                    <input type="text" id="coterieName" name="coterie_name" class="form-control" value="${coterieName}" 
                           placeholder="e.g., The Phoenix Circle" required>
                    <div class="invalid-feedback">Please enter a coterie name.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="coterieType" class="form-label">Type</label>
                    <select id="coterieType" name="coterie_type" class="form-select">
                        <option value="">Select type...</option>
                        <option value="faction" ${coterieType === 'faction' ? 'selected' : ''}>Faction</option>
                        <option value="role" ${coterieType === 'role' ? 'selected' : ''}>Role</option>
                        <option value="membership" ${coterieType === 'membership' ? 'selected' : ''}>Membership</option>
                        <option value="informal_group" ${coterieType === 'informal_group' ? 'selected' : ''}>Informal Group</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12">
                    <label for="coterieRole" class="form-label">Role</label>
                    <input type="text" id="coterieRole" name="role" class="form-control" value="${role}" 
                           placeholder="e.g., Leader, Member, Advisor">
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="coterieDescription" class="form-label">Description</label>
                <textarea id="coterieDescription" name="description" class="form-control" rows="3" 
                          placeholder="Describe the coterie and the character's involvement...">${description}</textarea>
            </div>
            
            <div class="form-group mb-3">
                <label for="coterieNotes" class="form-label">Notes</label>
                <textarea id="coterieNotes" name="notes" class="form-control" rows="2" 
                          placeholder="Additional notes about this coterie association...">${notes}</textarea>
            </div>
        </form>
    `;
}

// CRUD Functions
function openAddCoterieModal() {
    const modalElement = document.getElementById('coterieModal');
    if (!modalElement) {
        console.error('coterieModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '👥 Add Coterie Association';
    modalBody.innerHTML = generateCoterieFormHtml();
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="coterieForm" class="btn btn-primary">Save Coterie</button>
    `;
    
    // Re-attach form submission handler
    const form = document.getElementById('coterieForm');
    if (form) {
        form.removeEventListener('submit', handleCoterieFormSubmit);
        form.addEventListener('submit', handleCoterieFormSubmit);
    }
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function editCoterie(characterId, coterieName) {
    const coterie = allCoteries.find(c => c.character_id == characterId && c.coterie_name === coterieName);
    if (!coterie) return;
    
    const modalElement = document.getElementById('coterieModal');
    if (!modalElement) {
        console.error('coterieModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '✏️ Edit Coterie Association';
    modalBody.innerHTML = generateCoterieFormHtml(coterie);
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="coterieForm" class="btn btn-primary">Save Changes</button>
    `;
    
    // Re-attach form submission handler
    const form = document.getElementById('coterieForm');
    if (form) {
        form.removeEventListener('submit', handleCoterieFormSubmit);
        form.addEventListener('submit', handleCoterieFormSubmit);
    }
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function viewCoterie(characterId, coterieName) {
    const coterie = allCoteries.find(c => c.character_id == characterId && c.coterie_name === coterieName);
    if (!coterie) return;
    
    const modalElement = document.getElementById('viewModal');
    if (!modalElement) {
        console.error('viewModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = `👥 ${escapeHtml(coterie.coterie_name)}`;
    modalBody.innerHTML = `
        <div class="info-grid">
            <div>
                <h3>Character Information</h3>
                <p><strong>Character:</strong> ${escapeHtml(coterie.character_name || 'Unknown')}</p>
                <p><strong>Clan:</strong> ${escapeHtml(coterie.clan || 'N/A')}</p>
                <p><strong>Player:</strong> ${escapeHtml(coterie.player_name || 'N/A')}</p>
            </div>
            <div>
                <h3>Coterie Information</h3>
                <p><strong>Coterie Name:</strong> ${escapeHtml(coterie.coterie_name || 'Unknown')}</p>
                <p><strong>Type:</strong> <span class="badge bg-info">${escapeHtml(coterie.coterie_type || 'N/A')}</span></p>
                <p><strong>Role:</strong> ${escapeHtml(coterie.role || 'N/A')}</p>
            </div>
        </div>
        ${coterie.description ? `<div><h3>Description</h3><p>${escapeHtml(coterie.description).replace(/\n/g, '<br>')}</p></div>` : ''}
        ${coterie.notes ? `<div><h3>Notes</h3><p>${escapeHtml(coterie.notes).replace(/\n/g, '<br>')}</p></div>` : ''}
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" onclick="editCoterie(${coterie.character_id}, '${escapeHtml(coterie.coterie_name)}'); bootstrap.Modal.getInstance(document.getElementById('viewModal')).hide();">Edit</button>
    `;
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function deleteCoterie(characterId, coterieName, characterName) {
    if (!confirm(`Are you sure you want to delete the coterie association between "${escapeHtml(characterName)}" and "${escapeHtml(coterieName)}"?`)) {
        return;
    }
    
    fetch('api_coterie_crud.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            character_id: characterId,
            coterie_name: coterieName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCoteries();
        } else {
            alert('Failed to delete coterie association: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting coterie:', error);
        alert('Error deleting coterie: ' + error.message);
    });
}

function handleCoterieFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const formData = {
        character_id: parseInt(form.querySelector('#coterieCharacterId').value),
        coterie_name: form.querySelector('#coterieName').value.trim(),
        coterie_type: form.querySelector('#coterieType').value.trim(),
        role: form.querySelector('#coterieRole').value.trim(),
        description: form.querySelector('#coterieDescription').value.trim(),
        notes: form.querySelector('#coterieNotes').value.trim()
    };
    
    const oldCoterieNameInput = form.querySelector('#oldCoterieName');
    const isEdit = oldCoterieNameInput && oldCoterieNameInput.value;
    
    if (isEdit) {
        formData.old_coterie_name = oldCoterieNameInput.value;
    }
    
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch('api_coterie_crud.php', {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('coterieModal')).hide();
            loadCoteries();
        } else {
            alert('Failed to save coterie association: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving coterie:', error);
        alert('Error saving coterie: ' + error.message);
    });
}

function openManageCoterieModal() {
    const modalElement = document.getElementById('manageCoterieModal');
    if (!modalElement) {
        console.error('manageCoterieModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    // Get unique coterie names
    const uniqueCoteries = [...new Set(allCoteries.map(c => c.coterie_name).filter(Boolean))].sort();
    
    modalTitle.textContent = '👥 Manage Coterie Members';
    modalBody.innerHTML = `
        <div class="mb-3">
            <label for="manageCoterieSelect" class="form-label">Select Coterie</label>
            <select id="manageCoterieSelect" class="form-select">
                <option value="">Select a coterie...</option>
                ${uniqueCoteries.map(name => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('')}
            </select>
        </div>
        <div id="coterieMembersList" class="mb-3" style="display: none;">
            <h5>Current Members</h5>
            <div id="currentMembers" class="mb-3"></div>
            <hr>
            <h5>Add New Members</h5>
            <div class="mb-3">
                <label for="newMemberCharacter" class="form-label">Character</label>
                <select id="newMemberCharacter" class="form-select">
                    <option value="">Select character...</option>
                    ${allCharacters.map(char => `<option value="${char.id}">${escapeHtml(char.character_name)} (${escapeHtml(char.clan || 'Unknown')})</option>`).join('')}
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label for="newMemberType" class="form-label">Type</label>
                    <select id="newMemberType" class="form-select">
                        <option value="">Select type...</option>
                        <option value="faction">Faction</option>
                        <option value="role">Role</option>
                        <option value="membership">Membership</option>
                        <option value="informal_group">Informal Group</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="newMemberRole" class="form-label">Role</label>
                    <input type="text" id="newMemberRole" class="form-control" placeholder="e.g., Leader, Member">
                </div>
            </div>
            <div class="mb-3">
                <label for="newMemberDescription" class="form-label">Description</label>
                <textarea id="newMemberDescription" class="form-control" rows="2" placeholder="Describe their involvement..."></textarea>
            </div>
            <button type="button" class="btn btn-primary" id="addMemberBtn">Add Member</button>
        </div>
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    `;
    
    // Handle coterie selection
    const coterieSelect = document.getElementById('manageCoterieSelect');
    const membersList = document.getElementById('coterieMembersList');
    const currentMembers = document.getElementById('currentMembers');
    
    coterieSelect.addEventListener('change', function() {
        const selectedCoterie = this.value;
        if (!selectedCoterie) {
            membersList.style.display = 'none';
            return;
        }
        
        // Show members of this coterie
        const members = allCoteries.filter(c => c.coterie_name === selectedCoterie);
        if (members.length > 0) {
            currentMembers.innerHTML = `
                <div class="list-group">
                    ${members.map(m => `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${escapeHtml(m.character_name || 'Unknown')}</strong>
                                ${m.role ? `<br><small class="opacity-75">Role: ${escapeHtml(m.role)}</small>` : ''}
                                ${m.coterie_type ? `<br><small class="opacity-75">Type: ${escapeHtml(m.coterie_type)}</small>` : ''}
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="removeCoterieMember(${m.character_id}, '${escapeHtml(m.coterie_name)}', '${escapeHtml(m.character_name)}')">Remove</button>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            currentMembers.innerHTML = '<p class="opacity-75">No members yet.</p>';
        }
        
        membersList.style.display = 'block';
    });
    
    // Handle add member button
    const addMemberBtn = document.getElementById('addMemberBtn');
    if (addMemberBtn) {
        addMemberBtn.addEventListener('click', function() {
            const coterieName = coterieSelect.value;
            const characterId = parseInt(document.getElementById('newMemberCharacter').value);
            const coterieType = document.getElementById('newMemberType').value;
            const role = document.getElementById('newMemberRole').value.trim();
            const description = document.getElementById('newMemberDescription').value.trim();
            
            if (!coterieName) {
                alert('Please select a coterie first');
                return;
            }
            
            if (!characterId) {
                alert('Please select a character');
                return;
            }
            
            // Check if character is already in this coterie
            const alreadyMember = allCoteries.some(c => c.coterie_name === coterieName && c.character_id == characterId);
            if (alreadyMember) {
                alert('This character is already a member of this coterie');
                return;
            }
            
            // Add the member
            fetch('api_coterie_crud.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    character_id: characterId,
                    coterie_name: coterieName,
                    coterie_type: coterieType,
                    role: role,
                    description: description,
                    notes: ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    document.getElementById('newMemberCharacter').value = '';
                    document.getElementById('newMemberType').value = '';
                    document.getElementById('newMemberRole').value = '';
                    document.getElementById('newMemberDescription').value = '';
                    
                    // Reload and refresh display
                    loadCoteries();
                    
                    // Trigger change event to refresh member list
                    coterieSelect.dispatchEvent(new Event('change'));
                } else {
                    alert('Failed to add member: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error adding member:', error);
                alert('Error adding member: ' + error.message);
            });
        });
    }
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function removeCoterieMember(characterId, coterieName, characterName) {
    if (!confirm(`Remove ${escapeHtml(characterName)} from ${escapeHtml(coterieName)}?`)) {
        return;
    }
    
    deleteCoterie(characterId, coterieName, characterName);
    
    // Refresh the manage modal if it's open
    setTimeout(() => {
        const coterieSelect = document.getElementById('manageCoterieSelect');
        if (coterieSelect && coterieSelect.value) {
            coterieSelect.dispatchEvent(new Event('change'));
        }
    }, 500);
}

// Utility function
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

