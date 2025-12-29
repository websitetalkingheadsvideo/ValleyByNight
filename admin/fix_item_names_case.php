<?php
/**
 * Fix Item Names Case
 * Converts all item names from UPPERCASE to Title Case
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
$extra_css = ['css/admin_items.css'];
$body_class = 'admin-items-page';
include __DIR__ . '/../includes/header.php';

// Function to convert to Title Case
function toTitleCase($string) {
    // Convert to lowercase first
    $string = mb_strtolower($string, 'UTF-8');
    
    // Words that should remain lowercase (unless they're the first word)
    $lowercaseWords = ['of', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'a', 'an'];
    
    // Split into words
    $words = preg_split('/\s+/', $string);
    $result = [];
    
    foreach ($words as $index => $word) {
        // Always capitalize first word, or if word is not in lowercase list
        if ($index === 0 || !in_array($word, $lowercaseWords)) {
            // Capitalize first letter of word
            $word = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($word, 1, null, 'UTF-8');
        }
        $result[] = $word;
    }
    
    return implode(' ', $result);
}

try {
    // Get all items
    $query = "SELECT id, name FROM items ORDER BY id";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $updated = 0;
    $skipped = 0;
    $updates = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $currentName = $row['name'];
        $titleCaseName = toTitleCase($currentName);
        
        // Only update if the name actually changed
        if ($currentName !== $titleCaseName) {
            $updateQuery = "UPDATE items SET name = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, 'si', $titleCaseName, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $updated++;
                $updates[] = [
                    'id' => $id,
                    'old' => $currentName,
                    'new' => $titleCaseName
                ];
            } else {
                echo "Error updating item ID $id: " . mysqli_error($conn) . "<br>";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $skipped++;
        }
    }
    
    echo "<div class='container-fluid py-4 px-3 px-md-4'>";
    echo "<h2 class='text-light mb-4'>Item Names Case Fix Complete</h2>";
    echo "<div class='alert alert-success'><strong>Updated:</strong> $updated items</div>";
    echo "<div class='alert alert-info'><strong>Skipped:</strong> $skipped items (already in correct case)</div>";
    
    if ($updated > 0) {
        echo "<h3 class='text-light mb-3'>Updated Items:</h3>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-dark table-striped'>";
        echo "<thead><tr><th>ID</th><th>Old Name</th><th>New Name</th></tr></thead>";
        echo "<tbody>";
        foreach ($updates as $update) {
            echo "<tr>";
            echo "<td>{$update['id']}</td>";
            echo "<td>" . htmlspecialchars($update['old']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($update['new']) . "</strong></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
    }
    
    echo "<div class='mt-4'>";
    echo "<a href='admin_items.php' class='btn btn-primary'>Back to Items Management</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='container-fluid py-4 px-3 px-md-4'>";
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<a href='admin_items.php' class='btn btn-secondary'>Back to Items Management</a>";
    echo "</div>";
}

mysqli_close($conn);
include __DIR__ . '/../includes/footer.php';
?>

