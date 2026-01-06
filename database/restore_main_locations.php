<?php
/**
 * Restore missing main locations: Hawthorne Estate and The Chantry
 * These should exist as main locations, with PC Havens as sublocations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Optional: Check if admin (but allow direct access for debugging)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     die('Unauthorized');
// }

require_once __DIR__ . '/../includes/connect.php';

header('Content-Type: text/html; charset=utf-8');
echo "<pre>\n";
echo "Restoring missing main locations...\n\n";

// Check if locations already exist
function locationExists($conn, $name, $type) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type FROM locations WHERE name = ? AND type = ?");
    mysqli_stmt_bind_param($stmt, "ss", $name, $type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    $row = null;
    if ($exists) {
        $row = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
    return ['exists' => $exists, 'data' => $row];
}

// 1. Hawthorne Estate - should be Domain or Elysium
$hawthorne_check = locationExists($conn, 'Hawthorne Estate', 'Domain');
if (!$hawthorne_check['exists']) {
    $hawthorne_check = locationExists($conn, 'Hawthorne Estate', 'Elysium');
}

$hawthorne_id = null;
if (!$hawthorne_check['exists']) {
    echo "Creating: Hawthorne Estate (Domain/Elysium)\n";
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO locations (name, type, summary, description, status, district, owner_type, faction, access_control, security_level, pc_haven, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())"
    );
    
    $name = 'Hawthorne Estate';
    $type = 'Elysium'; // Based on the markdown, it's used for Elysium gatherings
    $summary = 'Grand estate in Northern Scottsdale serving as a primary Elysium location for Camarilla gatherings and social functions.';
    $description = 'The Hawthorne Estate is a grand, refined desert-elite estate in Northern Scottsdale. The estate features a grand entrance hall with onyx pillars and vaulted ceiling, main salon for gatherings, gallery wing with surreal art, veranda overlooking the desert, courtyard garden with reflection pool, and a private study. The estate is designed for sophisticated social intrigue and masked rivalries, with intentional low lighting, artful shadows, and acoustics that amplify whispers.';
    $status = 'Active';
    $district = 'Northern Scottsdale';
    $owner_type = 'Personal';
    $faction = 'Camarilla';
    $access_control = 'Invitation Only';
    $security_level = 7;
    
    mysqli_stmt_bind_param($stmt, 'sssssssssi', $name, $type, $summary, $description, $status, $district, $owner_type, $faction, $access_control, $security_level);
    
    if (mysqli_stmt_execute($stmt)) {
        $hawthorne_id = mysqli_insert_id($conn);
        echo "  ✓ Created Hawthorne Estate (ID: $hawthorne_id)\n";
    } else {
        echo "  ✗ Failed to create Hawthorne Estate: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    $hawthorne_id = $hawthorne_check['data']['id'];
    echo "Hawthorne Estate already exists (ID: $hawthorne_id, Type: {$hawthorne_check['data']['type']})\n";
}

// 2. The Chantry - should be Chantry type
$chantry_check = locationExists($conn, 'The Chantry', 'Chantry');
$chantry_id = null;
if (!$chantry_check['exists']) {
    echo "\nCreating: The Chantry (Chantry)\n";
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO locations (name, type, summary, description, status, district, owner_type, faction, access_control, security_level, pc_haven, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())"
    );
    
    $name = 'The Chantry';
    $type = 'Chantry';
    $summary = 'Tremere Chantry in West Phoenix serving as the primary research and ritual facility for the Phoenix Tremere. Access is controlled through three independent axes: Library Access, Laboratory Access, and Grounds Access.';
    $description = 'The Chantry is an access lattice, not a haven. For Tremere PCs, the Chantry is always present in some form - what changes is what parts of it open to them, and why. The question is never "Do you live here?" but "How much of this place is allowed to acknowledge you?" Access is controlled through three independent axes: Library Access (knowledge as the soft leash), Laboratory Access (power as the hard leash), and Grounds/Physical Space (belonging masquerading as privilege). These axes remain separate to allow complex permission structures - a PC can have full library access but no lab access, or lab access but restricted grounds. The Chantry decides how much of yourself you\'re allowed to use.';
    $status = 'Active';
    $district = 'West Phoenix / Camelback Mountain Area (Horse Zoning District)';
    $owner_type = 'Clan';
    $faction = 'Camarilla';
    $access_control = 'Restricted';
    $security_level = 9;
    
    mysqli_stmt_bind_param($stmt, 'sssssssssi', $name, $type, $summary, $description, $status, $district, $owner_type, $faction, $access_control, $security_level);
    
    if (mysqli_stmt_execute($stmt)) {
        $chantry_id = mysqli_insert_id($conn);
        echo "  ✓ Created The Chantry (ID: $chantry_id)\n";
    } else {
        echo "  ✗ Failed to create The Chantry: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    $chantry_id = $chantry_check['data']['id'];
    echo "The Chantry already exists (ID: $chantry_id, Type: {$chantry_check['data']['type']})\n";
}

// 3. Link PC Havens to their parent locations
echo "\n\n=== Linking PC Havens to Parent Locations ===\n\n";

// Link Hawthorne Estate PC Haven
if ($hawthorne_id) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, parent_location_id FROM locations WHERE name LIKE 'Hawthorne Estate%' AND type = 'Haven' AND pc_haven = 1");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['parent_location_id'] != $hawthorne_id) {
                $update_stmt = mysqli_prepare($conn, "UPDATE locations SET parent_location_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "ii", $hawthorne_id, $row['id']);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "  ✓ Linked '{$row['name']}' (ID: {$row['id']}) to Hawthorne Estate (ID: $hawthorne_id)\n";
                } else {
                    echo "  ✗ Failed to link '{$row['name']}': " . mysqli_error($conn) . "\n";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                echo "  - '{$row['name']}' (ID: {$row['id']}) already linked to Hawthorne Estate\n";
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// Link The Chantry PC Haven
if ($chantry_id) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, parent_location_id FROM locations WHERE name LIKE 'The Chantry%' AND type = 'Haven' AND pc_haven = 1");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['parent_location_id'] != $chantry_id) {
                $update_stmt = mysqli_prepare($conn, "UPDATE locations SET parent_location_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "ii", $chantry_id, $row['id']);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "  ✓ Linked '{$row['name']}' (ID: {$row['id']}) to The Chantry (ID: $chantry_id)\n";
                } else {
                    echo "  ✗ Failed to link '{$row['name']}': " . mysqli_error($conn) . "\n";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                echo "  - '{$row['name']}' (ID: {$row['id']}) already linked to The Chantry\n";
            }
        }
    }
    mysqli_stmt_close($stmt);
}

echo "\n\n=== Verification ===\n\n";
echo "Checking all related locations:\n\n";

$check_names = ['Hawthorne Estate', 'The Chantry'];
foreach ($check_names as $check_name) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, type, pc_haven, parent_location_id, status FROM locations WHERE name LIKE ? ORDER BY type, id");
    $pattern = "%{$check_name}%";
    mysqli_stmt_bind_param($stmt, "s", $pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $isPCHaven = ($row['type'] === 'Haven' && ($row['pc_haven'] == 1 || $row['pc_haven'] === true));
            $parent_info = $row['parent_location_id'] ? " | Parent ID: {$row['parent_location_id']}" : " | Parent: None";
            echo "ID: {$row['id']} | Name: {$row['name']} | Type: {$row['type']} | PC Haven: " . ($isPCHaven ? 'YES' : 'NO') . $parent_info . " | Status: {$row['status']}\n";
        }
    }
    mysqli_stmt_close($stmt);
    echo "\n";
}

echo "</pre>";

mysqli_close($conn);
?>
