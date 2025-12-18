-- Paths Database Completion Verification Query
-- 
-- This query verifies that all required fields are populated for Necromancy and Thaumaturgy paths.
-- Run this query after completion to confirm all fields are non-NULL and valid.
--
-- Expected results:
-- - missing_path_descriptions: 0
-- - missing_system_text: 0
-- - unknown_challenge_types: 0
-- - missing_challenge_notes: 0

SELECT 
    -- Path descriptions check
    (SELECT COUNT(*) 
     FROM paths_master 
     WHERE type IN ('Necromancy', 'Thaumaturgy')
     AND (description IS NULL OR description = '')) AS missing_path_descriptions,
    
    -- Power system_text check
    (SELECT COUNT(*) 
     FROM path_powers pp
     INNER JOIN paths_master pm ON pp.path_id = pm.id
     WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
     AND (pp.system_text IS NULL OR pp.system_text = '')) AS missing_system_text,
    
    -- Challenge type check
    (SELECT COUNT(*) 
     FROM path_powers pp
     INNER JOIN paths_master pm ON pp.path_id = pm.id
     WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
     AND (pp.challenge_type IS NULL OR pp.challenge_type = '' OR pp.challenge_type = 'unknown')) AS unknown_challenge_types,
    
    -- Challenge notes check
    (SELECT COUNT(*) 
     FROM path_powers pp
     INNER JOIN paths_master pm ON pp.path_id = pm.id
     WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
     AND (pp.challenge_notes IS NULL OR pp.challenge_notes = '')) AS missing_challenge_notes,
    
    -- Total counts for reference
    (SELECT COUNT(*) 
     FROM paths_master 
     WHERE type IN ('Necromancy', 'Thaumaturgy')) AS total_paths,
    
    (SELECT COUNT(*) 
     FROM path_powers pp
     INNER JOIN paths_master pm ON pp.path_id = pm.id
     WHERE pm.type IN ('Necromancy', 'Thaumaturgy')) AS total_powers;

-- Detailed breakdown by path type
SELECT 
    pm.type,
    COUNT(DISTINCT pm.id) AS total_paths,
    SUM(CASE WHEN pm.description IS NULL OR pm.description = '' THEN 1 ELSE 0 END) AS paths_missing_description,
    COUNT(pp.id) AS total_powers,
    SUM(CASE WHEN pp.system_text IS NULL OR pp.system_text = '' THEN 1 ELSE 0 END) AS powers_missing_system_text,
    SUM(CASE WHEN pp.challenge_type IS NULL OR pp.challenge_type = '' OR pp.challenge_type = 'unknown' THEN 1 ELSE 0 END) AS powers_unknown_challenge_type,
    SUM(CASE WHEN pp.challenge_notes IS NULL OR pp.challenge_notes = '' THEN 1 ELSE 0 END) AS powers_missing_challenge_notes
FROM paths_master pm
LEFT JOIN path_powers pp ON pm.id = pp.path_id
WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
GROUP BY pm.type;

-- List paths still missing descriptions
SELECT 
    id,
    name,
    type
FROM paths_master
WHERE type IN ('Necromancy', 'Thaumaturgy')
AND (description IS NULL OR description = '')
ORDER BY type, name;

-- List powers still needing updates
SELECT 
    pp.id,
    pm.name AS path_name,
    pm.type AS path_type,
    pp.level,
    pp.power_name,
    CASE WHEN pp.system_text IS NULL OR pp.system_text = '' THEN 'Missing' ELSE 'Present' END AS system_text_status,
    CASE WHEN pp.challenge_type IS NULL OR pp.challenge_type = '' OR pp.challenge_type = 'unknown' THEN 'Missing/Invalid' ELSE pp.challenge_type END AS challenge_type_status,
    CASE WHEN pp.challenge_notes IS NULL OR pp.challenge_notes = '' THEN 'Missing' ELSE 'Present' END AS challenge_notes_status
FROM path_powers pp
INNER JOIN paths_master pm ON pp.path_id = pm.id
WHERE pm.type IN ('Necromancy', 'Thaumaturgy')
AND (
    pp.system_text IS NULL OR pp.system_text = ''
    OR pp.challenge_type IS NULL OR pp.challenge_type = '' OR pp.challenge_type = 'unknown'
    OR pp.challenge_notes IS NULL OR pp.challenge_notes = ''
)
ORDER BY pm.type, pm.name, pp.level;

