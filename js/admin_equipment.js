/**
 * Admin Equipment Management JavaScript
 * Handles table operations, CRUD, filtering, and character assignment with toggle behavior
 */

let allEquipment = [];
let filteredEquipment = [];
let currentPage = 1;
let equipmentPerPage = 20;
let currentSort = { column: 'id', direction: 'asc' };
let currentFilter = 'all';
let currentTypeFilter = 'all';
let currentRarityFilter = 'all';
let currentSearchTerm = '';
let currentEquipmentId = null;
let assignedCharacterIds = new Set(); // Track which characters have the current equipment

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    setupAccessibleModals();
    loadEquipment();
});

// Accessible modal helpers (focus trap, restore focus, ESC)
let lastActiveElement = null;
function getFocusable(container) {
    return Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
        .filter(el => el.offsetParent !== null || el.getAttribute('aria-hidden') !== 'true');
}
function trapFocus(modal) {
    console.log('trapFocus called for modal:', modal.id);
    function onKeyDown(e) {
        if (e.key === 'Tab') {
            const list = getFocusable(modal);
            if (list.length === 0) { e.preventDefault(); return; }
            const first = list[0];
            const last = list[list.length - 1];
            if (document.activeElement === modal) {
                e.preventDefault();
                if (e.shiftKey) { last.focus(); } else { first.focus(); }
                return;
            }
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        } else if (e.key === 'Escape') {
            closeAnyOpenModal();
        }
    }
    modal.__trapHandler = onKeyDown;
    document.addEventListener('keydown', onKeyDown);
    console.log('Focus trap event listener added');
}
function releaseFocus(modal) {
    if (modal && modal.__trapHandler) {
        document.removeEventListener('keydown', modal.__trapHandler);
        delete modal.__trapHandler;
    }
    if (lastActiveElement) {
        try { lastActiveElement.focus(); } catch (_) {}
        lastActiveElement = null;
    }
}
function openModalA11y(modalId) {
    console.log('openModalA11y called with:', modalId);
    const modal = document.getElementById(modalId);
    console.log('Modal element found:', !!modal);
    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }
    lastActiveElement = document.activeElement;
    console.log('Last active element saved');
    
    // Blur active element before setting aria-hidden to avoid warnings
    if (document.activeElement && document.activeElement.blur) {
        try {
            document.activeElement.blur();
            console.log('Active element blurred');
        } catch (_) {}
    }
    
    // aria-hide siblings at this level
    const parent = modal.parentElement;
    console.log('Parent element:', !!parent);
    if (parent) {
      const siblings = Array.from(parent.children).filter(ch => ch !== modal);
      console.log('Siblings count:', siblings.length);
      siblings.forEach(el => {
        if (!el.hasAttribute('data-aria-hidden-was')) {
          el.setAttribute('data-aria-hidden-was', el.getAttribute('aria-hidden') || '');
        }
        el.setAttribute('aria-hidden','true');
        el.setAttribute('inert','');
      });
      modal.setAttribute('aria-hidden','false');
      modal.removeAttribute('inert');
      console.log('Aria attributes set');
    }
    modal.classList.add('active');
    console.log('Active class added');
    if (!modal.hasAttribute('tabindex')) modal.setAttribute('tabindex','-1');
    try { 
        modal.focus(); 
        console.log('Modal focused');
    } catch (_) {}
    console.log('About to call trapFocus');
    trapFocus(modal);
    console.log('trapFocus called');
}
function closeModalA11y(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('active');
    const parent = modal.parentElement;
    if (parent) {
      const siblings = Array.from(parent.children).filter(ch => ch !== modal);
      siblings.forEach(el => {
        const prev = el.getAttribute('data-aria-hidden-was');
        if (prev !== null) {
          if (prev === '' ) { el.removeAttribute('aria-hidden'); }
          else { el.setAttribute('aria-hidden', prev); }
          el.removeAttribute('data-aria-hidden-was');
        } else {
          el.removeAttribute('aria-hidden');
        }
        el.removeAttribute('inert');
      });
    }
    releaseFocus(modal);
}
function closeAnyOpenModal() {
    ['equipmentModal','viewModal','assignModal','deleteModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.classList.contains('active')) {
            closeModalA11y(id);
        }
    });
}
function setupAccessibleModals() {
    // Close when clicking on background (optional, if your CSS supports it)
    ['equipmentModal','viewModal','assignModal','deleteModal'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('mousedown', (e) => {
            if (e.target === el) closeModalA11y(id);
        });
    });
    // Ensure close buttons have accessible labels
    document.querySelectorAll('.modal-close').forEach(btn => {
        if (!btn.getAttribute('aria-label')) {
            btn.setAttribute('aria-label', 'Close dialog');
        }
    });
}

function initializeEventListeners() {
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

    // Rarity filter
    document.getElementById('rarityFilter').addEventListener('change', function() {
        currentRarityFilter = this.value;
        applyFilters();
    });

    // Search
    document.getElementById('equipmentSearch').addEventListener('input', function() {
        currentSearchTerm = this.value.toLowerCase();
        applyFilters();
    });

    // Page size
    document.getElementById('pageSize').addEventListener('change', function() {
        equipmentPerPage = parseInt(this.value);
        currentPage = 1;
        applyFilters();
    });

    // Form submission
    document.getElementById('equipmentForm').addEventListener('submit', handleFormSubmit);
}

async function loadEquipment() {
    try {
        const response = await fetch('api_equipment.php');
        const data = await response.json();
        
        if (data.success) {
            allEquipment = data.items || data.equipment || [];
            applyFilters();
        } else {
            showNotification('Failed to load equipment: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error loading equipment:', error);
        showNotification('Failed to load equipment', 'error');
    }
}

function applyFilters() {
    filteredEquipment = allEquipment.filter(item => {
        // Type filter
        if (currentFilter !== 'all') {
            if (currentFilter === 'weapons' && item.type !== 'Weapon') return false;
            if (currentFilter === 'armor' && item.type !== 'Armor') return false;
            if (currentFilter === 'tools' && item.type !== 'Tool') return false;
            if (currentFilter === 'consumables' && item.type !== 'Consumable') return false;
            if (currentFilter === 'artifacts' && item.type !== 'Artifact') return false;
        }

        // Type dropdown filter
        if (currentTypeFilter !== 'all' && item.type !== currentTypeFilter) return false;

        // Rarity filter
        if (currentRarityFilter !== 'all' && item.rarity !== currentRarityFilter) return false;

        // Search filter
        if (currentSearchTerm && !item.name.toLowerCase().includes(currentSearchTerm)) return false;

        return true;
    });

    // Apply sorting
    sortEquipment();
    
    // Reset to first page
    currentPage = 1;
    
    // Render table
    renderTable();
    renderPagination();
}

function sortEquipment() {
    filteredEquipment.sort((a, b) => {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];

        // Handle numeric columns
        if (['id', 'price'].includes(currentSort.column)) {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        }

        // Handle string columns
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (currentSort.direction === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
}

function renderTable() {
    const tbody = document.querySelector('#equipmentTable tbody');
    const startIndex = (currentPage - 1) * equipmentPerPage;
    const endIndex = startIndex + equipmentPerPage;
    const pageItems = filteredEquipment.slice(startIndex, endIndex);

    if (pageItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No equipment found.</td></tr>';
        return;
    }

    tbody.innerHTML = pageItems.map(item => `
        <tr class="equipment-row" data-type="${item.type.toLowerCase()}" data-rarity="${item.rarity}">
            <td>${item.id}</td>
            <td><strong>${escapeHtml(item.name)}</strong></td>
            <td><span class="badge-${getTypeClass(item.type)}">${escapeHtml(item.type)}</span></td>
            <td>${escapeHtml(item.category)}</td>
            <td>${escapeHtml(item.damage || 'N/A')}</td>
            <td>${escapeHtml(item.range || 'N/A')}</td>
            <td><span class="badge-${item.rarity}">${escapeHtml(item.rarity)}</span></td>
            <td>$${parseInt(item.price).toLocaleString()}</td>
            <td>${formatDate(item.created_at)}</td>
            <td class="actions">
                <button class="action-btn view-btn" onclick="viewEquipment(${item.id})" title="View Equipment">👁️</button>
                <button class="action-btn edit-btn" onclick="editEquipment(${item.id})" title="Edit Equipment">✏️</button>
                <button class="action-btn assign-btn" onclick="openAssignModal(${item.id}, '${escapeHtml(item.name)}')" title="Assign to Characters">🎯</button>
                <button class="action-btn delete-btn" onclick="deleteEquipment(${item.id}, '${escapeHtml(item.name)}')" title="Delete Equipment">🗑️</button>
            </td>
        </tr>
    `).join('');

    // Add sorting event listeners
    document.querySelectorAll('#equipmentTable th[data-sort]').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.sort;
            
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            // Update sort indicators
            document.querySelectorAll('#equipmentTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(`sorted-${currentSort.direction}`);

            sortEquipment();
            renderTable();
        });
    });
}

function renderPagination() {
    const totalPages = Math.ceil(filteredEquipment.length / equipmentPerPage);
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    if (totalPages <= 1) {
        paginationInfo.textContent = `Showing ${filteredEquipment.length} equipment`;
        paginationButtons.innerHTML = '';
        return;
    }

    const startItem = (currentPage - 1) * equipmentPerPage + 1;
    const endItem = Math.min(currentPage * equipmentPerPage, filteredEquipment.length);
    paginationInfo.textContent = `Showing ${startItem}-${endItem} of ${filteredEquipment.length} equipment`;

    let buttons = '';
    
    // Previous button
    if (currentPage > 1) {
        buttons += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">‹ Previous</button>`;
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        buttons += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            buttons += `<span style="padding: 8px 12px; color: #b8a090;">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        buttons += `<button class="page-btn ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            buttons += `<span style="padding: 8px 12px; color: #b8a090;">...</span>`;
        }
        buttons += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }

    // Next button
    if (currentPage < totalPages) {
        buttons += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">Next ›</button>`;
    }

    paginationButtons.innerHTML = buttons;
}

function goToPage(page) {
    currentPage = page;
    renderTable();
    renderPagination();
}

// CRUD Operations
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    // Clean up data
    Object.keys(data).forEach(key => {
        if (data[key] === '') data[key] = null;
    });

    // Convert formatted requirements back to JSON
    if (data.requirements && data.requirements.trim()) {
        try {
            // First try parsing as JSON (in case user pasted JSON)
            JSON.parse(data.requirements);
            // If successful, it's already JSON, keep it
        } catch (error) {
            // Not JSON, try to parse formatted text back to JSON
            try {
                const parsed = parseRequirementsFromText(data.requirements);
                data.requirements = JSON.stringify(parsed);
            } catch (parseError) {
                showNotification('Invalid requirements format. Use: attribute: value, attribute2: value2', 'error');
                return;
            }
        }
    } else {
        data.requirements = null;
    }

    const isEdit = data.id && data.id !== '';
    const url = 'api_admin_equipment_crud.php';
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            closeEquipmentModal();
            loadEquipment(); // Reload equipment
        } else {
            showNotification(result.message || 'Failed to save equipment', 'error');
        }
    } catch (error) {
        console.error('Error saving equipment:', error);
        showNotification('Failed to save equipment', 'error');
    }
}

function openAddEquipmentModal() {
    console.log('openAddEquipmentModal called');
    document.getElementById('equipmentModalTitle').textContent = 'Add New Equipment';
    document.getElementById('equipmentForm').reset();
    document.getElementById('equipmentId').value = '';
    document.getElementById('assignEquipmentBtn').style.display = 'none';
    openModalA11y('equipmentModal');
}

function editEquipment(equipmentId) {
    console.log('editEquipment called with id:', equipmentId);
    const item = allEquipment.find(i => i.id == equipmentId);
    if (!item) return;

    document.getElementById('equipmentModalTitle').textContent = 'Edit Equipment';
    document.getElementById('equipmentId').value = item.id;
    document.getElementById('equipmentName').value = item.name;
    document.getElementById('equipmentType').value = item.type;
    document.getElementById('equipmentCategory').value = item.category;
    document.getElementById('equipmentDamage').value = item.damage || '';
    document.getElementById('equipmentRange').value = item.range || '';
    document.getElementById('equipmentRarity').value = item.rarity;
    document.getElementById('equipmentPrice').value = item.price;
    document.getElementById('equipmentDescription').value = item.description;
    // Display requirements in readable format, not JSON
    document.getElementById('equipmentRequirements').value = item.requirements ? formatRequirementsForEdit(item.requirements) : '';
    document.getElementById('equipmentImage').value = item.image || '';
    document.getElementById('equipmentNotes').value = item.notes || '';
    
    // Show assign button for existing equipment
    document.getElementById('assignEquipmentBtn').style.display = 'inline-block';
    currentEquipmentId = equipmentId;
    
    openModalA11y('equipmentModal');
}

function viewEquipment(equipmentId) {
    console.log('viewEquipment called with id:', equipmentId);
    console.log('allEquipment length:', allEquipment.length);
    const item = allEquipment.find(i => i.id == equipmentId);
    if (!item) {
        console.error('Equipment not found:', equipmentId);
        showNotification('Equipment not found', 'error');
        return;
    }

    document.getElementById('viewEquipmentName').textContent = item.name;
    const viewContainer = document.getElementById('viewEquipmentContent');
    if (viewContainer) {
      viewContainer.setAttribute('aria-busy','true');
      viewContainer.textContent = 'Loading...';
    }
    
    const content = `
        <div class="view-section" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(139, 0, 0, 0.2);">
            <div>
                <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Basic Information</h3>
                <div style="padding-left: 14px;">
                    <p style="margin-bottom: 4px; font-size: 0.95em;"><strong>Name:</strong> ${escapeHtml(item.name)}</p>
                    <p style="margin-bottom: 4px; font-size: 0.95em;"><strong>Type:</strong> <span class="badge-${getTypeClass(item.type)}">${escapeHtml(item.type)}</span></p>
                    <p style="margin-bottom: 4px; font-size: 0.95em;"><strong>Category:</strong> ${escapeHtml(item.category)}</p>
                    <p style="margin-bottom: 4px; font-size: 0.95em;"><strong>Rarity:</strong> <span class="badge-${item.rarity}">${escapeHtml(item.rarity)}</span></p>
                    <p style="margin-bottom: 0; font-size: 0.95em;"><strong>Price:</strong> $${parseInt(item.price).toLocaleString()}</p>
                </div>
            </div>
            <div>
                <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Combat Stats</h3>
                <div style="padding-left: 14px;">
                    <p style="margin-bottom: 4px; font-size: 0.95em;"><strong>Damage:</strong> ${escapeHtml(item.damage || 'N/A')}</p>
                    <p style="margin-bottom: 0; font-size: 0.95em;"><strong>Range:</strong> ${escapeHtml(item.range || 'N/A')}</p>
                </div>
            </div>
            <div>
                <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Requirements</h3>
                <div style="padding-left: 14px;">
                    <p style="margin-bottom: 0; font-size: 0.95em;">${formatRequirements(item.requirements)}</p>
                </div>
            </div>
        </div>
        <div class="view-section" style="margin-bottom: 10px;">
            <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Description</h3>
            <div style="padding-left: 14px;">
                <p style="margin-bottom: 0; font-size: 0.95em; line-height: 1.4;">${escapeHtml(item.description)}</p>
            </div>
        </div>
        ${item.notes ? `
        <div class="view-section" style="margin-bottom: 10px;">
            <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Notes</h3>
            <div style="padding-left: 14px;">
                <p style="margin-bottom: 0; font-size: 0.95em; line-height: 1.4;">${escapeHtml(item.notes)}</p>
            </div>
        </div>
        ` : ''}
        ${item.image ? `
        <div class="view-section" style="margin-bottom: 0;">
            <h3 style="font-size: 1.1em; margin-bottom: 8px; font-family: var(--font-title), 'Libre Baskerville', serif; color: #f5e6d3; border-left: 4px solid #8B0000; padding-left: 10px;">Image</h3>
            <div style="padding-left: 14px;">
                <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" style="max-width: 150px; border-radius: 4px;">
            </div>
        </div>
        ` : ''}
    `;
    
    if (viewContainer) {
      viewContainer.innerHTML = content;
      viewContainer.setAttribute('aria-busy','false');
    }
    openModalA11y('viewModal');
}

async function openAssignModal(equipmentId, equipmentName) {
    console.log('openAssignModal called with id:', equipmentId);
    currentEquipmentId = equipmentId;
    const nameElement = document.getElementById('assignEquipmentName');
    if (nameElement) {
        nameElement.textContent = equipmentName || 'Unknown Equipment';
    }
    
    // Show loading state
    const characterSelection = document.getElementById('characterSelection');
    if (characterSelection) {
        characterSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #b8a090;">Loading characters...</div>';
    }
    
    // Fetch which characters currently have this equipment
    try {
        const response = await fetch(`api_admin_equipment_assignments.php?equipment_id=${equipmentId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success) {
            assignedCharacterIds = new Set((data.character_ids || []).map(id => parseInt(id)));
        } else {
            assignedCharacterIds = new Set();
            console.warn('Failed to fetch assignments:', data.error);
        }
    } catch (error) {
        console.error('Error fetching assignments:', error);
        assignedCharacterIds = new Set();
        showNotification('Failed to load character assignments', 'error');
    }
    
    // Populate character list
    if (!characterSelection) {
        console.error('characterSelection element not found');
        return;
    }
    
    if (!allCharacters || allCharacters.length === 0) {
        characterSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #b8a090;">No characters available</div>';
        openModalA11y('assignModal');
        return;
    }
    
    characterSelection.innerHTML = allCharacters.map(char => {
        const hasEquipment = assignedCharacterIds.has(parseInt(char.id));
        return `
            <div class="character-item ${hasEquipment ? 'has-equipment' : ''}" 
                 data-character-id="${char.id}"
                 data-has-equipment="${hasEquipment}">
                <strong>${escapeHtml(char.character_name)}</strong>
                <small>${escapeHtml(char.clan)} - ${escapeHtml(char.player_name)}</small>
            </div>
        `;
    }).join('');
    
    // Add click handlers for toggle behavior
    characterSelection.querySelectorAll('.character-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const charId = parseInt(this.dataset.characterId);
            const hasEquipment = assignedCharacterIds.has(charId);
            
            if (hasEquipment) {
                // Remove from set and update UI
                assignedCharacterIds.delete(charId);
                this.classList.remove('has-equipment');
                this.dataset.hasEquipment = 'false';
            } else {
                // Add to set and update UI
                assignedCharacterIds.add(charId);
                this.classList.add('has-equipment');
                this.dataset.hasEquipment = 'true';
            }
        });
    });
    
    openModalA11y('assignModal');
}

// Make function globally accessible for onclick handlers
window.openAssignModal = openAssignModal;

function openAssignModalFromEdit() {
    const equipmentId = document.getElementById('equipmentId').value;
    const equipmentName = document.getElementById('equipmentName').value;
    if (equipmentId) {
        openAssignModal(equipmentId, equipmentName);
    } else {
        showNotification('Please save the equipment first before assigning to characters', 'error');
    }
}

// Make function globally accessible
window.openAssignModalFromEdit = openAssignModalFromEdit;

async function saveAssignments() {
    if (!currentEquipmentId) {
        showNotification('No equipment selected', 'error');
        return;
    }
    
    try {
        // Get current assignments from database
        const currentResponse = await fetch(`api_admin_equipment_assignments.php?equipment_id=${currentEquipmentId}`);
        const currentData = await currentResponse.json();
        const currentAssignedIds = new Set((currentData.character_ids || []).map(id => parseInt(id)));
        
        // Determine what to add and what to remove
        const toAdd = Array.from(assignedCharacterIds).filter(id => !currentAssignedIds.has(id));
        const toRemove = Array.from(currentAssignedIds).filter(id => !assignedCharacterIds.has(id));
        
        // Perform additions
        const addPromises = toAdd.map(characterId =>
            fetch('api_admin_equipment_assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    equipment_id: currentEquipmentId,
                    character_id: characterId,
                    quantity: 1
                })
            })
        );
        
        // Perform removals
        const removePromises = toRemove.map(characterId =>
            fetch('api_admin_equipment_assignments.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    equipment_id: currentEquipmentId,
                    character_id: characterId
                })
            })
        );
        
        const allPromises = [...addPromises, ...removePromises];
        const results = await Promise.all(allPromises);
        const responses = await Promise.all(results.map(r => r.json()));
        
        const successCount = responses.filter(r => r.success).length;
        const totalOperations = allPromises.length;
        
        if (successCount === totalOperations) {
            console.log('Equipment assignments saved successfully');
            showNotification(`Equipment assignments updated successfully!`, 'success');
            closeAssignModal();
        } else {
            console.log(`Updated ${successCount} of ${totalOperations} assignments`);
            showNotification(`Updated ${successCount} of ${totalOperations} assignments`, 'error');
        }
    } catch (error) {
        console.error('Error saving assignments:', error);
        showNotification('Failed to save assignments', 'error');
    }
}

function deleteEquipment(equipmentId, equipmentName) {
    console.log('deleteEquipment called with id:', equipmentId);
    currentEquipmentId = equipmentId;
    document.getElementById('deleteEquipmentName').textContent = equipmentName;
    
    // Check if equipment is assigned to characters by querying the database directly
    fetch(`api_admin_equipment_crud.php?check_assignments=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.assignment_count > 0) {
                document.getElementById('deleteWarning').style.display = 'block';
                document.getElementById('deleteWarning').innerHTML = 
                    `⚠️ <strong>This equipment is assigned to ${data.assignment_count} character(s)</strong> - remove assignments first!`;
            } else {
                document.getElementById('deleteWarning').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking assignments:', error);
            document.getElementById('deleteWarning').style.display = 'none';
        });
    
    openModalA11y('deleteModal');
}

async function confirmDelete() {
    try {
        const response = await fetch('api_admin_equipment_crud.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentEquipmentId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            closeDeleteModal();
            loadEquipment(); // Reload equipment
        } else {
            showNotification(result.message || 'Failed to delete equipment', 'error');
        }
    } catch (error) {
        console.error('Error deleting equipment:', error);
        showNotification('Failed to delete equipment', 'error');
    }
}

// Modal functions
function closeEquipmentModal() { 
    closeModalA11y('equipmentModal');
    currentEquipmentId = null;
}

function closeViewModal() { 
    closeModalA11y('viewModal');
}

function closeAssignModal() {
    closeModalA11y('assignModal');
    assignedCharacterIds.clear();
    currentEquipmentId = null;
}

function closeDeleteModal() {
    closeModalA11y('deleteModal');
    currentEquipmentId = null;
}

// Set up delete confirmation - wait for DOM
document.addEventListener('DOMContentLoaded', function() {
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', confirmDelete);
    }
});

// Make all onclick handler functions globally accessible
window.openAddEquipmentModal = openAddEquipmentModal;
window.editEquipment = editEquipment;
window.viewEquipment = viewEquipment;
window.deleteEquipment = deleteEquipment;
window.closeEquipmentModal = closeEquipmentModal;
window.closeViewModal = closeViewModal;
window.closeAssignModal = closeAssignModal;
window.closeDeleteModal = closeDeleteModal;
window.saveAssignments = saveAssignments;
window.confirmDelete = confirmDelete;
window.goToPage = goToPage;

// Utility functions
function getTypeClass(type) {
    const typeMap = {
        'Weapon': 'weapon',
        'Armor': 'armor', 
        'Tool': 'tool',
        'Consumable': 'consumable',
        'Artifact': 'artifact',
        'Misc': 'misc',
        'Miscellaneous': 'misc',
        'Equipment': 'equipment'
    };
    return typeMap[type] || 'misc';
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function formatRequirements(requirements) {
    if (!requirements) return 'None';
    
    // If it's a string, try to parse it
    let reqObj = requirements;
    if (typeof requirements === 'string') {
        try {
            reqObj = JSON.parse(requirements);
        } catch (e) {
            return escapeHtml(requirements);
        }
    }
    
    // If it's an object, format it nicely
    if (typeof reqObj === 'object' && reqObj !== null) {
        const parts = [];
        for (const [key, value] of Object.entries(reqObj)) {
            parts.push(`${escapeHtml(key)}: ${escapeHtml(value)}`);
        }
        return parts.length > 0 ? parts.join(', ') : 'None';
    }
    
    return 'None';
}

function formatRequirementsForEdit(requirements) {
    if (!requirements) return '';
    
    // If it's a string, try to parse it
    let reqObj = requirements;
    if (typeof requirements === 'string') {
        try {
            reqObj = JSON.parse(requirements);
        } catch (e) {
            return requirements; // Return as-is if not valid JSON
        }
    }
    
    // If it's an object, format it nicely for editing
    if (typeof reqObj === 'object' && reqObj !== null) {
        const parts = [];
        for (const [key, value] of Object.entries(reqObj)) {
            parts.push(`${key}: ${value}`);
        }
        return parts.join(', ');
    }
    
    return '';
}

function parseRequirementsFromText(text) {
    if (!text || !text.trim()) return null;
    
    // Try to parse as JSON first
    try {
        return JSON.parse(text);
    } catch (e) {
        // Not JSON, parse formatted text like "strength: 3, dexterity: 2"
        const result = {};
        const pairs = text.split(',');
        
        for (const pair of pairs) {
            const trimmed = pair.trim();
            if (!trimmed) continue;
            
            const colonIndex = trimmed.indexOf(':');
            if (colonIndex === -1) {
                throw new Error('Invalid format: missing colon');
            }
            
            const key = trimmed.substring(0, colonIndex).trim();
            const value = trimmed.substring(colonIndex + 1).trim();
            
            if (!key) {
                throw new Error('Invalid format: missing key');
            }
            
            // Try to parse value as number, otherwise keep as string
            const numValue = Number(value);
            result[key] = isNaN(numValue) ? value : numValue;
        }
        
        return result;
    }
}
