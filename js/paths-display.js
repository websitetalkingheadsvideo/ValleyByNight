/**
 * Paths Display - Sorting and Search Functionality
 * Handles client-side sorting and searching of paths table
 */

let allPaths = [];
let sortedPaths = [];
let currentSort = { column: 'type', direction: 'asc' };

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializePaths();
    initializeSorting();
    initializeSearch();
});

/**
 * Initialize paths data from table
 */
function initializePaths() {
    const tbody = document.querySelector('#pathsTable tbody');
    const rows = tbody.querySelectorAll('tr');
    
    allPaths = [];
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const pathId = row.dataset.pathId || '';
            allPaths.push({
                id: pathId,
                type: cells[0].textContent.trim(),
                name: cells[1].textContent.trim(),
                description: cells[2].textContent.trim(),
                source: cells[3].textContent.trim(),
                rowElement: row
            });
        }
    });
    
    sortedPaths = [...allPaths];
}

/**
 * Initialize sorting event listeners
 */
function initializeSorting() {
    document.querySelectorAll('#pathsTable th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.column;
            
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update sort indicators
            document.querySelectorAll('#pathsTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(`sorted-${currentSort.direction}`);
            
            sortPaths();
            renderTable();
        });
    });
}

/**
 * Sort paths based on current sort settings
 */
function sortPaths() {
    sortedPaths.sort((a, b) => {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];
        
        // Handle null/undefined values
        if (aVal === null || aVal === undefined || aVal === '') {
            aVal = '';
        }
        if (bVal === null || bVal === undefined || bVal === '') {
            bVal = '';
        }
        
        // Handle string columns (type, name, source, description)
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase().trim();
        }
        if (typeof bVal === 'string') {
            bVal = bVal.toLowerCase().trim();
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
    const searchInput = document.getElementById('pathSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applySearch();
        });
    }
}

/**
 * Apply search filter to paths
 */
function applySearch() {
    const searchInput = document.getElementById('pathSearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    if (!searchTerm) {
        // Show all paths
        sortedPaths.forEach(path => {
            path.rowElement.style.display = '';
        });
    } else {
        // Filter paths
        sortedPaths.forEach(path => {
            const type = (path.type || '').toLowerCase();
            const name = (path.name || '').toLowerCase();
            const description = (path.description || '').toLowerCase();
            const source = (path.source || '').toLowerCase();
            
            // Check if search term matches any field
            const matches = type.includes(searchTerm) ||
                          name.includes(searchTerm) ||
                          description.includes(searchTerm) ||
                          source.includes(searchTerm);
            
            path.rowElement.style.display = matches ? '' : 'none';
        });
    }
}

/**
 * Render sorted table
 */
function renderTable() {
    const tbody = document.querySelector('#pathsTable tbody');
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Append sorted rows
    sortedPaths.forEach(path => {
        tbody.appendChild(path.rowElement);
    });
    
    // Reapply search filter after rendering
    applySearch();
}

