/**
 * Admin Panel - Character Management JavaScript
 * Handles search, filter, sort, and delete functionality
 */

// State management
let currentFilter = 'all';
let currentClanFilter = 'all';
const sortStorageKey = 'adminPanelSort';
const pageSizeStorageKey = 'adminPanelPageSize';
let currentSort = { column: 'character_name', direction: 'asc' };
let deleteCharacterId = null;
let currentPage = 1;
let pageSize = 20;
let allRows = [];

function loadSavedSortState() {
    try {
        const raw = sessionStorage.getItem(sortStorageKey);
        if (!raw) {
            return;
        }
        const parsed = JSON.parse(raw);
        if (
            parsed &&
            typeof parsed === 'object' &&
            typeof parsed.column === 'string' &&
            (parsed.direction === 'asc' || parsed.direction === 'desc')
        ) {
            currentSort = {
                column: parsed.column,
                direction: parsed.direction
            };
        }
    } catch (error) {
        console.error('Unable to restore admin panel sort state', error);
    }
}

function persistSortState() {
    try {
        sessionStorage.setItem(sortStorageKey, JSON.stringify(currentSort));
    } catch (error) {
        console.error('Unable to persist admin panel sort state', error);
    }
}

function applySavedSortState() {
    if (!currentSort || !currentSort.column || !currentSort.direction) {
        return;
    }

    const headers = document.querySelectorAll('.character-table th[data-sort]');
    if (!headers.length) {
        return;
    }

    const headerArray = Array.from(headers);
    const targetHeader = headerArray.find(header => header.dataset.sort === currentSort.column);
    if (!targetHeader) {
        return;
    }

    headerArray.forEach(header => header.classList.remove('sorted-asc', 'sorted-desc'));
    targetHeader.classList.add('sorted-' + currentSort.direction);
    sortTable(currentSort.column, currentSort.direction);
}

function loadSavedPageSize() {
    const stored = sessionStorage.getItem(pageSizeStorageKey);
    if (!stored) {
        return;
    }
    const parsed = parseInt(stored, 10);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return;
    }
    pageSize = parsed;
    const pageSizeSelect = document.getElementById('pageSize');
    if (pageSizeSelect) {
        const optionExists = Array.from(pageSizeSelect.options).some(option => parseInt(option.value, 10) === parsed);
        if (optionExists) {
            pageSizeSelect.value = String(parsed);
        }
    }
}

function persistPageSize() {
    try {
        sessionStorage.setItem(pageSizeStorageKey, String(pageSize));
    } catch (error) {
        console.error('Unable to persist admin panel page size', error);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Store all rows for pagination
    allRows = Array.from(document.querySelectorAll('.character-row'));
    
    loadSavedSortState();
    loadSavedPageSize();
    initializeFilters();
    initializeClanFilter();
    initializeSearch();
    initializeSorting();
    applySavedSortState();
    initializeDeleteButtons();
    initializeViewButtons();
    // View mode toggle initialized by character_view_modal.php include
    initializePagination();
});

// Filter functionality
function initializeFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Get filter type
            currentFilter = this.dataset.filter;
            
            // Apply filter
            applyFilters();
        });
    });
}

// Clan filter functionality
function initializeClanFilter() {
    const clanFilter = document.getElementById('clanFilter');
    
    if (clanFilter) {
        clanFilter.addEventListener('change', function() {
            currentClanFilter = this.value;
            applyFilters();
        });
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('characterSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applyFilters();
        });
    }
}

// Apply filter, clan filter, and search
function applyFilters(resetPage = true) {
    const searchTerm = document.getElementById('characterSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.character-row');
    
    let visibleRows = [];
    
    rows.forEach(row => {
        const type = row.dataset.type;
        const name = row.dataset.name.toLowerCase();
        const clan = row.dataset.clan || '';
        
        // Check filter (PC/NPC)
        let showByFilter = true;
        if (currentFilter === 'pcs' && type !== 'pc') {
            showByFilter = false;
        } else if (currentFilter === 'npcs' && type !== 'npc') {
            showByFilter = false;
        }
        
        // Check clan filter
        let showByClan = true;
        if (currentClanFilter !== 'all' && clan !== currentClanFilter) {
            showByClan = false;
        }
        
        
        // Check search
        let showBySearch = true;
        if (searchTerm && !name.includes(searchTerm)) {
            showBySearch = false;
        }
        
        // Track visible rows
        if (showByFilter && showByClan && showBySearch) {
            row.classList.remove('filtered-out');
            visibleRows.push(row);
        } else {
            row.classList.add('filtered-out');
        }
    });
    
    if (resetPage) {
        currentPage = 1;
    } else {
        const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
    }
    updatePagination(visibleRows);
}

// Sorting functionality
function initializeSorting() {
    const headers = document.querySelectorAll('.character-table th[data-sort]');
    
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            
            // Toggle direction if same column, otherwise start with ascending
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update header styling
            headers.forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add('sorted-' + currentSort.direction);
            
            // Sort table
            sortTable(column, currentSort.direction);
            persistSortState();
        });
    });
}

function sortTable(column, direction) {
    const tbody = document.querySelector('.character-table tbody');
    const rows = Array.from(tbody.querySelectorAll('.character-row'));
    
    rows.sort((a, b) => {
        let aVal = '';
        let bVal = '';

        switch(column) {
            case 'id':
                aVal = parseInt(a.dataset.id || '0', 10) || 0;
                bVal = parseInt(b.dataset.id || '0', 10) || 0;
                break;
            case 'character_name':
                aVal = (a.dataset.name || '').toLowerCase();
                bVal = (b.dataset.name || '').toLowerCase();
                break;
            case 'player_name':
                aVal = (a.dataset.player || '').toLowerCase();
                bVal = (b.dataset.player || '').toLowerCase();
                break;
            case 'clan':
                aVal = (a.dataset.clan || '').toLowerCase();
                bVal = (b.dataset.clan || '').toLowerCase();
                break;
            case 'generation':
                aVal = parseInt(a.dataset.generation || '0', 10) || 0;
                bVal = parseInt(b.dataset.generation || '0', 10) || 0;
                break;
            case 'status':
                aVal = (a.dataset.status || '').toLowerCase();
                bVal = (b.dataset.status || '').toLowerCase();
                break;
            case 'owner':
                aVal = (a.dataset.owner || '').toLowerCase();
                bVal = (b.dataset.owner || '').toLowerCase();
                break;
            default:
                aVal = (a.dataset.name || '').toLowerCase();
                bVal = (b.dataset.name || '').toLowerCase();
        }
        
        let comparison = 0;
        if (aVal > bVal) comparison = 1;
        if (aVal < bVal) comparison = -1;
        
        return direction === 'asc' ? comparison : -comparison;
    });
    
    // Re-append rows in sorted order
    rows.forEach(row => tbody.appendChild(row));
}

// View functionality
function initializeViewButtons() {
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            viewCharacter(this.dataset.id);
        });
    });
}

// Delete functionality
let deleteModalInstance = null;

function initializeDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    // Initialize Bootstrap modal instance
    const modalEl = document.getElementById('deleteModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        deleteModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    }
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            deleteCharacterId = this.dataset.id;
            const characterName = this.dataset.name;
            const isFinalized = this.dataset.status === 'finalized';
            
            // Populate modal content
            const nameEl = document.getElementById('deleteCharacterName');
            if (nameEl) {
                nameEl.textContent = characterName;
            }
            
            const warningEl = document.getElementById('deleteWarning');
            if (warningEl) {
                warningEl.style.display = isFinalized ? 'block' : 'none';
            }
            
            // Show Bootstrap modal
            if (deleteModalInstance) {
                deleteModalInstance.show();
            }
        });
    });
    
    // Confirm delete button
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmDelete);
    }
    
    // Reset on modal close
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            deleteCharacterId = null;
        });
    }
}

function closeDeleteModal() {
    if (deleteModalInstance) {
        deleteModalInstance.hide();
    }
    deleteCharacterId = null;
}

function confirmDelete() {
    if (!deleteCharacterId) return;
    
    fetch('/admin/delete_character_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            character_id: deleteCharacterId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            handleDeleteSuccess(deleteCharacterId);
        } else {
            alert('Error deleting character: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Error deleting character. Check console for details.');
    });
}

// Pagination functionality
function initializePagination() {
    // Page size change handler
    const pageSizeSelect = document.getElementById('pageSize');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            pageSize = parseInt(this.value);
            persistPageSize();
            currentPage = 1;
            updatePagination();
        });
    }
    
    updatePagination();
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSize').value);
    currentPage = 1;
    updatePagination();
}

function updatePagination(visibleRows = null) {
    // Get all visible rows (not filtered out)
    if (!visibleRows) {
        visibleRows = Array.from(document.querySelectorAll('.character-row:not(.filtered-out)'));
    }
    
    const totalVisible = visibleRows.length;
    const totalPages = Math.ceil(totalVisible / pageSize);
    
    // Hide all rows first
    document.querySelectorAll('.character-row').forEach(row => {
        row.classList.add('hidden');
    });
    
    // Show only rows for current page
    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalVisible);
    
    for (let i = startIndex; i < endIndex; i++) {
        if (visibleRows[i]) {
            visibleRows[i].classList.remove('hidden');
        }
    }
    
    // Update pagination info
    const showing = totalVisible === 0 ? 0 : startIndex + 1;
    document.getElementById('paginationInfo').textContent = 
        `Showing ${showing}-${endIndex} of ${totalVisible} characters`;
    
    // Generate pagination buttons
    const buttonsDiv = document.getElementById('paginationButtons');
    buttonsDiv.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    if (currentPage > 1) {
        const prevBtn = createPageButton('← Prev', currentPage - 1);
        buttonsDiv.appendChild(prevBtn);
    }
    
    // Page number buttons
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            const pageBtn = createPageButton(i, i);
            if (i === currentPage) pageBtn.classList.add('active');
            buttonsDiv.appendChild(pageBtn);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.color = '#666';
            dots.style.padding = '0 5px';
            buttonsDiv.appendChild(dots);
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        const nextBtn = createPageButton('Next →', currentPage + 1);
        buttonsDiv.appendChild(nextBtn);
    }
}

function createPageButton(text, page) {
    const btn = document.createElement('button');
    btn.className = 'page-btn btn btn-outline-danger btn-sm';
    btn.textContent = text;
    btn.onclick = () => goToPage(page);
    return btn;
}

function goToPage(page) {
    currentPage = page;
    updatePagination();
}

function handleDeleteSuccess(characterId) {
    const deleteButton = document.querySelector(`button.delete-btn[data-id="${characterId}"]`);
    const row = deleteButton ? deleteButton.closest('tr') : null;
    
    if (!row) {
        closeDeleteModal();
        return;
    }
    
    row.remove();
    allRows = Array.from(document.querySelectorAll('.character-row'));
    
    closeDeleteModal();
    recalculateStats();
    applyFilters(false);
    persistSortState();
}

function recalculateStats() {
    const rows = Array.from(document.querySelectorAll('.character-row'));
    const total = rows.length;
    
    let pcs = 0;
    let npcs = 0;
    rows.forEach(row => {
        if (row.dataset.type === 'npc') {
            npcs += 1;
        } else {
            pcs += 1;
        }
    });
    
    const totalEl = document.getElementById('statTotal');
    const pcsEl = document.getElementById('statPcs');
    const npcsEl = document.getElementById('statNpcs');
    
    if (totalEl) totalEl.textContent = total.toString();
    if (pcsEl) pcsEl.textContent = pcs.toString();
    if (npcsEl) npcsEl.textContent = npcs.toString();
}

// View character functionality
// View character functions are provided by includes/character_view_modal.php

