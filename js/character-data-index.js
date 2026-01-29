/**
 * Character Data Quality index page: sortable table headers and filter/overlay.
 * Table id: character-missing-table. Sortable headers: th[data-sort].
 */
(function() {
    'use strict';

    var sortKey = null;
    var sortDir = 1;

    function findSortButton(el) {
        while (el && el !== document.body) {
            if (el.tagName === 'BUTTON' && el.getAttribute('data-sort')) {
                var tbl = el.parentNode;
                while (tbl && tbl.tagName !== 'TABLE') { tbl = tbl.parentNode; }
                if (tbl && tbl.id === 'character-missing-table') { return el; }
            }
            el = el.parentElement;
        }
        return null;
    }

    document.addEventListener('click', function(e) {
        var btn = findSortButton(e.target);
        if (!btn) return;
        e.preventDefault();
        var th = btn.parentNode;
        var sortTable = document.getElementById('character-missing-table');
        var tbody = sortTable ? sortTable.querySelector('tbody') : null;
        if (!tbody) return;
        var key = btn.getAttribute('data-sort');
        if (sortKey === key) {
            sortDir = -sortDir;
        } else {
            sortKey = key;
            sortDir = 1;
        }
        var headerButtons = sortTable.querySelectorAll('th .sortable-header-btn');
        for (var i = 0; i < headerButtons.length; i++) {
            var h = headerButtons[i].parentNode;
            h.classList.remove('sort-asc', 'sort-desc');
            if (headerButtons[i].getAttribute('data-sort') === key) {
                h.classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
            }
        }
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var dataAttr = 'data-' + key.replace('_', '-');
        rows.sort(function(a, b) {
            var va = a.getAttribute(dataAttr);
            var vb = b.getAttribute(dataAttr);
            if (key === 'missing_count') {
                va = parseInt(va, 10) || 0;
                vb = parseInt(vb, 10) || 0;
                return sortDir * (va - vb);
            }
            va = (va || '').toLowerCase();
            vb = (vb || '').toLowerCase();
            return sortDir * (va < vb ? -1 : va > vb ? 1 : 0);
        });
        rows.forEach(function(r) { tbody.appendChild(r); });
    });

    document.addEventListener('DOMContentLoaded', function() {
        var filterButtons = document.querySelectorAll('.filter-btn');
        var clearFilterBtn = document.getElementById('clear-filter');
        var tableRows = document.querySelectorAll('#character-missing-table tbody tr');

        if (filterButtons.length && clearFilterBtn) {
            var activeFilter = null;

            function clearFilter() {
                activeFilter = null;
                for (var i = 0; i < filterButtons.length; i++) {
                    filterButtons[i].classList.remove('active', 'btn-danger');
                    filterButtons[i].classList.add('btn-outline-danger');
                }
                for (var j = 0; j < tableRows.length; j++) {
                    tableRows[j].style.display = '';
                }
            }

            for (var i = 0; i < filterButtons.length; i++) {
                filterButtons[i].addEventListener('click', function() {
                    var field = this.getAttribute('data-field');
                    if (activeFilter === field) {
                        clearFilter();
                        return;
                    }
                    activeFilter = field;
                    for (var k = 0; k < filterButtons.length; k++) {
                        filterButtons[k].classList.remove('active', 'btn-danger');
                        filterButtons[k].classList.add('btn-outline-danger');
                    }
                    this.classList.add('active', 'btn-danger');
                    this.classList.remove('btn-outline-danger');
                    for (var r = 0; r < tableRows.length; r++) {
                        var row = tableRows[r];
                        var cell = row.querySelector('td:last-child');
                        if (!cell) continue;
                        var badges = cell.querySelectorAll('.badge.bg-danger');
                        var hasField = false;
                        for (var b = 0; b < badges.length; b++) {
                            if (badges[b].textContent.trim() === field) {
                                hasField = true;
                                break;
                            }
                        }
                        row.style.display = hasField ? '' : 'none';
                    }
                });
            }
            clearFilterBtn.addEventListener('click', clearFilter);
        }

        var updateForm = document.getElementById('update-database-form');
        var loadingOverlay = document.getElementById('loading-overlay');
        if (updateForm && loadingOverlay) {
            updateForm.addEventListener('submit', function() {
                loadingOverlay.style.display = 'flex';
            });
        }
    });
})();
