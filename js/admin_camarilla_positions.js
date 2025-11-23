/**
 * Admin Camarilla Positions JavaScript
 * Handles filtering, searching, and table interactions
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        setupFilters();
        setupSearch();
        setupTableSorting();
    }
    
    /**
     * Setup filter controls (category, clan)
     */
    function setupFilters() {
        const categoryFilter = document.getElementById('categoryFilter');
        const clanFilter = document.getElementById('clanFilter');
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', applyFilters);
        }
        
        if (clanFilter) {
            clanFilter.addEventListener('change', applyFilters);
        }
    }
    
    /**
     * Setup search functionality
     */
    function setupSearch() {
        const searchInput = document.getElementById('positionSearch');
        
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
    }
    
    /**
     * Apply all active filters and search
     */
    function applyFilters() {
        const categoryFilter = document.getElementById('categoryFilter');
        const clanFilter = document.getElementById('clanFilter');
        const searchInput = document.getElementById('positionSearch');
        
        const selectedCategory = categoryFilter ? categoryFilter.value : 'all';
        const selectedClan = clanFilter ? clanFilter.value : 'all';
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        
        const rows = document.querySelectorAll('#positionsTable tbody tr.position-row');
        
        rows.forEach(row => {
            const category = row.getAttribute('data-category') || '';
            const clan = row.getAttribute('data-clan') || '';
            const name = row.getAttribute('data-name') || '';
            
            let show = true;
            
            // Category filter
            if (selectedCategory !== 'all' && category !== selectedCategory) {
                show = false;
            }
            
            // Clan filter
            if (show && selectedClan !== 'all' && clan !== selectedClan) {
                show = false;
            }
            
            // Search filter
            if (show && searchTerm && !name.includes(searchTerm)) {
                show = false;
            }
            
            // Show/hide row
            if (show) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    /**
     * Setup table column sorting
     */
    function setupTableSorting() {
        const headers = document.querySelectorAll('#positionsTable thead th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const sortType = this.getAttribute('data-sort');
                const currentSort = this.getAttribute('data-sort-direction') || 'none';
                
                // Reset all headers
                headers.forEach(h => {
                    h.removeAttribute('data-sort-direction');
                    h.classList.remove('sorted-asc', 'sorted-desc');
                });
                
                // Determine new sort direction
                let newDirection = 'asc';
                if (currentSort === 'asc') {
                    newDirection = 'desc';
                }
                
                this.setAttribute('data-sort-direction', newDirection);
                this.classList.add('sorted-' + newDirection);
                
                sortTable(sortType, newDirection);
            });
        });
    }
    
    /**
     * Sort table by column
     */
    function sortTable(sortType, direction) {
        const tbody = document.querySelector('#positionsTable tbody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr.position-row'));
        
        rows.sort((a, b) => {
            let aValue, bValue;
            
            switch(sortType) {
                case 'name':
                    aValue = a.querySelector('td:first-child strong')?.textContent?.trim() || '';
                    bValue = b.querySelector('td:first-child strong')?.textContent?.trim() || '';
                    break;
                case 'category':
                    aValue = a.getAttribute('data-category') || '';
                    bValue = b.getAttribute('data-category') || '';
                    break;
                case 'holder':
                    aValue = a.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
                    bValue = b.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
                    break;
                case 'start':
                    aValue = a.querySelector('td:nth-child(5)')?.textContent?.trim() || '';
                    bValue = b.querySelector('td:nth-child(5)')?.textContent?.trim() || '';
                    // Parse dates for proper sorting
                    if (aValue && aValue !== '—') {
                        aValue = new Date(aValue).getTime() || 0;
                    } else {
                        aValue = 0;
                    }
                    if (bValue && bValue !== '—') {
                        bValue = new Date(bValue).getTime() || 0;
                    } else {
                        bValue = 0;
                    }
                    break;
                default:
                    return 0;
            }
            
            // Compare values
            if (aValue < bValue) {
                return direction === 'asc' ? -1 : 1;
            }
            if (aValue > bValue) {
                return direction === 'asc' ? 1 : -1;
            }
            return 0;
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
        
        // Re-apply filters after sorting
        applyFilters();
    }
    
    /**
     * View position history (placeholder - can be enhanced with modal)
     */
    window.viewPositionHistory = function(positionId) {
        // For now, scroll to agent section and pre-fill position lookup
        const positionSelect = document.getElementById('position_id');
        if (positionSelect) {
            positionSelect.value = positionId;
            // Scroll to agent section
            const agentSection = document.querySelector('.agent-section');
            if (agentSection) {
                agentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    };
    
})();

