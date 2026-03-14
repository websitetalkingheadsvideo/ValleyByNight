-- Set Alessandro Vescari's official portrait to the new image.
-- Run this in Supabase Dashboard → SQL Editor. File must already exist in uploads/characters/Alessandro Vescari.png
-- Column is portrait_name (Supabase characters table has no character_image).
UPDATE characters
SET portrait_name = 'Alessandro Vescari.png'
WHERE character_name = 'Alessandro Vescari';
