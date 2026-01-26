<?php
/**
 * Update Marisol "Roadrunner" Vega
 * - Add concept
 * - Add character traits
 * - Update image to "Marisol Vega.png"
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/connect.php';

$character_id = 124; // Marisol "Roadrunner" Vega
$character_name = 'Marisol "Roadrunner" Vega';

// Concept from VERSION.md: "Gangrel tracker mapping supernatural safe trails"
$concept = 'Gangrel tracker mapping supernatural safe trails';

// Traits based on her description and abilities
$traits = [
    'Physical' => ['Athletic', 'Fit', 'Quick', 'Tough', 'Resilient', 'Agile'],
    'Social' => ['Independent', 'Resourceful', 'Observant'],
    'Mental' => ['Alert', 'Perceptive', 'Intuitive', 'Cunning']
];

// Update concept
$update_sql = "UPDATE characters SET concept = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $concept, $character_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "✓ Updated concept for {$character_name}\n";
    } else {
        echo "❌ Error updating concept: " . mysqli_stmt_error($stmt) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "❌ Error preparing concept update: " . mysqli_error($conn) . "\n";
}

// Update image
$image_filename = 'Marisol Vega.png';
$update_image_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_image_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $image_filename, $character_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "✓ Updated image to: {$image_filename}\n";
    } else {
        echo "❌ Error updating image: " . mysqli_stmt_error($stmt) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "❌ Error preparing image update: " . mysqli_error($conn) . "\n";
}

// Delete existing traits
$delete_traits_sql = "DELETE FROM character_traits WHERE character_id = ?";
$stmt = mysqli_prepare($conn, $delete_traits_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $character_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo "✓ Cleared existing traits\n";
}

// Insert new traits
$insert_trait_sql = "INSERT INTO character_traits (character_id, trait_name, trait_category, trait_type) VALUES (?, ?, ?, 'positive')";
$stmt = mysqli_prepare($conn, $insert_trait_sql);

$inserted = 0;
foreach ($traits as $category => $trait_list) {
    foreach ($trait_list as $trait_name) {
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iss', $character_id, $trait_name, $category);
            if (mysqli_stmt_execute($stmt)) {
                $inserted++;
            }
        }
    }
}

if ($inserted > 0) {
    echo "✓ Inserted {$inserted} traits\n";
} else {
    echo "❌ Error inserting traits\n";
}

if ($stmt) {
    mysqli_stmt_close($stmt);
}

echo "\nDone!\n";
