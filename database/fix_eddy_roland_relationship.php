<?php
/**
 * Fix Eddy Valiant's Roland Relationship
 * Updates the relationship from "Seneschal Roland" (Ventrue) to "Roland Cross" (Toreador Sheriff)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

echo "<h1>Fixing Eddy Valiant's Roland Relationship</h1>\n";
echo "<pre>\n";

try {
    // Find Eddy Valiant's character ID
    $eddy_query = "SELECT id, character_name FROM characters WHERE character_name = 'Eddy Valiant' LIMIT 1";
    $eddy = db_fetch_one($conn, $eddy_query);
    
    if (!$eddy) {
        throw new Exception("Eddy Valiant not found in database");
    }
    
    $eddy_id = $eddy['id'];
    echo "Found Eddy Valiant (ID: $eddy_id)\n\n";
    
    // Find the relationship with "Seneschal Roland"
    $rel_query = "SELECT id, related_character_name, relationship_type, relationship_subtype, strength, description 
                  FROM character_relationships 
                  WHERE character_id = ? AND related_character_name LIKE '%Roland%'";
    $relationship = db_fetch_one($conn, $rel_query, "i", [$eddy_id]);
    
    if (!$relationship) {
        echo "⚠️  No Roland relationship found. Checking all relationships...\n";
        $all_rels = db_fetch_all($conn, "SELECT * FROM character_relationships WHERE character_id = ?", "i", [$eddy_id]);
        echo "Eddy has " . count($all_rels) . " relationship(s):\n";
        foreach ($all_rels as $rel) {
            echo "  - {$rel['related_character_name']} ({$rel['relationship_type']})\n";
        }
        throw new Exception("Roland relationship not found");
    }
    
    echo "Found relationship:\n";
    echo "  ID: {$relationship['id']}\n";
    echo "  Name: {$relationship['related_character_name']}\n";
    echo "  Type: {$relationship['relationship_type']}\n";
    echo "  Description: {$relationship['description']}\n\n";
    
    // Update the relationship
    $update_query = "UPDATE character_relationships 
                    SET related_character_name = ?, 
                        description = ?
                    WHERE id = ?";
    
    $new_name = "Roland Cross";
    $new_description = "Official Camarilla contact; professional tension. Sheriff of Phoenix.";
    
    $updated = db_execute($conn, $update_query, "ssi", [$new_name, $new_description, $relationship['id']]);
    
    if ($updated === false) {
        throw new Exception("Failed to update relationship: " . mysqli_error($conn));
    }
    
    echo "✅ Updated relationship:\n";
    echo "  Name: $new_name\n";
    echo "  Description: $new_description\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Update complete!\n";
echo "</pre>\n";
mysqli_close($conn);
?>





































