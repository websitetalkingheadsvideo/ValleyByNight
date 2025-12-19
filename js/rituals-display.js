/**
 * Rituals Display - Sorting Functionality
 * Handles client-side sorting of rituals table
 */

let allRituals = [];
let sortedRituals = [];
let currentSort = { column: 'type', direction: 'asc' };

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeRituals();
    initializeSorting();
    initializeSearch();
});

/**
 * Initialize rituals data from table
 */
function initializeRituals() {
    const tbody = document.querySelector('#ritualsTable tbody');
    const rows = tbody.querySelectorAll('tr');
    
    allRituals = [];
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const ritualId = row.dataset.ritualId || '';
            allRituals.push({
                id: ritualId,
                type: cells[0].textContent.trim(),
                level: parseInt(cells[1].textContent.trim()) || 0,
                name: cells[2].textContent.trim(),
                description: cells[3].textContent.trim(),
                rowElement: row
            });
        }
    });
    
    sortedRituals = [...allRituals];
}

/**
 * Initialize sorting event listeners
 */
function initializeSorting() {
    document.querySelectorAll('#ritualsTable th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.column;
            
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update sort indicators
            document.querySelectorAll('#ritualsTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(`sorted-${currentSort.direction}`);
            
            sortRituals();
            renderTable();
        });
    });
}

/**
 * Sort rituals based on current sort settings
 */
function sortRituals() {
    sortedRituals.sort((a, b) => {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];
        
        // Handle null/undefined values
        if (aVal === null || aVal === undefined || aVal === '') {
            aVal = '';
        }
        if (bVal === null || bVal === undefined || bVal === '') {
            bVal = '';
        }
        
        // Handle numeric columns
        if (currentSort.column === 'level') {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        } else {
            // Handle string columns (type, name, source, description)
            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase().trim();
            }
            if (typeof bVal === 'string') {
                bVal = bVal.toLowerCase().trim();
            }
        }
        
        // Compare values
        let comparison = 0;
        if (aVal > bVal) {
            comparison = 1;
        } else if (aVal < bVal) {
            comparison = -1;
        }
        
        // Apply sort direction
        return currentSort.direction === 'asc' ? comparison : -comparison;
    });
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('ritualSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applySearch();
        });
    }
}

/**
 * Apply search filter to rituals
 */
function applySearch() {
    const searchInput = document.getElementById('ritualSearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    if (!searchTerm) {
        // Show all rituals
        sortedRituals.forEach(ritual => {
            ritual.rowElement.style.display = '';
        });
    } else {
        // Filter rituals
        sortedRituals.forEach(ritual => {
            const type = (ritual.type || '').toLowerCase();
            const level = String(ritual.level || '');
            const name = (ritual.name || '').toLowerCase();
            const description = (ritual.description || '').toLowerCase();
            
            // Check if search term matches any field
            const matches = type.includes(searchTerm) ||
                          level.includes(searchTerm) ||
                          name.includes(searchTerm) ||
                          description.includes(searchTerm);
            
            ritual.rowElement.style.display = matches ? '' : 'none';
        });
    }
}

/**
 * Render sorted table
 */
function renderTable() {
    const tbody = document.querySelector('#ritualsTable tbody');
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Append sorted rows
    sortedRituals.forEach(ritual => {
        tbody.appendChild(ritual.rowElement);
    });
    
    // Reapply search filter after rendering
    applySearch();
}

