import { createClient } from '@supabase/supabase-js'

// Environment variables for Expo (must use EXPO_PUBLIC_ prefix for client-side access)
const supabaseUrl = process.env.EXPO_PUBLIC_SUPABASE_URL!
const supabaseAnonKey = process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY!

if (!supabaseUrl || !supabaseAnonKey) {
  throw new Error('Missing Supabase environment variables. Please check EXPO_PUBLIC_SUPABASE_URL and EXPO_PUBLIC_SUPABASE_ANON_KEY')
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: false
  },
  realtime: {
    params: {
      eventsPerSecond: 10
    }
  }
})

// Database health check utility
export const checkDatabaseHealth = async () => {
  const startTime = Date.now()
  try {
    // Use a simple query that will always work if connected
    const { data, error } = await supabase
      .from('users')
      .select('count')
      .limit(1)
    
    const latency = Date.now() - startTime
    
    // If we get here without throwing, we're connected
    // Error just means table doesn't exist yet
    return {
      status: 'connected' as const,
      latency,
      lastChecked: new Date(),
      schemaExists: !error
    }
  } catch (error) {
    return {
      status: 'disconnected' as const,
      latency: Date.now() - startTime,
      lastChecked: new Date(),
      errorMessage: error instanceof Error ? error.message : 'Unknown error'
    }
  }
}

// Create database schema
export const setupDatabaseSchema = async () => {
  try {
    console.log('Setting up database schema...')
    
    // Try to query the users table to see if it exists
    const { data, error } = await supabase
      .from('users')
      .select('count')
      .limit(1)
    
    if (!error) {
      console.log('Database schema already exists')
      
      // Check if auth mapping exists
      const { data: authCheck, error: authError } = await supabase
        .from('users')
        .select('auth_user_id')
        .limit(1)
      
      if (authError && authError.message.includes('auth_user_id')) {
        console.log('Auth mapping needs to be added')
        return { success: false, message: 'Please run auth-mapping-fix.sql for authentication setup' }
      }
      
      console.log('Schema and auth mapping ready')
      return { success: true, message: 'Schema ready for use' }
    }
    
    // If we get here, the table doesn't exist, so we need to create the schema
    console.log('Tables do not exist, database setup required')
    return { success: false, message: 'Please run the SQL schema in Supabase dashboard' }
    
  } catch (error) {
    console.error('Schema setup error:', error)
    return { success: false, message: error instanceof Error ? error.message : 'Unknown error' }
  }
}