-- Quick Fix for RLS Infinite Recursion Issues
-- This script fixes the problematic policies that cause infinite recursion

-- First, drop the problematic policies
DROP POLICY IF EXISTS "Users can view own activities" ON public.activities;
DROP POLICY IF EXISTS "Users can view RSVP'd activities" ON public.activities;
DROP POLICY IF EXISTS "Users can view relevant RSVPs" ON public.rsvps;
DROP POLICY IF EXISTS "Users can view relevant comments" ON public.comments;
DROP POLICY IF EXISTS "Users can create comments" ON public.comments;

-- Create a helper function to get current user ID (avoids subquery issues)
CREATE OR REPLACE FUNCTION public.current_user_id()
RETURNS UUID
LANGUAGE sql
STABLE
SECURITY DEFINER
AS $$
  SELECT id FROM public.users WHERE auth_user_id = auth.uid() LIMIT 1;
$$;

-- SIMPLIFIED ACTIVITIES POLICIES (No recursion)
-- Users can view their own activities
CREATE POLICY "Users can view own activities" ON public.activities
  FOR SELECT
  USING (host_id = current_user_id());

-- SIMPLIFIED RSVPS POLICIES (No recursion)
-- Users can view their own RSVPs and RSVPs for activities they host
CREATE POLICY "Users can view relevant RSVPs" ON public.rsvps
  FOR SELECT
  USING (
    user_id = current_user_id()
    OR activity_id IN (
      SELECT id FROM public.activities WHERE host_id = current_user_id()
    )
  );

-- SIMPLIFIED COMMENTS POLICIES (No recursion)
-- Users can view comments on public activities
CREATE POLICY "Users can view public comments" ON public.comments
  FOR SELECT
  USING (
    activity_id IN (
      SELECT id FROM public.activities
      WHERE is_public = true AND status = 'active'
    )
  );

-- Users can view comments on their own activities
CREATE POLICY "Users can view own activity comments" ON public.comments
  FOR SELECT
  USING (
    activity_id IN (
      SELECT id FROM public.activities WHERE host_id = current_user_id()
    )
  );

-- Users can create comments on public activities
CREATE POLICY "Users can create comments" ON public.comments
  FOR INSERT
  WITH CHECK (
    user_id = current_user_id()
    AND activity_id IN (
      SELECT id FROM public.activities
      WHERE is_public = true AND status = 'active'
    )
  );
