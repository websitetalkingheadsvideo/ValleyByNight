<?php
declare(strict_types=1);

/**
 * Character Images Audit - Admin report of characters missing or broken images.
 */
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';
$extra_css = ['css/modal.css', 'css/admin-agents.css', 'css/admin_panel.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <h1 class="panel-title display-5 text-light fw-bold mb-1">Character Image Audit</h1>
    <p class="panel-subtitle lead text-light fst-italic mb-4">Characters missing or broken images (aligned with View Character modal)</p>

    <p class="mb-3">
        <a href="admin_panel.php" class="btn btn-outline-danger btn-sm">← Back to Character Management</a>
        <a href="api_character_images_audit.php" class="btn btn-outline-secondary btn-sm ms-2" target="_blank" rel="noopener">Download JSON report</a>
        <span id="auditSavedNotice" class="ms-2 text-light d-none" aria-live="polite"></span>
    </p>

    <div id="auditSummary" class="row g-3 mb-4" aria-live="polite"></div>
    <div id="auditError" class="alert alert-danger d-none" role="alert"></div>
    <div id="auditTableWrap" class="table-responsive">
        <table class="table table-dark table-striped table-hover">
            <thead>
                <tr>
                    <th>Character name</th>
                    <th>Image status</th>
                    <th>Image reference</th>
                </tr>
            </thead>
            <tbody id="auditTableBody"></tbody>
        </table>
    </div>
</div>

<script>
(function() {
    'use strict';
    var summaryEl = document.getElementById('auditSummary');
    var errorEl = document.getElementById('auditError');
    var bodyEl = document.getElementById('auditTableBody');

    function renderSummary(s) {
        if (!s) return;
        summaryEl.innerHTML =
            '<div class="col-12 col-sm-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="vbn-stat-number">' + s.total + '</div><div class="vbn-stat-label">Total</div></div></div>' +
            '<div class="col-12 col-sm-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="vbn-stat-number">' + s.present + '</div><div class="vbn-stat-label">Present</div></div></div>' +
            '<div class="col-12 col-sm-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="vbn-stat-number">' + s.missing + '</div><div class="vbn-stat-label">Missing</div></div></div>' +
            '<div class="col-12 col-sm-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="vbn-stat-number">' + s.broken + '</div><div class="vbn-stat-label">Broken</div></div></div>';
    }

    function statusClass(status) {
        if (status === 'Present') return 'badge-active text-success';
        if (status === 'Broken') return 'badge-npc';
        return 'badge-inactive';
    }

    function renderTable(characters) {
        bodyEl.innerHTML = '';
        if (!characters || characters.length === 0) {
            bodyEl.innerHTML = '<tr><td colspan="3">No characters.</td></tr>';
            return;
        }
        characters.forEach(function(c) {
            var ref = c.image_reference && c.image_reference.trim() !== '' ? c.image_reference : '—';
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + escapeHtml(c.character_name) + '</strong></td>' +
                '<td><span class="badge ' + statusClass(c.image_status) + '">' + escapeHtml(c.image_status) + '</span></td>' +
                '<td>' + escapeHtml(ref) + '</td>';
            bodyEl.appendChild(tr);
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    fetch('api_character_images_audit.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.summary && data.characters) {
                errorEl.classList.add('d-none');
                renderSummary(data.summary);
                renderTable(data.characters);
                fetch('api_character_images_audit.php?save=1')
                    .then(function(r) { return r.json(); })
                    .then(function(saveData) {
                        if (saveData.success && saveData.saved_to && saveData.saved_to.length) {
                            var notice = document.getElementById('auditSavedNotice');
                            notice.textContent = 'Report saved to admin/reports/';
                            notice.classList.remove('d-none');
                        }
                    })
                    .catch(function() {});
            } else {
                errorEl.textContent = data.message || 'Invalid response';
                errorEl.classList.remove('d-none');
            }
        })
        .catch(function(err) {
            errorEl.textContent = 'Failed to load audit: ' + (err.message || 'Unknown error');
            errorEl.classList.remove('d-none');
        });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
