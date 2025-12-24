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
        setupActionButtons();
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
     * Setup action buttons (view, edit, delete)
     */
    function setupActionButtons() {
        // View buttons
        const viewButtons = document.querySelectorAll('.view-btn');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const positionId = this.dataset.id;
                if (!positionId) {
                    console.error('View button missing position ID');
                    return;
                }
                if (typeof window.viewPosition === 'function') {
                    window.viewPosition(positionId, 'view');
                } else {
                    console.error('viewPosition function not found. Make sure position_view_modal.php is included.');
                }
            });
        });
        
        // Edit buttons
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const positionId = this.dataset.id;
                if (!positionId) {
                    console.error('Edit button missing position ID');
                    return;
                }
                if (typeof window.editPosition === 'function') {
                    window.editPosition(positionId);
                } else {
                    console.error('editPosition function not found. Make sure position_view_modal.php is included.');
                }
            });
        });
        
        // Delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const positionId = this.dataset.id;
                const positionName = this.dataset.name || 'Unknown';
                if (positionId) {
                    openDeletePositionModal(positionId, positionName);
                }
            });
        });
        
        // Confirm delete button
        const confirmDeleteBtn = document.getElementById('confirmDeletePositionBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', confirmDeletePosition);
        }
    }
    
    /**
     * Open delete confirmation modal
     */
    let deletePositionModalInstance = null;
    
    function openDeletePositionModal(positionId, positionName) {
        const modal = document.getElementById('deletePositionModal');
        if (!modal) return;
        
        // Initialize Bootstrap modal instance if needed
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (!deletePositionModalInstance) {
                deletePositionModalInstance = bootstrap.Modal.getOrCreateInstance(modal);
            }
        }
        
        const nameEl = document.getElementById('deletePositionName');
        const warningEl = document.getElementById('deleteWarning');
        
        if (nameEl) {
            nameEl.textContent = positionName;
        }
        
        if (warningEl) {
            warningEl.style.display = 'block';
        }
        
        // Store position ID in modal element
        modal.dataset.positionId = positionId;
        
        // Show Bootstrap modal
        if (deletePositionModalInstance) {
            deletePositionModalInstance.show();
        }
    }
    
    /**
     * Close delete confirmation modal
     */
    window.closeDeletePositionModal = function() {
        if (deletePositionModalInstance) {
            deletePositionModalInstance.hide();
        }
        const modal = document.getElementById('deletePositionModal');
        if (modal) {
            delete modal.dataset.positionId;
        }
    };
    
    // Reset on modal close
    if (document.getElementById('deletePositionModal')) {
        document.getElementById('deletePositionModal').addEventListener('hidden.bs.modal', function() {
            const modal = document.getElementById('deletePositionModal');
            if (modal) {
                delete modal.dataset.positionId;
            }
        });
    }
    
    /**
     * Confirm and execute position deletion
     */
    function confirmDeletePosition() {
        const modal = document.getElementById('deletePositionModal');
        if (!modal) return;
        
        const positionId = modal.dataset.positionId;
        if (!positionId) return;
        
        const confirmBtn = document.getElementById('confirmDeletePositionBtn');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Deleting...';
        }
        
        fetch('/admin/delete_position_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                position_id: positionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table
                const row = document.querySelector(`tr.position-row[data-id="${positionId}"]`);
                if (row) {
                    row.remove();
                }
                closeDeletePositionModal();
            } else {
                alert('Error deleting position: ' + (data.message || 'Unknown error'));
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete';
                }
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('Error deleting position. Check console for details.');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';
            }
        });
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

