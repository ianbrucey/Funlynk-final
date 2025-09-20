const { Client } = require('pg');

const setupDatabase = async () => {
  const client = new Client({
    connectionString: process.env.DATABASE_URL,
    ssl: {
      rejectUnauthorized: false
    }
  });

  try {
    await client.connect();
    console.log('Connected to Supabase PostgreSQL database');

    // Enable extensions
    await client.query('CREATE EXTENSION IF NOT EXISTS postgis;');
    await client.query('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
    console.log('Extensions enabled: PostGIS, UUID-OSSP');

    // Create users table
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          email VARCHAR(255) UNIQUE NOT NULL,
          username VARCHAR(50) UNIQUE NOT NULL,
          display_name VARCHAR(100) NOT NULL,
          bio TEXT,
          profile_image_url TEXT,
          location_name VARCHAR(255),
          location_coordinates GEOGRAPHY(POINT, 4326),
          interests TEXT[],
          is_host BOOLEAN DEFAULT FALSE,
          stripe_account_id VARCHAR(255),
          stripe_onboarding_complete BOOLEAN DEFAULT FALSE,
          follower_count INTEGER DEFAULT 0,
          following_count INTEGER DEFAULT 0,
          activity_count INTEGER DEFAULT 0,
          is_verified BOOLEAN DEFAULT FALSE,
          is_active BOOLEAN DEFAULT TRUE,
          privacy_level VARCHAR(20) DEFAULT 'public',
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
      );
    `);
    console.log('Created users table');

    // Create activities table
    await client.query(`
      CREATE TABLE IF NOT EXISTS activities (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          host_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          title VARCHAR(255) NOT NULL,
          description TEXT NOT NULL,
          activity_type VARCHAR(50) NOT NULL,
          location_name VARCHAR(255) NOT NULL,
          location_coordinates GEOGRAPHY(POINT, 4326) NOT NULL,
          start_time TIMESTAMP WITH TIME ZONE NOT NULL,
          end_time TIMESTAMP WITH TIME ZONE,
          max_attendees INTEGER,
          current_attendees INTEGER DEFAULT 0,
          is_paid BOOLEAN DEFAULT FALSE,
          price_cents INTEGER,
          currency VARCHAR(3) DEFAULT 'USD',
          stripe_price_id VARCHAR(255),
          is_public BOOLEAN DEFAULT TRUE,
          requires_approval BOOLEAN DEFAULT FALSE,
          tags TEXT[] NOT NULL DEFAULT '{}',
          images TEXT[],
          status VARCHAR(20) DEFAULT 'active',
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          
          CONSTRAINT valid_price CHECK (
              (is_paid = FALSE AND price_cents IS NULL) OR 
              (is_paid = TRUE AND price_cents > 0)
          ),
          CONSTRAINT valid_times CHECK (end_time IS NULL OR end_time > start_time),
          CONSTRAINT valid_attendees CHECK (max_attendees IS NULL OR max_attendees > 0)
      );
    `);
    console.log('Created activities table');

    // Create follows table
    await client.query(`
      CREATE TABLE IF NOT EXISTS follows (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          follower_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          following_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          
          UNIQUE(follower_id, following_id),
          CONSTRAINT no_self_follow CHECK (follower_id != following_id)
      );
    `);
    console.log('Created follows table');

    // Create rsvps table
    await client.query(`
      CREATE TABLE IF NOT EXISTS rsvps (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
          status VARCHAR(20) NOT NULL DEFAULT 'attending',
          is_paid BOOLEAN DEFAULT FALSE,
          payment_intent_id VARCHAR(255),
          payment_status VARCHAR(20),
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          
          UNIQUE(user_id, activity_id)
      );
    `);
    console.log('Created rsvps table');

    // Create comments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS comments (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
          user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          parent_comment_id UUID REFERENCES comments(id) ON DELETE CASCADE,
          content TEXT NOT NULL,
          is_edited BOOLEAN DEFAULT FALSE,
          is_deleted BOOLEAN DEFAULT FALSE,
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          
          CONSTRAINT content_not_empty CHECK (LENGTH(TRIM(content)) > 0)
      );
    `);
    console.log('Created comments table');

    // Create tags table
    await client.query(`
      CREATE TABLE IF NOT EXISTS tags (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          name VARCHAR(50) UNIQUE NOT NULL,
          category VARCHAR(50),
          description TEXT,
          usage_count INTEGER DEFAULT 0,
          is_featured BOOLEAN DEFAULT FALSE,
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
      );
    `);
    console.log('Created tags table');

    // Create notifications table
    await client.query(`
      CREATE TABLE IF NOT EXISTS notifications (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          type VARCHAR(50) NOT NULL,
          title VARCHAR(255) NOT NULL,
          message TEXT NOT NULL,
          data JSONB,
          is_read BOOLEAN DEFAULT FALSE,
          delivery_status VARCHAR(20) DEFAULT 'pending',
          delivery_method VARCHAR(20) NOT NULL,
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          read_at TIMESTAMP WITH TIME ZONE
      );
    `);
    console.log('Created notifications table');

    // Create flares table
    await client.query(`
      CREATE TABLE IF NOT EXISTS flares (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          title VARCHAR(255) NOT NULL,
          description TEXT NOT NULL,
          activity_type VARCHAR(50) NOT NULL,
          location_name VARCHAR(255),
          location_coordinates GEOGRAPHY(POINT, 4326),
          preferred_time TIMESTAMP WITH TIME ZONE,
          max_participants INTEGER,
          tags TEXT[] NOT NULL DEFAULT '{}',
          status VARCHAR(20) DEFAULT 'active',
          expires_at TIMESTAMP WITH TIME ZONE,
          converted_activity_id UUID REFERENCES activities(id),
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
      );
    `);
    console.log('Created flares table');

    // Create reports table
    await client.query(`
      CREATE TABLE IF NOT EXISTS reports (
          id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
          reporter_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
          reported_user_id UUID REFERENCES users(id) ON DELETE CASCADE,
          reported_activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
          reported_comment_id UUID REFERENCES comments(id) ON DELETE CASCADE,
          reason VARCHAR(50) NOT NULL,
          description TEXT,
          status VARCHAR(20) DEFAULT 'pending',
          admin_notes TEXT,
          reviewed_by UUID REFERENCES users(id),
          reviewed_at TIMESTAMP WITH TIME ZONE,
          created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
          
          CONSTRAINT report_target_check CHECK (
              (reported_user_id IS NOT NULL)::int + 
              (reported_activity_id IS NOT NULL)::int + 
              (reported_comment_id IS NOT NULL)::int = 1
          )
      );
    `);
    console.log('Created reports table');

    console.log('✅ Database schema setup completed successfully!');

  } catch (error) {
    console.error('❌ Database setup failed:', error.message);
    throw error;
  } finally {
    await client.end();
  }
};

setupDatabase().catch(console.error);