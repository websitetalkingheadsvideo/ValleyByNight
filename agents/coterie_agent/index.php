<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/connect.php';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function buildQueryString(array $override): string {
  $base = $_GET;
  foreach ($override as $k => $v) {
    if ($v === null) unset($base[$k]);
    else $base[$k] = $v;
  }
  return http_build_query($base);
}

/* -----------------------------
   Inputs
------------------------------ */
$chronicle = isset($_GET['chronicle']) ? trim((string)$_GET['chronicle']) : '';
$activeOnly = isset($_GET['active']) ? 1 : 1; // default ON
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$selectedCoterieId = isset($_GET['coterie_id']) ? (int)$_GET['coterie_id'] : 0;

/* -----------------------------
   Add character to coterie handler (BEFORE header output)
------------------------------ */
$addCharacterId = isset($_GET['add_character']) ? (int)$_GET['add_character'] : 0;
if ($addCharacterId > 0 && $selectedCoterieId > 0) {
  // Check if character is already in coterie
  $checkStmt = $conn->prepare("SELECT id FROM coterie_members WHERE coterie_id = ? AND character_id = ?");
  if ($checkStmt) {
    $checkStmt->bind_param("ii", $selectedCoterieId, $addCharacterId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    if ($checkRes->num_rows === 0) {
      // Add character to coterie
      $insertStmt = $conn->prepare("INSERT INTO coterie_members (coterie_id, character_id, role) VALUES (?, ?, 'Member')");
      if ($insertStmt) {
        $insertStmt->bind_param("ii", $selectedCoterieId, $addCharacterId);
        $insertStmt->execute();
        $insertStmt->close();
        // Redirect to remove the add_character parameter
        header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'add_character' => null]));
        exit;
      }
    }
    $checkStmt->close();
  }
}

/* -----------------------------
   Update character role handler (BEFORE header output)
------------------------------ */
$updateRoleCharacterId = isset($_GET['update_role_character_id']) ? (int)$_GET['update_role_character_id'] : 0;
$newRole = isset($_GET['new_role']) ? trim((string)$_GET['new_role']) : '';
if ($updateRoleCharacterId > 0 && $selectedCoterieId > 0 && $newRole !== '') {
  $updateStmt = $conn->prepare("UPDATE coterie_members SET role = ? WHERE coterie_id = ? AND character_id = ?");
  if ($updateStmt) {
    $updateStmt->bind_param("sii", $newRole, $selectedCoterieId, $updateRoleCharacterId);
    if ($updateStmt->execute()) {
      $updateStmt->close();
      $redirectUrl = "?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_role_character_id' => null, 'new_role' => null, 't' => time()]);
      header("Location: " . $redirectUrl);
      exit;
    } else {
      error_log("Failed to update role: " . $updateStmt->error);
      $updateStmt->close();
    }
  }
}

/* -----------------------------
   Update coterie focus handler (BEFORE header output)
------------------------------ */
$updateFocus = isset($_GET['update_focus']) ? trim((string)$_GET['update_focus']) : '';
if ($updateFocus !== '' && $selectedCoterieId > 0) {
  // First, check if description column exists
  $checkColumn = $conn->query("SHOW COLUMNS FROM coteries LIKE 'description'");
  if ($checkColumn && $checkColumn->num_rows === 0) {
    // Column doesn't exist, add it
    if (!$conn->query("ALTER TABLE coteries ADD COLUMN description TEXT")) {
      error_log("Failed to add description column: " . $conn->error);
    }
  }
  
  // Now update the description
  $updateFocusStmt = $conn->prepare("UPDATE coteries SET description = ? WHERE id = ?");
  if ($updateFocusStmt) {
    $updateFocusStmt->bind_param("si", $updateFocus, $selectedCoterieId);
    if ($updateFocusStmt->execute()) {
      $affectedRows = $updateFocusStmt->affected_rows;
      $updateFocusStmt->close();
      if ($affectedRows > 0) {
        $redirectUrl = "?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_focus' => null, 't' => time()]);
        header("Location: " . $redirectUrl);
        exit;
      } else {
        error_log("No rows affected when updating focus for coterie {$selectedCoterieId}");
      }
    } else {
      error_log("Failed to update focus: " . $updateFocusStmt->error);
      $updateFocusStmt->close();
    }
  } else {
    error_log("Failed to prepare update focus statement: " . $conn->error);
  }
}

/* -----------------------------
   Remove character from coterie handler (BEFORE header output)
------------------------------ */
$removeCharacterId = isset($_GET['remove_character']) ? (int)$_GET['remove_character'] : 0;
if ($removeCharacterId > 0 && $selectedCoterieId > 0) {
  // Verify the member exists before deleting
  $verifyStmt = $conn->prepare("SELECT id FROM coterie_members WHERE coterie_id = ? AND character_id = ?");
  if ($verifyStmt) {
    $verifyStmt->bind_param("ii", $selectedCoterieId, $removeCharacterId);
    $verifyStmt->execute();
    $verifyRes = $verifyStmt->get_result();
    if ($verifyRes->num_rows > 0) {
      $verifyStmt->close();
      // Remove character from coterie
      $deleteStmt = $conn->prepare("DELETE FROM coterie_members WHERE coterie_id = ? AND character_id = ?");
      if ($deleteStmt) {
        $deleteStmt->bind_param("ii", $selectedCoterieId, $removeCharacterId);
        if ($deleteStmt->execute()) {
          $affectedRows = $deleteStmt->affected_rows;
          $deleteStmt->close();
          if ($affectedRows > 0) {
            // Redirect to remove the remove_character parameter with cache busting
            $redirectUrl = "?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'remove_character' => null, 't' => time()]);
            header("Location: " . $redirectUrl);
            exit;
          } else {
            error_log("No rows affected when removing character {$removeCharacterId} from coterie {$selectedCoterieId}");
          }
        } else {
          error_log("Failed to remove character from coterie: " . $deleteStmt->error);
          $deleteStmt->close();
        }
      } else {
        error_log("Failed to prepare delete statement: " . $conn->error);
      }
    } else {
      error_log("Character {$removeCharacterId} not found in coterie {$selectedCoterieId}");
      $verifyStmt->close();
    }
  }
}

$extra_css = ['css/modal.css', 'css/coterie_agent.css'];
include __DIR__ . '/../../includes/header.php';

/* -----------------------------
   Chronicles dropdown
------------------------------ */
$chronicles = [];
$res = $conn->query("SELECT DISTINCT chronicle FROM characters WHERE chronicle IS NOT NULL AND chronicle <> '' ORDER BY chronicle");
if ($res) while ($row = $res->fetch_assoc()) $chronicles[] = (string)$row['chronicle'];

/* -----------------------------
   Coterie list query
------------------------------ */
$coteries = [];
$coterieListError = '';

$sql = "
  SELECT
    c.id,
    c.name AS coterie_name,
    c.chronicle,
    c.status,
    COUNT(cm.id) AS members_total
  FROM coteries c
  LEFT JOIN coterie_members cm ON cm.coterie_id = c.id
";

$where = [];
$types = '';
$params = [];

if ($chronicle !== '') {
  $where[] = "c.chronicle = ?";
  $types .= 's';
  $params[] = $chronicle;
}

if ($activeOnly === 1) {
  $where[] = "c.status = 'active'";
}

if ($search !== '') {
  $where[] = "(c.name LIKE ?)";
  $types .= 's';
  $params[] = '%' . $search . '%';
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$sql .= " GROUP BY c.id, c.name, c.chronicle, c.status ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  $coterieListError = "Query prepare failed: " . $conn->error;
} else {
  if ($types !== '') $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $coteries[] = $row;
  $stmt->close();
}

/* -----------------------------
   Selected coterie + roster
   NOTE: your coteries table does NOT have a description column
------------------------------ */
$selected = null;
$roster = [];
$rosterError = '';
$availableCharacters = [];

if ($selectedCoterieId > 0) {
  // Check if description column exists first
  $hasDescriptionColumn = false;
  $checkColumn = $conn->query("SHOW COLUMNS FROM coteries LIKE 'description'");
  if ($checkColumn && $checkColumn->num_rows > 0) {
    $hasDescriptionColumn = true;
  }
  
  if ($hasDescriptionColumn) {
    $stmt = $conn->prepare("SELECT id, name AS coterie_name, chronicle, status, description FROM coteries WHERE id = ? LIMIT 1");
  } else {
    $stmt = $conn->prepare("SELECT id, name AS coterie_name, chronicle, status FROM coteries WHERE id = ? LIMIT 1");
  }
  
  if (!$stmt) {
    $rosterError = "Query prepare failed: " . $conn->error;
  } else {
    $stmt->bind_param("i", $selectedCoterieId);
    $stmt->execute();
    $res = $stmt->get_result();
    $selected = $res->fetch_assoc() ?: null;
    $stmt->close();
    
    // Ensure description field exists in array
    if ($selected) {
      $selected['description'] = (string)($selected['description'] ?? '');
    }
  }

  if ($selected) {
    $sql = "
      SELECT
        cm.id AS member_id,
        cm.role,
        ch.id AS character_id,
        ch.character_name,
        ch.clan,
        ch.player_name,
        ch.status AS character_status
      FROM coterie_members cm
      JOIN characters ch ON ch.id = cm.character_id
      WHERE cm.coterie_id = ?
      ORDER BY
        CASE
          WHEN cm.role LIKE '%Leader%' THEN 0
          WHEN cm.role LIKE '%2nd%' THEN 1
          ELSE 2
        END,
        ch.character_name ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      $rosterError = "Query prepare failed: " . $conn->error;
    } else {
      $stmt->bind_param("i", $selectedCoterieId);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) $roster[] = $row;
      $stmt->close();
    }

    // Get all characters NOT in this coterie for the dropdown
    $existingCharacterIds = array_map(function($m) { return (int)($m['character_id'] ?? 0); }, $roster);
    
    if (count($existingCharacterIds) > 0) {
      $charSql = "SELECT id, character_name, clan, player_name, status 
                  FROM characters 
                  WHERE id NOT IN (" . str_repeat('?,', count($existingCharacterIds) - 1) . "?) 
                  ORDER BY character_name ASC";
      $charStmt = $conn->prepare($charSql);
      if ($charStmt) {
        $types = str_repeat('i', count($existingCharacterIds));
        $charStmt->bind_param($types, ...$existingCharacterIds);
        $charStmt->execute();
        $charRes = $charStmt->get_result();
        while ($row = $charRes->fetch_assoc()) $availableCharacters[] = $row;
        $charStmt->close();
      }
    } else {
      // No existing members, so all characters are available
      $charSql = "SELECT id, character_name, clan, player_name, status 
                  FROM characters 
                  ORDER BY character_name ASC";
      $charRes = $conn->query($charSql);
      if ($charRes) {
        while ($row = $charRes->fetch_assoc()) $availableCharacters[] = $row;
      }
    }
  }
}

/* -----------------------------
   Focus heuristics
------------------------------ */
$focus = [
  'summary' => '',
  'strengths' => [],
  'gaps' => [],
  'hooks' => [],
];

if ($selected) {
  $membersCount = count($roster);
  $roles = [];
  $clans = [];

  foreach ($roster as $m) {
    $r = trim((string)($m['role'] ?? ''));
    if ($r !== '') $roles[] = $r;
    $c = trim((string)($m['clan'] ?? ''));
    if ($c !== '') $clans[] = $c;
  }

  $uniqueClans = array_values(array_unique($clans));

  $focus['summary'] =
    "Coteries are the unit of play: roster, roles, access vectors, and pressure points. " .
    "Tracking {$membersCount} member" . ($membersCount === 1 ? "" : "s") . ".";

  $hasLeader = false;
  foreach ($roles as $r) {
    if (stripos($r, 'leader') !== false) { $hasLeader = true; break; }
  }

  if ($hasLeader) $focus['strengths'][] = "Clear internal hierarchy (leadership role present).";
  else $focus['gaps'][] = "No explicit leader role recorded — designate a spokesperson for court situations.";

  if (count($uniqueClans) >= 2) $focus['strengths'][] = "Diverse clan mix (" . implode(", ", $uniqueClans) . ") — wider reach across factional lines.";
  else $focus['gaps'][] = "Low clan diversity — easier to pigeonhole socially; consider a complementary ally.";

  $focus['hooks'][] = "Mission: secure patronage, venues, and Elysium-safe logistics (events, cover stories).";
  $focus['hooks'][] = "Pressure: a mortal asset or public reputation becomes a liability (blackmail / Masquerade risk).";
  $focus['hooks'][] = "Opportunity: rival coterie wants access — negotiate boons, not favors for free.";
}

?>
<div class="container-fluid py-4 coterie-agent">
  <div class="row">
    <div class="col-lg-4 mb-4">

      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Coteries</h5>

          <form method="get" action="" class="mb-3">
            <div class="row">
              <div class="col-12 mb-2">
                <input type="text" class="form-control" id="q" name="q" value="<?php echo h($search); ?>" placeholder="Search coteries">
              </div>

              <div class="col-7 mb-2">
                <select class="form-select" id="chronicle" name="chronicle">
                  <option value="">All Chronicles</option>
                  <?php foreach ($chronicles as $ch): ?>
                    <option value="<?php echo h($ch); ?>" <?php echo ($chronicle === $ch ? 'selected' : ''); ?>>
                      <?php echo h($ch); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-5 mb-2">
                <div class="form-check mt-2">
                  <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?php echo ($activeOnly === 1 ? 'checked' : ''); ?>>
                  <label class="form-check-label" for="active">Active</label>
                </div>
              </div>

              <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">

              <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">Filter</button>
              </div>
            </div>
          </form>

          <?php if ($coterieListError): ?>
            <div class="alert alert-danger mb-0"><?php echo h($coterieListError); ?></div>
          <?php else: ?>
            <?php if (count($coteries) === 0): ?>
              <div class="alert alert-warning mb-0">No coteries found.</div>
            <?php else: ?>
              <div class="list-group">
                <?php foreach ($coteries as $c): ?>
                  <?php
                    $cid = (int)$c['id'];
                    $isSelected = ($cid === $selectedCoterieId);
                    $qs = buildQueryString(['coterie_id' => $cid]);
                    $total = (int)($c['members_total'] ?? 0);
                  ?>
                  <a href="?<?php echo h($qs); ?>" class="list-group-item list-group-item-action <?php echo $isSelected ? 'active' : ''; ?>">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1"><?php echo h((string)($c['coterie_name'] ?? '')); ?></h6>
                      <small class="<?php echo $isSelected ? '' : 'text-light'; ?>">
                        <?php echo $total . ' member' . ($total === 1 ? '' : 's'); ?>
                      </small>
                    </div>
                    <div class="d-flex w-100 justify-content-between">
                      <small class="<?php echo $isSelected ? '' : 'text-light'; ?>"><?php echo h((string)($c['chronicle'] ?? '')); ?></small>
                      <small class="<?php echo $isSelected ? '' : 'text-light'; ?>"><?php echo h((string)($c['status'] ?? '')); ?></small>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="card shadow-sm mt-4">
        <div class="card-body">
          <h6 class="card-title mb-2">Agent Focus</h6>
          <p class="mb-0 small">
            Coteries are the unit of play: roster, internal roles, access vectors, and story pressure points.
          </p>
        </div>
      </div>

    </div>

    <div class="col-lg-8 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">

          <?php if (!$selected): ?>
            <h5 class="card-title mb-2">Select a coterie</h5>
            <p class="mb-0">Choose a coterie on the left to view roster and focus guidance.</p>
            <?php if ($rosterError): ?>
              <div class="alert alert-danger mt-3 mb-0"><?php echo h($rosterError); ?></div>
            <?php endif; ?>
          <?php else: ?>

            <div class="d-flex align-items-start justify-content-between">
              <div>
                <h4 class="mb-1"><?php echo h((string)($selected['coterie_name'] ?? '')); ?></h4>
                <div>
                  <?php if (!empty($selected['chronicle'])): ?>
                    <span class="badge badge-secondary mr-1"><?php echo h((string)$selected['chronicle']); ?></span>
                  <?php endif; ?>

                  <?php if (!empty($selected['status'])): ?>
                    <span class="badge badge-<?php echo ($selected['status'] === 'active') ? 'success' : 'dark'; ?>">
                      <?php echo h((string)$selected['status']); ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="text-right">
                <a class="btn btn-outline-secondary btn-sm" href="?<?php echo h(buildQueryString(['coterie_id' => null])); ?>">Clear</a>
              </div>
            </div>

            <?php if ($rosterError): ?>
              <div class="alert alert-danger mt-3"><?php echo h($rosterError); ?></div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mt-4">
              <h6 class="mb-2">Roster</h6>
              <div class="d-flex align-items-center gap-2">
                <span class="badge badge-secondary"><?php echo count($roster); ?> member<?php echo (count($roster) === 1 ? '' : 's'); ?></span>
                <?php if (count($roster) > 0): ?>
                  <div class="dropdown">
                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button" id="removeCharacterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                      Remove Character
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="removeCharacterDropdown">
                      <?php foreach ($roster as $member): ?>
                        <li>
                          <a class="dropdown-item" href="?<?php echo h(buildQueryString(['coterie_id' => $selectedCoterieId, 'remove_character' => (int)$member['character_id']])); ?>">
                            <strong><?php echo h((string)($member['character_name'] ?? '')); ?></strong>
                            <?php if (!empty($member['role'])): ?>
                              <span class="opacity-75"> - <?php echo h((string)$member['role']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($member['clan'])): ?>
                              <br><small class="opacity-75"><?php echo h((string)$member['clan']); ?></small>
                            <?php endif; ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
                <?php if (count($availableCharacters ?? []) > 0): ?>
                  <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="addCharacterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                      Add Character
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="addCharacterDropdown">
                      <?php foreach ($availableCharacters as $char): ?>
                        <li>
                          <a class="dropdown-item" href="?<?php echo h(buildQueryString(['coterie_id' => $selectedCoterieId, 'add_character' => (int)$char['id']])); ?>">
                            <strong><?php echo h((string)($char['character_name'] ?? '')); ?></strong>
                            <?php if (!empty($char['clan'])): ?>
                              <span class="opacity-75"> - <?php echo h((string)$char['clan']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($char['player_name'])): ?>
                              <br><small class="opacity-75"><?php echo h((string)$char['player_name']); ?></small>
                            <?php endif; ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <?php if (count($roster) === 0): ?>
              <div class="alert alert-warning mb-0">No members found for this coterie.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead>
                    <tr>
                      <th class="text-center">Character</th>
                      <th class="text-center">Role</th>
                      <th class="text-center">Clan</th>
                      <th class="text-center">Player</th>
                      <th class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($roster as $m): ?>
                      <tr>
                        <td>
                          <?php
                            $name = (string)($m['character_name'] ?? '');
                            $charId = (int)($m['character_id'] ?? 0);
                            if ($charId > 0) echo '<a href="/character.php?id=' . $charId . '">' . h($name) . '</a>';
                            else echo h($name);
                          ?>
                        </td>
                        <td>
                          <form method="get" action="" class="d-inline">
                            <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
                            <input type="hidden" name="update_role_character_id" value="<?php echo (int)$m['character_id']; ?>">
                            <div class="input-group input-group-sm">
                              <input type="text" class="form-control form-control-sm" name="new_role" value="<?php echo h((string)($m['role'] ?? '')); ?>" placeholder="Role">
                              <button class="btn btn-outline-primary btn-sm" type="submit" title="Update Role">
                                <small>✓</small>
                              </button>
                            </div>
                          </form>
                        </td>
                        <td><?php echo h((string)($m['clan'] ?? '')); ?></td>
                        <td><?php echo h((string)($m['player_name'] ?? '')); ?></td>
                        <td><?php echo h((string)($m['character_status'] ?? '')); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mt-4">
              <h6 class="mb-2">Coterie Focus</h6>
              <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editFocusModal">
                Edit Focus
              </button>
            </div>
            <?php if (!empty($selected['description'])): ?>
              <div class="alert alert-primary"><?php echo nl2br(h((string)$selected['description'])); ?></div>
            <?php else: ?>
              <div class="alert alert-primary"><?php echo h($focus['summary']); ?></div>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-6">
                <div class="card mb-3">
                  <div class="card-header">Strengths</div>
                  <ul class="list-group list-group-flush">
                    <?php if (count($focus['strengths']) === 0): ?>
                      <li class="list-group-item">None identified yet.</li>
                    <?php else: ?>
                      <?php foreach ($focus['strengths'] as $s): ?>
                        <li class="list-group-item"><?php echo h($s); ?></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card mb-3">
                  <div class="card-header">Gaps</div>
                  <ul class="list-group list-group-flush">
                    <?php if (count($focus['gaps']) === 0): ?>
                      <li class="list-group-item">None identified yet.</li>
                    <?php else: ?>
                      <?php foreach ($focus['gaps'] as $g): ?>
                        <li class="list-group-item"><?php echo h($g); ?></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">Story Hooks</div>
              <ul class="list-group list-group-flush">
                <?php foreach ($focus['hooks'] as $hk): ?>
                  <li class="list-group-item"><?php echo h($hk); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>

          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Focus Modal -->
<?php if ($selected): ?>
<div class="modal fade" id="editFocusModal" tabindex="-1" aria-labelledby="editFocusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editFocusModalLabel">Edit Coterie Focus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get" action="">
        <div class="modal-body">
          <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
          <div class="mb-3">
            <label for="focusText" class="form-label">Focus Description</label>
            <textarea class="form-control" id="focusText" name="update_focus" rows="5" placeholder="Enter coterie focus description..."><?php echo h((string)($selected['description'] ?? '')); ?></textarea>
            <small class="form-text text-muted">This will replace the auto-generated focus summary.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Focus</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
