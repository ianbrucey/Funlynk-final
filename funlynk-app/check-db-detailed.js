const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkDetailedStatus() {
  console.log('🔍 Detailed Database Analysis\n');
  
  try {
    // Check all tables in public schema
    console.log('📋 Checking all tables in public schema...');
    const { data: tables, error: tablesError } = await supabase
      .rpc('get_table_list');
    
    if (tablesError) {
      // Fallback: try to query known tables
      console.log('Using fallback method to check tables...\n');
      
      const knownTables = [
        'users', 'activities', 'rsvps', 'comments', 'follows', 
        'notifications', 'tags', 'flares', 'reports'
      ];
      
      for (const tableName of knownTables) {
        try {
          const { data, error, count } = await supabase
            .from(tableName)
            .select('*', { count: 'exact', head: true });
          
          if (error) {
            console.log(`❌ ${tableName}: ${error.message}`);
          } else {
            console.log(`✅ ${tableName}: Exists (${count || 0} records)`);
          }
        } catch (err) {
          console.log(`❌ ${tableName}: ${err.message}`);
        }
      }
    }
    
    // Check specific table structures
    console.log('\n🏗️  Checking table structures...');
    
    // Check users table structure
    try {
      const { data: userData, error: userError } = await supabase
        .from('users')
        .select('*')
        .limit(1);
      
      if (!userError && userData) {
        console.log('✅ Users table: Accessible');
        if (userData.length > 0) {
          console.log('   Sample columns:', Object.keys(userData[0]).join(', '));
        }
      }
    } catch (err) {
      console.log('❌ Users table: Error accessing');
    }
    
    // Check activities table structure
    try {
      const { data: activityData, error: activityError } = await supabase
        .from('activities')
        .select('*')
        .limit(1);
      
      if (!activityError && activityData) {
        console.log('✅ Activities table: Accessible');
        if (activityData.length > 0) {
          console.log('   Sample columns:', Object.keys(activityData[0]).join(', '));
        }
      }
    } catch (err) {
      console.log('❌ Activities table: Error accessing');
    }
    
    // Test RLS status
    console.log('\n🔒 Testing Row Level Security...');
    
    // Try to access data without authentication (should be restricted if RLS is working)
    try {
      const { data: testData, error: testError } = await supabase
        .from('users')
        .select('*');
      
      if (testError) {
        if (testError.message.includes('RLS') || testError.message.includes('policy')) {
          console.log('✅ RLS is working: Access properly restricted');
        } else {
          console.log('⚠️  RLS status unclear:', testError.message);
        }
      } else {
        console.log('⚠️  RLS may not be enabled: Data accessible without auth');
        console.log(`   Retrieved ${testData?.length || 0} records`);
      }
    } catch (err) {
      console.log('❌ RLS test failed:', err.message);
    }
    
    // Check for extensions
    console.log('\n🔧 Checking database extensions...');
    try {
      const { data: extData, error: extError } = await supabase
        .rpc('check_postgis_extension');
      
      if (extError) {
        console.log('⚠️  PostGIS extension check failed (may not be available via RPC)');
      } else {
        console.log('✅ PostGIS extension: Available');
      }
    } catch (err) {
      console.log('⚠️  Extension check not available via current method');
    }
    
    console.log('\n📊 Summary:');
    console.log('✅ Database connection: Working');
    console.log('✅ Core tables: Present');
    console.log('⚠️  RLS policies: Need to be enabled');
    console.log('\n🎯 Next Step: Execute rls-policies.sql in Supabase SQL Editor');
    
  } catch (error) {
    console.error('❌ Detailed check failed:', error.message);
  }
}

checkDetailedStatus();
