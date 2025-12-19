/**
 * Abilities Display - Sorting and Search Functionality
 * Handles client-side sorting and searching of abilities table
 */

let allAbilities = [];
let sortedAbilities = [];
let currentSort = { column: 'category', direction: 'asc' };

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeAbilities();
    initializeSorting();
    initializeSearch();
});

/**
 * Initialize abilities data from table
 */
function initializeAbilities() {
    const tbody = document.querySelector('#abilitiesTable tbody');
    const rows = tbody.querySelectorAll('tr');
    
    allAbilities = [];
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 5) {
            const abilityId = row.dataset.abilityId || '';
            allAbilities.push({
                id: abilityId,
                category: cells[0].textContent.trim(),
                name: cells[1].textContent.trim(),
                description: cells[2].textContent.trim(),
                min_level: cells[3].textContent.trim(),
                max_level: cells[4].textContent.trim(),
                rowElement: row
            });
        }
    });
    
    sortedAbilities = [...allAbilities];
}

/**
 * Initialize sorting event listeners
 */
function initializeSorting() {
    document.querySelectorAll('#abilitiesTable th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.column;
            
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update sort indicators
            document.querySelectorAll('#abilitiesTable th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add(`sorted-${currentSort.direction}`);
            
            sortAbilities();
            renderTable();
        });
    });
}

/**
 * Sort abilities based on current sort settings
 */
function sortAbilities() {
    sortedAbilities.sort((a, b) => {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];
        
        // Handle null/undefined values
        if (aVal === null || aVal === undefined || aVal === '') {
            aVal = '';
        }
        if (bVal === null || bVal === undefined || bVal === '') {
            bVal = '';
        }
        
        // Handle numeric columns (min_level, max_level)
        if (currentSort.column === 'min_level' || currentSort.column === 'max_level') {
            const aNum = aVal === 'N/A' ? -1 : parseInt(aVal, 10);
            const bNum = bVal === 'N/A' ? -1 : parseInt(bVal, 10);
            const comparison = aNum - bNum;
            return currentSort.direction === 'asc' ? comparison : -comparison;
        }
        
        // Handle string columns (category, name, description)
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
    const searchInput = document.getElementById('abilitySearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applySearch();
        });
    }
}

/**
 * Apply search filter to abilities
 */
function applySearch() {
    const searchInput = document.getElementById('abilitySearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    if (!searchTerm) {
        // Show all abilities
        sortedAbilities.forEach(ability => {
            ability.rowElement.style.display = '';
        });
    } else {
        // Filter abilities
        sortedAbilities.forEach(ability => {
            const category = (ability.category || '').toLowerCase();
            const name = (ability.name || '').toLowerCase();
            const description = (ability.description || '').toLowerCase();
            const minLevel = (ability.min_level || '').toLowerCase();
            const maxLevel = (ability.max_level || '').toLowerCase();
            
            // Check if search term matches any field
            const matches = category.includes(searchTerm) ||
                          name.includes(searchTerm) ||
                          description.includes(searchTerm) ||
                          minLevel.includes(searchTerm) ||
                          maxLevel.includes(searchTerm);
            
            ability.rowElement.style.display = matches ? '' : 'none';
        });
    }
}

/**
 * Render sorted table
 */
function renderTable() {
    const tbody = document.querySelector('#abilitiesTable tbody');
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Append sorted rows
    sortedAbilities.forEach(ability => {
        tbody.appendChild(ability.rowElement);
    });
    
    // Reapply search filter after rendering
    applySearch();
}

