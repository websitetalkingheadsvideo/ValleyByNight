<?php
/**
 * Mortal Character Admin Panel
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/supabase_client.php';
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../includes/verify_role.php';
if (!isAdminUser(verifyUserRole(null, $user_id))) { header('Location: ../login.php'); exit; }
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <p class="mb-2"><a href="admin_panel.php" class="text-danger">← Back to Character Management</a></p>
            <h1 class="mb-4">Mortal Character Management</h1>
            <div class="character-table-wrapper table-responsive rounded-3">
                <table class="character-table table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start">Name</th>
                            <th class="text-center text-nowrap">Power Source</th>
                            <th class="text-center text-nowrap">Status</th>
                            <th class="text-center text-nowrap" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rows_error = '';
                        try {
                            $rows = supabase_table_get('mortal_characters', ['select' => '*', 'order' => 'id.desc']);
                        } catch (Throwable $e) {
                            error_log('mortal_admin_panel: mortal_characters query failed: ' . $e->getMessage());
                            $rows = [];
                            $rows_error = $e->getMessage();
                        }
                        if ($rows_error !== '') { echo '<tr><td colspan="4" class="text-center text-warning">Failed to load: ' . htmlspecialchars($rows_error, ENT_QUOTES, 'UTF-8') . '</td></tr>'; }
                        elseif (empty($rows)) { echo "<tr><td colspan='4' class='text-center'>No Mortal characters found.</td></tr>"; } else {
                            foreach ($rows as $c) {
                                $st = strtolower(trim($c['status'] ?? 'active')); if ($st === '') $st = 'active';
                                $badge = $st === 'active' ? 'badge bg-success' : ($st === 'inactive' ? 'badge bg-secondary' : 'badge bg-warning');
                                ?>
                                <tr>
                                    <td class="text-light"><strong><?php echo htmlspecialchars($c['character_name']); ?></strong></td>
                                    <td class="text-center"><?php echo htmlspecialchars($c['power_source'] ?? '—'); ?></td>
                                    <td class="text-center"><span class="<?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($st)); ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="view-btn btn btn-primary" data-id="<?php echo (int)$c['id']; ?>" title="View">👁️</button>
                                            <button class="delete-btn btn btn-danger" data-id="<?php echo (int)$c['id']; ?>" data-name="<?php echo htmlspecialchars($c['character_name']); ?>" title="Delete">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $apiEndpoint = '/admin/view_mortal_character_api.php'; $modalId = 'viewCharacterModal'; include __DIR__ . '/../includes/character_view_modal.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-btn').forEach(function(b) { b.addEventListener('click', function() { if (window.viewCharacter && this.dataset.id) window.viewCharacter(this.dataset.id); }); });
    document.querySelectorAll('.delete-btn').forEach(function(b) {
        b.addEventListener('click', function() {
            if (!confirm('Delete: ' + this.dataset.name + '?')) return;
            fetch('/admin/delete_mortal_character_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ character_id: this.dataset.id }) })
                .then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert(d.message || 'Delete failed'); }).catch(function() { alert('Delete failed'); });
        });
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
