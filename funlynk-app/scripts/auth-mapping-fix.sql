-- Fix authentication mapping for RLS policies
-- This connects our users table to Supabase's auth.users table

-- Add auth_user_id column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_user_id UUID UNIQUE REFERENCES auth.users(id);

-- Create index for performance
CREATE INDEX IF NOT EXISTS idx_users_auth_user_id ON users(auth_user_id);

-- Add constraint to ensure auth_user_id is required for new users
-- (We'll make it NOT NULL after populating existing data)
-- ALTER TABLE users ALTER COLUMN auth_user_id SET NOT NULL;