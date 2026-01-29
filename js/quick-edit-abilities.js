/**
 * Quick-edit abilities UI (same add/remove pattern as wraith_char_create / character editor).
 * Expects #quick-edit-form, #abilities_json, .ability-option-btn, .ability-list[data-category].
 */
(function () {
    'use strict';

    const form = document.getElementById('quick-edit-form');
    const hidden = document.getElementById('abilities_json');
    if (!form || !hidden) return;

    let state = { Physical: [], Social: [], Mental: [], Optional: [] };
    try {
        const parsed = JSON.parse(hidden.value || '{}');
        ['Physical', 'Social', 'Mental', 'Optional'].forEach(function (cat) {
            state[cat] = Array.isArray(parsed[cat]) ? parsed[cat].slice() : [];
        });
    } catch (e) {}

    function syncHidden() {
        hidden.value = JSON.stringify(state);
    }

    function render() {
        ['Physical', 'Social', 'Mental', 'Optional'].forEach(function (category) {
            const listEl = document.getElementById(category.toLowerCase() + 'AbilitiesList');
            if (!listEl) return;
            const arr = state[category] || [];
            const counts = {};
            arr.forEach(function (name) {
                counts[name] = (counts[name] || 0) + 1;
            });
            let html = '';
            Object.keys(counts).forEach(function (name) {
                const level = counts[name];
                html += '<div class="selected-ability d-inline-flex align-items-center gap-1 me-2 mb-1">';
                html += '<span class="ability-name">' + escapeHtml(name) + (level > 1 ? ' (' + level + ')' : '') + '</span>';
                html += '<button type="button" class="btn btn-sm btn-outline-danger remove-ability-btn" data-category="' + escapeHtml(category) + '" data-ability="' + escapeHtml(name) + '">×</button>';
                html += '</div>';
            });
            listEl.innerHTML = html || '<span class="text-white">None selected</span>';
            listEl.querySelectorAll('.remove-ability-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cat = this.getAttribute('data-category');
                    const ab = this.getAttribute('data-ability');
                    const idx = (state[cat] || []).lastIndexOf(ab);
                    if (idx > -1) {
                        state[cat].splice(idx, 1);
                        render();
                        syncHidden();
                    }
                });
            });
        });
        syncHidden();
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    form.querySelectorAll('.ability-option-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const category = this.getAttribute('data-category');
            const ability = this.getAttribute('data-ability');
            if (!category || !ability) return;
            const arr = state[category] || [];
            const n = arr.filter(function (a) { return a === ability; }).length;
            if (n >= 5) return;
            state[category] = arr.concat(ability);
            render();
        });
    });

    form.addEventListener('submit', function () {
        syncHidden();
    });

    render();
})();
