<?php
/**
 * Update Item Images Script
 * Matches image files in uploads/Items/ to items in the database
 * Updates items that don't have images yet
 */

require_once __DIR__ . '/../includes/connect.php';

// Get uploads directory
$uploads_dir = dirname(__DIR__) . '/uploads/Items/';

// Manual mappings for images that don't match automatically
$manual_mappings = [
    'canopic-jar.jpg' => 'Canopic Jar of Whispers',
    'silver-oak-leaf.jpg' => 'Silver Oak Leaf Pendant',
    'The Ashur Tablets.png' => 'The Ashur Tablets (fragment of Erciyes)',
    'eye-of-set.jpg' => 'Serpent\'s Eye Amulet',
    'ritual-pyre-ashes.jpg' => null, // No clear match yet
    'Scorched Tarot Card.webp' => null, // No clear match yet
    'Rare Desert Herbs & Minerals.jpg' => null, // Could be Desert Sand Pouch or Vial of Desert Night
    'Ancient Egyptian Canopic Jar of Whispering Echoes.png' => 'Canopic Jar of Whispers', // Alternative image
];

// Normalize a string for matching (lowercase, remove special chars, spaces to underscores)
function normalize_for_matching($str) {
    $str = strtolower($str);
    // Remove file extension
    $str = preg_replace('/\.[^.]+$/', '', $str);
    // Replace spaces, hyphens, underscores with nothing
    $str = preg_replace('/[\s\-_]+/', '', $str);
    // Remove special characters
    $str = preg_replace('/[^a-z0-9]/', '', $str);
    return $str;
}

// Calculate similarity score between two normalized strings
function calculate_similarity($str1, $str2) {
    if (empty($str1) || empty($str2)) {
        return 0;
    }
    
    // Exact match
    if ($str1 === $str2) {
        return 100;
    }
    
    // Check if one contains the other (for substantial matches)
    $min_len = min(strlen($str1), strlen($str2));
    if ($min_len < 4) {
        return 0; // Too short for partial matching
    }
    
    // Check if longer string contains shorter string
    $longer = strlen($str1) > strlen($str2) ? $str1 : $str2;
    $shorter = strlen($str1) > strlen($str2) ? $str2 : $str1;
    
    if (strpos($longer, $shorter) !== false) {
        // Calculate percentage match based on how much of the shorter is in the longer
        return (int)((strlen($shorter) / strlen($longer)) * 100);
    }
    
    // Use Levenshtein distance for fuzzy matching
    $max_len = max(strlen($str1), strlen($str2));
    $distance = levenshtein($str1, $str2);
    $similarity = (1 - ($distance / $max_len)) * 100;
    
    return (int)max(0, $similarity);
}

// Get all items from database
$items_query = "SELECT id, name, image FROM items ORDER BY name ASC";
$items_result = mysqli_query($conn, $items_query);

if (!$items_result) {
    die("Error fetching items: " . mysqli_error($conn));
}

$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}

// Get all image files from uploads directory
$image_files = [];
if (is_dir($uploads_dir)) {
    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $file_path = $uploads_dir . $file;
        if (is_file($file_path)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $image_files[] = $file;
            }
        }
    }
}

echo "Found " . count($items) . " items in database\n";
echo "Found " . count($image_files) . " image files in uploads/Items/\n\n";

// Build normalized lookup for items
$items_by_normalized = [];
foreach ($items as $item) {
    $normalized_name = normalize_for_matching($item['name']);
    if (!isset($items_by_normalized[$normalized_name])) {
        $items_by_normalized[$normalized_name] = [];
    }
    $items_by_normalized[$normalized_name][] = $item;
}

// Match images to items
$matched = [];
$unmatched_images = [];
$updated_count = 0;

foreach ($image_files as $image_file) {
    $image_normalized = normalize_for_matching($image_file);
    $matched_item = null;
    $best_score = 0;
    
    // Check manual mappings first
    if (isset($manual_mappings[$image_file])) {
        $manual_item_name = $manual_mappings[$image_file];
        if ($manual_item_name !== null) {
            foreach ($items as $item) {
                if ($item['name'] === $manual_item_name) {
                    $matched_item = $item;
                    $best_score = 100;
                    break;
                }
            }
        } else {
            // Manual mapping set to null means skip this image
            $unmatched_images[] = $image_file;
            continue;
        }
    }
    
    // Try exact match first
    if (!$matched_item && isset($items_by_normalized[$image_normalized])) {
        // If multiple items with same normalized name, prefer one without image
        foreach ($items_by_normalized[$image_normalized] as $item) {
            if (empty($item['image'])) {
                $matched_item = $item;
                $best_score = 100;
                break;
            }
        }
        // If all have images, use first one (but don't update)
        if (!$matched_item && !empty($items_by_normalized[$image_normalized])) {
            $matched_item = $items_by_normalized[$image_normalized][0];
            $best_score = 100;
        }
    }
    
    // Try keyword-based matching for specific patterns
    if (!$matched_item || $best_score < 100) {
        $image_lower = strtolower($image_file);
        
        // Keyword matching patterns
        $keyword_patterns = [
            ['desert', ['Desert Sand Pouch', 'Vial of Desert Night']],
            ['tarot', ['Burned Tarot Card']], // Already has image, but check anyway
            ['ash', ['Ashes of the Fallen']], // Already has image
            ['ritual', []], // No clear match
        ];
        
        foreach ($keyword_patterns as $pattern) {
            $keyword = $pattern[0];
            $possible_items = $pattern[1];
            
            if (strpos($image_lower, $keyword) !== false) {
                foreach ($possible_items as $item_name) {
                    foreach ($items as $item) {
                        if ($item['name'] === $item_name && empty($item['image'])) {
                            $matched_item = $item;
                            $best_score = 75; // Good match but not exact
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    // Try similarity matching (only for items without images)
    if (!$matched_item || $best_score < 100) {
        foreach ($items as $item) {
            // Skip items that already have images (unless exact match)
            if (!empty($item['image']) && $best_score < 100) {
                continue;
            }
            
            $item_normalized = normalize_for_matching($item['name']);
            $similarity = calculate_similarity($image_normalized, $item_normalized);
            
            // Require at least 50% similarity for partial matches
            if ($similarity >= 50 && $similarity > $best_score) {
                $matched_item = $item;
                $best_score = $similarity;
                
                // If we found an exact match for an item without image, stop searching
                if ($similarity == 100 && empty($item['image'])) {
                    break;
                }
            }
        }
    }
    
    if ($matched_item && $best_score >= 50) {
        $matched[] = [
            'image' => $image_file,
            'item_id' => $matched_item['id'],
            'item_name' => $matched_item['name'],
            'current_image' => $matched_item['image'],
            'score' => $best_score
        ];
    } else {
        $unmatched_images[] = $image_file;
    }
}

echo "=== MATCHING RESULTS ===\n\n";

// Show matched images that need updates
$needs_update = [];
foreach ($matched as $match) {
    $score_text = $match['score'] == 100 ? 'EXACT' : "{$match['score']}%";
    if (empty($match['current_image'])) {
        $needs_update[] = $match;
        echo "✅ MATCH ({$score_text}): {$match['image']} → {$match['item_name']} (ID: {$match['item_id']})\n";
    } else {
        echo "ℹ️  SKIP ({$score_text}): {$match['image']} → {$match['item_name']} (already has image: {$match['current_image']})\n";
    }
}

echo "\n";

// Show unmatched images
if (!empty($unmatched_images)) {
    echo "⚠️  UNMATCHED IMAGES (" . count($unmatched_images) . "):\n";
    foreach ($unmatched_images as $image) {
        echo "   - {$image}\n";
    }
    echo "\n";
}

// Show items without images
$items_without_images = [];
foreach ($items as $item) {
    if (empty($item['image'])) {
        $items_without_images[] = $item;
    }
}

if (!empty($items_without_images)) {
    echo "⚠️  ITEMS WITHOUT IMAGES (" . count($items_without_images) . "):\n";
    foreach ($items_without_images as $item) {
        echo "   - {$item['name']} (ID: {$item['id']})\n";
    }
    echo "\n";
}

// Update database
if (!empty($needs_update)) {
    echo "=== UPDATING DATABASE ===\n\n";
    
    foreach ($needs_update as $match) {
        $update_query = "UPDATE items SET image = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        
        if (!$stmt) {
            echo "❌ Error preparing update for {$match['item_name']}: " . mysqli_error($conn) . "\n";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $match['image'], $match['item_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $updated_count++;
            echo "✅ Updated: {$match['item_name']} (ID: {$match['item_id']}) → {$match['image']}\n";
        } else {
            echo "❌ Error updating {$match['item_name']}: " . mysqli_stmt_error($stmt) . "\n";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Updated {$updated_count} item(s) with new images.\n";
} else {
    echo "No items need updating.\n";
}

mysqli_close($conn);
?>

