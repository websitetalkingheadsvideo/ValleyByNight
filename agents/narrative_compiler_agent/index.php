<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../includes/supabase_client.php';
$conn = null;

// Helper function for HTML escaping
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// CSRF token generation and validation
function generateCSRFToken(): string {
    if (!isset($_SESSION['narrative_csrf_token'])) {
        $_SESSION['narrative_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['narrative_csrf_token'];
}

function validateCSRFToken(string $token): bool {
    return isset($_SESSION['narrative_csrf_token']) && 
           hash_equals($_SESSION['narrative_csrf_token'], $token);
}

$user_id = (int)$_SESSION['user_id'];
$selected_package_id = isset($_GET['package']) ? (int)$_GET['package'] : 0;
$message = '';
$error = '';

// Handle POST actions (before header output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($action) {
            case 'create_package':
                $title = trim($_POST['title'] ?? '');
                $scope_type = trim($_POST['scope_type'] ?? '');
                $time_start = trim($_POST['time_start'] ?? '');
                $time_end = trim($_POST['time_end'] ?? '');
                
                if (empty($title)) {
                    $error = 'Package title is required.';
                } elseif (!in_array($scope_type, ['chapter', 'arc', 'session', 'location', 'faction'])) {
                    $error = 'Invalid scope type.';
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO narrative_packages (title, scope_type, time_start, time_end, created_by_user_id, status)
                        VALUES (?, ?, ?, ?, ?, 'draft')
                    ");
                    if ($stmt) {
                        $time_start_null = empty($time_start) ? null : $time_start;
                        $time_end_null = empty($time_end) ? null : $time_end;
                        $stmt->bind_param("ssssi", $title, $scope_type, $time_start_null, $time_end_null, $user_id);
                        if ($stmt->execute()) {
                            $new_package_id = $stmt->insert_id;
                            $stmt->close();
                            header("Location: ?package=" . $new_package_id);
                            exit;
                        } else {
                            $error = 'Failed to create package: ' . $stmt->error;
                            $stmt->close();
                        }
                    } else {
                        $error = 'Failed to prepare statement: ' . $conn->error;
                    }
                }
                break;
                
            case 'create_scene_seed':
                $package_id = (int)($_POST['package_id'] ?? 0);
                $item_key = trim($_POST['item_key'] ?? '');
                $title = trim($_POST['title'] ?? '');
                $purpose = trim($_POST['purpose'] ?? '');
                $participants = trim($_POST['participants'] ?? '');
                $beats = trim($_POST['beats'] ?? '');
                $player_choices = trim($_POST['player_choices'] ?? '');
                
                if (empty($item_key) || empty($title)) {
                    $error = 'Item key and title are required.';
                } elseif ($package_id <= 0) {
                    $error = 'Invalid package ID.';
                } else {
                    // Verify package exists
                    $check_stmt = $conn->prepare("SELECT id FROM narrative_packages WHERE id = ?");
                    $check_stmt->bind_param("i", $package_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    if ($check_result->num_rows === 0) {
                        $error = 'Package not found.';
                        $check_stmt->close();
                    } else {
                        $check_stmt->close();
                        
                        // Sanitize item_key: lowercase, alphanumeric, underscore, hyphen only
                        $item_key = preg_replace('/[^a-z0-9_-]/', '', strtolower($item_key));
                        
                        // Check for duplicate item_key in this package
                        $dup_stmt = $conn->prepare("SELECT id FROM narrative_items WHERE package_id = ? AND item_key = ?");
                        $dup_stmt->bind_param("is", $package_id, $item_key);
                        $dup_stmt->execute();
                        $dup_result = $dup_stmt->get_result();
                        if ($dup_result->num_rows > 0) {
                            $error = 'Item key already exists in this package.';
                            $dup_stmt->close();
                        } else {
                            $dup_stmt->close();
                            
                            // Build content JSON
                            $content_json = json_encode([
                                'title' => $title,
                                'purpose' => $purpose,
                                'participants' => $participants,
                                'beats' => $beats,
                                'player_choices' => $player_choices
                            ], JSON_UNESCAPED_UNICODE);
                            
                            $stmt = $conn->prepare("
                                INSERT INTO narrative_items 
                                (package_id, item_type, item_key, content_json, source, review_status, created_by_user_id)
                                VALUES (?, 'scene_seed', ?, ?, 'human', 'draft', ?)
                            ");
                            if ($stmt) {
                                $stmt->bind_param("issi", $package_id, $item_key, $content_json, $user_id);
                                if ($stmt->execute()) {
                                    $stmt->close();
                                    $message = 'Scene seed created successfully.';
                                    header("Location: ?package=" . $package_id . "&msg=" . urlencode($message));
                                    exit;
                                } else {
                                    $error = 'Failed to create scene seed: ' . $stmt->error;
                                    $stmt->close();
                                }
                            } else {
                                $error = 'Failed to prepare statement: ' . $conn->error;
                            }
                        }
                    }
                }
                break;
                
            case 'approve_item':
            case 'reject_item':
                $item_id = (int)($_POST['item_id'] ?? 0);
                $package_id = (int)($_POST['package_id'] ?? 0);
                
                if ($item_id <= 0 || $package_id <= 0) {
                    $error = 'Invalid item or package ID.';
                } else {
                    // Verify item belongs to the specified package
                    $verify_stmt = $conn->prepare("SELECT id FROM narrative_items WHERE id = ? AND package_id = ?");
                    $verify_stmt->bind_param("ii", $item_id, $package_id);
                    $verify_stmt->execute();
                    $verify_result = $verify_stmt->get_result();
                    if ($verify_result->num_rows === 0) {
                        $error = 'Item not found or does not belong to this package.';
                        $verify_stmt->close();
                    } else {
                        $verify_stmt->close();
                        
                        $review_status = ($action === 'approve_item') ? 'approved' : 'rejected';
                        $stmt = $conn->prepare("
                            UPDATE narrative_items 
                            SET review_status = ?, approved_by_user_id = ?, approved_at = NOW()
                            WHERE id = ? AND package_id = ?
                        ");
                        if ($stmt) {
                            $stmt->bind_param("siii", $review_status, $user_id, $item_id, $package_id);
                            if ($stmt->execute()) {
                                $stmt->close();
                                $message = 'Item ' . ($action === 'approve_item' ? 'approved' : 'rejected') . ' successfully.';
                                header("Location: ?package=" . $package_id . "&msg=" . urlencode($message));
                                exit;
                            } else {
                                $error = 'Failed to update item: ' . $stmt->error;
                                $stmt->close();
                            }
                        } else {
                            $error = 'Failed to prepare statement: ' . $conn->error;
                        }
                    }
                }
                break;
        }
    }
}

// Get message from URL if redirected
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}

// Load packages (latest first)
$packages = [];
$packages_stmt = $conn->prepare("
    SELECT id, title, scope_type, time_start, time_end, status, created_at
    FROM narrative_packages
    ORDER BY created_at DESC
");
if ($packages_stmt) {
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
    while ($row = $packages_result->fetch_assoc()) {
        $packages[] = $row;
    }
    $packages_stmt->close();
}

// Load selected package details
$selected_package = null;
if ($selected_package_id > 0) {
    $package_stmt = $conn->prepare("
        SELECT id, title, scope_type, time_start, time_end, status, created_at
        FROM narrative_packages
        WHERE id = ?
    ");
    if ($package_stmt) {
        $package_stmt->bind_param("i", $selected_package_id);
        $package_stmt->execute();
        $package_result = $package_stmt->get_result();
        $selected_package = $package_result->fetch_assoc();
        $package_stmt->close();
    }
}

// Load items for selected package
$items = [];
if ($selected_package_id > 0 && $selected_package) {
    $items_stmt = $conn->prepare("
        SELECT id, item_key, item_type, content_json, source, review_status, created_at, approved_at, approved_by_user_id
        FROM narrative_items
        WHERE package_id = ?
        ORDER BY created_at DESC
    ");
    if ($items_stmt) {
        $items_stmt->bind_param("i", $selected_package_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }
        $items_stmt->close();
    }
}

$csrf_token = generateCSRFToken();
$extra_css = [];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h1>Narrative Compiler</h1>
    <p class="mb-4">AI suggestions come later; this is the approval spine.</p>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo h($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo h($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Create Package Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Create Package</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_package">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label for="scope_type" class="form-label">Scope Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="scope_type" name="scope_type" required>
                            <option value="">Select scope...</option>
                            <option value="chapter">Chapter</option>
                            <option value="arc">Arc</option>
                            <option value="session">Session</option>
                            <option value="location">Location</option>
                            <option value="faction">Faction</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="time_start" class="form-label">Time Start</label>
                        <input type="date" class="form-control" id="time_start" name="time_start">
                    </div>
                    <div class="col-md-6">
                        <label for="time_end" class="form-label">Time End</label>
                        <input type="date" class="form-control" id="time_end" name="time_end">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Package</button>
            </form>
        </div>
    </div>
    
    <!-- Packages Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Packages</h5>
        </div>
        <div class="card-body">
            <?php if (empty($packages)): ?>
                <p>No packages yet. Create one above.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Scope Type</th>
                                <th>Time Start</th>
                                <th>Time End</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $pkg): ?>
                                <tr>
                                    <td>
                                        <a href="?package=<?php echo $pkg['id']; ?>">
                                            <?php echo h($pkg['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo h($pkg['scope_type']); ?></td>
                                    <td><?php echo $pkg['time_start'] ? h($pkg['time_start']) : '—'; ?></td>
                                    <td><?php echo $pkg['time_end'] ? h($pkg['time_end']) : '—'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $pkg['status'] === 'draft' ? 'secondary' : 
                                                ($pkg['status'] === 'approved' ? 'success' : 'warning'); 
                                        ?>">
                                            <?php echo h($pkg['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo h($pkg['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($selected_package): ?>
        <!-- Package Header -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Package: <?php echo h($selected_package['title']); ?></h5>
            </div>
            <div class="card-body">
                <p><strong>Scope:</strong> <?php echo h($selected_package['scope_type']); ?></p>
                <p><strong>Time Window:</strong> 
                    <?php echo $selected_package['time_start'] ? h($selected_package['time_start']) : '—'; ?>
                    <?php if ($selected_package['time_start'] || $selected_package['time_end']): ?>
                        to <?php echo $selected_package['time_end'] ? h($selected_package['time_end']) : '—'; ?>
                    <?php endif; ?>
                </p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php 
                        echo $selected_package['status'] === 'draft' ? 'secondary' : 
                            ($selected_package['status'] === 'approved' ? 'success' : 'warning'); 
                    ?>">
                        <?php echo h($selected_package['status']); ?>
                    </span>
                </p>
                <a href="?" class="btn btn-sm btn-outline-secondary">Back to All Packages</a>
            </div>
        </div>
        
        <!-- Add Scene Seed Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add Scene Seed</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_scene_seed">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                    <input type="hidden" name="package_id" value="<?php echo $selected_package_id; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="item_key" class="form-label">Item Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_key" name="item_key" 
                                   pattern="[a-z0-9_-]+" 
                                   placeholder="e.g., opening-scene-elysium" required>
                            <div class="mt-1">Lowercase letters, numbers, hyphens, and underscores only</div>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="participants" class="form-label">Participants</label>
                        <input type="text" class="form-control" id="participants" name="participants" 
                               placeholder="Comma-separated list">
                    </div>
                    
                    <div class="mb-3">
                        <label for="beats" class="form-label">Beats</label>
                        <textarea class="form-control" id="beats" name="beats" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="player_choices" class="form-label">Player Choices</label>
                        <textarea class="form-control" id="player_choices" name="player_choices" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Scene Seed</button>
                </form>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <p>No items yet. Create a scene seed above.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Key</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Review Status</th>
                                    <th>Source</th>
                                    <th>Created At</th>
                                    <th>Approved At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $content = json_decode($item['content_json'], true);
                                    $title = $content['title'] ?? '—';
                                    ?>
                                    <tr>
                                        <td><?php echo h($item['item_key']); ?></td>
                                        <td><?php echo h($title); ?></td>
                                        <td><?php echo h($item['item_type']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $item['review_status'] === 'approved' ? 'success' : 
                                                    ($item['review_status'] === 'rejected' ? 'danger' : 'secondary'); 
                                            ?>">
                                                <?php echo h($item['review_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo h($item['source']); ?></td>
                                        <td><?php echo h($item['created_at']); ?></td>
                                        <td><?php echo $item['approved_at'] ? h($item['approved_at']) : '—'; ?></td>
                                        <td>
                                            <?php if ($item['review_status'] !== 'approved'): ?>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to approve this item?');">
                                                    <input type="hidden" name="action" value="approve_item">
                                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="package_id" value="<?php echo $selected_package_id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($item['review_status'] !== 'rejected'): ?>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to reject this item?');">
                                                    <input type="hidden" name="action" value="reject_item">
                                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="package_id" value="<?php echo $selected_package_id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
