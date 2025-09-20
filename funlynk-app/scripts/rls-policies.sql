-- Row Level Security (RLS) Policies for Funlynk Social Network
-- This implements comprehensive access control for all database tables

-- Enable RLS on all user-facing tables
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.follows ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rsvps ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tags ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.flares ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reports ENABLE ROW LEVEL SECURITY;

-- Helper function to check if user is admin (for future admin features)
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS boolean
LANGUAGE sql
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.users 
    WHERE auth_user_id = auth.uid() 
    AND email IN ('admin@funlynk.com') -- Add admin emails as needed
  )
$$;

-- USERS TABLE POLICIES
-- Users can view public profile information
CREATE POLICY "Users can view public profiles" ON public.users
  FOR SELECT
  USING (
    privacy_level = 'public' 
    OR auth_user_id = auth.uid()
    OR is_admin()
  );

-- Users can update their own profile
CREATE POLICY "Users can update own profile" ON public.users
  FOR UPDATE
  USING (auth_user_id = auth.uid())
  WITH CHECK (auth_user_id = auth.uid());

-- Users can insert their own profile (auto-created by trigger)
CREATE POLICY "Users can insert own profile" ON public.users
  FOR INSERT
  WITH CHECK (auth_user_id = auth.uid());

-- ACTIVITIES TABLE POLICIES
-- Anyone can view public active activities
CREATE POLICY "Anyone can view public activities" ON public.activities
  FOR SELECT
  USING (is_public = true AND status = 'active');

-- Users can view their own activities (public or private)
CREATE POLICY "Users can view own activities" ON public.activities
  FOR SELECT
  USING (
    host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can view activities they've RSVP'd to
CREATE POLICY "Users can view RSVP'd activities" ON public.activities
  FOR SELECT
  USING (
    id IN (
      SELECT activity_id FROM public.rsvps 
      WHERE user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    )
  );

-- Users can create activities (must set themselves as host)
CREATE POLICY "Users can create activities" ON public.activities
  FOR INSERT
  WITH CHECK (
    host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can update their own activities
CREATE POLICY "Users can update own activities" ON public.activities
  FOR UPDATE
  USING (
    host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  )
  WITH CHECK (
    host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can delete their own activities
CREATE POLICY "Users can delete own activities" ON public.activities
  FOR DELETE
  USING (
    host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- FOLLOWS TABLE POLICIES
-- Users can view who they follow and who follows them
CREATE POLICY "Users can view follow relationships" ON public.follows
  FOR SELECT
  USING (
    follower_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid()) 
    OR following_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can create follow relationships (must be the follower)
CREATE POLICY "Users can create follows" ON public.follows
  FOR INSERT
  WITH CHECK (
    follower_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can delete their own follow relationships
CREATE POLICY "Users can delete own follows" ON public.follows
  FOR DELETE
  USING (
    follower_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- RSVPS TABLE POLICIES
-- Users can view RSVPs for activities they host or are RSVP'd to
CREATE POLICY "Users can view relevant RSVPs" ON public.rsvps
  FOR SELECT
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    OR activity_id IN (
      SELECT id FROM public.activities 
      WHERE host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    )
  );

-- Users can create RSVPs (must be for themselves)
CREATE POLICY "Users can create own RSVPs" ON public.rsvps
  FOR INSERT
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can update their own RSVPs
CREATE POLICY "Users can update own RSVPs" ON public.rsvps
  FOR UPDATE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  )
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can delete their own RSVPs
CREATE POLICY "Users can delete own RSVPs" ON public.rsvps
  FOR DELETE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- COMMENTS TABLE POLICIES
-- Users can view comments on public activities or activities they're involved with
CREATE POLICY "Users can view relevant comments" ON public.comments
  FOR SELECT
  USING (
    activity_id IN (
      SELECT id FROM public.activities 
      WHERE is_public = true AND status = 'active'
    )
    OR activity_id IN (
      SELECT id FROM public.activities 
      WHERE host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    )
    OR activity_id IN (
      SELECT activity_id FROM public.rsvps 
      WHERE user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    )
  );

-- Users can create comments (must be themselves as author)
CREATE POLICY "Users can create comments" ON public.comments
  FOR INSERT
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    AND activity_id IN (
      SELECT id FROM public.activities 
      WHERE is_public = true AND status = 'active'
      OR host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
      OR id IN (
        SELECT activity_id FROM public.rsvps 
        WHERE user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
      )
    )
  );

-- Users can update their own comments
CREATE POLICY "Users can update own comments" ON public.comments
  FOR UPDATE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  )
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can delete their own comments or activity hosts can delete comments on their activities
CREATE POLICY "Users can delete relevant comments" ON public.comments
  FOR DELETE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    OR activity_id IN (
      SELECT id FROM public.activities 
      WHERE host_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    )
  );

-- TAGS TABLE POLICIES
-- Anyone can read tags (public information)
CREATE POLICY "Anyone can view tags" ON public.tags
  FOR SELECT
  USING (true);

-- Only authenticated users can create tags
CREATE POLICY "Authenticated users can create tags" ON public.tags
  FOR INSERT
  WITH CHECK (auth.uid() IS NOT NULL);

-- NOTIFICATIONS TABLE POLICIES
-- Users can only see their own notifications
CREATE POLICY "Users can view own notifications" ON public.notifications
  FOR SELECT
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- System can create notifications (handled by triggers/functions)
CREATE POLICY "System can create notifications" ON public.notifications
  FOR INSERT
  WITH CHECK (true);

-- Users can update their own notifications (mark as read)
CREATE POLICY "Users can update own notifications" ON public.notifications
  FOR UPDATE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  )
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- FLARES TABLE POLICIES
-- Users can view public flares or their own flares
CREATE POLICY "Users can view relevant flares" ON public.flares
  FOR SELECT
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    OR status = 'active'
  );

-- Users can create their own flares
CREATE POLICY "Users can create own flares" ON public.flares
  FOR INSERT
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can update their own flares
CREATE POLICY "Users can update own flares" ON public.flares
  FOR UPDATE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  )
  WITH CHECK (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Users can delete their own flares
CREATE POLICY "Users can delete own flares" ON public.flares
  FOR DELETE
  USING (
    user_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- REPORTS TABLE POLICIES
-- Users can view their own reports or admins can view all
CREATE POLICY "Users can view relevant reports" ON public.reports
  FOR SELECT
  USING (
    reporter_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
    OR is_admin()
  );

-- Users can create reports
CREATE POLICY "Users can create reports" ON public.reports
  FOR INSERT
  WITH CHECK (
    reporter_id IN (SELECT id FROM public.users WHERE auth_user_id = auth.uid())
  );

-- Only admins can update reports
CREATE POLICY "Admins can update reports" ON public.reports
  FOR UPDATE
  USING (is_admin())
  WITH CHECK (is_admin());