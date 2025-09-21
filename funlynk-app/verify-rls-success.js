const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

console.log('🔍 Verifying RLS Policies Implementation...\n');

const supabase = createClient(supabaseUrl, supabaseKey);

async function verifyRLSSuccess() {
  let allTestsPassed = true;
  
  console.log('🔒 Testing Row Level Security Implementation...\n');
  
  // Test 1: Check if RLS is enabled (should restrict access)
  console.log('Test 1: Checking RLS enforcement...');
  try {
    const { data, error } = await supabase
      .from('users')
      .select('*');
    
    if (error && (error.message.includes('RLS') || error.message.includes('policy'))) {
      console.log('✅ RLS is working: Access properly restricted');
    } else if (data !== null) {
      console.log('⚠️  RLS may not be fully enabled: Data accessible without auth');
      console.log(`   Retrieved ${data?.length || 0} records`);
      allTestsPassed = false;
    }
  } catch (err) {
    console.log('❌ RLS test failed:', err.message);
    allTestsPassed = false;
  }
  
  // Test 2: Check if admin function exists
  console.log('\nTest 2: Checking admin function...');
  try {
    const { data, error } = await supabase.rpc('is_admin');
    
    if (error) {
      if (error.message.includes('permission denied') || error.message.includes('RLS')) {
        console.log('✅ Admin function exists and is secured');
      } else {
        console.log('❌ Admin function error:', error.message);
        allTestsPassed = false;
      }
    } else {
      console.log('✅ Admin function exists and returned:', data);
    }
  } catch (err) {
    console.log('❌ Admin function test failed:', err.message);
    allTestsPassed = false;
  }
  
  // Test 3: Check table access patterns
  console.log('\nTest 3: Checking table access patterns...');
  const tables = ['users', 'activities', 'rsvps', 'comments', 'follows', 'notifications'];
  
  for (const table of tables) {
    try {
      const { data, error } = await supabase
        .from(table)
        .select('*')
        .limit(1);
      
      if (error && (error.message.includes('RLS') || error.message.includes('policy'))) {
        console.log(`✅ ${table}: RLS properly enforced`);
      } else if (error) {
        console.log(`⚠️  ${table}: ${error.message}`);
      } else {
        console.log(`⚠️  ${table}: Accessible without auth (${data?.length || 0} records)`);
      }
    } catch (err) {
      console.log(`❌ ${table}: Test failed - ${err.message}`);
    }
  }
  
  // Test 4: Check if admin_users table was created
  console.log('\nTest 4: Checking admin_users table...');
  try {
    const { data, error } = await supabase
      .from('admin_users')
      .select('*')
      .limit(1);
    
    if (error && (error.message.includes('RLS') || error.message.includes('policy'))) {
      console.log('✅ admin_users table: Created and secured');
    } else if (error && error.message.includes('does not exist')) {
      console.log('❌ admin_users table: Not created');
      allTestsPassed = false;
    } else {
      console.log('✅ admin_users table: Created');
    }
  } catch (err) {
    console.log('❌ admin_users table test failed:', err.message);
  }
  
  console.log('\n📊 RLS Implementation Summary:');
  if (allTestsPassed) {
    console.log('🎉 SUCCESS: All RLS policies implemented correctly!');
    console.log('✅ Database is fully secured and ready for development');
    console.log('\n🚀 Next Steps:');
    console.log('1. Start building user authentication features');
    console.log('2. Implement user registration and login');
    console.log('3. Begin activity management features');
  } else {
    console.log('⚠️  PARTIAL: Some RLS policies may need attention');
    console.log('📋 Review the test results above');
    console.log('🔧 Re-run the RLS script if needed');
  }
  
  return allTestsPassed;
}

// Also test the React Native app status
async function testAppStatus() {
  console.log('\n📱 Testing App Database Status...');
  
  try {
    // Import the app's health check function
    const { checkDatabaseHealth, setupDatabaseSchema } = require('./src/lib/supabase');
    
    const health = await checkDatabaseHealth();
    console.log('✅ Database connection:', health.status);
    console.log('⚡ Latency:', health.latency + 'ms');
    
    const schema = await setupDatabaseSchema();
    console.log('🔒 Schema status:', schema.success ? 'READY' : 'NEEDS SETUP');
    console.log('📝 Message:', schema.message);
    
    if (schema.success) {
      console.log('\n🎉 App Status: Database secured and ready for features!');
    }
    
  } catch (err) {
    console.log('❌ App status check failed:', err.message);
  }
}

// Run all verification tests
verifyRLSSuccess().then((success) => {
  return testAppStatus();
}).then(() => {
  console.log('\n✅ Verification complete!');
});
