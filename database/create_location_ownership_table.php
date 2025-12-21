<?php
/**
 * Create location_ownership table
 * Junction table linking characters to locations (many-to-many)
 */

require_once __DIR__ . '/../includes/connect.php';

$query = "CREATE TABLE IF NOT EXISTS `location_ownership` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `location_id` INT NOT NULL,
    `character_id` INT NOT NULL,
    `ownership_type` VARCHAR(50) DEFAULT 'Resident',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`character_id`) REFERENCES `characters`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_location_character` (`location_id`, `character_id`),
    INDEX `idx_location_id` (`location_id`),
    INDEX `idx_character_id` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $query)) {
    echo "Table 'location_ownership' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>

