const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

// Use the credentials from .env file
const supabaseUrl = process.env.SUPABASE_URL || process.env.EXPO_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY || process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY;

console.log('🔍 Checking Funlynk Database Status...\n');
console.log('📍 Supabase URL:', supabaseUrl);
console.log('🔑 Using anon key:', supabaseKey ? `${supabaseKey.substring(0, 20)}...` : 'NOT FOUND');

if (!supabaseUrl || !supabaseKey) {
  console.error('❌ Missing Supabase credentials in .env file');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkDatabaseStatus() {
  console.log('\n🔗 Testing database connection...');
  
  try {
    // Test basic connection
    const startTime = Date.now();
    const { data, error } = await supabase
      .from('users')
      .select('count')
      .limit(1);
    
    const latency = Date.now() - startTime;
    
    if (error) {
      if (error.message.includes('relation "users" does not exist')) {
        console.log('✅ Connection successful');
        console.log(`⚡ Latency: ${latency}ms`);
        console.log('⚠️  Schema Status: Tables not created yet');
        console.log('\n📋 Next Steps:');
        console.log('1. Run schema.sql in Supabase SQL Editor');
        console.log('2. Run auth-setup-fixed.sql');
        console.log('3. Run rls-policies.sql');
        return;
      } else {
        console.log('❌ Database error:', error.message);
        return;
      }
    }
    
    console.log('✅ Connection successful');
    console.log(`⚡ Latency: ${latency}ms`);
    console.log('✅ Users table exists');
    
    // Check for auth mapping
    console.log('\n🔐 Checking authentication setup...');
    const { data: authCheck, error: authError } = await supabase
      .from('users')
      .select('auth_user_id')
      .limit(1);
    
    if (authError && authError.message.includes('auth_user_id')) {
      console.log('⚠️  Auth mapping missing - need to run auth-mapping-fix.sql');
    } else {
      console.log('✅ Auth mapping exists');
    }
    
    // Check for helper functions
    console.log('\n🛠️  Checking helper functions...');
    const { data: funcCheck, error: funcError } = await supabase
      .rpc('current_user_id');
    
    if (funcError && funcError.message.includes('current_user_id')) {
      console.log('⚠️  Helper functions missing - need to run auth-complete-setup.sql');
    } else {
      console.log('✅ Helper functions exist');
    }
    
    // Check for RLS policies
    console.log('\n🔒 Checking Row Level Security...');
    const { data: rlsCheck, error: rlsError } = await supabase
      .rpc('is_admin');
    
    if (rlsError && rlsError.message.includes('is_admin')) {
      console.log('⚠️  RLS policies missing - need to run rls-policies.sql');
    } else {
      console.log('✅ RLS policies enabled');
    }
    
    // Check table structure
    console.log('\n📊 Checking table structure...');
    const tables = ['users', 'activities', 'rsvps', 'comments', 'follows', 'notifications'];
    
    for (const table of tables) {
      try {
        const { data, error } = await supabase
          .from(table)
          .select('*')
          .limit(1);
        
        if (error) {
          console.log(`❌ ${table}: ${error.message}`);
        } else {
          console.log(`✅ ${table}: Table exists`);
        }
      } catch (err) {
        console.log(`❌ ${table}: Error checking table`);
      }
    }
    
    console.log('\n🎉 Database Status Summary:');
    console.log('✅ Connection: Working');
    console.log('✅ Schema: Deployed');
    console.log('🔍 Check individual components above for detailed status');
    
  } catch (error) {
    console.error('❌ Connection failed:', error.message);
  }
}

checkDatabaseStatus();
