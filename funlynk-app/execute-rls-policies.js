const { createClient } = require('@supabase/supabase-js');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

console.log('🔒 Executing Row Level Security Policies...\n');

if (!supabaseUrl || !supabaseKey) {
  console.error('❌ Missing Supabase credentials');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function executeRLSPolicies() {
  try {
    // Read the RLS policies SQL file
    const rlsFilePath = path.join(__dirname, 'scripts', 'rls-policies.sql');
    console.log('📄 Reading RLS policies from:', rlsFilePath);
    
    if (!fs.existsSync(rlsFilePath)) {
      console.error('❌ RLS policies file not found:', rlsFilePath);
      return;
    }
    
    const rlsSQL = fs.readFileSync(rlsFilePath, 'utf8');
    console.log('✅ RLS policies file loaded');
    console.log(`📊 SQL content length: ${rlsSQL.length} characters\n`);
    
    // For RLS policies, we need service role access which the anon key doesn't have
    console.log('⚠️  RLS policies require service role privileges');
    console.log('🔑 The anon key cannot execute DDL statements like CREATE POLICY');
    
    console.log('\n📋 Manual execution required:');
    console.log('1. Go to Supabase Dashboard → SQL Editor');
    console.log('2. Copy the contents of scripts/rls-policies.sql');
    console.log('3. Paste and execute in SQL Editor (uses service role automatically)');
    
    // Let's at least verify what we can access
    console.log('\n🔍 Testing current access level...');
    
    // Test basic table access
    const { data: userData, error: userError } = await supabase
      .from('users')
      .select('*')
      .limit(1);
    
    if (userError) {
      console.log('❌ Cannot access users table:', userError.message);
    } else {
      console.log('✅ Can access users table (RLS not yet enabled)');
    }
    
    // Test if we can create a simple function (this will likely fail)
    const { data: funcData, error: funcError } = await supabase
      .rpc('test_function_creation');
    
    if (funcError) {
      console.log('❌ Cannot create functions (expected - need service role)');
    } else {
      console.log('✅ Can create functions');
    }
    
    return false; // Indicate manual setup needed
    
  } catch (error) {
    console.error('❌ Failed to execute RLS policies:', error.message);
    return false;
  }
}

// Verification function
async function verifyRLSStatus() {
  console.log('\n🔍 Verifying current RLS status...');
  
  try {
    // Test if RLS is working by trying to access data without auth
    const { data, error } = await supabase
      .from('users')
      .select('*');
    
    if (error && (error.message.includes('RLS') || error.message.includes('policy'))) {
      console.log('✅ RLS is working: Access properly restricted');
      return true;
    } else if (data !== null) {
      console.log('⚠️  RLS not enabled: Data accessible without auth');
      console.log(`   Retrieved ${data?.length || 0} records`);
      return false;
    }
  } catch (err) {
    console.log('❌ RLS verification failed:', err.message);
    return false;
  }
}

// Run the execution
executeRLSPolicies().then((success) => {
  return verifyRLSStatus();
}).then((rlsWorking) => {
  if (rlsWorking) {
    console.log('\n🎉 Database is fully secured and ready for development!');
  } else {
    console.log('\n⚠️  RLS setup required - use Supabase SQL Editor');
    console.log('\n🎯 Copy this file content to SQL Editor:');
    console.log('   scripts/rls-policies.sql');
  }
});
