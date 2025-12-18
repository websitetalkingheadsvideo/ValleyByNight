/**
 * Admin Sire/Childe Relationship Tracker - JavaScript
 * Extracted from admin/admin_sire_childe.php
 */

let currentFilter = 'all';
let currentRelationshipId = null;
let currentSort = { column: null, direction: 'asc' };

document.addEventListener('DOMContentLoaded', function() {
    initializeAll();
});

function initializeAll() {
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });
    
    // Search
    const relationshipSearch = document.getElementById('relationshipSearch');
    if (relationshipSearch) {
        relationshipSearch.addEventListener('input', applyFilters);
    }
    
    // Sort buttons - using pattern from admin_panel.js
    const headers = document.querySelectorAll('#relationshipTable th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
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
        });
    });
    
    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            editRelationship(this.dataset.id, this.dataset.name, this.dataset.sire);
        });
    });
    
    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            viewCharacter(this.dataset.id);
        });
    });
    
    // Form submission
    const relationshipForm = document.getElementById('relationshipForm');
    if (relationshipForm) {
        relationshipForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Add Relationship Button
    const addRelationshipBtn = document.getElementById('addRelationshipBtn');
    if (addRelationshipBtn) {
        addRelationshipBtn.addEventListener('click', openAddRelationshipModal);
    }
    
    // Show Family Tree Button
    const showFamilyTreeBtn = document.getElementById('showFamilyTreeBtn');
    if (showFamilyTreeBtn) {
        showFamilyTreeBtn.addEventListener('click', showFamilyTree);
    }
    
    // Initial filter
    applyFilters();
}

function sortTable(column, direction) {
    const tbody = document.querySelector('#relationshipTable tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('.relationship-row'));
    
    rows.sort((a, b) => {
        let aVal = '';
        let bVal = '';

        switch(column) {
            case 'character_name':
                aVal = (a.dataset.name || '').toLowerCase();
                bVal = (b.dataset.name || '').toLowerCase();
                break;
            case 'clan':
                aVal = (a.cells[1]?.textContent?.trim() || '').toLowerCase();
                bVal = (b.cells[1]?.textContent?.trim() || '').toLowerCase();
                break;
            case 'generation':
                aVal = parseInt(a.cells[2]?.textContent?.replace('th', '').trim() || '0', 10) || 0;
                bVal = parseInt(b.cells[2]?.textContent?.replace('th', '').trim() || '0', 10) || 0;
                break;
            case 'sire':
                aVal = (a.dataset.sire || '').toLowerCase();
                bVal = (b.dataset.sire || '').toLowerCase();
                break;
            case 'player_name':
                aVal = (a.cells[5]?.textContent?.trim() || '').toLowerCase();
                bVal = (b.cells[5]?.textContent?.trim() || '').toLowerCase();
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
    
    // Update sort icons
    document.querySelectorAll('#relationshipTable th[data-sort] .sort-icon').forEach(icon => {
        icon.textContent = '⇅';
    });
    const activeTh = document.querySelector(`#relationshipTable th[data-sort="${column}"]`);
    if (activeTh) {
        const icon = activeTh.querySelector('.sort-icon');
        if (icon) {
            icon.textContent = direction === 'asc' ? '↑' : '↓';
        }
    }
}

function applyFilters() {
    const relationshipSearch = document.getElementById('relationshipSearch');
    if (!relationshipSearch) return;
    
    const searchTerm = relationshipSearch.value.toLowerCase();
    const rows = document.querySelectorAll('.relationship-row');
    
    rows.forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const sire = row.dataset.sire.toLowerCase();
        const hasSire = row.dataset.hasSire === 'true';
        const hasChilder = row.dataset.hasChilder === 'true';
        
        let show = true;
        
        // Apply filter
        if (currentFilter === 'sires' && !hasChilder) show = false;
        if (currentFilter === 'childer' && !hasSire) show = false;
        if (currentFilter === 'sireless' && hasSire) show = false;
        
        // Apply search
        if (searchTerm && !name.includes(searchTerm) && !sire.includes(searchTerm)) show = false;
        
        if (show) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function openAddRelationshipModal() {
    currentRelationshipId = null;
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.textContent = 'Add Relationship';
    }
    const relationshipForm = document.getElementById('relationshipForm');
    if (relationshipForm) {
        relationshipForm.reset();
    }
    const characterId = document.getElementById('characterId');
    if (characterId) {
        characterId.value = '';
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('relationshipModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
}

function editRelationship(id, name, sire) {
    currentRelationshipId = id;
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.textContent = 'Edit Relationship';
    }
    const characterId = document.getElementById('characterId');
    if (characterId) {
        characterId.value = id;
    }
    const characterSelect = document.getElementById('characterSelect');
    if (characterSelect) {
        characterSelect.value = id;
    }
    const sireSelect = document.getElementById('sireSelect');
    if (sireSelect) {
        sireSelect.value = sire;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('relationshipModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
}

function closeRelationshipModal() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('relationshipModal'));
        if (modal) modal.hide();
    }
    currentRelationshipId = null;
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    fetch('api_sire_childe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('relationshipModal'));
                if (modal) modal.hide();
            }
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error saving relationship');
        console.error(error);
    });
}

function showFamilyTree() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(document.getElementById('treeModal'));
        modal.show();
    } else {
        console.error('Bootstrap is not loaded');
    }
    const familyTreeContent = document.getElementById('familyTreeContent');
    if (familyTreeContent) {
        familyTreeContent.innerHTML = '<div class="text-center text-light py-4">Loading family tree...</div>';
    }
    
    // Simple family tree display
    fetch('api_sire_childe.php?action=tree')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderFamilyTree(data.tree);
            } else {
                const familyTreeContent = document.getElementById('familyTreeContent');
                if (familyTreeContent) {
                    familyTreeContent.innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
                }
            }
        })
        .catch(error => {
            const familyTreeContent = document.getElementById('familyTreeContent');
            if (familyTreeContent) {
                familyTreeContent.innerHTML = '<div class="alert alert-danger">Error loading family tree</div>';
            }
            console.error(error);
        });
}

function renderFamilyTree(tree) {
    let html = '';
    
    // Group by generation
    const generations = {};
    tree.forEach(char => {
        if (!generations[char.generation]) {
            generations[char.generation] = [];
        }
        generations[char.generation].push(char);
    });
    
    // Sort generations (highest first)
    const sortedGens = Object.keys(generations).sort((a, b) => parseInt(b) - parseInt(a));
    
    sortedGens.forEach(gen => {
        html += `<div class="card bg-dark border-danger mb-3">`;
        html += `<div class="card-header border-danger">`;
        html += `<h3 class="h5 mb-0 text-light">Generation ${gen}</h3>`;
        html += `</div>`;
        html += `<div class="card-body">`;
        html += `<div class="row g-3">`;
        
        generations[gen].forEach(char => {
            html += `<div class="col-md-6 col-lg-4">`;
            html += `<div class="card bg-secondary border-danger h-100">`;
            html += `<div class="card-body">`;
            html += `<h5 class="card-title text-light">${char.character_name}</h5>`;
            html += `<p class="card-text text-muted mb-2">${char.clan}</p>`;
            if (char.sire) {
                html += `<p class="card-text small text-danger mb-1"><strong>Sired by:</strong> ${char.sire}</p>`;
            }
            if (char.childer && char.childer.length > 0) {
                html += `<p class="card-text small text-purple mb-0"><strong>Childer:</strong> ${char.childer.join(', ')}</p>`;
            }
            html += `</div>`;
            html += `</div>`;
            html += `</div>`;
        });
        
        html += `</div>`;
        html += `</div>`;
        html += `</div>`;
    });
    
    const familyTreeContent = document.getElementById('familyTreeContent');
    if (familyTreeContent) {
        familyTreeContent.innerHTML = html;
    }
}

function closeTreeModal() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('treeModal'));
        if (modal) modal.hide();
    }
}

// viewCharacter function is defined globally by character_view_modal.php
// We just need to call it - no need to redefine it here

