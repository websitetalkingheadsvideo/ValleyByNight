-- Create public.users table for Valley by Night auth
-- Run this in Supabase Dashboard → SQL Editor
-- Required columns from: login_process, register_process, update_account, verify_role

CREATE TABLE IF NOT EXISTS public.users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'player',
    email_verified BOOLEAN NOT NULL DEFAULT false,
    verification_token TEXT,
    verification_expires TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    last_login TIMESTAMPTZ
);

-- Expose to PostgREST (supabase uses service_role, but ensure schema is visible)
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;

-- Allow service_role full access (default for API key)
CREATE POLICY "Service role full access" ON public.users
    FOR ALL
    USING (true)
    WITH CHECK (true);

-- Reload PostgREST schema cache so the new table is visible
NOTIFY pgrst, 'reload schema';
