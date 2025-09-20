-- Complete authentication setup for RLS policies
-- This ensures all users are properly linked to auth.users

-- 1. Backfill existing users with auth mapping (if any exist)
UPDATE users u 
SET auth_user_id = a.id 
FROM auth.users a 
WHERE a.email = u.email 
AND u.auth_user_id IS NULL;

-- 2. Make auth_user_id required for all users
ALTER TABLE users ALTER COLUMN auth_user_id SET NOT NULL;

-- 3. Create function to auto-provision users on signup
CREATE OR REPLACE FUNCTION public.handle_new_auth_user() 
RETURNS trigger 
LANGUAGE plpgsql 
SECURITY DEFINER 
SET search_path = public
AS $$
BEGIN
  INSERT INTO users (
    id, 
    auth_user_id, 
    email, 
    username, 
    display_name,
    created_at,
    updated_at
  ) VALUES (
    gen_random_uuid(), 
    NEW.id, 
    NEW.email, 
    COALESCE(
      split_part(NEW.email, '@', 1) || '_' || substr(NEW.id::text, 1, 8), 
      NEW.id::text
    ), 
    split_part(NEW.email, '@', 1),
    NOW(),
    NOW()
  );
  RETURN NEW;
END;
$$;

-- 4. Create trigger to auto-create users on auth signup
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_auth_user();

-- 5. Create helper function for RLS policies
CREATE OR REPLACE FUNCTION public.current_user_id() 
RETURNS uuid 
LANGUAGE sql 
STABLE
AS $$
  SELECT id FROM users WHERE auth_user_id = auth.uid()
$$;