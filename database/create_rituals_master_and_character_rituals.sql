-- Create rituals_master and character_rituals for rituals_agent
-- Run in Supabase Dashboard → SQL Editor
-- Requires: public.characters (or ensure character_id type matches your characters.id)

-- rituals_master: canonical ritual definitions
CREATE TABLE IF NOT EXISTS public.rituals_master (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(32) NOT NULL,
    level INT NOT NULL,
    description TEXT NOT NULL,
    system_text TEXT,
    requirements TEXT,
    ingredients TEXT,
    source VARCHAR(100),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT rituals_master_name_unique UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_rituals_master_type ON public.rituals_master (type);
CREATE INDEX IF NOT EXISTS idx_rituals_master_level ON public.rituals_master (level);

ALTER TABLE public.rituals_master ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Service role full access rituals_master" ON public.rituals_master
    FOR ALL USING (true) WITH CHECK (true);

-- character_rituals: which rituals a character knows (read-only by agent)
CREATE TABLE IF NOT EXISTS public.character_rituals (
    id BIGSERIAL PRIMARY KEY,
    character_id BIGINT NOT NULL,
    ritual_name VARCHAR(100) NOT NULL,
    ritual_type VARCHAR(50),
    level INT,
    is_custom SMALLINT NOT NULL DEFAULT 0,
    description TEXT
);

CREATE INDEX IF NOT EXISTS idx_character_rituals_character_id ON public.character_rituals (character_id);

ALTER TABLE public.character_rituals ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Service role full access character_rituals" ON public.character_rituals
    FOR ALL USING (true) WITH CHECK (true);

-- Reload PostgREST schema cache so new tables are visible
NOTIFY pgrst, 'reload schema';
