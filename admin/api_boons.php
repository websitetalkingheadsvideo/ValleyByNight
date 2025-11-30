<?php
/**
 * API for Boon Management
 * Handles CRUD operations for boons table
 * Updated to match actual database schema
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get action from GET or POST
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle different actions
switch ($action) {
    case 'list':
        handleListBoons();
        break;
    case 'get':
        handleGetBoon();
        break;
    default:
        // Handle POST/PUT/DELETE based on method
        if ($method === 'POST') {
            handleCreateBoon();
        } elseif ($method === 'PUT') {
            handleUpdateBoon();
        } elseif ($method === 'DELETE') {
            handleDeleteBoon();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
}

function handleListBoons() {
    global $conn;
    
    $status = $_GET['status'] ?? 'all';
    
    // Join with characters table to get names
    $query = "SELECT 
                b.id as boon_id,
                b.creditor_id,
                b.debtor_id,
                creditor.character_name as giver_name,
                debtor.character_name as receiver_name,
                b.boon_type,
                b.status,
                b.description,
                b.created_date as date_created,
                b.fulfilled_date,
                b.due_date,
                b.notes,
                b.created_by,
                b.updated_at,
                b.registered_with_harpy,
                b.date_registered,
                b.harpy_notes
              FROM boons b
              LEFT JOIN characters creditor ON b.creditor_id = creditor.id
              LEFT JOIN characters debtor ON b.debtor_id = debtor.id";
    
    $params = [];
    $types = "";
    
    if ($status !== 'all') {
        // Map UI status to DB enum values
        $statusMap = [
            'Owed' => 'active',
            'Called' => 'active',
            'Paid' => 'fulfilled',
            'Broken' => 'disputed'
        ];
        $dbStatus = $statusMap[$status] ?? $status;
        
        $query .= " WHERE b.status = ?";
        $params[] = strtolower($dbStatus);
        $types .= "s";
    }
    
    $query .= " ORDER BY b.created_date DESC";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            return;
        }
    } else {
        $result = mysqli_query($conn, $query);
    }
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        return;
    }
    
    $boons = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Map DB status to UI status
        $statusMap = [
            'active' => 'Owed',
            'fulfilled' => 'Paid',
            'cancelled' => 'Broken',
            'disputed' => 'Broken'
        ];
        $row['status'] = $statusMap[strtolower($row['status'])] ?? $row['status'];
        
        // Map DB boon_type to title case for UI
        $row['boon_type'] = ucfirst(strtolower($row['boon_type']));
        
        $boons[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $boons]);
}

function handleGetBoon() {
    global $conn;
    
    $boon_id = intval($_GET['id'] ?? 0);
    
    if (!$boon_id) {
        echo json_encode(['success' => false, 'message' => 'Boon ID required']);
        return;
    }
    
    // Join with characters table to get names
    $query = "SELECT 
                b.id as boon_id,
                b.creditor_id,
                b.debtor_id,
                creditor.character_name as giver_name,
                debtor.character_name as receiver_name,
                b.boon_type,
                b.status,
                b.description,
                b.created_date as date_created,
                b.fulfilled_date,
                b.due_date,
                b.notes,
                b.created_by,
                b.updated_at,
                b.registered_with_harpy,
                b.date_registered,
                b.harpy_notes
              FROM boons b
              LEFT JOIN characters creditor ON b.creditor_id = creditor.id
              LEFT JOIN characters debtor ON b.debtor_id = debtor.id
              WHERE b.id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $boon_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        return;
    }
    
    $boon = mysqli_fetch_assoc($result);
    
    if (!$boon) {
        echo json_encode(['success' => false, 'message' => 'Boon not found']);
        return;
    }
    
    // Map DB status to UI status
    $statusMap = [
        'active' => 'Owed',
        'fulfilled' => 'Paid',
        'cancelled' => 'Broken',
        'disputed' => 'Broken'
    ];
    $boon['status'] = $statusMap[strtolower($boon['status'])] ?? $boon['status'];
    
    // Map DB boon_type to title case
    $boon['boon_type'] = ucfirst(strtolower($boon['boon_type']));
    
    echo json_encode(['success' => true, 'data' => $boon]);
}

function handleCreateBoon() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    // Support both name-based and ID-based input for backward compatibility
    $creditor_id = null;
    $debtor_id = null;
    
    if (isset($input['creditor_id']) && is_numeric($input['creditor_id'])) {
        $creditor_id = intval($input['creditor_id']);
    } elseif (!empty($input['giver_name'])) {
        // Look up character ID by name
        $creditor_id = getCharacterIdByName($input['giver_name']);
        if (!$creditor_id) {
            echo json_encode(['success' => false, 'message' => 'Creditor (giver) not found in database']);
            return;
        }
    }
    
    if (isset($input['debtor_id']) && is_numeric($input['debtor_id'])) {
        $debtor_id = intval($input['debtor_id']);
    } elseif (!empty($input['receiver_name'])) {
        // Look up character ID by name
        $debtor_id = getCharacterIdByName($input['receiver_name']);
        if (!$debtor_id) {
            echo json_encode(['success' => false, 'message' => 'Debtor (receiver) not found in database']);
            return;
        }
    }
    
    if (!$creditor_id || !$debtor_id) {
        echo json_encode(['success' => false, 'message' => 'Creditor and debtor IDs or names are required']);
        return;
    }
    
    // Get boon type (map from title case to lowercase)
    $boon_type = strtolower(trim($input['boon_type'] ?? ''));
    $allowed_types = ['trivial', 'minor', 'major', 'life'];
    if (!in_array($boon_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid boon type. Must be: ' . implode(', ', $allowed_types)]);
        return;
    }
    
    // Get status (map from UI status to DB enum)
    $status = strtolower(trim($input['status'] ?? 'active'));
    $statusMap = [
        'owed' => 'active',
        'called' => 'active',
        'paid' => 'fulfilled',
        'broken' => 'disputed'
    ];
    $status = $statusMap[strtolower($status)] ?? 'active';
    $allowed_statuses = ['active', 'fulfilled', 'cancelled', 'disputed'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'active';
    }
    
    $description = trim($input['description'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $due_date = !empty($input['due_date']) ? $input['due_date'] : null;
    $registered_with_harpy = !empty($input['registered_with_harpy']) ? trim($input['registered_with_harpy']) : null;
    $harpy_notes = !empty($input['harpy_notes']) ? trim($input['harpy_notes']) : null;
    
    $created_by = $_SESSION['user_id'] ?? null;
    
    $query = "INSERT INTO boons (creditor_id, debtor_id, boon_type, status, description, notes, due_date, created_by, registered_with_harpy, harpy_notes, created_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iisssssiss", $creditor_id, $debtor_id, $boon_type, $status, $description, $notes, $due_date, $created_by, $registered_with_harpy, $harpy_notes);
    
    if (mysqli_stmt_execute($stmt)) {
        $boon_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Boon created successfully', 'boon_id' => $boon_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}

function handleUpdateBoon() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    $boon_id = intval($input['boon_id'] ?? $input['id'] ?? 0);
    
    if (!$boon_id) {
        echo json_encode(['success' => false, 'message' => 'Boon ID required']);
        return;
    }
    
    // Build update query dynamically based on provided fields
    $updates = [];
    $params = [];
    $types = "";
    
    // Handle creditor_id/giver_name
    if (isset($input['creditor_id']) && is_numeric($input['creditor_id'])) {
        $updates[] = "creditor_id = ?";
        $params[] = intval($input['creditor_id']);
        $types .= "i";
    } elseif (!empty($input['giver_name'])) {
        $creditor_id = getCharacterIdByName($input['giver_name']);
        if ($creditor_id) {
            $updates[] = "creditor_id = ?";
            $params[] = $creditor_id;
            $types .= "i";
        }
    }
    
    // Handle debtor_id/receiver_name
    if (isset($input['debtor_id']) && is_numeric($input['debtor_id'])) {
        $updates[] = "debtor_id = ?";
        $params[] = intval($input['debtor_id']);
        $types .= "i";
    } elseif (!empty($input['receiver_name'])) {
        $debtor_id = getCharacterIdByName($input['receiver_name']);
        if ($debtor_id) {
            $updates[] = "debtor_id = ?";
            $params[] = $debtor_id;
            $types .= "i";
        }
    }
    
    // Handle boon_type
    if (!empty($input['boon_type'])) {
        $boon_type = strtolower(trim($input['boon_type']));
        $allowed_types = ['trivial', 'minor', 'major', 'life'];
        if (in_array($boon_type, $allowed_types)) {
            $updates[] = "boon_type = ?";
            $params[] = $boon_type;
            $types .= "s";
        }
    }
    
    // Handle status
    if (!empty($input['status'])) {
        $status = strtolower(trim($input['status']));
        $statusMap = [
            'owed' => 'active',
            'called' => 'active',
            'paid' => 'fulfilled',
            'broken' => 'disputed'
        ];
        $status = $statusMap[strtolower($status)] ?? $status;
        $allowed_statuses = ['active', 'fulfilled', 'cancelled', 'disputed'];
        if (in_array($status, $allowed_statuses)) {
            $updates[] = "status = ?";
            $params[] = $status;
            $types .= "s";
            
            // If marking as fulfilled, set fulfilled_date
            if ($status === 'fulfilled') {
                $updates[] = "fulfilled_date = NOW()";
            }
        }
    }
    
    // Handle description
    if (isset($input['description'])) {
        $updates[] = "description = ?";
        $params[] = trim($input['description']);
        $types .= "s";
    }
    
    // Handle notes
    if (isset($input['notes'])) {
        $updates[] = "notes = ?";
        $params[] = trim($input['notes']);
        $types .= "s";
    }
    
    // Handle Harpy fields
    if (isset($input['registered_with_harpy'])) {
        $updates[] = "registered_with_harpy = ?";
        $params[] = !empty($input['registered_with_harpy']) ? trim($input['registered_with_harpy']) : null;
        $types .= "s";
        
        // Set date_registered if registering
        if (!empty($input['registered_with_harpy'])) {
            $updates[] = "date_registered = NOW()";
        }
    }
    
    if (isset($input['harpy_notes'])) {
        $updates[] = "harpy_notes = ?";
        $params[] = !empty($input['harpy_notes']) ? trim($input['harpy_notes']) : null;
        $types .= "s";
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    // Always update updated_at
    $updates[] = "updated_at = NOW()";
    
    $query = "UPDATE boons SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $boon_id;
    $types .= "i";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        return;
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Boon updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}

function handleDeleteBoon() {
    global $conn;
    
    $boon_id = intval($_GET['id'] ?? 0);
    
    if (!$boon_id) {
        echo json_encode(['success' => false, 'message' => 'Boon ID required']);
        return;
    }
    
    $query = "DELETE FROM boons WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $boon_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Boon deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}

/**
 * Get character ID by character name
 * 
 * @param string $name
 * @return int|null
 */
function getCharacterIdByName($name) {
    global $conn;
    
    $query = "SELECT id FROM characters WHERE character_name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row ? intval($row['id']) : null;
}

mysqli_close($conn);
?>
