<?php
/**
 * Werewolf (Garou) Character Admin Panel
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/connect.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/verify_role.php';
$user_role = verifyUserRole($conn, $user_id);
if (!isAdminUser($user_role)) {
    header('Location: ../login.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <p class="mb-2"><a href="admin_panel.php" class="text-danger">← Back to Character Management</a></p>
            <h1 class="mb-4">Garou (Werewolf) Character Management</h1>

            <div class="character-table-wrapper table-responsive rounded-3">
                <table class="character-table table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start">Name</th>
                            <th class="text-center text-nowrap">Breed</th>
                            <th class="text-center text-nowrap">Auspice</th>
                            <th class="text-center text-nowrap">Tribe</th>
                            <th class="text-center text-nowrap">Status</th>
                            <th class="text-center text-nowrap" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $char_query = "SELECT w.*, u.username as owner_username FROM werewolf_characters w LEFT JOIN users u ON w.user_id = u.id ORDER BY w.id DESC";
                        $char_result = mysqli_query($conn, $char_query);
                        if (!$char_result) {
                            echo "<tr><td colspan='6'>Query error: " . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
                        } elseif (mysqli_num_rows($char_result) === 0) {
                            echo "<tr><td colspan='6' class='text-center'>No Garou characters found.</td></tr>";
                        } else {
                            while ($char = mysqli_fetch_assoc($char_result)) {
                                $status = strtolower(trim($char['status'] ?? 'active'));
                                if ($status === '') $status = 'active';
                                $badge = $status === 'active' ? 'badge bg-success' : ($status === 'inactive' ? 'badge bg-secondary' : 'badge bg-warning');
                                ?>
                                <tr>
                                    <td class="text-light"><strong><?php echo htmlspecialchars($char['character_name']); ?></strong></td>
                                    <td class="text-center text-nowrap"><?php echo htmlspecialchars($char['breed'] ?? '—'); ?></td>
                                    <td class="text-center text-nowrap"><?php echo htmlspecialchars($char['auspice'] ?? '—'); ?></td>
                                    <td class="text-center text-nowrap"><?php echo htmlspecialchars($char['tribe'] ?? '—'); ?></td>
                                    <td class="text-center text-nowrap"><span class="<?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="action-btn view-btn btn btn-primary" data-id="<?php echo (int)$char['id']; ?>" title="View">👁️</button>
                                            <button class="action-btn delete-btn btn btn-danger" data-id="<?php echo (int)$char['id']; ?>" data-name="<?php echo htmlspecialchars($char['character_name']); ?>" data-type="werewolf" title="Delete">🗑️</button>
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

<?php
$apiEndpoint = '/admin/view_werewolf_character_api.php';
$modalId = 'viewCharacterModal';
include __DIR__ . '/../includes/character_view_modal.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (window.viewCharacter && this.dataset.id) window.viewCharacter(this.dataset.id);
        });
    });
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id, name = this.dataset.name, type = this.dataset.type || 'werewolf';
            if (!confirm('Delete character: ' + name + '?')) return;
            fetch('/admin/delete_werewolf_character_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ character_id: id }) })
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d.success) location.reload(); else alert(d.message || 'Delete failed'); })
                .catch(function() { alert('Delete failed'); });
        });
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
