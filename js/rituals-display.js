/**
 * Rituals Display - Sorting, Search, and Pagination
 * Matches character (admin) page pattern: sort, filter, per-page, pagination
 */

let allRituals = [];
let sortedRituals = [];
let currentSort = { column: 'type', direction: 'asc' };
let currentPage = 1;
let pageSize = 20;
const pageSizeStorageKey = 'ritualsDisplayPageSize';

function loadSavedPageSize() {
    try {
        const stored = sessionStorage.getItem(pageSizeStorageKey);
        if (!stored) return;
        const parsed = parseInt(stored, 10);
        if (!Number.isFinite(parsed) || parsed <= 0) return;
        pageSize = parsed;
        const sel = document.getElementById('ritualPageSize');
        if (sel && [20, 50, 100].indexOf(parsed) !== -1) {
            sel.value = String(parsed);
        }
    } catch (e) {
        console.error('Unable to restore rituals page size', e);
    }
}

function persistPageSize() {
    try {
        sessionStorage.setItem(pageSizeStorageKey, String(pageSize));
    } catch (e) {
        console.error('Unable to persist rituals page size', e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeRituals();
    loadSavedPageSize();
    initializeSorting();
    initializeSearch();
    initializePagination();
    /* Apply default sort and show indicator */
    var defaultHeader = document.querySelector('#ritualsTable th.sortable[data-column="' + currentSort.column + '"]');
    if (defaultHeader) {
        defaultHeader.classList.add('sorted-' + currentSort.direction);
    }
    sortRituals();
    renderTable();
});

function initializeRituals() {
    const tbody = document.querySelector('#ritualsTable tbody');
    const rows = tbody ? tbody.querySelectorAll('tr.ritual-row') : [];
    allRituals = [];
    rows.forEach(function(row) {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const ritualId = row.dataset.ritualId || '';
            allRituals.push({
                id: ritualId,
                type: cells[0].textContent.trim(),
                level: parseInt(cells[1].textContent.trim(), 10) || 0,
                name: cells[2].textContent.trim(),
                description: cells[3].textContent.trim(),
                rowElement: row
            });
        }
    });
    sortedRituals = allRituals.slice();
}

function initializeSorting() {
    document.querySelectorAll('#ritualsTable th.sortable').forEach(function(th) {
        th.addEventListener('click', function() {
            const column = this.dataset.column;
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            document.querySelectorAll('#ritualsTable th').forEach(function(h) {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });
            this.classList.add('sorted-' + currentSort.direction);
            sortRituals();
            renderTable();
            currentPage = 1;
            updatePagination();
        });
    });
}

function sortRituals() {
    sortedRituals.sort(function(a, b) {
        let aVal = a[currentSort.column];
        let bVal = b[currentSort.column];
        if (aVal === null || aVal === undefined || aVal === '') aVal = '';
        if (bVal === null || bVal === undefined || bVal === '') bVal = '';
        if (currentSort.column === 'level') {
            aVal = parseInt(aVal, 10) || 0;
            bVal = parseInt(bVal, 10) || 0;
        } else {
            if (typeof aVal === 'string') aVal = aVal.toLowerCase().trim();
            if (typeof bVal === 'string') bVal = bVal.toLowerCase().trim();
        }
        let comparison = 0;
        if (aVal > bVal) comparison = 1;
        else if (aVal < bVal) comparison = -1;
        return currentSort.direction === 'asc' ? comparison : -comparison;
    });
}

function initializeSearch() {
    const searchInput = document.getElementById('ritualSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentPage = 1;
            updatePagination();
        });
    }
}

function getVisibleRituals() {
    const searchInput = document.getElementById('ritualSearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!searchTerm) return sortedRituals;
    return sortedRituals.filter(function(ritual) {
        const type = (ritual.type || '').toLowerCase();
        const level = String(ritual.level || '');
        const name = (ritual.name || '').toLowerCase();
        const description = (ritual.description || '').toLowerCase();
        return type.includes(searchTerm) || level.includes(searchTerm) ||
               name.includes(searchTerm) || description.includes(searchTerm);
    });
}

function initializePagination() {
    const pageSizeSelect = document.getElementById('ritualPageSize');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            pageSize = parseInt(this.value, 10) || 20;
            persistPageSize();
            currentPage = 1;
            updatePagination();
        });
    }
}

function updatePagination() {
    const visibleRituals = getVisibleRituals();
    const totalVisible = visibleRituals.length;
    const totalPages = Math.max(1, Math.ceil(totalVisible / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;

    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalVisible);
    const pageRituals = visibleRituals.slice(startIndex, endIndex);

    sortedRituals.forEach(function(ritual) {
        var show = pageRituals.indexOf(ritual) !== -1;
        ritual.rowElement.style.display = show ? '' : 'none';
    });

    const infoEl = document.getElementById('ritualPaginationInfo');
    if (infoEl) {
        if (totalVisible === 0) {
            infoEl.textContent = 'No rituals to show';
        } else {
            infoEl.textContent = 'Showing ' + (startIndex + 1) + '-' + endIndex + ' of ' + totalVisible + ' rituals';
        }
    }

    const buttonsDiv = document.getElementById('ritualPaginationButtons');
    if (!buttonsDiv) return;
    buttonsDiv.innerHTML = '';
    if (totalPages <= 1) return;

    if (currentPage > 1) {
        var prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn btn btn-outline-danger btn-sm';
        prevBtn.textContent = '\u2190 Prev';
        prevBtn.onclick = function() { goToPage(currentPage - 1); };
        buttonsDiv.appendChild(prevBtn);
    }

    for (var i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            var pageBtn = document.createElement('button');
            pageBtn.className = 'page-btn btn btn-outline-danger btn-sm';
            pageBtn.textContent = i;
            if (i === currentPage) pageBtn.classList.add('active');
            pageBtn.onclick = (function(p) { return function() { goToPage(p); }; })(i);
            buttonsDiv.appendChild(pageBtn);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            var dots = document.createElement('span');
            dots.textContent = '...';
            dots.classList.add('pagination-dots');
            buttonsDiv.appendChild(dots);
        }
    }

    if (currentPage < totalPages) {
        var nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn btn btn-outline-danger btn-sm';
        nextBtn.textContent = 'Next \u2192';
        nextBtn.onclick = function() { goToPage(currentPage + 1); };
        buttonsDiv.appendChild(nextBtn);
    }
}

function goToPage(page) {
    currentPage = page;
    updatePagination();
}

function renderTable() {
    const tbody = document.querySelector('#ritualsTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    sortedRituals.forEach(function(ritual) {
        tbody.appendChild(ritual.rowElement);
    });
    updatePagination();
}
