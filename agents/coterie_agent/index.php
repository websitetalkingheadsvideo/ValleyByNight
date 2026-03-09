<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/supabase_client.php';

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
   Create new coterie handler (BEFORE header output)
------------------------------ */
$newCoterieName = isset($_GET['new_coterie_name']) ? trim((string)$_GET['new_coterie_name']) : '';
$newCoterieChronicle = isset($_GET['new_coterie_chronicle']) ? trim((string)$_GET['new_coterie_chronicle']) : '';
if ($newCoterieName !== '') {
  $res = supabase_rest_request('POST', '/rest/v1/coteries', [], [
    'name' => $newCoterieName,
    'chronicle' => $newCoterieChronicle,
    'status' => 'active'
  ], ['Prefer: return=representation']);
  if ($res['error'] === null && is_array($res['data']) && isset($res['data'][0]['id'])) {
    header("Location: ?" . buildQueryString(['coterie_id' => $res['data'][0]['id'], 'new_coterie_name' => null, 'new_coterie_chronicle' => null]));
    exit;
  }
  if ($res['error'] !== null) {
    error_log("Failed to create coterie: " . $res['error']);
  }
}

/* -----------------------------
   Add character to coterie handler (BEFORE header output)
------------------------------ */
$addCharacterId = isset($_GET['add_character']) ? (int)$_GET['add_character'] : 0;
if ($addCharacterId > 0 && $selectedCoterieId > 0) {
  $existing = supabase_table_get('coterie_members', [
    'select' => 'id',
    'coterie_id' => 'eq.' . $selectedCoterieId,
    'character_id' => 'eq.' . $addCharacterId,
    'limit' => '1'
  ]);
  if (empty($existing)) {
    $res = supabase_rest_request('POST', '/rest/v1/coterie_members', [], [
      'coterie_id' => $selectedCoterieId,
      'character_id' => $addCharacterId,
      'role' => 'Member'
    ], ['Prefer: return=minimal']);
    if ($res['error'] === null) {
      header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'add_character' => null]));
      exit;
    }
  }
}

/* -----------------------------
   Update character role handler (BEFORE header output)
------------------------------ */
$updateRoleCharacterId = isset($_GET['update_role_character_id']) ? (int)$_GET['update_role_character_id'] : 0;
$newRole = isset($_GET['new_role']) ? trim((string)$_GET['new_role']) : '';
if ($updateRoleCharacterId > 0 && $selectedCoterieId > 0 && $newRole !== '') {
  $res = supabase_rest_request('PATCH', '/rest/v1/coterie_members', [
    'coterie_id' => 'eq.' . $selectedCoterieId,
    'character_id' => 'eq.' . $updateRoleCharacterId
  ], ['role' => $newRole], ['Prefer: return=minimal']);
  if ($res['error'] === null) {
    header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_role_character_id' => null, 'new_role' => null, 't' => time()]));
    exit;
  }
  if ($res['error'] !== null) {
    error_log("Failed to update role: " . $res['error']);
  }
}

/* -----------------------------
   Update coterie focus handler (BEFORE header output)
------------------------------ */
$updateFocus = isset($_GET['update_focus']) ? trim((string)$_GET['update_focus']) : '';
if ($updateFocus !== '' && $selectedCoterieId > 0) {
  $res = supabase_rest_request('PATCH', '/rest/v1/coteries', ['id' => 'eq.' . $selectedCoterieId], ['description' => $updateFocus], ['Prefer: return=minimal']);
  if ($res['error'] === null) {
    header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_focus' => null, 't' => time()]));
    exit;
  }
  if ($res['error'] !== null) {
    error_log("Failed to update focus: " . $res['error']);
  }
}

/* -----------------------------
   Update coterie history handler (BEFORE header output)
------------------------------ */
$updateHistory = isset($_GET['update_history']) ? trim((string)$_GET['update_history']) : '';
if ($updateHistory !== '' && $selectedCoterieId > 0) {
  $res = supabase_rest_request('PATCH', '/rest/v1/coteries', ['id' => 'eq.' . $selectedCoterieId], ['history' => $updateHistory], ['Prefer: return=minimal']);
  if ($res['error'] === null) {
    header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_history' => null, 't' => time()]));
    exit;
  }
  if ($res['error'] !== null) {
    error_log("Failed to update history: " . $res['error']);
  }
}

/* -----------------------------
   Update coterie reason handler (BEFORE header output)
------------------------------ */
$updateReason = isset($_GET['update_reason']) ? trim((string)$_GET['update_reason']) : '';
if ($updateReason !== '' && $selectedCoterieId > 0) {
  $res = supabase_rest_request('PATCH', '/rest/v1/coteries', ['id' => 'eq.' . $selectedCoterieId], ['reason' => $updateReason], ['Prefer: return=minimal']);
  if ($res['error'] === null) {
    header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'update_reason' => null, 't' => time()]));
    exit;
  }
  if ($res['error'] !== null) {
    error_log("Failed to update reason: " . $res['error']);
  }
}

/* -----------------------------
   Remove character from coterie handler (BEFORE header output)
------------------------------ */
$removeCharacterId = isset($_GET['remove_character']) ? (int)$_GET['remove_character'] : 0;
if ($removeCharacterId > 0 && $selectedCoterieId > 0) {
  $verify = supabase_table_get('coterie_members', [
    'select' => 'id',
    'coterie_id' => 'eq.' . $selectedCoterieId,
    'character_id' => 'eq.' . $removeCharacterId,
    'limit' => '1'
  ]);
  if (!empty($verify)) {
    $res = supabase_rest_request('DELETE', '/rest/v1/coterie_members', [
      'coterie_id' => 'eq.' . $selectedCoterieId,
      'character_id' => 'eq.' . $removeCharacterId
    ], null, ['Prefer: return=minimal']);
    if ($res['error'] === null) {
      header("Location: ?" . buildQueryString(['coterie_id' => $selectedCoterieId, 'remove_character' => null, 't' => time()]));
      exit;
    }
    if ($res['error'] !== null) {
      error_log("Failed to remove character from coterie: " . $res['error']);
    }
  } else {
    error_log("Character {$removeCharacterId} not found in coterie {$selectedCoterieId}");
  }
}

$extra_css = ['css/modal.css', 'css/coterie_agent.css'];
$extra_js = ['js/coterie_agent.js'];
include __DIR__ . '/../../includes/header.php';

/* -----------------------------
   Chronicles dropdown
------------------------------ */
$chronicles = [];
$charRows = supabase_table_get('characters', ['select' => 'chronicle', 'chronicle' => 'not.is.null', 'order' => 'chronicle.asc']);
$chronicles = array_values(array_unique(array_filter(array_map(static fn($r) => trim((string)($r['chronicle'] ?? '')), is_array($charRows) ? $charRows : []))));
sort($chronicles);

/* -----------------------------
   Coterie list query
------------------------------ */
$coteries = [];
$coterieListError = '';
$cotQuery = ['select' => 'id,name,chronicle,status', 'order' => 'name.asc'];
if ($chronicle !== '') {
  $cotQuery['chronicle'] = 'eq.' . $chronicle;
}
if ($activeOnly === 1) {
  $cotQuery['status'] = 'eq.active';
}
if ($search !== '') {
  $cotQuery['name'] = 'ilike.%' . addcslashes($search, '%_\\') . '%';
}
$cotRows = supabase_table_get('coteries', $cotQuery);
$coterieIds = array_column(is_array($cotRows) ? $cotRows : [], 'id');
$memberCounts = [];
if (!empty($coterieIds)) {
  $cms = supabase_table_get('coterie_members', ['select' => 'coterie_id']);
  foreach (is_array($cms) ? $cms : [] as $cm) {
    $cid = (int)($cm['coterie_id'] ?? 0);
    $memberCounts[$cid] = ($memberCounts[$cid] ?? 0) + 1;
  }
}
foreach (is_array($cotRows) ? $cotRows : [] as $r) {
  $r['coterie_name'] = $r['name'] ?? '';
  $r['members_total'] = $memberCounts[(int)($r['id'] ?? 0)] ?? 0;
  $coteries[] = $r;
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
  $selRows = supabase_table_get('coteries', [
    'select' => 'id,name,chronicle,status,description,history,reason',
    'id' => 'eq.' . $selectedCoterieId,
    'limit' => '1'
  ]);
  $selected = $selRows[0] ?? null;
  if ($selected) {
    $selected['coterie_name'] = $selected['name'] ?? '';
    $selected['description'] = (string)($selected['description'] ?? '');
    $selected['history'] = (string)($selected['history'] ?? '');
    $selected['reason'] = (string)($selected['reason'] ?? '');
  }
  if ($selected) {
    $cms = supabase_table_get('coterie_members', [
      'select' => 'id,role,character_id',
      'coterie_id' => 'eq.' . $selectedCoterieId
    ]);
    $charIds = array_filter(array_unique(array_column(is_array($cms) ? $cms : [], 'character_id')));
    $charMap = [];
    if (!empty($charIds)) {
      $chars = supabase_table_get('characters', [
        'select' => 'id,character_name,clan,player_name,status',
        'id' => 'in.(' . implode(',', array_map('intval', $charIds)) . ')'
      ]);
      foreach (is_array($chars) ? $chars : [] as $ch) {
        $charMap[(int)($ch['id'] ?? 0)] = $ch;
      }
    }
    $roster = [];
    foreach (is_array($cms) ? $cms : [] as $cm) {
      $cid = (int)($cm['character_id'] ?? 0);
      $ch = $charMap[$cid] ?? null;
      $roster[] = [
        'member_id' => $cm['id'] ?? null,
        'role' => $cm['role'] ?? '',
        'character_id' => $cid,
        'character_name' => $ch['character_name'] ?? '',
        'clan' => $ch['clan'] ?? '',
        'player_name' => $ch['player_name'] ?? '',
        'character_status' => $ch['status'] ?? ''
      ];
    }
    usort($roster, static function ($a, $b) {
      $oa = (stripos($a['role'] ?? '', 'Leader') !== false) ? 0 : ((stripos($a['role'] ?? '', '2nd') !== false) ? 1 : 2);
      $ob = (stripos($b['role'] ?? '', 'Leader') !== false) ? 0 : ((stripos($b['role'] ?? '', '2nd') !== false) ? 1 : 2);
      if ($oa !== $ob) return $oa <=> $ob;
      return strcasecmp($a['character_name'] ?? '', $b['character_name'] ?? '');
    });
    $existingCharacterIds = array_column($roster, 'character_id');
    $charQuery = ['select' => 'id,character_name,clan,player_name,status', 'order' => 'character_name.asc'];
    if (!empty($existingCharacterIds)) {
      $charQuery['id'] = 'not.in.(' . implode(',', array_map('intval', $existingCharacterIds)) . ')';
    }
    $availableCharacters = is_array($ac = supabase_table_get('characters', $charQuery)) ? $ac : [];
  }
}

/* -----------------------------
   Focus heuristics
------------------------------ */
/* =============================
   COTERIE FOCUS (CALCULATED)
   ============================= */

/* Helper: calculate focus, APPENDING hooks (not replacing) */
function calculate_coterie_focus_v2(array $selected, array $roster, array $existingHooks = []): array {
  $focus = [
    'summary' => '',
    'strengths' => [],
    'gaps' => [],
    'hooks' => $existingHooks,
  ];

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

  /* --- Summary (always computed) */
  $focus['summary'] =
    "Coteries are the unit of play: roster, roles, access vectors, and pressure points. " .
    "Tracking {$membersCount} member" . ($membersCount === 1 ? "" : "s") . ".";

  /* --- Structure rules */
  $hasLeader = false;
  $hasSecond = false;

  foreach ($roles as $r) {
    if (stripos($r, 'leader') !== false) $hasLeader = true;
    if (stripos($r, '2nd') !== false || stripos($r, 'second') !== false) $hasSecond = true;
  }

  if ($hasLeader) $focus['strengths'][] = "Clear internal hierarchy (leadership role present).";
  else $focus['gaps'][] = "No explicit leader role recorded — designate a spokesperson for court situations.";

  if ($hasSecond) $focus['strengths'][] = "Continuity and delegation (a second-in-command is identified).";
  else $focus['gaps'][] = "No clear second-in-command — leadership bottleneck risk during crisis.";

  if ($membersCount < 3) $focus['gaps'][] = "Small roster — low manpower for simultaneous objectives.";
  if ($membersCount >= 5) $focus['gaps'][] = "Large roster — coordination overhead and higher Masquerade exposure.";

  /* --- Clan mix rules */
  if (count($uniqueClans) >= 2) {
    $focus['strengths'][] = "Diverse clan mix (" . implode(", ", $uniqueClans) . ") — wider reach across factional lines.";
  } else {
    $focus['gaps'][] = "Low clan diversity — easier to pigeonhole socially; consider a complementary ally.";
  }

  /* --- Access vectors by clan presence */
  $hasClan = function(string $needle) use ($uniqueClans): bool {
    foreach ($uniqueClans as $c) if (strcasecmp($c, $needle) === 0) return true;
    return false;
  };

  if ($hasClan('Toreador')) {
    $focus['strengths'][] = "Natural Elysium access via culture, patronage, and social legitimacy.";
    $focus['hooks'][] = "Hook: a patron or critic demands a favor tied to an event, exhibit, or introduction.";
  }
  if ($hasClan('Brujah')) {
    $focus['strengths'][] = "Street leverage and intimidation when diplomacy fails.";
    $focus['gaps'][] = "Escalation risk — visibility can turn politics into a breach.";
    $focus['hooks'][] = "Hook: a confrontation forces a choice between saving face and keeping the peace.";
  }
  if ($hasClan('Gangrel')) {
    $focus['strengths'][] = "Scouting, pursuit, and movement beyond safe city routes.";
    $focus['hooks'][] = "Hook: border pressure from the desert forces a debt or compromise.";
  }
  if ($hasClan('Malkavian')) {
    $focus['strengths'][] = "Uncanny insight and pattern recognition others miss.";
    $focus['gaps'][] = "Unpredictability — allies may doubt reliability at the worst time.";
    $focus['hooks'][] = "Hook: an omen points to a threat hidden inside court protocol.";
  }
  if ($hasClan('Nosferatu')) {
    $focus['strengths'][] = "Information access through unseen channels and surveillance.";
    $focus['hooks'][] = "Hook: a secret gets priced — pay in boons, not cash.";
  }
  if ($hasClan('Ventrue')) {
    $focus['strengths'][] = "Institutional pull and court-facing authority.";
    $focus['hooks'][] = "Hook: status obligations — the court demands a public stance.";
  }

  /* --- Baseline hooks (append-only, capped later) */
  $pushHook = function(string $s) use (&$focus) {
    if (count($focus['hooks']) < 12) $focus['hooks'][] = $s;
  };

  $pushHook("Mission: secure patronage, venues, and Elysium-safe logistics (events, cover stories).");
  $pushHook("Pressure: a mortal asset or public reputation becomes a liability (blackmail / Masquerade risk).");
  $pushHook("Opportunity: a rival coterie wants access — negotiate boons, not favors for free.");

  /* --- Trim & dedupe for UI */
  $focus['strengths'] = array_slice(array_values(array_unique($focus['strengths'])), 0, 6);
  $focus['gaps']      = array_slice(array_values(array_unique($focus['gaps'])), 0, 6);
  $focus['hooks']     = array_slice(array_values(array_unique($focus['hooks'])), 0, 6);

  return $focus;
}

/* =============================
   APPLY FOCUS (CALL SITE)
   ============================= */

$focus = [
  'summary' => '',
  'strengths' => [],
  'gaps' => [],
  'hooks' => [], // authored/system hooks can be pre-filled here
];

if ($selected) {
  $existingHooks = $focus['hooks'] ?? [];
  $focus = calculate_coterie_focus_v2($selected, $roster, $existingHooks);
}
?>
<div class="container-fluid py-4 coterie-agent">
  <div class="row">
    <div class="col-lg-4 mb-4">

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="card-title mb-0">Coteries</h5>
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#newCoterieModal">
              Add New
            </button>
          </div>

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
                              <span> - <?php echo h((string)$member['role']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($member['clan'])): ?>
                              <br><small><?php echo h((string)$member['clan']); ?></small>
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
                              <span> - <?php echo h((string)$char['clan']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($char['player_name'])): ?>
                              <br><small><?php echo h((string)$char['player_name']); ?></small>
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
                            if ($charId > 0) {
                              echo '<a href="#" onclick="event.preventDefault(); if(window.viewCharacter) window.viewCharacter(' . $charId . '); return false;">' . h($name) . '</a>';
                            } else {
                              echo h($name);
                            }
                          ?>
                        </td>
                        <td>
                          <form method="get" action="" class="d-inline">
                            <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
                            <input type="hidden" name="update_role_character_id" value="<?php echo (int)$m['character_id']; ?>">
                            <div class="input-group input-group-sm">
                              <input type="text" class="form-control form-control-sm" name="new_role" value="<?php echo h((string)($m['role'] ?? '')); ?>" placeholder="Role">
                              <button class="btn btn-outline-primary btn-sm" type="submit" title="Update Role">
                                Save
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
              <h6 class="mb-2">History</h6>
              <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editHistoryModal">
                Edit History
              </button>
            </div>
            <?php if (!empty($selected['history'])): ?>
              <div class="alert alert-info"><?php echo nl2br(h((string)$selected['history'])); ?></div>
            <?php else: ?>
              <div class="alert alert-info">No history recorded yet.</div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mt-4">
              <h6 class="mb-2">Reason</h6>
              <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editReasonModal">
                Edit Reason
              </button>
            </div>
            <?php if (!empty($selected['reason'])): ?>
              <div class="alert alert-info"><?php echo nl2br(h((string)$selected['reason'])); ?></div>
            <?php else: ?>
              <div class="alert alert-info">No reason recorded yet.</div>
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
                  <div class="card-body">
                    <?php if (count($focus['strengths']) === 0): ?>
                      <p class="mb-0">None identified yet.</p>
                    <?php else: ?>
                      <ul class="mb-0">
                        <?php foreach ($focus['strengths'] as $s): ?>
                          <li><?php echo h($s); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card mb-3">
                  <div class="card-header">Gaps</div>
                  <div class="card-body">
                    <?php if (count($focus['gaps']) === 0): ?>
                      <p class="mb-0">None identified yet.</p>
                    <?php else: ?>
                      <ul class="mb-0">
                        <?php foreach ($focus['gaps'] as $g): ?>
                          <li><?php echo h($g); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">Story Hooks</div>
              <div class="card-body">
                <ul class="mb-0">
                  <?php foreach ($focus['hooks'] as $hk): ?>
                    <li><?php echo h($hk); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>

          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- New Coterie Modal -->
<div class="modal fade" id="newCoterieModal" tabindex="-1" aria-labelledby="newCoterieModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newCoterieModalLabel">Create New Coterie</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get" action="">
        <div class="modal-body">
          <?php if ($selectedCoterieId > 0): ?>
            <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
          <?php endif; ?>
          <div class="mb-3">
            <label for="newCoterieName" class="form-label">Coterie Name</label>
            <input type="text" class="form-control" id="newCoterieName" name="new_coterie_name" required placeholder="Enter coterie name">
          </div>
          <div class="mb-3">
            <label for="newCoterieChronicle" class="form-label">Chronicle</label>
            <select class="form-select" id="newCoterieChronicle" name="new_coterie_chronicle">
              <option value="">Select Chronicle</option>
              <?php foreach ($chronicles as $ch): ?>
                <option value="<?php echo h($ch); ?>"><?php echo h($ch); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Coterie</button>
        </div>
      </form>
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
            <div class="mt-1">This will replace the auto-generated focus summary.</div>
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

<!-- Edit History Modal -->
<div class="modal fade" id="editHistoryModal" tabindex="-1" aria-labelledby="editHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editHistoryModalLabel">Edit Coterie History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get" action="">
        <div class="modal-body">
          <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
          <div class="mb-3">
            <label for="historyText" class="form-label">History</label>
            <textarea class="form-control" id="historyText" name="update_history" rows="8" placeholder="Enter coterie history..."><?php echo h((string)($selected['history'] ?? '')); ?></textarea>
            <div class="mt-1">Record the coterie's background and past events.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save History</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Reason Modal -->
<div class="modal fade" id="editReasonModal" tabindex="-1" aria-labelledby="editReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editReasonModalLabel">Edit Coterie Reason</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get" action="">
        <div class="modal-body">
          <input type="hidden" name="coterie_id" value="<?php echo (int)$selectedCoterieId; ?>">
          <div class="mb-3">
            <label for="reasonText" class="form-label">Reason</label>
            <textarea class="form-control" id="reasonText" name="update_reason" rows="8" placeholder="Enter the reason for this coterie's formation..."><?php echo h((string)($selected['reason'] ?? '')); ?></textarea>
            <div class="mt-1">Explain why this coterie was formed and what brings the members together.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Reason</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
// Include character view modal (admin-only page, so use admin endpoint)
$apiEndpoint = '/admin/view_character_api.php';
include __DIR__ . '/../../includes/character_view_modal.php';
?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
