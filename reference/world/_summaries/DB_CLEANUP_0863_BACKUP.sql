-- VbN Database Cleanup Backup
-- Version: 0.8.63
-- Date: 2025-12-21 10:43:52
-- Purpose: Backup of affected rows before cleanup

-- Simplified backup: Affected character IDs only
-- For full backup, use: mysqldump -u user -p database characters --where="id IN (42,47,48,50,52,57,68,70,87,92,102,125,130)"

-- Affected character IDs:
--   ID: 42
--   ID: 47
--   ID: 48
--   ID: 50
--   ID: 52
--   ID: 57
--   ID: 68
--   ID: 70
--   ID: 87
--   ID: 92
--   ID: 102
--   ID: 125
--   ID: 130

-- To restore, you would need to:
-- 1. Run full mysqldump backup first (recommended before any cleanup)
-- 2. If needed, restore from that backup

-- Backup of related table rows
