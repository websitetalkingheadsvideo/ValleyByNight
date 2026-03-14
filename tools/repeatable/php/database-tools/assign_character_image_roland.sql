-- Set Roland Cross's official portrait to the new image.
-- Run this in Supabase Dashboard → SQL Editor. File must already exist in uploads/characters/Roland Cross.png
-- Column is portrait_name (Supabase characters table).
UPDATE characters
SET portrait_name = 'Roland Cross.png'
WHERE character_name = 'Roland Cross';
