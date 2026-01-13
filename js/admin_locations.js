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
let currentStatusFilter = 'all';
let currentOwnerFilter = 'all';
let currentPCHavenFilter = 'all';
let hideEarnableHavens = false;
let currentSearchTerm = '';
let currentLocationId = null;

// Global data variables (loaded from JSON script tags)
let allCharacters = [];
let allCharactersForLocations = [];
let locationStatuses = [];
let locationOwners = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load data from JSON script tags
    const allCharactersElement = document.getElementById('allCharactersData');
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
            const filterType = this.dataset.filter;
            
            // Special handling for hide earnable button (toggle, doesn't affect other filters)
            if (filterType === 'hide-earnable') {
                hideEarnableHavens = !hideEarnableHavens;
                if (hideEarnableHavens) {
                    this.classList.add('active');
                    this.textContent = 'Show Earnable';
                } else {
                    this.classList.remove('active');
                    this.textContent = 'Hide Earnable';
                }
                applyFilters();
                return;
            }
            
            // Regular filter buttons - only one active at a time
            document.querySelectorAll('.filter-btn').forEach(b => {
                if (b.dataset.filter !== 'hide-earnable') {
                    b.classList.remove('active');
                }
            });
            this.classList.add('active');
            currentFilter = filterType;
            applyFilters();
        });
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

    // Initialize sorting event listeners (only once)
    initializeSorting();
    
    // Form submission handler is attached dynamically when modals are opened
    // (see openAddLocationModal and editLocation functions)
}

function initializeSorting() {
    // Use event delegation on the table to handle sorting
    const table = document.getElementById('locationsTable');
    if (!table) return;
    
    const thead = table.querySelector('thead');
    if (!thead) return;
    
    thead.addEventListener('click', function(e) {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        
        const column = th.dataset.sort;
        const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
        
        // Update sort indicators
        document.querySelectorAll('#locationsTable th[data-sort]').forEach(h => {
            h.classList.remove('sorted-asc', 'sorted-desc');
        });
        th.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
        
        sortTable(column, direction);
    });
}

async function loadLocations() {
    try {
        const response = await fetch('api_locations.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse JSON response:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            allLocations = data.locations;
            applyFilters();
        } else {
            showNotification('Failed to load locations: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error loading locations:', error);
        showNotification('Failed to load locations: ' + error.message, 'error');
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

        // Hide Earnable Havens filter
        if (hideEarnableHavens) {
            const isEarnableHaven = location.type === 'Haven' && (location.pc_haven == 1 || location.pc_haven === true);
            if (isEarnableHaven) return false;
        }

        // Search filter
        if (currentSearchTerm) {
            const searchLower = currentSearchTerm.toLowerCase();
            const nameMatch = location.name.toLowerCase().includes(searchLower);
            const isPCHaven = location.type === 'Haven' && (location.pc_haven == 1 || location.pc_haven === true);
            const earnableMatch = (searchLower.includes('earnable') || searchLower.includes('pc earnable')) && isPCHaven;
            const pcMatch = searchLower === 'pc' && isPCHaven;
            
            if (!nameMatch && !earnableMatch && !pcMatch) return false;
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
    
    filteredLocations.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        // Handle different data types
        if (column === 'id' || column === 'security_level') {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        } else if (column === 'pc_earnable') {
            // For PC Earnable, check if it's a PC Haven
            const aIsPCHaven = a.type === 'Haven' && (a.pc_haven == 1 || a.pc_haven === true);
            const bIsPCHaven = b.type === 'Haven' && (b.pc_haven == 1 || b.pc_haven === true);
            aVal = aIsPCHaven ? 1 : 0;
            bVal = bIsPCHaven ? 1 : 0;
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
        const pcEarnableBadge = isPCHaven 
            ? '<span class="badge bg-warning text-dark" title="PCs can earn this haven">Earnable</span>' 
            : '<span class="opacity-75">—</span>';
        return `
        <tr>
            <td>${location.id}</td>
            <td><strong>${escapeHtml(location.name)}</strong></td>
            <td><span class="badge-${location.type.toLowerCase().replace(' ', '-')}">${escapeHtml(location.type)}</span></td>
            <td><span class="badge-${location.status.toLowerCase()}">${escapeHtml(location.status)}</span></td>
            <td>${escapeHtml(location.district || 'N/A')}</td>
            <td>${escapeHtml(location.owner_type || 'N/A')}</td>
            <td class="text-center">${pcEarnableBadge}</td>
            <td class="actions text-center align-top w-150px">
                <div class="btn-group btn-group-sm" role="group" aria-label="Location actions">
                    <button class="action-btn view-btn btn btn-primary" 
                            onclick="viewLocation(${location.id})" 
                            title="View Location">👁️</button>
                    <button class="action-btn edit-btn btn btn-warning" 
                            onclick="editLocation(${location.id})" 
                            title="Edit Location">✏️</button>
                    <button class="action-btn assign-btn btn btn-info" 
                            onclick="assignLocation(${location.id}, '${escapeHtml(location.name)}')" 
                            title="Assign Characters">🎯</button>
                    <button class="action-btn delete-btn btn btn-danger" 
                            onclick="deleteLocation(${location.id}, '${escapeHtml(location.name)}')" 
                            title="Delete Location">🗑️</button>
                </div>
            </td>
        </tr>
        `;
    }).join('');

    // Update sort indicators based on current sort
    document.querySelectorAll('#locationsTable th[data-sort]').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        if (th.dataset.sort === currentSort.column) {
            th.classList.add(currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
        }
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

// Helper function to safely get field values
function getFieldValue(location, field, defaultValue = '') {
    if (!location) return defaultValue;
    const value = location[field];
    if (value === null || value === undefined) return defaultValue;
    if (typeof value === 'string') return escapeHtml(value);
    return value;
}

function getFieldValueRaw(location, field, defaultValue = '') {
    if (!location) return defaultValue;
    const value = location[field];
    return value === null || value === undefined ? defaultValue : value;
}

function getCheckboxValue(location, field) {
    if (!location) return false;
    const value = location[field];
    return (value == 1 || value === true || value === '1' || value === 'true');
}

// Generate location form HTML
function generateLocationFormHtml(location = null) {
    const locationId = getFieldValueRaw(location, 'id', '');
    const locationName = getFieldValue(location, 'name', '');
    const locationType = getFieldValueRaw(location, 'type', '');
    const locationStatus = getFieldValueRaw(location, 'status', '');
    const locationStatusNotes = getFieldValue(location, 'status_notes', '');
    const locationDistrict = getFieldValue(location, 'district', '');
    const locationAddress = getFieldValue(location, 'address', '');
    const locationLatitude = getFieldValueRaw(location, 'latitude', '');
    const locationLongitude = getFieldValueRaw(location, 'longitude', '');
    const locationOwnerType = getFieldValueRaw(location, 'owner_type', '');
    const locationOwnerNotes = getFieldValue(location, 'owner_notes', '');
    const locationFaction = getFieldValue(location, 'faction', '');
    const locationAccessControl = getFieldValueRaw(location, 'access_control', '');
    const locationAccessNotes = getFieldValue(location, 'access_notes', '');
    const locationSecurityLevel = getFieldValueRaw(location, 'security_level', 3);
    const locationSecurityNotes = getFieldValue(location, 'security_notes', '');
    const locationDescription = getFieldValue(location, 'description', '');
    const locationSummary = getFieldValue(location, 'summary', '');
    const locationNotes = getFieldValue(location, 'notes', '');
    const locationPCHaven = location && locationType === 'Haven' ? getCheckboxValue(location, 'pc_haven') : false;
    const locationParentId = getFieldValueRaw(location, 'parent_location_id', '');
    const locationRelationshipType = getFieldValue(location, 'relationship_type', '');
    const locationRelationshipNotes = getFieldValue(location, 'relationship_notes', '');
    const locationSocialFeatures = getFieldValue(location, 'social_features', '');
    const locationCapacity = getFieldValueRaw(location, 'capacity', '');
    const locationPrestigeLevel = getFieldValueRaw(location, 'prestige_level', '');
    const locationNodePoints = getFieldValueRaw(location, 'node_points', '');
    const locationNodeType = getFieldValue(location, 'node_type', '');
    const locationRitualSpace = getFieldValue(location, 'ritual_space', '');
    const locationMagicalProtection = getFieldValue(location, 'magical_protection', '');
    const locationCursedBlessed = getFieldValue(location, 'cursed_blessed', '');
    const locationImage = getFieldValue(location, 'image', '');
    const locationBlueprint = getFieldValue(location, 'blueprint', '');
    const locationMoodboard = getFieldValue(location, 'moodboard', '');
    
    // Security checkboxes
    const securityLocks = getCheckboxValue(location, 'security_locks');
    const securityAlarms = getCheckboxValue(location, 'security_alarms');
    const securityGuards = getCheckboxValue(location, 'security_guards');
    const securityHiddenEntrance = getCheckboxValue(location, 'security_hidden_entrance');
    const securitySunlightProtected = getCheckboxValue(location, 'security_sunlight_protected');
    const securityWardingRituals = getCheckboxValue(location, 'security_warding_rituals');
    const securityCameras = getCheckboxValue(location, 'security_cameras');
    const securityReinforced = getCheckboxValue(location, 'security_reinforced');
    const hasSupernatural = getCheckboxValue(location, 'has_supernatural');
    
    // Utility checkboxes
    const utilityBloodStorage = getCheckboxValue(location, 'utility_blood_storage');
    const utilityComputers = getCheckboxValue(location, 'utility_computers');
    const utilityLibrary = getCheckboxValue(location, 'utility_library');
    const utilityMedical = getCheckboxValue(location, 'utility_medical');
    const utilityWorkshop = getCheckboxValue(location, 'utility_workshop');
    const utilityHiddenCaches = getCheckboxValue(location, 'utility_hidden_caches');
    const utilityArmory = getCheckboxValue(location, 'utility_armory');
    const utilityCommunications = getCheckboxValue(location, 'utility_communications');
    const utilityNotes = getFieldValue(location, 'utility_notes', '');
    
    return `
        <form id="locationForm" class="needs-validation" novalidate>
            <input type="hidden" id="locationId" name="id" value="${locationId}">
            
            <ul class="nav nav-tabs mb-3" id="locationFormTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ownership-tab" data-bs-toggle="tab" data-bs-target="#ownership" type="button" role="tab">Ownership</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Description</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">Security</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="facilities-tab" data-bs-toggle="tab" data-bs-target="#facilities" type="button" role="tab">Facilities</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="relationships-tab" data-bs-toggle="tab" data-bs-target="#relationships" type="button" role="tab">Relationships</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="supernatural-tab" data-bs-toggle="tab" data-bs-target="#supernatural" type="button" role="tab">Supernatural</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">Social</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="blueprint-tab" data-bs-toggle="tab" data-bs-target="#blueprint" type="button" role="tab">Blueprint</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="moodboard-tab" data-bs-toggle="tab" data-bs-target="#moodboard" type="button" role="tab">Moodboard</button>
                </li>
            </ul>
            
            <div class="tab-content" id="locationFormTabContent">
                <!-- Basic Information Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
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
                        <div class="form-group mb-3 col-12">
                            <label for="locationStatusNotes" class="form-label">Status Notes</label>
                            <textarea id="locationStatusNotes" name="status_notes" class="form-control" rows="2" placeholder="Additional details about the status...">${locationStatusNotes}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-row row g-3">
                        <div class="form-group mb-3 col-12">
                            <label for="locationAddress" class="form-label">Address</label>
                            <input type="text" id="locationAddress" name="address" class="form-control" value="${locationAddress}" placeholder="Physical address or location description">
                        </div>
                    </div>
                    
                    <div class="form-row row g-3">
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationLatitude" class="form-label">Latitude</label>
                            <input type="number" id="locationLatitude" name="latitude" class="form-control" step="any" value="${locationLatitude}" placeholder="e.g., 33.4484">
                        </div>
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationLongitude" class="form-label">Longitude</label>
                            <input type="number" id="locationLongitude" name="longitude" class="form-control" step="any" value="${locationLongitude}" placeholder="e.g., -112.0740">
                        </div>
                    </div>
                    
                    <div class="form-group mb-3" id="pcHavenContainer" style="${locationType === 'Haven' || !location ? '' : 'display:none;'}">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="locationPCHaven" name="pc_haven" value="1" ${locationPCHaven ? 'checked' : ''}>
                            <label class="form-check-label" for="locationPCHaven">
                                Possible PC Haven
                            </label>
                            <small class="form-text opacity-75">Mark this haven as a possible player character haven (only applies to Havens)</small>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationImage" class="form-label">Image URL/Path</label>
                        <input type="text" id="locationImage" name="image" class="form-control" value="${locationImage}" placeholder="URL or path to location image">
                        ${locationImage ? `<div class="mt-2"><img src="${escapeHtml(locationImage)}" alt="Location image" class="img-fluid" style="max-height: 200px;" onerror="this.style.display='none'"></div>` : ''}
                    </div>
                </div>
                
                <!-- Ownership Tab -->
                <div class="tab-pane fade" id="ownership" role="tabpanel">
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
                    
                    <div class="form-group mb-3">
                        <label for="locationOwnerNotes" class="form-label">Owner Notes</label>
                        <textarea id="locationOwnerNotes" name="owner_notes" class="form-control" rows="3" placeholder="Details about the owner...">${locationOwnerNotes}</textarea>
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
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationAccessNotes" class="form-label">Access Notes</label>
                        <textarea id="locationAccessNotes" name="access_notes" class="form-control" rows="3" placeholder="Detailed access control information...">${locationAccessNotes}</textarea>
                    </div>
                </div>
                
                <!-- Description Tab -->
                <div class="tab-pane fade" id="description" role="tabpanel">
                    <div class="form-group mb-3">
                        <label for="locationSummary" class="form-label">Summary</label>
                        <textarea id="locationSummary" name="summary" class="form-control" rows="3" placeholder="Brief summary for quick reference...">${locationSummary}</textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationDescription" class="form-label">Description</label>
                        <textarea id="locationDescription" name="description" class="form-control" rows="8" placeholder="Detailed description of the location...">${locationDescription}</textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationNotes" class="form-label">Notes</label>
                        <textarea id="locationNotes" name="notes" class="form-control" rows="6" placeholder="Additional notes, plot hooks, etc...">${locationNotes}</textarea>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="form-row row g-3 mb-3">
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationSecurityLevel" class="form-label">Security Level</label>
                            <input type="number" id="locationSecurityLevel" name="security_level" class="form-control" min="1" max="10" value="${locationSecurityLevel}">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityLocks" name="security_locks" value="1" ${securityLocks ? 'checked' : ''}>
                                <label class="form-check-label" for="securityLocks">Security Locks</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityAlarms" name="security_alarms" value="1" ${securityAlarms ? 'checked' : ''}>
                                <label class="form-check-label" for="securityAlarms">Security Alarms</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityGuards" name="security_guards" value="1" ${securityGuards ? 'checked' : ''}>
                                <label class="form-check-label" for="securityGuards">Security Guards</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityHiddenEntrance" name="security_hidden_entrance" value="1" ${securityHiddenEntrance ? 'checked' : ''}>
                                <label class="form-check-label" for="securityHiddenEntrance">Hidden Entrance</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securitySunlightProtected" name="security_sunlight_protected" value="1" ${securitySunlightProtected ? 'checked' : ''}>
                                <label class="form-check-label" for="securitySunlightProtected">Sunlight Protected</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityWardingRituals" name="security_warding_rituals" value="1" ${securityWardingRituals ? 'checked' : ''}>
                                <label class="form-check-label" for="securityWardingRituals">Warding Rituals</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityCameras" name="security_cameras" value="1" ${securityCameras ? 'checked' : ''}>
                                <label class="form-check-label" for="securityCameras">Security Cameras</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="securityReinforced" name="security_reinforced" value="1" ${securityReinforced ? 'checked' : ''}>
                                <label class="form-check-label" for="securityReinforced">Reinforced</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationSecurityNotes" class="form-label">Security Notes</label>
                        <textarea id="locationSecurityNotes" name="security_notes" class="form-control" rows="4" placeholder="Detailed security information...">${locationSecurityNotes}</textarea>
                    </div>
                </div>
                
                <!-- Facilities Tab -->
                <div class="tab-pane fade" id="facilities" role="tabpanel">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityBloodStorage" name="utility_blood_storage" value="1" ${utilityBloodStorage ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityBloodStorage">Blood Storage</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityComputers" name="utility_computers" value="1" ${utilityComputers ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityComputers">Computers</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityLibrary" name="utility_library" value="1" ${utilityLibrary ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityLibrary">Library</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityMedical" name="utility_medical" value="1" ${utilityMedical ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityMedical">Medical</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityWorkshop" name="utility_workshop" value="1" ${utilityWorkshop ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityWorkshop">Workshop</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityHiddenCaches" name="utility_hidden_caches" value="1" ${utilityHiddenCaches ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityHiddenCaches">Hidden Caches</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityArmory" name="utility_armory" value="1" ${utilityArmory ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityArmory">Armory</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="utilityCommunications" name="utility_communications" value="1" ${utilityCommunications ? 'checked' : ''}>
                                <label class="form-check-label" for="utilityCommunications">Communications</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="utilityNotes" class="form-label">Utility Notes</label>
                        <textarea id="utilityNotes" name="utility_notes" class="form-control" rows="4" placeholder="Detailed utility information...">${utilityNotes}</textarea>
                    </div>
                </div>
                
                <!-- Relationships Tab -->
                <div class="tab-pane fade" id="relationships" role="tabpanel">
                    <div class="form-row row g-3">
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationParentId" class="form-label">Parent Location ID</label>
                            <input type="number" id="locationParentId" name="parent_location_id" class="form-control" min="0" value="${locationParentId}" placeholder="ID of parent location (if sub-location)">
                        </div>
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationRelationshipType" class="form-label">Relationship Type</label>
                            <input type="text" id="locationRelationshipType" name="relationship_type" class="form-control" value="${locationRelationshipType}" placeholder="e.g., Complex with sub-locations">
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationRelationshipNotes" class="form-label">Relationship Notes</label>
                        <textarea id="locationRelationshipNotes" name="relationship_notes" class="form-control" rows="6" placeholder="Details about relationships with other locations...">${locationRelationshipNotes}</textarea>
                    </div>
                </div>
                
                <!-- Supernatural Tab -->
                <div class="tab-pane fade" id="supernatural" role="tabpanel">
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="hasSupernatural" name="has_supernatural" value="1" ${hasSupernatural ? 'checked' : ''}>
                            <label class="form-check-label" for="hasSupernatural">Has Supernatural Elements</label>
                        </div>
                    </div>
                    
                    <div class="form-row row g-3">
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationNodePoints" class="form-label">Node Points</label>
                            <input type="number" id="locationNodePoints" name="node_points" class="form-control" min="0" value="${locationNodePoints}" placeholder="Node points">
                        </div>
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationNodeType" class="form-label">Node Type</label>
                            <input type="text" id="locationNodeType" name="node_type" class="form-control" value="${locationNodeType}" placeholder="e.g., Shrecknet Major Hub">
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationRitualSpace" class="form-label">Ritual Space</label>
                        <textarea id="locationRitualSpace" name="ritual_space" class="form-control" rows="3" placeholder="Ritual space description...">${locationRitualSpace}</textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationMagicalProtection" class="form-label">Magical Protection</label>
                        <textarea id="locationMagicalProtection" name="magical_protection" class="form-control" rows="3" placeholder="Magical protection details...">${locationMagicalProtection}</textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="locationCursedBlessed" class="form-label">Cursed/Blessed</label>
                        <textarea id="locationCursedBlessed" name="cursed_blessed" class="form-control" rows="3" placeholder="Curses or blessings...">${locationCursedBlessed}</textarea>
                    </div>
                </div>
                
                <!-- Social Tab -->
                <div class="tab-pane fade" id="social" role="tabpanel">
                    <div class="form-group mb-3">
                        <label for="locationSocialFeatures" class="form-label">Social Features</label>
                        <textarea id="locationSocialFeatures" name="social_features" class="form-control" rows="6" placeholder="Social features and gathering spaces...">${locationSocialFeatures}</textarea>
                    </div>
                    
                    <div class="form-row row g-3">
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationCapacity" class="form-label">Capacity</label>
                            <input type="number" id="locationCapacity" name="capacity" class="form-control" min="0" value="${locationCapacity}" placeholder="Maximum capacity">
                        </div>
                        <div class="form-group mb-3 col-12 col-md-6">
                            <label for="locationPrestigeLevel" class="form-label">Prestige Level</label>
                            <input type="number" id="locationPrestigeLevel" name="prestige_level" class="form-control" min="0" value="${locationPrestigeLevel}" placeholder="Prestige level">
                        </div>
                    </div>
                </div>
                
                <!-- Blueprint Tab -->
                <div class="tab-pane fade" id="blueprint" role="tabpanel">
                    <div class="form-group mb-3">
                        <label for="locationBlueprint" class="form-label">Blueprint URL/Path</label>
                        <div class="input-group">
                            <input type="text" id="locationBlueprint" name="blueprint" class="form-control" value="${locationBlueprint}" placeholder="reference/Locations/...">
                            <button type="button" class="btn btn-outline-secondary" onclick="openFileBrowser('locationBlueprint')">Browse Files</button>
                        </div>
                        <div class="mt-2">Select a file from reference/Locations or enter the path manually.</div>
                    </div>
                    <div id="fileBrowserBlueprint" class="file-browser-container" style="display: none;">
                        <div class="card bg-dark border-danger">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Browse Files: reference/Locations</span>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="closeFileBrowser('blueprint')">Close</button>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div id="fileBrowserBlueprintContent">
                                    <div class="text-center">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${locationBlueprint ? `<div class="mt-3"><img src="${escapeHtml(locationBlueprint)}" alt="Location blueprint" class="img-fluid" style="max-height: 400px; width: 100%; object-fit: contain;" onerror="this.style.display='none'"></div>` : ''}
                </div>
                
                <!-- Moodboard Tab -->
                <div class="tab-pane fade" id="moodboard" role="tabpanel">
                    <div class="form-group mb-3">
                        <label for="locationMoodboard" class="form-label">Moodboard URL/Path</label>
                        <div class="input-group">
                            <input type="text" id="locationMoodboard" name="moodboard" class="form-control" value="${locationMoodboard}" placeholder="reference/Locations/...">
                            <button type="button" class="btn btn-outline-secondary" onclick="openFileBrowser('locationMoodboard')">Browse Files</button>
                        </div>
                        <div class="mt-2">Select a file from reference/Locations or enter the path manually.</div>
                    </div>
                    <div id="fileBrowserMoodboard" class="file-browser-container" style="display: none;">
                        <div class="card bg-dark border-danger">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Browse Files: reference/Locations</span>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="closeFileBrowser('moodboard')">Close</button>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div id="fileBrowserMoodboardContent">
                                    <div class="text-center">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${locationMoodboard ? `<div class="mt-3"><img src="${escapeHtml(locationMoodboard)}" alt="Location moodboard" class="img-fluid" style="max-height: 400px; width: 100%; object-fit: contain;" onerror="this.style.display='none'"></div>` : ''}
                </div>
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
    
    // Attach fullscreen button handler - use event delegation on modal element
    const attachFullscreenHandler = function() {
        const fullscreenBtn = modalElement.querySelector('.modal-fullscreen-btn');
        if (!fullscreenBtn) return;
        
        if (fullscreenBtn.dataset.listenerAttached === 'true') return;
        
        const fullscreenIcon = fullscreenBtn.querySelector('.modal-fullscreen-icon');
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isFullscreen = modalElement.classList.contains('fullscreen');
            if (isFullscreen) {
                modalElement.classList.remove('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-expand modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            } else {
                modalElement.classList.add('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-compress modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        });
        fullscreenBtn.dataset.listenerAttached = 'true';
    };
    
    // Attach handler when modal is shown
    modalElement.addEventListener('shown.bs.modal', attachFullscreenHandler, { once: false });
    
    // Also try to attach immediately (in case modal is already in DOM)
    attachFullscreenHandler();
    
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
    
    modalTitle.textContent = `🏠 Edit Location: ${escapeHtml(location.name)}`;
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
    
    // Attach fullscreen button handler - use event delegation on modal element
    const attachFullscreenHandler = function() {
        const fullscreenBtn = modalElement.querySelector('.modal-fullscreen-btn');
        if (!fullscreenBtn) return;
        
        if (fullscreenBtn.dataset.listenerAttached === 'true') return;
        
        const fullscreenIcon = fullscreenBtn.querySelector('.modal-fullscreen-icon');
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isFullscreen = modalElement.classList.contains('fullscreen');
            if (isFullscreen) {
                modalElement.classList.remove('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-expand modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            } else {
                modalElement.classList.add('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-compress modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        });
        fullscreenBtn.dataset.listenerAttached = 'true';
    };
    
    // Attach handler when modal is shown
    modalElement.addEventListener('shown.bs.modal', attachFullscreenHandler, { once: false });
    
    // Also try to attach immediately (in case modal is already in DOM)
    attachFullscreenHandler();
    
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
    
    // Attach fullscreen button handler - use event delegation on modal element
    const attachFullscreenHandler = function() {
        const fullscreenBtn = modalElement.querySelector('.modal-fullscreen-btn');
        if (!fullscreenBtn) return;
        
        if (fullscreenBtn.dataset.listenerAttached === 'true') return;
        
        const fullscreenIcon = fullscreenBtn.querySelector('.modal-fullscreen-icon');
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isFullscreen = modalElement.classList.contains('fullscreen');
            if (isFullscreen) {
                modalElement.classList.remove('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-expand modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            } else {
                modalElement.classList.add('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-compress modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        });
        fullscreenBtn.dataset.listenerAttached = 'true';
    };
    
    // Attach handler when modal is shown
    modalElement.addEventListener('shown.bs.modal', attachFullscreenHandler, { once: false });
    
    // Also try to attach immediately (in case modal is already in DOM)
    attachFullscreenHandler();
    
    modalInstance.show();
    
    try {
        // Fetch character assignments
        const response = await fetch(`api_admin_location_assignments.php?location_id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        
        if (!responseText || responseText.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Response text:', responseText);
            throw new Error(`Invalid JSON response: ${parseError.message}`);
        }
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load assignments');
        }
        
        let assignmentsHtml = '';
        if (data.assignments && data.assignments.length > 0) {
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
                                    <span class="assignment-badge assignment-${(assignment.ownership_type || 'Resident').toLowerCase().replace(/\s+/g, '-')}">
                                        ${escapeHtml(assignment.ownership_type || 'Resident')}
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
        
        // Helper to display field - always shows, even if empty
        function displayField(label, value, format = 'text') {
            let displayValue = '';
            if (value === null || value === undefined || value === '') {
                displayValue = '<em class="text-light">Not set</em>';
            } else {
                if (format === 'badge') {
                    return `<p><strong>${label}:</strong> <span class="badge-${String(value).toLowerCase().replace(/\s+/g, '-')}">${escapeHtml(value)}</span></p>`;
                }
                if (format === 'number') {
                    displayValue = String(value);
                } else if (format === 'boolean') {
                    displayValue = (value == 1 || value === true) ? 'Yes' : 'No';
                } else {
                    displayValue = escapeHtml(String(value));
                }
            }
            return `<p><strong>${label}:</strong> ${displayValue}</p>`;
        }
        
        // Helper to display textarea field - always shows
        function displayTextarea(label, value) {
            const displayValue = (value === null || value === undefined || value === '') 
                ? '<em class="text-light">Not set</em>' 
                : escapeHtml(value);
            return `<p><strong>${label}:</strong></p><p>${displayValue}</p>`;
        }
        
        function displayCheckboxList(label, items) {
            const checked = items.filter(item => item.checked);
            if (checked.length === 0) return '';
            return `<p><strong>${label}:</strong> ${checked.map(item => escapeHtml(item.label)).join(', ')}</p>`;
        }
        
        const securityFeatures = [
            { field: 'security_locks', label: 'Security Locks', checked: location.security_locks == 1 },
            { field: 'security_alarms', label: 'Security Alarms', checked: location.security_alarms == 1 },
            { field: 'security_guards', label: 'Security Guards', checked: location.security_guards == 1 },
            { field: 'security_hidden_entrance', label: 'Hidden Entrance', checked: location.security_hidden_entrance == 1 },
            { field: 'security_sunlight_protected', label: 'Sunlight Protected', checked: location.security_sunlight_protected == 1 },
            { field: 'security_warding_rituals', label: 'Warding Rituals', checked: location.security_warding_rituals == 1 },
            { field: 'security_cameras', label: 'Security Cameras', checked: location.security_cameras == 1 },
            { field: 'security_reinforced', label: 'Reinforced', checked: location.security_reinforced == 1 }
        ].filter(item => item.checked);
        
        const utilities = [
            { field: 'utility_blood_storage', label: 'Blood Storage', checked: location.utility_blood_storage == 1 },
            { field: 'utility_computers', label: 'Computers', checked: location.utility_computers == 1 },
            { field: 'utility_library', label: 'Library', checked: location.utility_library == 1 },
            { field: 'utility_medical', label: 'Medical', checked: location.utility_medical == 1 },
            { field: 'utility_workshop', label: 'Workshop', checked: location.utility_workshop == 1 },
            { field: 'utility_hidden_caches', label: 'Hidden Caches', checked: location.utility_hidden_caches == 1 },
            { field: 'utility_armory', label: 'Armory', checked: location.utility_armory == 1 },
            { field: 'utility_communications', label: 'Communications', checked: location.utility_communications == 1 }
        ].filter(item => item.checked);
        
        // Helper to display image with link
        function displayImage(label, imagePath) {
            if (!imagePath) {
                return `<p><strong>${label}:</strong> <em class="text-light">Not set</em></p>`;
            }
            
            // Convert relative paths to web-accessible URLs and properly encode
            let imageUrl = imagePath;
            if (imagePath.startsWith('reference/')) {
                imageUrl = '../' + imagePath;
            }
            
            // URL encode the path (spaces become %20, etc.)
            // Split by /, encode each part, then rejoin
            const urlParts = imageUrl.split('/').map(part => encodeURIComponent(part));
            const encodedUrl = urlParts.join('/');
            
            const containerId = 'img-' + label.toLowerCase().replace(/\s+/g, '-') + '-' + Math.random().toString(36).substr(2, 9);
            const errorPath = escapeHtml(imagePath);
            
            return `
                <div class="mb-3">
                    <p><strong>${label}:</strong></p>
                    <div class="mt-3 text-center" id="${containerId}">
                        <img src="${encodedUrl}" alt="${escapeHtml(label)}" class="img-fluid" style="max-height: 600px; width: 100%; object-fit: contain; border: 2px solid rgba(139, 0, 0, 0.4); border-radius: 8px; background: rgba(26, 15, 15, 0.3);" onerror="this.onerror=null; const c=document.getElementById('${containerId}'); if(c) c.innerHTML='<div class=\\'p-3\\' style=\\'border: 2px solid rgba(139, 0, 0, 0.4); border-radius: 8px; background: rgba(139, 0, 0, 0.1);\\'><p class=\\'text-light\\'>Failed to load image</p><p class=\\'text-light\\'>${errorPath}</p></div>'">
                    </div>
                    <div class="mt-2 text-center">
                        <div class="text-light"><a href="${encodedUrl}" target="_blank" class="text-primary">Open image in new tab</a> | ${escapeHtml(imagePath)}</div>
                    </div>
                </div>
            `;
        }
        
        const content = `
            ${assignmentsHtml}
            
            <ul class="nav nav-tabs mb-3" id="viewLocationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="view-basic-tab" data-bs-toggle="tab" data-bs-target="#view-basic" type="button" role="tab">Basic</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-ownership-tab" data-bs-toggle="tab" data-bs-target="#view-ownership" type="button" role="tab">Ownership</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-description-tab" data-bs-toggle="tab" data-bs-target="#view-description" type="button" role="tab">Description</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-security-tab" data-bs-toggle="tab" data-bs-target="#view-security" type="button" role="tab">Security</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-facilities-tab" data-bs-toggle="tab" data-bs-target="#view-facilities" type="button" role="tab">Facilities</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-relationships-tab" data-bs-toggle="tab" data-bs-target="#view-relationships" type="button" role="tab">Relationships</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-supernatural-tab" data-bs-toggle="tab" data-bs-target="#view-supernatural" type="button" role="tab">Supernatural</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-social-tab" data-bs-toggle="tab" data-bs-target="#view-social" type="button" role="tab">Social</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-blueprint-tab" data-bs-toggle="tab" data-bs-target="#view-blueprint" type="button" role="tab">Blueprint</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="view-moodboard-tab" data-bs-toggle="tab" data-bs-target="#view-moodboard" type="button" role="tab">Moodboard</button>
                </li>
            </ul>
            
            <div class="tab-content" id="viewLocationTabContent">
                <!-- Basic Information Tab -->
                <div class="tab-pane fade show active" id="view-basic" role="tabpanel">
                    <div class="view-section">
                        ${displayField('Name', location.name)}
                        ${displayField('Type', location.type, 'badge')}
                        ${displayField('Status', location.status, 'badge')}
                        ${displayField('Status Notes', location.status_notes)}
                        ${displayField('District', location.district)}
                        ${displayField('Address', location.address)}
                        ${displayField('Latitude', location.latitude, 'number')}
                        ${displayField('Longitude', location.longitude, 'number')}
                        ${displayField('PC Earnable Haven', location.pc_haven, 'boolean')}
                        ${displayImage('Image', location.image)}
                    </div>
                </div>
                
                <!-- Ownership Tab -->
                <div class="tab-pane fade" id="view-ownership" role="tabpanel">
                    <div class="view-section">
                        ${displayField('Owner Type', location.owner_type)}
                        ${displayTextarea('Owner Notes', location.owner_notes)}
                        ${displayField('Faction', location.faction)}
                        ${displayField('Access Control', location.access_control)}
                        ${displayTextarea('Access Notes', location.access_notes)}
                    </div>
                </div>
                
                <!-- Description Tab -->
                <div class="tab-pane fade" id="view-description" role="tabpanel">
                    <div class="view-section">
                        ${displayTextarea('Summary', location.summary)}
                        ${displayTextarea('Description', location.description)}
                        ${displayTextarea('Notes', location.notes)}
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="view-security" role="tabpanel">
                    <div class="view-section">
                        ${displayField('Security Level', location.security_level, 'number')}
                        <p><strong>Security Features:</strong></p>
                        <ul>
                            ${securityFeatures.length > 0 ? securityFeatures.map(f => `<li>${escapeHtml(f.label)}</li>`).join('') : '<li><em class="text-light">None</em></li>'}
                        </ul>
                        ${displayTextarea('Security Notes', location.security_notes)}
                    </div>
                </div>
                
                <!-- Facilities Tab -->
                <div class="tab-pane fade" id="view-facilities" role="tabpanel">
                    <div class="view-section">
                        <p><strong>Available Utilities:</strong></p>
                        <ul>
                            ${utilities.length > 0 ? utilities.map(u => `<li>${escapeHtml(u.label)}</li>`).join('') : '<li><em class="text-light">None</em></li>'}
                        </ul>
                        ${displayTextarea('Utility Notes', location.utility_notes)}
                    </div>
                </div>
                
                <!-- Relationships Tab -->
                <div class="tab-pane fade" id="view-relationships" role="tabpanel">
                    <div class="view-section">
                        ${displayField('Parent Location ID', location.parent_location_id, 'number')}
                        ${displayField('Relationship Type', location.relationship_type)}
                        ${displayTextarea('Relationship Notes', location.relationship_notes)}
                    </div>
                </div>
                
                <!-- Supernatural Tab -->
                <div class="tab-pane fade" id="view-supernatural" role="tabpanel">
                    <div class="view-section">
                        ${displayField('Has Supernatural Elements', location.has_supernatural, 'boolean')}
                        ${displayField('Node Points', location.node_points, 'number')}
                        ${displayField('Node Type', location.node_type)}
                        ${displayTextarea('Ritual Space', location.ritual_space)}
                        ${displayTextarea('Magical Protection', location.magical_protection)}
                        ${displayTextarea('Cursed/Blessed', location.cursed_blessed)}
                    </div>
                </div>
                
                <!-- Social Tab -->
                <div class="tab-pane fade" id="view-social" role="tabpanel">
                    <div class="view-section">
                        ${displayTextarea('Social Features', location.social_features)}
                        ${displayField('Capacity', location.capacity, 'number')}
                        ${displayField('Prestige Level', location.prestige_level, 'number')}
                    </div>
                </div>
                
                <!-- Blueprint Tab -->
                <div class="tab-pane fade" id="view-blueprint" role="tabpanel">
                    <div class="view-section">
                        ${displayImage('Blueprint', location.blueprint)}
                    </div>
                </div>
                
                <!-- Moodboard Tab -->
                <div class="tab-pane fade" id="view-moodboard" role="tabpanel">
                    <div class="view-section">
                        ${displayImage('Moodboard', location.moodboard)}
                    </div>
                </div>
            </div>
            
            <div class="view-section mt-4">
                <h3>Metadata</h3>
                ${displayField('Created At', location.created_at)}
                ${displayField('Updated At', location.updated_at)}
            </div>
        `;
        
        modalBody.innerHTML = content;
        modalBody.setAttribute('aria-busy','false');
        
    } catch (error) {
        console.error('Error loading location details:', error);
        modalBody.innerHTML = `
            <div class="view-section">
                <h3>Error</h3>
                <p>Failed to load location details: ${escapeHtml(error.message || 'Unknown error')}</p>
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
    
    // Attach fullscreen button handler - use event delegation on modal element
    const attachFullscreenHandler = function() {
        const fullscreenBtn = modalElement.querySelector('.modal-fullscreen-btn');
        if (!fullscreenBtn) return;
        
        if (fullscreenBtn.dataset.listenerAttached === 'true') return;
        
        const fullscreenIcon = fullscreenBtn.querySelector('.modal-fullscreen-icon');
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isFullscreen = modalElement.classList.contains('fullscreen');
            if (isFullscreen) {
                modalElement.classList.remove('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-expand modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            } else {
                modalElement.classList.add('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-compress modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        });
        fullscreenBtn.dataset.listenerAttached = 'true';
    };
    
    // Attach handler when modal is shown
    modalElement.addEventListener('shown.bs.modal', attachFullscreenHandler, { once: false });
    
    // Also try to attach immediately (in case modal is already in DOM)
    attachFullscreenHandler();
    
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
    
    // Attach fullscreen button handler - use event delegation on modal element
    const attachFullscreenHandler = function() {
        const fullscreenBtn = modalElement.querySelector('.modal-fullscreen-btn');
        if (!fullscreenBtn) return;
        
        if (fullscreenBtn.dataset.listenerAttached === 'true') return;
        
        const fullscreenIcon = fullscreenBtn.querySelector('.modal-fullscreen-icon');
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isFullscreen = modalElement.classList.contains('fullscreen');
            if (isFullscreen) {
                modalElement.classList.remove('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-expand modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            } else {
                modalElement.classList.add('fullscreen');
                if (fullscreenIcon) {
                    fullscreenIcon.className = 'fas fa-compress modal-fullscreen-icon';
                }
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        });
        fullscreenBtn.dataset.listenerAttached = 'true';
    };
    
    // Attach handler when modal is shown
    modalElement.addEventListener('shown.bs.modal', attachFullscreenHandler, { once: false });
    
    // Also try to attach immediately (in case modal is already in DOM)
    attachFullscreenHandler();
    
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
    
    // Ensure boolean fields are explicitly set (0 if checkbox is unchecked)
    const booleanFields = ['pc_haven', 'security_locks', 'security_alarms', 'security_guards', 
                          'security_hidden_entrance', 'security_sunlight_protected', 'security_warding_rituals',
                          'security_cameras', 'security_reinforced', 'utility_blood_storage', 'utility_computers',
                          'utility_library', 'utility_medical', 'utility_workshop', 'utility_hidden_caches',
                          'utility_armory', 'utility_communications', 'has_supernatural'];
    
    booleanFields.forEach(field => {
        if (!data.hasOwnProperty(field)) {
            data[field] = '0';
        }
    });
    
    // Convert empty strings to null for optional numeric fields
    const numericFields = ['latitude', 'longitude', 'security_level', 'capacity', 'prestige_level', 
                          'node_points', 'parent_location_id'];
    numericFields.forEach(field => {
        if (data[field] === '' || data[field] === null || data[field] === undefined) {
            data[field] = null;
        } else {
            data[field] = data[field] !== null ? Number(data[field]) : null;
        }
    });
    
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
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP error:', response.status, errorText);
            throw new Error(`Server error: ${response.status}`);
        }
        
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
            const errorMsg = result.error || 'Unknown error occurred';
            console.error('API error:', errorMsg);
            showNotification('Error: ' + errorMsg, 'error');
        }
    } catch (error) {
        console.error('Error saving location:', error);
        showNotification('Failed to save location: ' + error.message, 'error');
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

// File Browser Functions
let currentFileBrowserPath = {};
let currentFileBrowserType = null;

async function openFileBrowser(inputId) {
    const type = inputId === 'locationBlueprint' ? 'blueprint' : 'moodboard';
    currentFileBrowserType = type;
    currentFileBrowserPath[type] = '';
    
    const browserContainer = document.getElementById(`fileBrowser${type.charAt(0).toUpperCase() + type.slice(1)}`);
    const browserContent = document.getElementById(`fileBrowser${type.charAt(0).toUpperCase() + type.slice(1)}Content`);
    
    if (browserContainer && browserContent) {
        browserContainer.style.display = 'block';
        await loadFileBrowser(type, '');
    }
}

function closeFileBrowser(type) {
    const browserContainer = document.getElementById(`fileBrowser${type.charAt(0).toUpperCase() + type.slice(1)}`);
    if (browserContainer) {
        browserContainer.style.display = 'none';
    }
}

async function loadFileBrowser(type, path) {
    const browserContent = document.getElementById(`fileBrowser${type.charAt(0).toUpperCase() + type.slice(1)}Content`);
    if (!browserContent) return;
    
    browserContent.innerHTML = '<div class="text-center">Loading...</div>';
    
    try {
        const url = `api_list_location_files.php${path ? '?path=' + encodeURIComponent(path) : ''}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            currentFileBrowserPath[type] = path;
            renderFileBrowser(type, data, browserContent);
        } else {
            browserContent.innerHTML = `<div class="text-danger">Error: ${escapeHtml(data.error || 'Failed to load files')}</div>`;
        }
    } catch (error) {
        console.error('Error loading files:', error);
        browserContent.innerHTML = `<div class="text-danger">Error: ${escapeHtml(error.message)}</div>`;
    }
}

function renderFileBrowser(type, data, container) {
    let html = '';
    
    // Breadcrumb navigation
    const pathParts = data.path ? data.path.split('/') : [];
    html += '<div class="mb-3">';
    html += '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadFileBrowser(\'' + type + '\', \'\')">📁 reference/Locations</button>';
    let currentPath = '';
    pathParts.forEach((part, index) => {
        currentPath += (currentPath ? '/' : '') + part;
        html += ' <span class="mx-1">/</span> ';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadFileBrowser(\'' + type + '\', \'' + escapeHtml(currentPath) + '\')">' + escapeHtml(part) + '</button>';
    });
    html += '</div>';
    
    // Directories
    if (data.directories.length > 0) {
        html += '<div class="mb-3"><strong>Directories:</strong></div>';
        html += '<div class="list-group mb-3">';
        data.directories.forEach(dir => {
            html += `<button type="button" class="list-group-item list-group-item-action bg-dark text-light border-secondary" onclick="loadFileBrowser('${type}', '${escapeHtml(dir.path)}')">`;
            html += '📁 ' + escapeHtml(dir.name);
            html += '</button>';
        });
        html += '</div>';
    }
    
    // Files
    if (data.files.length > 0) {
        html += '<div class="mb-3"><strong>Files:</strong></div>';
        html += '<div class="list-group">';
        data.files.forEach(file => {
            const fullPath = 'reference/Locations/' + file.path;
            html += `<button type="button" class="list-group-item list-group-item-action bg-dark text-light border-secondary" onclick="selectFile('${type}', '${escapeHtml(fullPath)}')">`;
            html += '🖼️ ' + escapeHtml(file.name);
            html += '</button>';
        });
        html += '</div>';
    } else if (data.directories.length === 0) {
        html += '<div class="text-center text-light">No files found in this directory.</div>';
    }
    
    container.innerHTML = html;
}

function selectFile(type, filePath) {
    const inputId = type === 'blueprint' ? 'locationBlueprint' : 'locationMoodboard';
    const input = document.getElementById(inputId);
    
    if (input) {
        input.value = filePath;
        closeFileBrowser(type);
        
        // Trigger change event to update preview if needed
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
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
