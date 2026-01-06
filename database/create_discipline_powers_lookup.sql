-- MET Discipline Powers Lookup Table
-- Reference table for discipline powers (not character-specific)
-- Used by laws_agent and other systems for rules lookup

CREATE TABLE IF NOT EXISTS `discipline_powers_lookup` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `discipline_id` INT(11) NOT NULL,
  `discipline_name` VARCHAR(100) NOT NULL,
  `power_level` TINYINT(1) NOT NULL COMMENT 'Power level (1-5)',
  `power_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `system_text` TEXT DEFAULT NULL COMMENT 'System/mechanics text',
  `challenge_type` VARCHAR(50) DEFAULT NULL,
  `challenge_notes` TEXT DEFAULT NULL,
  `prerequisites` VARCHAR(255) DEFAULT NULL,
  `source_book` VARCHAR(255) DEFAULT NULL COMMENT 'Source rulebook',
  `source_page` INT(11) DEFAULT NULL COMMENT 'Page number in source',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discipline_id` (`discipline_id`),
  KEY `idx_discipline_name` (`discipline_name`),
  KEY `idx_power_level` (`power_level`),
  KEY `idx_discipline_level` (`discipline_id`, `power_level`),
  CONSTRAINT `fk_discipline_powers_lookup_discipline` 
    FOREIGN KEY (`discipline_id`) 
    REFERENCES `disciplines` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add unique constraint to prevent duplicate powers at same level
ALTER TABLE `discipline_powers_lookup` 
ADD UNIQUE KEY `unique_discipline_power_level` (`discipline_id`, `power_name`, `power_level`);
