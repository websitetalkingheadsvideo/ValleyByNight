<?php
/**
 * Migrate Item Categories
 * Updates items.category to match valid categories from items_categories lookup table
 * Maps old/invalid categories to closest matching valid category (usually by first word)
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

try {
    // Get all valid categories from lookup table
    $valid_categories_query = "SELECT DISTINCT category_name FROM items_categories ORDER BY category_name";
    $valid_categories_result = mysqli_query($conn, $valid_categories_query);
    
    if (!$valid_categories_result) {
        throw new Exception('Failed to query items_categories: ' . mysqli_error($conn));
    }
    
    $valid_categories = [];
    while ($row = mysqli_fetch_assoc($valid_categories_result)) {
        $valid_categories[] = $row['category_name'];
    }
    
    if (empty($valid_categories)) {
        echo "<h2>Error</h2>";
        echo "<p>No valid categories found in items_categories table.</p>";
        exit();
    }
    
    // Function to suggest category based on item type
    function suggestCategoryByType($itemType, $validCategories) {
        // Type-based category mapping
        $typeMapping = [
            'Weapon' => ['Firearms', 'Melee', 'Ranged', 'Firearm'],
            'Armor' => ['Body', 'Protective Gear', 'Protection'],
            'Tool' => ['Utility', 'Criminal Equipment'],
            'Electronics' => ['Communication', 'Computing', 'Surveillance', 'Audio'],
            'Artifact' => ['Artifact', 'Divination', 'Religious', 'Ritual'],
            'Magical Artifact' => ['Artifact', 'Divination'],
            'Magical Tool' => ['Divination', 'Artifact', 'Alchemy'],
            'Magical Potion' => ['Alchemy', 'Chemical'],
            'Magical Material' => ['Alchemy', 'Chemical', 'Artifact'],
            'Magical Token' => ['Artifact', 'Divination'],
            'Consumable' => ['Utility', 'Chemical'],
            'Ammunition' => ['Firearms', 'Firearm', 'Firearm Accessory'],
            'Gear' => ['Utility', 'Protective Gear', 'Container'],
            'Token' => ['Artifact', 'Divination'],
            'Trait' => ['Utility'],
            'Communication' => ['Communication', 'Audio'],
            'Weapon/Tool' => ['Firearms', 'Criminal Equipment', 'Firearm']
        ];
        
        if (!isset($typeMapping[$itemType])) {
            return null;
        }
        
        $suggestedTypes = $typeMapping[$itemType];
        
        // Try to find first matching category from suggestions
        foreach ($suggestedTypes as $suggested) {
            foreach ($validCategories as $valid) {
                if (strcasecmp($suggested, $valid) === 0) {
                    return $valid;
                }
            }
        }
        
        // Try partial match
        foreach ($suggestedTypes as $suggested) {
            foreach ($validCategories as $valid) {
                if (stripos($valid, $suggested) !== false || stripos($suggested, $valid) !== false) {
                    return $valid;
                }
            }
        }
        
        return null;
    }
    
    // Function to find closest matching category
    function findClosestCategory($oldCategory, $validCategories) {
        // Normalize input
        $oldCategory = trim($oldCategory);
        if (empty($oldCategory)) {
            return null;
        }
        
        // First, check for exact match (case-insensitive)
        foreach ($validCategories as $valid) {
            if (strcasecmp($oldCategory, $valid) === 0) {
                return $valid;
            }
        }
        
        // Handle categories with slashes (e.g., "Detection/Tracking" -> try both parts)
        $parts = [];
        if (strpos($oldCategory, '/') !== false) {
            $parts = explode('/', $oldCategory);
            $parts = array_map('trim', $parts);
        } else {
            $parts[] = $oldCategory;
        }
        
        // Try each part (especially first part) against valid categories
        foreach ($parts as $part) {
            // Exact match on part
            foreach ($validCategories as $valid) {
                if (strcasecmp($part, $valid) === 0) {
                    return $valid;
                }
            }
            
            // Extract first word from part (handle multi-word categories)
            $firstWord = trim(explode(' ', $part)[0]);
            $firstWord = trim($firstWord, '-'); // Remove trailing dash if present
            
            // Exact match on first word
            foreach ($validCategories as $valid) {
                if (strcasecmp($firstWord, $valid) === 0) {
                    return $valid;
                }
            }
            
            // Partial match - does first word start with any valid category or vice versa?
            foreach ($validCategories as $valid) {
                if (stripos($firstWord, $valid) === 0 || stripos($valid, $firstWord) === 0) {
                    return $valid;
                }
            }
            
            // Try finding first word as substring in valid categories
            foreach ($validCategories as $valid) {
                if (stripos($valid, $firstWord) !== false) {
                    return $valid;
                }
            }
        }
        
        // No match found - return null
        return null;
    }
    
    // Check if items table has category column
    $check_column_query = "SHOW COLUMNS FROM items LIKE 'category'";
    $check_column_result = mysqli_query($conn, $check_column_query);
    $has_category_column = ($check_column_result && mysqli_num_rows($check_column_result) > 0);
    
    if (!$has_category_column) {
        // Category column doesn't exist - we need to add it first
        $add_column_query = "ALTER TABLE items ADD COLUMN category VARCHAR(255) NULL";
        if (!mysqli_query($conn, $add_column_query)) {
            throw new Exception('Failed to add category column: ' . mysqli_error($conn));
        }
        echo "<p><strong>Note:</strong> Added 'category' column to items table.</p>";
    }
    
    // Get all items (including those without categories)
    $items_query = "SELECT id, name, type, category FROM items ORDER BY id";
    $items_result = mysqli_query($conn, $items_query);
    
    if (!$items_result) {
        throw new Exception('Failed to query items: ' . mysqli_error($conn));
    }
    
    $updates = [];
    $skipped = [];
    $unmapped = [];
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $itemId = $item['id'];
        $itemName = $item['name'];
        $itemType = $item['type'];
        $currentCategory = $item['category'] ?? '';
        
        // If item has no category, try to suggest one based on type
        if (empty($currentCategory) || $currentCategory === null) {
            // Try to find a category based on item type
            $suggestedCategory = suggestCategoryByType($itemType, $valid_categories);
            
            if ($suggestedCategory !== null) {
                // Update item with suggested category
                $update_query = "UPDATE items SET category = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, 'si', $suggestedCategory, $itemId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $updates[] = [
                        'id' => $itemId,
                        'name' => $itemName,
                        'old_category' => '(none)',
                        'new_category' => $suggestedCategory
                    ];
                } else {
                    echo "Error updating item ID $itemId: " . mysqli_error($conn) . "<br>";
                }
                
                mysqli_stmt_close($stmt);
                continue;
            } else {
                // No suggestion found
                $unmapped[] = [
                    'id' => $itemId,
                    'name' => $itemName,
                    'old_category' => '(none)',
                    'type' => $itemType
                ];
                continue;
            }
        }
        
        // Check if current category is valid
        $isValid = false;
        foreach ($valid_categories as $valid) {
            if (strcasecmp($currentCategory, $valid) === 0) {
                $isValid = true;
                break;
            }
        }
        
        if ($isValid) {
            $skipped[] = [
                'id' => $itemId,
                'name' => $itemName,
                'category' => $currentCategory
            ];
            continue;
        }
        
        // Find closest matching category
        $newCategory = findClosestCategory($currentCategory, $valid_categories);
        
        if ($newCategory === null) {
            $unmapped[] = [
                'id' => $itemId,
                'name' => $itemName,
                'old_category' => $currentCategory,
                'type' => $itemType
            ];
            continue;
        }
        
        // Update item
        $update_query = "UPDATE items SET category = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'si', $newCategory, $itemId);
        
        if (mysqli_stmt_execute($stmt)) {
            $updates[] = [
                'id' => $itemId,
                'name' => $itemName,
                'old_category' => $currentCategory,
                'new_category' => $newCategory
            ];
        } else {
            echo "Error updating item ID $itemId: " . mysqli_error($conn) . "<br>";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Output results
    echo "<h2>Category Migration Complete</h2>";
    echo "<p><strong>Valid Categories:</strong> " . implode(', ', $valid_categories) . "</p>";
    echo "<p><strong>Updated:</strong> " . count($updates) . " items</p>";
    echo "<p><strong>Skipped:</strong> " . count($skipped) . " items (already have valid categories)</p>";
    echo "<p><strong>Unmapped:</strong> " . count($unmapped) . " items (no close match found)</p>";
    
    if (count($updates) > 0) {
        echo "<h3>Updated Items:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Old Category</th><th>New Category</th></tr>";
        foreach ($updates as $update) {
            echo "<tr>";
            echo "<td>{$update['id']}</td>";
            echo "<td>" . htmlspecialchars($update['name']) . "</td>";
            echo "<td>" . htmlspecialchars($update['old_category']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($update['new_category']) . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if (count($unmapped) > 0) {
        echo "<h3>Unmapped Items (Manual Review Required):</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Old Category</th></tr>";
        foreach ($unmapped as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['type'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['old_category']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

mysqli_close($conn);
?>

