/**
 * Admin Items Management JavaScript
 * Handles table operations, CRUD, filtering, and equipment assignment
 */

let allItems = [];
let filteredItems = [];
let currentPage = 1;
let itemsPerPage = 20;
let currentSort = { column: 'id', direction: 'asc' };
let currentFilter = 'all';
let currentTypeFilter = 'all';
let currentRarityFilter = 'all';
let currentSearchTerm = '';
let currentItemId = null;
let itemsLoaded = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    // Initialize sort listeners after a small delay to ensure headers are fully loaded
    setTimeout(() => {
        initializeSortListeners();
    }, 100);
    loadItems();
});

// Bootstrap handles accessibility automatically

function initializeEventListeners() {
    // Add Item button
    const addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', openAddItemModal);
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

    // Rarity filter
    document.getElementById('rarityFilter').addEventListener('change', function() {
        currentRarityFilter = this.value;
        applyFilters();
    });

    // Search
    document.getElementById('itemSearch').addEventListener('input', function() {
        currentSearchTerm = this.value.toLowerCase();
        applyFilters();
    });

    // Page size
    document.getElementById('pageSize').addEventListener('change', function() {
        itemsPerPage = parseInt(this.value);
        currentPage = 1;
        applyFilters();
    });

    // Form submission
    document.getElementById('itemForm').addEventListener('submit', handleFormSubmit);
}

function initializeSortListeners() {
    // Add sorting event listeners to headers (only once, using event delegation)
    const table = document.getElementById('itemsTable');
    if (!table) return;
    
    const thead = table.querySelector('thead');
    if (!thead) return;
    
    // Store original header content to prevent accidental clearing
    const headers = thead.querySelectorAll('th[data-sort]');
    headers.forEach(th => {
        if (!th.dataset.originalContent) {
            // Store both innerHTML and textContent for comparison
            th.dataset.originalContent = th.innerHTML;
            th.dataset.originalText = th.textContent.trim();
        }
    });
    
    // Use event delegation on thead to handle clicks
    thead.addEventListener('click', function(e) {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        
        // Prevent default if needed
        e.preventDefault();
        e.stopPropagation();
        
        const column = th.dataset.sort;
        
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }

        // Update sort indicators - ONLY classes, never content
        const allHeaders = document.querySelectorAll('#itemsTable th[data-sort]');
        allHeaders.forEach(h => {
            // Only remove/add classes, preserve content
            h.classList.remove('sorted-asc', 'sorted-desc');
            
            // Restore original content if header text is missing (only sort icon remains)
            if (h.dataset.originalContent) {
                const textContent = h.textContent.trim();
                const hasOnlySortIcon = textContent === '⇅' || textContent === '▲' || textContent === '▼' || textContent.length === 0;
                if (hasOnlySortIcon) {
                    h.innerHTML = h.dataset.originalContent;
                }
            }
        });
        th.classList.add(`sorted-${currentSort.direction}`);

        sortItems();
        renderTable();
    });
}

async function loadItems() {
    console.log('loadItems() called');
    try {
        const response = await fetch('../api_items.php');
        console.log('API response status:', response.status);
        const data = await response.json();
        console.log('API response data:', data);
        
        if (data.success) {
            allItems = Array.isArray(data.items) ? data.items : [];
            console.log('Loaded items count:', allItems.length);
            itemsLoaded = true;
            applyFilters();
        } else {
            console.error('API returned error:', data.error);
            showNotification('Failed to load items: ' + data.error, 'error');
            allItems = [];
            filteredItems = [];
            renderTable();
        }
    } catch (error) {
        console.error('Error loading items:', error);
        showNotification('Failed to load items', 'error');
        allItems = [];
        applyFilters();
    }
}

function applyFilters() {
    // Don't filter if items haven't loaded yet
    if (!itemsLoaded) {
        console.log('applyFilters() called before items loaded, skipping');
        return;
    }
    
    if (!Array.isArray(allItems)) {
        allItems = [];
    }
    
    // Don't filter if we have no items yet
    if (allItems.length === 0) {
        filteredItems = [];
        renderTable();
        renderPagination();
        return;
    }
    
    filteredItems = allItems.filter(item => {
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
    sortItems();
    
    // Reset to first page
    currentPage = 1;
    
    // Render table
    renderTable();
    renderPagination();
}

// Rarity order: Legendary (highest) -> Epic -> Very Rare -> Rare -> Uncommon -> Common (lowest)
function getRarityOrder(rarity) {
    const rarityOrder = {
        'legendary': 0,
        'epic': 1,
        'very rare': 2,
        'rare': 3,
        'uncommon': 4,
        'common': 5
    };
    const normalized = (rarity || '').toLowerCase().trim();
    return rarityOrder[normalized] !== undefined ? rarityOrder[normalized] : 999;
}

function capitalizeRarity(rarity) {
    if (!rarity) return '';
    return rarity.split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    ).join(' ');
}

function sortItems() {
    if (!Array.isArray(filteredItems) || filteredItems.length === 0) {
        return;
    }
    filteredItems.sort((a, b) => {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];

        // Handle numeric columns
        if (['id', 'price'].includes(currentSort.column)) {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        }
        // Handle rarity column with custom order
        else if (currentSort.column === 'rarity') {
            aVal = getRarityOrder(aVal);
            bVal = getRarityOrder(bVal);
        }
        // Handle string columns
        else if (typeof aVal === 'string') {
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
    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) {
        console.error('CRITICAL: #itemsTable tbody not found!');
        return;
    }
    
    if (!Array.isArray(filteredItems)) {
        filteredItems = [];
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageItems = filteredItems.slice(startIndex, endIndex);

    if (pageItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No items found.</td></tr>';
        return;
    }

    tbody.innerHTML = pageItems.map(item => `
        <tr class="item-row" data-type="${item.type.toLowerCase()}" data-rarity="${item.rarity}">
            <td>${item.id}</td>
            <td data-full-name="${escapeHtml(item.name)}"><strong>${escapeHtml(item.name)}</strong></td>
            <td><span class="badge-${getTypeClass(item.type)}">${escapeHtml(item.type)}</span></td>
            <td title="${escapeHtml(item.category)}">${escapeHtml(item.category)}</td>
            <td>${escapeHtml(item.damage || 'N/A')}</td>
            <td title="${escapeHtml(item.range || 'N/A')}">${escapeHtml(item.range || 'N/A')}</td>
            <td><span class="badge-${(item.rarity || '').toLowerCase().replace(/\s+/g, '-')}">${escapeHtml(capitalizeRarity(item.rarity))}</span></td>
            <td>$${parseInt(item.price).toLocaleString()}</td>
            <td class="actions">
                <button class="action-btn view-btn" onclick="viewItem(${item.id})" title="View Item">👁️</button>
                <button class="action-btn edit-btn" onclick="editItem(${item.id})" title="Edit Item">✏️</button>
                <button class="action-btn assign-btn" onclick="assignItem(${item.id}, '${escapeHtml(item.name)}')" title="Assign to Characters">🎯</button>
                <button class="action-btn delete-btn" onclick="deleteItem(${item.id}, '${escapeHtml(item.name)}')" title="Delete Item">🗑️</button>
            </td>
        </tr>
    `).join('');

    // Update sort indicators on current column - ONLY modify classes, preserve content
    const allHeaders = document.querySelectorAll('#itemsTable th[data-sort]');
    allHeaders.forEach(h => {
        // Only remove/add classes, never modify content
        h.classList.remove('sorted-asc', 'sorted-desc');
        
        // Restore original content if header text is missing (only sort icon remains)
        if (h.dataset.originalContent && h.dataset.originalText) {
            const currentText = h.textContent.trim();
            const originalText = h.dataset.originalText;
            // Check if current text is significantly shorter than original (missing the label)
            // Original should be like "ID ⇅" or "Name ⇅", if it's just "⇅" or empty, restore it
            const sortIconOnly = currentText === '⇅' || currentText === '▲' || currentText === '▼';
            const textMissing = currentText.length < originalText.length * 0.3; // Less than 30% of original length
            if (sortIconOnly || textMissing) {
                h.innerHTML = h.dataset.originalContent;
            }
        }
        
        if (h.dataset.sort === currentSort.column) {
            h.classList.add(`sorted-${currentSort.direction}`);
        }
    });
}

function renderPagination() {
    const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    if (totalPages <= 1) {
        paginationInfo.textContent = `Showing ${filteredItems.length} items`;
        paginationButtons.innerHTML = '';
        return;
    }

    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, filteredItems.length);
    paginationInfo.textContent = `Showing ${startItem}-${endItem} of ${filteredItems.length} items`;

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

    // Validate JSON requirements
    if (data.requirements) {
        try {
            JSON.parse(data.requirements);
        } catch (error) {
            showNotification('Invalid JSON in Requirements field', 'error');
            return;
        }
    }

    const isEdit = data.id && data.id !== '';
    const url = 'api_admin_items_crud.php';
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
            const modalElement = document.getElementById('itemModal');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            loadItems(); // Reload items
        } else {
            showNotification(result.message || 'Failed to save item', 'error');
        }
    } catch (error) {
        console.error('Error saving item:', error);
        showNotification('Failed to save item', 'error');
    }
}

function openAddItemModal() {
    document.getElementById('itemModalTitle').textContent = 'Add New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    const modalElement = document.getElementById('itemModal');
    if (modalElement) {
        const modalInstance = new bootstrap.Modal(modalElement);
        modalInstance.show();
    }
}

function editItem(itemId) {
    const item = allItems.find(i => i.id == itemId);
    if (!item) return;

    const titleEl = document.getElementById('itemModalTitle');
    const idEl = document.getElementById('itemId');
    const nameEl = document.getElementById('itemName');
    const typeEl = document.getElementById('itemType');
    const categoryEl = document.getElementById('itemCategory');
    const damageEl = document.getElementById('itemDamage');
    const rangeEl = document.getElementById('itemRange');
    const rarityEl = document.getElementById('itemRarity');
    const priceEl = document.getElementById('itemPrice');
    const descriptionEl = document.getElementById('itemDescription');
    const requirementsEl = document.getElementById('itemRequirements');
    const imageEl = document.getElementById('itemImage');
    const notesEl = document.getElementById('itemNotes');
    
    if (!titleEl || !idEl || !nameEl || !typeEl || !categoryEl || !rarityEl || !priceEl || !descriptionEl) {
        console.error('Required form elements not found for editing item');
        return;
    }
    
    if (titleEl) titleEl.textContent = 'Edit Item';
    if (idEl) idEl.value = item.id;
    if (nameEl) nameEl.value = item.name;
    if (typeEl) typeEl.value = item.type;
    if (categoryEl) categoryEl.value = item.category;
    if (damageEl) damageEl.value = item.damage || '';
    if (rangeEl) rangeEl.value = item.range || '';
    if (rarityEl) {
        // Normalize rarity to lowercase to match option values
        const normalizedRarity = item.rarity ? item.rarity.toLowerCase() : '';
        rarityEl.value = normalizedRarity;
        
        // Verify the option exists, if not log a warning
        if (normalizedRarity && !Array.from(rarityEl.options).some(opt => opt.value === normalizedRarity)) {
            console.warn(`Rarity option "${normalizedRarity}" not found in dropdown. Available options:`, 
                Array.from(rarityEl.options).map(opt => opt.value));
        }
    }
    if (priceEl) priceEl.value = item.price;
    if (descriptionEl) descriptionEl.value = item.description;
    if (requirementsEl) requirementsEl.value = item.requirements ? JSON.stringify(item.requirements, null, 2) : '';
    if (imageEl) imageEl.value = item.image || '';
    if (notesEl) notesEl.value = item.notes || '';
    
    const modalElement = document.getElementById('itemModal');
    if (modalElement) {
        const modalInstance = new bootstrap.Modal(modalElement);
        modalInstance.show();
    } else {
        console.error('itemModal element not found');
    }
}

// Get backup icon URL based on item type
function getItemBackupIcon(itemType) {
    if (!itemType) return null;
    const typeMap = {
        'Weapon': 'weapon-icon.svg',
        'Armor': 'armor-icon.svg',
        'Tool': 'tool-icon.svg',
        'Consumable': 'consumable-icon.svg',
        'Artifact': 'artifact-icon.svg',
        'Misc': 'misc-icon.svg'
    };
    const iconFile = typeMap[itemType] || 'misc-icon.svg';
    const pathPrefix = typeof PATH_PREFIX !== 'undefined' ? PATH_PREFIX : '../';
    return pathPrefix + 'images/Item%20Icons/' + iconFile;
}

// Format requirements JSON as HTML
function formatRequirements(requirements) {
    if (!requirements) {
        return '<p style="margin-top: 5px; color: #b8a090;">None</p>';
    }
    
    let reqObj;
    try {
        // Parse if it's a string
        if (typeof requirements === 'string') {
            reqObj = JSON.parse(requirements);
        } else {
            reqObj = requirements;
        }
    } catch (e) {
        // If parsing fails, return as-is
        return '<p style="margin-top: 5px; color: #b8a090;">Invalid requirements data</p>';
    }
    
    if (!reqObj || typeof reqObj !== 'object' || Object.keys(reqObj).length === 0) {
        return '<p style="margin-top: 5px; color: #b8a090;">None</p>';
    }
    
    let html = '<ul style="margin-top: 5px; margin-bottom: 0; padding-left: 20px; color: #d4c4b0;">';
    for (const [key, value] of Object.entries(reqObj)) {
        const keyFormatted = escapeHtml(String(key).charAt(0).toUpperCase() + String(key).slice(1));
        const valueFormatted = escapeHtml(String(value));
        html += `<li><strong>${keyFormatted}:</strong> ${valueFormatted}</li>`;
    }
    html += '</ul>';
    
    return html;
}

function viewItem(itemId) {
    const item = allItems.find(i => i.id == itemId);
    if (!item) return;
    
    // Build image HTML with fallback
    const pathPrefix = typeof PATH_PREFIX !== 'undefined' ? PATH_PREFIX : '../';
    const hasImage = !!(item.image && item.image.trim());
    const imageUrl = hasImage ? (pathPrefix + 'uploads/Items/' + escapeHtml(item.image)) : null;
    const fallbackUrl = getItemBackupIcon(item.type);
    
    let imageHtml = '';
    if (imageUrl || fallbackUrl) {
        const finalUrl = imageUrl || fallbackUrl;
        imageHtml = `
            <div class="item-image-wrapper">
                <div class="item-image-media">
                    ${imageUrl ? `
                        <img src="${imageUrl}" 
                             class="item-image img-fluid" 
                             alt="${escapeHtml(item.name)}"
                             onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                    ` : ''}
                    ${fallbackUrl ? `
                        <img src="${fallbackUrl}" 
                             class="item-image item-image-fallback img-fluid ${imageUrl ? 'd-none' : ''}" 
                             alt="${escapeHtml(item.name)} (${escapeHtml(item.type)})">
                    ` : ''}
                    ${!imageUrl && !fallbackUrl ? `
                        <div class="item-image-placeholder">No Image</div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    const content = `
        <div class="info-grid">
            <div>
                <h3>Basic Information</h3>
                <p><strong>Name:</strong> ${escapeHtml(item.name)}</p>
                <p><strong>Type:</strong> <span class="badge-${getTypeClass(item.type)}">${escapeHtml(item.type)}</span></p>
                <p><strong>Category:</strong> ${escapeHtml(item.category)}</p>
                <p><strong>Rarity:</strong> <span class="badge-${item.rarity}">${escapeHtml(item.rarity)}</span></p>
                <p><strong>Price:</strong> $${parseInt(item.price).toLocaleString()}</p>
            </div>
            <div>
                ${imageHtml}
            </div>
        </div>
        <div class="info-grid">
            <div>
                <h3>Combat Stats</h3>
                <p><strong>Damage:</strong> ${escapeHtml(item.damage || 'N/A')}</p>
                <p><strong>Range:</strong> ${escapeHtml(item.range || 'N/A')}</p>
                <div>
                    <strong>Requirements:</strong>
                    ${formatRequirements(item.requirements)}
                </div>
            </div>
            <div>
                <!-- Empty column for alignment -->
            </div>
        </div>
        <div>
            <h3>Description</h3>
            <p>${escapeHtml(item.description)}</p>
        </div>
        ${item.notes ? `
        <div>
            <h3>Notes</h3>
            <p>${escapeHtml(item.notes)}</p>
        </div>
        ` : ''}
    `;
    
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
    
    modalTitle.textContent = `📄 ${escapeHtml(item.name)}`;
    modalBody.innerHTML = content;
    modalBody.setAttribute('aria-busy','false');
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

function assignItem(itemId, itemName) {
    currentItemId = itemId;
    
    const modalElement = document.getElementById('assignModal');
    if (!modalElement) {
        console.error('assignModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '🎯 Assign Item to Characters';
    modalBody.innerHTML = `
        <p class="vbn-modal-message">Assign <strong id="assignItemName">${escapeHtml(itemName)}</strong> to characters:</p>
        <div class="character-selection" id="characterSelection">
            ${(window.allCharactersForItems || []).map(char => `
                <div class="character-item" data-character-id="${char.id}">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${escapeHtml(char.character_name)}</strong>
                            <small style="display: block; color: #b8a090;">
                                ${escapeHtml(char.clan)} - ${escapeHtml(char.player_name)}
                            </small>
                        </div>
                        <input type="number" class="quantity-input" value="1" min="1" max="99" data-character-id="${char.id}">
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="assignItemsToCharacters()">Assign Items</button>
    `;
    
    // Add click handlers for character selection
    modalBody.querySelectorAll('.character-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.classList.contains('quantity-input')) return;
            this.classList.toggle('selected');
        });
    });
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

async function assignItemsToCharacters() {
    const selectedCharacters = document.querySelectorAll('.character-item.selected');
    
    if (selectedCharacters.length === 0) {
        showNotification('Please select at least one character', 'error');
        return;
    }

    const assignments = [];
    selectedCharacters.forEach(char => {
        const charId = char.dataset.characterId;
        const quantity = parseInt(char.querySelector('.quantity-input').value);
        assignments.push({ character_id: charId, quantity });
    });

    try {
        const promises = assignments.map(assignment => 
            fetch('api_admin_add_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    character_id: assignment.character_id,
                    item_id: currentItemId,
                    quantity: assignment.quantity
                })
            })
        );

        const results = await Promise.all(promises);
        const responses = await Promise.all(results.map(r => r.json()));

        const successCount = responses.filter(r => r.success).length;
        
        if (successCount === assignments.length) {
            showNotification(`Item assigned to ${successCount} character(s) successfully!`, 'success');
            closeAssignModal();
        } else {
            showNotification(`Item assigned to ${successCount} of ${assignments.length} character(s)`, 'error');
        }
    } catch (error) {
        console.error('Error assigning items:', error);
        showNotification('Failed to assign items', 'error');
    }
}

function deleteItem(itemId, itemName) {
    currentItemId = itemId;
    
    const modalElement = document.getElementById('deleteModal');
    if (!modalElement) {
        console.error('deleteModal element not found');
        return;
    }
    
    const modalTitle = modalElement.querySelector('.vbn-modal-title');
    const modalBody = modalElement.querySelector('.vbn-modal-body');
    const modalFooter = modalElement.querySelector('.vbn-modal-footer');
    
    if (!modalTitle || !modalBody || !modalFooter) {
        console.error('Modal structure incomplete. Missing required elements.');
        return;
    }
    
    modalTitle.textContent = '⚠️ Confirm Deletion';
    modalBody.innerHTML = `
        <p class="vbn-modal-message">Delete item:</p>
        <p class="vbn-modal-character-name" id="deleteItemName">${escapeHtml(itemName)}</p>
        <p class="vbn-modal-warning" id="deleteWarning" style="display:none;"></p>
    `;
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
    `;
    
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
    
    // Check if item is assigned to characters
    fetch(`api_admin_items_crud.php?check_assignments=${itemId}`)
        .then(response => response.json())
        .then(data => {
            const warningElement = document.getElementById('deleteWarning');
            if (data.success && data.assignment_count > 0) {
                warningElement.style.display = 'block';
                warningElement.innerHTML = 
                    `⚠️ <strong>This item is assigned to ${data.assignment_count} character(s)</strong> - remove assignments first!`;
            } else {
                warningElement.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking assignments:', error);
            const warningElement = document.getElementById('deleteWarning');
            if (warningElement) {
                warningElement.style.display = 'none';
            }
        });
}

async function confirmDelete() {
    try {
        const response = await fetch('api_admin_items_crud.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentItemId })
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
            currentItemId = null;
            loadItems(); // Reload items
        } else {
            showNotification(result.message || 'Failed to delete item', 'error');
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        showNotification('Failed to delete item', 'error');
    }
}

// Modal functions
function closeItemModal() {
    const modalElement = document.getElementById('itemModal');
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
    currentItemId = null;
}

function closeDeleteModal() {
    const modalElement = document.getElementById('deleteModal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
    currentItemId = null;
}

// Character selection in assign modal
document.addEventListener('click', function(e) {
    if (e.target.closest('.character-item')) {
        const item = e.target.closest('.character-item');
        item.classList.toggle('selected');
    }
});

// Delete confirmation is handled via onclick in the modal footer

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
        'Equipment': 'equipment',
        'Accessory': 'accessory',
        'Clothing': 'clothing',
        'Vehicle': 'vehicle',
        'Book': 'book',
        'Food': 'food',
        'Drink': 'drink',
        'Drug': 'drug',
        'Electronic': 'electronic'
    };
    return typeMap[type] || 'misc';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
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
