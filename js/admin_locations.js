/**
 * Admin Locations Management JavaScript
 * Handles table operations, CRUD, filtering, and character assignment
 */

let allLocations = [];
let filteredLocations = [];
let currentPage = 1;
let locationsPerPage = 20;
let currentSort = { column: 'id', direction: 'asc' };
let currentFilter = 'all';
let currentTypeFilter = 'all';
let currentStatusFilter = 'all';
let currentOwnerFilter = 'all';
let currentPCHavenFilter = 'all';
let currentSearchTerm = '';
let currentLocationId = null;

// Global data variables (loaded from JSON script tags)
let allCharacters = [];
let allCharactersForLocations = [];
let locationTypes = [];
let locationStatuses = [];
let locationOwners = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load data from JSON script tags
    const allCharactersElement = document.getElementById('allCharactersData');
    const locationTypesElement = document.getElementById('locationTypesData');
    const locationStatusesElement = document.getElementById('locationStatusesData');
    const locationOwnersElement = document.getElementById('locationOwnersData');
    
    if (allCharactersElement) {
        try {
            allCharacters = JSON.parse(allCharactersElement.textContent);
            allCharactersForLocations = allCharacters; // Use same data for both
        } catch (e) {
            console.error('Failed to parse allCharacters data:', e);
        }
    }
    
    if (locationTypesElement) {
        try {
            locationTypes = JSON.parse(locationTypesElement.textContent);
        } catch (e) {
            console.error('Failed to parse locationTypes data:', e);
        }
    }
    
    if (locationStatusesElement) {
        try {
            locationStatuses = JSON.parse(locationStatusesElement.textContent);
        } catch (e) {
            console.error('Failed to parse locationStatuses data:', e);
        }
    }
    
    if (locationOwnersElement) {
        try {
            locationOwners = JSON.parse(locationOwnersElement.textContent);
        } catch (e) {
            console.error('Failed to parse locationOwners data:', e);
        }
    }
    
    initializeEventListeners();
    loadLocations();
});

// Bootstrap handles accessibility automatically

function initializeEventListeners() {
    // Add Location button
    const addLocationBtn = document.getElementById('addLocationBtn');
    if (addLocationBtn) {
        addLocationBtn.addEventListener('click', openAddLocationModal);
    }
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });

    // Type filter
    document.getElementById('typeFilter').addEventListener('change', function() {
        currentTypeFilter = this.value;
        applyFilters();
    });

    // Status filter
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentStatusFilter = this.value;
        applyFilters();
    });

    // Owner filter
    document.getElementById('ownerFilter').addEventListener('change', function() {
        currentOwnerFilter = this.value;
        applyFilters();
    });

    // PC Haven filter
    const pcHavenFilter = document.getElementById('pcHavenFilter');
    if (pcHavenFilter) {
        pcHavenFilter.addEventListener('change', function() {
            currentPCHavenFilter = this.value;
            applyFilters();
        });
    }

    // Search
    document.getElementById('locationSearch').addEventListener('input', function() {
        currentSearchTerm = this.value.toLowerCase();
        applyFilters();
    });

    // Page size
    document.getElementById('pageSize').addEventListener('change', function() {
        locationsPerPage = parseInt(this.value);
        currentPage = 1;
        applyFilters();
    });

    // Form submission handler is attached dynamically when modals are opened
    // (see openAddLocationModal and editLocation functions)
}

async function loadLocations() {
    try {
        const response = await fetch('api_locations.php');
        const data = await response.json();
        
        if (data.success) {
            allLocations = data.locations;
            applyFilters();
        } else {
            showNotification('Failed to load locations: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading locations:', error);
        showNotification('Failed to load locations', 'error');
    }
}

function applyFilters() {
    filteredLocations = allLocations.filter(location => {
        // Type filter
        if (currentFilter !== 'all') {
            if (currentFilter === 'havens' && location.type !== 'Haven') return false;
            if (currentFilter === 'elysiums' && location.type !== 'Elysium') return false;
            if (currentFilter === 'domains' && location.type !== 'Domain') return false;
            if (currentFilter === 'hunting-grounds' && location.type !== 'Hunting Ground') return false;
            if (currentFilter === 'nightclubs' && location.type !== 'Nightclub') return false;
            if (currentFilter === 'businesses' && location.type !== 'Business') return false;
        }

        // Type dropdown filter
        if (currentTypeFilter !== 'all' && location.type !== currentTypeFilter) return false;

        // Status filter
        if (currentStatusFilter !== 'all' && location.status !== currentStatusFilter) return false;

        // Owner filter
        if (currentOwnerFilter !== 'all' && location.owner_type !== currentOwnerFilter) return false;

        // PC Haven filter - STRICT: Only locations with type='Haven' can be PC Havens
        if (currentPCHavenFilter !== 'all') {
            if (currentPCHavenFilter === 'yes') {
                // "PC Havens Only" - ONLY show locations that are:
                // 1. Type = 'Haven' (not Elysium, Temple, etc.)
                // 2. pc_haven = 1
                if (location.type !== 'Haven') return false;
                const isPCHaven = location.pc_haven == 1 || location.pc_haven === true;
                if (!isPCHaven) return false;
            } else if (currentPCHavenFilter === 'no') {
                // "Non-PC Havens" - show everything EXCEPT type='Haven' with pc_haven=1
                if (location.type === 'Haven') {
                    const isPCHaven = location.pc_haven == 1 || location.pc_haven === true;
                    if (isPCHaven) return false; // Exclude PC Havens
                }
                // Include everything else (non-Havens, and Havens with pc_haven=0)
            }
        }

        // Search filter
        if (currentSearchTerm && !location.name.toLowerCase().includes(currentSearchTerm)) return false;

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
    
    filteredLocations.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle different data types
        if (column === 'id' || column === 'security_level') {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        } else if (column === 'created_at') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
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
    const tbody = document.querySelector('#locationsTable tbody');
    const startIndex = (currentPage - 1) * locationsPerPage;
    const endIndex = startIndex + locationsPerPage;
    const pageLocations = filteredLocations.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageLocations.map(location => {
        const isPCHaven = location.type === 'Haven' && (location.pc_haven == 1 || location.pc_haven === true);
        const pcHavenBadge = isPCHaven ? '<span class="badge bg-info" title="Possible PC Haven">PC</span> ' : '';
        return `
        <tr>
            <td>${location.id}</td>
            <td><strong>${pcHavenBadge}${escapeHtml(location.name)}</strong></td>
            <td><span class="badge-${location.type.toLowerCase().replace(' ', '-')}">${escapeHtml(location.type)}</span></td>
            <td><span class="badge-${location.status.toLowerCase()}">${escapeHtml(location.status)}</span></td>
            <td>${escapeHtml(location.district || 'N/A')}</td>
            <td>${escapeHtml(location.owner_type || 'N/A')}</td>
            <td>${formatDate(location.created_at)}</td>
            <td class="actions">
                <button class="action-btn view-btn" onclick="viewLocation(${location.id})" title="View Location">👁️</button>
                <button class="action-btn edit-btn" onclick="editLocation(${location.id})" title="Edit Location">✏️</button>
                <button class="action-btn assign-btn" onclick="assignLocation(${location.id}, '${escapeHtml(location.name)}')" title="Assign Characters">🎯</button>
                <button class="action-btn delete-btn" onclick="deleteLocation(${location.id}, '${escapeHtml(location.name)}')" title="Delete Location">🗑️</button>
            </td>
        </tr>
        `;
    }).join('');

    // Add sorting event listeners
    document.querySelectorAll('#locationsTable th[data-sort]').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.sort;
            const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            
            // Update sort indicators
            document.querySelectorAll('#locationsTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
            
            sortTable(column, direction);
        });
    });
    
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(filteredLocations.length / locationsPerPage);
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    
    // Update info
    const startIndex = (currentPage - 1) * locationsPerPage + 1;
    const endIndex = Math.min(currentPage * locationsPerPage, filteredLocations.length);
    paginationInfo.textContent = `Showing ${startIndex}-${endIndex} of ${filteredLocations.length} locations`;
    
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
    const totalPages = Math.ceil(filteredLocations.length / locationsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        updateTable();
    }
}

// Generate location form HTML
function generateLocationFormHtml(location = null) {
    const locationId = location ? location.id : '';
    const locationName = location ? escapeHtml(location.name) : '';
    const locationType = location ? location.type : '';
    const locationStatus = location ? location.status : '';
    const locationDistrict = location ? escapeHtml(location.district || '') : '';
    const locationOwnerType = location ? location.owner_type : '';
    const locationFaction = location ? escapeHtml(location.faction || '') : '';
    const locationAccessControl = location ? location.access_control : '';
    const locationSecurityLevel = location ? (location.security_level || 3) : 3;
    const locationDescription = location ? escapeHtml(location.description || '') : '';
    const locationSummary = location ? escapeHtml(location.summary || '') : '';
    const locationNotes = location ? escapeHtml(location.notes || '') : '';
    const locationPCHaven = location && location.type === 'Haven' ? (location.pc_haven == 1 || location.pc_haven === true) : false;
    
    return `
        <form id="locationForm" class="needs-validation" novalidate>
            <input type="hidden" id="locationId" name="id" value="${locationId}">
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationName" class="form-label">Name *</label>
                    <input type="text" id="locationName" name="name" class="form-control" value="${locationName}" required>
                    <div class="invalid-feedback">Please enter a location name.</div>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationType" class="form-label">Type *</label>
                    <select id="locationType" name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Haven" ${locationType === 'Haven' ? 'selected' : ''}>Haven</option>
                        <option value="Elysium" ${locationType === 'Elysium' ? 'selected' : ''}>Elysium</option>
                        <option value="Domain" ${locationType === 'Domain' ? 'selected' : ''}>Domain</option>
                        <option value="Hunting Ground" ${locationType === 'Hunting Ground' ? 'selected' : ''}>Hunting Ground</option>
                        <option value="Nightclub" ${locationType === 'Nightclub' ? 'selected' : ''}>Nightclub</option>
                        <option value="Gathering Place" ${locationType === 'Gathering Place' ? 'selected' : ''}>Gathering Place</option>
                        <option value="Business" ${locationType === 'Business' ? 'selected' : ''}>Business</option>
                        <option value="Chantry" ${locationType === 'Chantry' ? 'selected' : ''}>Chantry</option>
                        <option value="Temple" ${locationType === 'Temple' ? 'selected' : ''}>Temple</option>
                        <option value="Wilderness" ${locationType === 'Wilderness' ? 'selected' : ''}>Wilderness</option>
                        <option value="Other" ${locationType === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationStatus" class="form-label">Status *</label>
                    <select id="locationStatus" name="status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="Active" ${locationStatus === 'Active' ? 'selected' : ''}>Active</option>
                        <option value="Abandoned" ${locationStatus === 'Abandoned' ? 'selected' : ''}>Abandoned</option>
                        <option value="Destroyed" ${locationStatus === 'Destroyed' ? 'selected' : ''}>Destroyed</option>
                        <option value="Contested" ${locationStatus === 'Contested' ? 'selected' : ''}>Contested</option>
                        <option value="Hidden" ${locationStatus === 'Hidden' ? 'selected' : ''}>Hidden</option>
                    </select>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationDistrict" class="form-label">District</label>
                    <input type="text" id="locationDistrict" name="district" class="form-control" value="${locationDistrict}" placeholder="e.g., Downtown, Warehouse District">
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationOwnerType" class="form-label">Owner Type *</label>
                    <select id="locationOwnerType" name="owner_type" class="form-select" required>
                        <option value="">Select Owner Type</option>
                        <option value="Personal" ${locationOwnerType === 'Personal' ? 'selected' : ''}>Personal</option>
                        <option value="Clan" ${locationOwnerType === 'Clan' ? 'selected' : ''}>Clan</option>
                        <option value="Sect" ${locationOwnerType === 'Sect' ? 'selected' : ''}>Sect</option>
                        <option value="Coterie" ${locationOwnerType === 'Coterie' ? 'selected' : ''}>Coterie</option>
                        <option value="NPC" ${locationOwnerType === 'NPC' ? 'selected' : ''}>NPC</option>
                        <option value="Contested" ${locationOwnerType === 'Contested' ? 'selected' : ''}>Contested</option>
                        <option value="Public" ${locationOwnerType === 'Public' ? 'selected' : ''}>Public</option>
                    </select>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationFaction" class="form-label">Faction</label>
                    <input type="text" id="locationFaction" name="faction" class="form-control" value="${locationFaction}" placeholder="e.g., Camarilla, Sabbat">
                </div>
            </div>
            
            <div class="form-row row g-3">
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationAccessControl" class="form-label">Access Control *</label>
                    <select id="locationAccessControl" name="access_control" class="form-select" required>
                        <option value="">Select Access Control</option>
                        <option value="Open" ${locationAccessControl === 'Open' ? 'selected' : ''}>Open</option>
                        <option value="Restricted" ${locationAccessControl === 'Restricted' ? 'selected' : ''}>Restricted</option>
                        <option value="Private" ${locationAccessControl === 'Private' ? 'selected' : ''}>Private</option>
                        <option value="Secret" ${locationAccessControl === 'Secret' ? 'selected' : ''}>Secret</option>
                        <option value="Invitation Only" ${locationAccessControl === 'Invitation Only' ? 'selected' : ''}>Invitation Only</option>
                    </select>
                </div>
                <div class="form-group mb-3 col-12 col-md-6">
                    <label for="locationSecurityLevel" class="form-label">Security Level</label>
                    <input type="number" id="locationSecurityLevel" name="security_level" class="form-control" min="1" max="10" value="${locationSecurityLevel}">
                </div>
            </div>
            
            <div class="form-group mb-3" id="pcHavenContainer" style="${locationType === 'Haven' || !location ? '' : 'display:none;'}">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="locationPCHaven" name="pc_haven" value="1" ${locationPCHaven ? 'checked' : ''}>
                    <label class="form-check-label" for="locationPCHaven">
                        Possible PC Haven
                    </label>
                    <small class="form-text text-muted">Mark this haven as a possible player character haven (only applies to Havens)</small>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="locationDescription" class="form-label">Description</label>
                <textarea id="locationDescription" name="description" class="form-control" placeholder="Detailed description of the location...">${locationDescription}</textarea>
            </div>
            
            <div class="form-group mb-3">
                <label for="locationSummary" class="form-label">Summary</label>
                <textarea id="locationSummary" name="summary" class="form-control" placeholder="Brief summary for quick reference...">${locationSummary}</textarea>
            </div>
            
            <div class="form-group mb-3">
                <label for="locationNotes" class="form-label">Notes</label>
                <textarea id="locationNotes" name="notes" class="form-control" placeholder="Additional notes, plot hooks, etc...">${locationNotes}</textarea>
            </div>
        </form>
    `;
}

// CRUD Functions
function openAddLocationModal() {
    const modalElement = document.getElementById('locationModal');
    if (!modalElement) {
        console.error('locationModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '🏠 Add New Location';
    modalBody.innerHTML = generateLocationFormHtml();
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="locationForm" class="btn btn-primary">Save Location</button>
    `;
    
    // Re-attach form submission handler
    const form = document.getElementById('locationForm');
    if (form) {
        form.removeEventListener('submit', handleFormSubmit);
        form.addEventListener('submit', handleFormSubmit);
    }
    
    // Setup PC Haven checkbox visibility based on type
    const typeSelect = document.getElementById('locationType');
    const pcHavenContainer = document.getElementById('pcHavenContainer');
    const pcHavenCheckbox = document.getElementById('locationPCHaven');
    if (typeSelect && pcHavenContainer && pcHavenCheckbox) {
        typeSelect.addEventListener('change', function() {
            pcHavenContainer.style.display = this.value === 'Haven' ? '' : 'none';
            if (this.value !== 'Haven') {
                pcHavenCheckbox.checked = false;
            }
        });
    }
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function editLocation(id) {
    const location = allLocations.find(loc => loc.id == id);
    if (!location) return;
    
    const modalElement = document.getElementById('locationModal');
    if (!modalElement) {
        console.error('locationModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '🏠 Edit Location';
    modalBody.innerHTML = generateLocationFormHtml(location);
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="locationForm" class="btn btn-primary">Save Location</button>
    `;
    
    // Re-attach form submission handler
    const form = document.getElementById('locationForm');
    if (form) {
        form.removeEventListener('submit', handleFormSubmit);
        form.addEventListener('submit', handleFormSubmit);
    }
    
    // Setup PC Haven checkbox visibility based on type
    const typeSelect = document.getElementById('locationType');
    const pcHavenContainer = document.getElementById('pcHavenContainer');
    const pcHavenCheckbox = document.getElementById('locationPCHaven');
    if (typeSelect && pcHavenContainer && pcHavenCheckbox) {
        typeSelect.addEventListener('change', function() {
            pcHavenContainer.style.display = this.value === 'Haven' ? '' : 'none';
            if (this.value !== 'Haven') {
                pcHavenCheckbox.checked = false;
            }
        });
    }
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

async function viewLocation(id) {
    const location = allLocations.find(loc => loc.id == id);
    if (!location) return;
    
    // Trigger music event for location enter
    if (window.musicManager) {
        window.musicManager.handleLocationEnter(location.id);
    } else {
        // Fallback: dispatch custom event for music system
        document.dispatchEvent(new CustomEvent('locationEntered', {
            detail: { locationId: location.id, id: location.id }
        }));
    }
    
    // Get modal elements
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
    
    modalTitle.textContent = `📄 ${escapeHtml(location.name)}`;
    modalBody.setAttribute('aria-busy','true');
    modalBody.innerHTML = '<div class="loading">Loading location details...</div>';
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
    
    try {
        // Fetch character assignments
        const response = await fetch(`api_admin_location_assignments.php?location_id=${id}`);
        const data = await response.json();
        
        let assignmentsHtml = '';
        if (data.success && data.assignments.length > 0) {
            assignmentsHtml = `
                <div class="view-section">
                    <h3>Assigned Characters (${data.count})</h3>
                    <div class="assignments-list">
                        ${data.assignments.map(assignment => `
                            <div class="assignment-item">
                                <div class="assignment-character">
                                    <strong>${escapeHtml(assignment.character_name)}</strong>
                                    <small>${escapeHtml(assignment.clan)} - ${escapeHtml(assignment.player_name)}</small>
                                </div>
                                <div class="assignment-type">
                                    <span class="assignment-badge assignment-${assignment.assignment_type.toLowerCase().replace(' ', '-')}">
                                        ${escapeHtml(assignment.assignment_type)}
                                    </span>
                                </div>
                                ${assignment.notes ? `<div class="assignment-notes"><small>${escapeHtml(assignment.notes)}</small></div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } else {
            assignmentsHtml = `
                <div class="view-section">
                    <h3>Assigned Characters</h3>
                    <p class="no-assignments">No characters assigned to this location.</p>
                </div>
            `;
        }
        
        const content = `
            <div class="view-section">
                <h3>Basic Information</h3>
                <p><strong>Name:</strong> ${escapeHtml(location.name)}</p>
                <p><strong>Type:</strong> <span class="badge-${location.type.toLowerCase().replace(' ', '-')}">${escapeHtml(location.type)}</span></p>
                <p><strong>Status:</strong> <span class="badge-${location.status.toLowerCase()}">${escapeHtml(location.status)}</span></p>
                <p><strong>District:</strong> ${escapeHtml(location.district || 'N/A')}</p>
            </div>
            
            <div class="view-section">
                <h3>Ownership & Control</h3>
                <p><strong>Owner Type:</strong> ${escapeHtml(location.owner_type || 'N/A')}</p>
                <p><strong>Faction:</strong> ${escapeHtml(location.faction || 'N/A')}</p>
                <p><strong>Access Control:</strong> ${escapeHtml(location.access_control || 'N/A')}</p>
                <p><strong>Security Level:</strong> ${location.security_level || 3}</p>
            </div>
            
            ${assignmentsHtml}
            
            ${location.description ? `
            <div class="view-section">
                <h3>Description</h3>
                <p>${escapeHtml(location.description)}</p>
            </div>
            ` : ''}
            
            ${location.summary ? `
            <div class="view-section">
                <h3>Summary</h3>
                <p>${escapeHtml(location.summary)}</p>
            </div>
            ` : ''}
            
            ${location.notes ? `
            <div class="view-section">
                <h3>Notes</h3>
                <p>${escapeHtml(location.notes)}</p>
            </div>
            ` : ''}
        `;
        
        modalBody.innerHTML = content;
        modalBody.setAttribute('aria-busy','false');
        
    } catch (error) {
        console.error('Error loading assignments:', error);
        modalBody.innerHTML = `
            <div class="view-section">
                <h3>Error</h3>
                <p>Failed to load character assignments.</p>
            </div>
        `;
        modalBody.setAttribute('aria-busy','false');
    }
}

function assignLocation(id, name) {
    currentLocationId = id;
    
    const modalElement = document.getElementById('assignModal');
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    modalTitle.textContent = '🎯 Assign Characters to Location';
    modalBody.innerHTML = `
        <p class="vbn-modal-message">Assign characters to <strong id="assignLocationName">${escapeHtml(name)}</strong>:</p>
        <div class="character-selection" id="characterSelection">
            ${(window.allCharactersForLocations || []).map(char => `
                <div class="character-item" data-character-id="${char.id}">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${escapeHtml(char.character_name)}</strong>
                            <small style="display: block; color: #b8a090;">
                                ${escapeHtml(char.clan)} - ${escapeHtml(char.player_name)}
                            </small>
                        </div>
                        <select class="assignment-type-select" data-character-id="${char.id}">
                            <option value="Resident">Resident</option>
                            <option value="Owner">Owner</option>
                            <option value="Visitor">Visitor</option>
                            <option value="Staff">Staff</option>
                            <option value="Guard">Guard</option>
                        </select>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="assignCharactersToLocation()">Assign Characters</button>
    `;
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function deleteLocation(id, name) {
    currentLocationId = id;
    
    const modalElement = document.getElementById('deleteModal');
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    modalTitle.textContent = '⚠️ Confirm Deletion';
    modalBody.innerHTML = `
        <p class="vbn-modal-message">Delete location:</p>
        <p class="vbn-modal-character-name" id="deleteLocationName">${escapeHtml(name)}</p>
        <p class="vbn-modal-warning" id="deleteWarning" style="display:none;"></p>
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteLocation()">Delete</button>
    `;
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
    
    // Check for assignments
    fetch(`api_admin_location_assignments.php?location_id=${id}`)
        .then(response => response.json())
        .then(data => {
            const warning = document.getElementById('deleteWarning');
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            if (data.success && data.count > 0) {
                warning.style.display = 'block';
                warning.innerHTML = `⚠️ <strong>This location has ${data.count} character assignment(s)</strong> - remove assignments first!`;
                if (deleteBtn) deleteBtn.disabled = true;
            } else {
                warning.style.display = 'none';
                if (deleteBtn) deleteBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error checking assignments:', error);
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            if (deleteBtn) deleteBtn.disabled = false;
        });
}

// Modal Functions
function closeLocationModal() {
    const modalElement = document.getElementById('locationModal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}

function closeViewModal() {
    const modalElement = document.getElementById('viewModal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}

function closeAssignModal() {
    const modalElement = document.getElementById('assignModal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}

function closeDeleteModal() {
    const modalElement = document.getElementById('deleteModal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}

// Form Handling
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const isEdit = data.id !== '';
    const url = 'api_admin_locations_crud.php';
    const method = isEdit ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            const modalElement = document.getElementById('locationModal');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            loadLocations();
        } else {
            showNotification('Error: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to save location', 'error');
    }
}

async function assignCharactersToLocation() {
    const selectedCharacters = [];
    
    document.querySelectorAll('.character-item').forEach(item => {
        if (item.classList.contains('selected')) {
            const characterId = item.dataset.characterId;
            const assignmentType = item.querySelector('.assignment-type-select').value;
            selectedCharacters.push({
                character_id: characterId,
                assignment_type: assignmentType
            });
        }
    });
    
    if (selectedCharacters.length === 0) {
        showNotification('Please select at least one character', 'error');
        return;
    }
    
    try {
        const response = await fetch('api_admin_location_assignments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                location_id: currentLocationId,
                assignments: selectedCharacters
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeAssignModal();
        } else {
            showNotification('Error: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to assign characters', 'error');
    }
}

async function confirmDeleteLocation() {
    try {
        const response = await fetch(`api_delete_location_simple.php?id=${currentLocationId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            const modalElement = document.getElementById('deleteModal');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            currentLocationId = null;
            loadLocations();
        } else {
            showNotification('Error: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to delete location', 'error');
    }
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Character selection for assignment modal
document.addEventListener('click', function(e) {
    if (e.target.closest('.character-item')) {
        const item = e.target.closest('.character-item');
        item.classList.toggle('selected');
    }
});

// Bootstrap handles ESC key automatically
