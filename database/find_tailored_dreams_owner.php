<?php
/**
 * Find owner of Tailored Dreams location in Ahwatukee
 * Searches locations and location_ownership tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/connect.php';

// Search for the location
$location_query = "SELECT * FROM locations WHERE name LIKE '%Tailored Dreams%' OR district = 'Ahwatukee'";
$location_result = mysqli_query($conn, $location_query);

echo "<h2>Locations Found:</h2>";
if (mysqli_num_rows($location_result) > 0) {
    while ($location = mysqli_fetch_assoc($location_result)) {
        echo "<h3>{$location['name']} (ID: {$location['id']})</h3>";
        echo "<p><strong>District:</strong> {$location['district']}</p>";
        echo "<p><strong>Type:</strong> {$location['type']}</p>";
        echo "<p><strong>Owner Type:</strong> {$location['owner_type']}</p>";
        echo "<p><strong>Owner Notes:</strong> " . ($location['owner_notes'] ?? 'None') . "</p>";
        
        // Check location_ownership table
        $ownership_query = "SELECT lo.*, c.character_name, c.clan, c.player_name 
                           FROM location_ownership lo
                           LEFT JOIN characters c ON lo.character_id = c.id
                           WHERE lo.location_id = {$location['id']}";
        $ownership_result = mysqli_query($conn, $ownership_query);
        
        echo "<h4>Ownership Records:</h4>";
        if (mysqli_num_rows($ownership_result) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Character</th><th>Clan</th><th>Player</th><th>Ownership Type</th><th>Notes</th></tr>";
            while ($ownership = mysqli_fetch_assoc($ownership_result)) {
                echo "<tr>";
                echo "<td>{$ownership['character_name']}</td>";
                echo "<td>{$ownership['clan']}</td>";
                echo "<td>{$ownership['player_name']}</td>";
                echo "<td>{$ownership['ownership_type']}</td>";
                echo "<td>" . ($ownership['notes'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p><em>No ownership records found in location_ownership table.</em></p>";
        }
        echo "<hr>";
    }
} else {
    echo "<p>No locations found matching 'Tailored Dreams' or in Ahwatukee district.</p>";
}

// List all Toreador characters
echo "<h2>All Toreador Characters:</h2>";
$toreador_query = "SELECT id, character_name, clan, player_name, concept, biography, equipment 
                   FROM characters 
                   WHERE clan = 'Toreador' 
                   ORDER BY character_name";
$toreador_result = mysqli_query($conn, $toreador_query);

if (mysqli_num_rows($toreador_result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Player</th><th>Concept</th><th>Equipment/Business Notes</th></tr>";
    while ($char = mysqli_fetch_assoc($toreador_result)) {
        echo "<tr>";
        echo "<td>{$char['id']}</td>";
        echo "<td><strong>{$char['character_name']}</strong></td>";
        echo "<td>{$char['player_name']}</td>";
        echo "<td>{$char['concept']}</td>";
        $equipment = substr($char['equipment'] ?? '', 0, 100);
        $biography = substr($char['biography'] ?? '', 0, 150);
        echo "<td>" . htmlspecialchars($equipment) . "<br><small>" . htmlspecialchars($biography) . "...</small></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No Toreador characters found.</p>";
}

mysqli_close($conn);
?>

