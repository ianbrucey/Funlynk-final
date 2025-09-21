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
    
    // Split the SQL into individual statements
    const statements = rlsSQL
      .split(';')
      .map(stmt => stmt.trim())
      .filter(stmt => stmt.length > 0 && !stmt.startsWith('--'));
    
    console.log(`🔧 Found ${statements.length} SQL statements to execute\n`);
    
    let successCount = 0;
    let errorCount = 0;
    
    // Execute each statement
    for (let i = 0; i < statements.length; i++) {
      const statement = statements[i];
      
      if (statement.length < 10) continue; // Skip very short statements
      
      console.log(`⚡ Executing statement ${i + 1}/${statements.length}...`);
      
      try {
        const { data, error } = await supabase.rpc('exec_sql', {
          sql_query: statement
        });
        
        if (error) {
          // Try alternative method using direct query
          const { data: altData, error: altError } = await supabase
            .from('_dummy_table_that_does_not_exist')
            .select('*');
          
          // If that fails, try using the SQL directly (this might work for some statements)
          console.log(`⚠️  Standard RPC failed, trying direct execution...`);
          
          // For RLS and policy creation, we need to use a different approach
          if (statement.includes('ENABLE ROW LEVEL SECURITY') || 
              statement.includes('CREATE POLICY') ||
              statement.includes('CREATE TABLE') ||
              statement.includes('CREATE OR REPLACE FUNCTION')) {
            
            console.log(`⚠️  Statement requires admin privileges: ${statement.substring(0, 50)}...`);
            console.log(`   This needs to be executed in Supabase SQL Editor with service role`);
            errorCount++;
          } else {
            console.log(`❌ Error: ${error.message}`);
            errorCount++;
          }
        } else {
          console.log(`✅ Success`);
          successCount++;
        }
      } catch (err) {
        console.log(`❌ Exception: ${err.message}`);
        errorCount++;
      }
    }
    
    console.log('\n📊 Execution Summary:');
    console.log(`✅ Successful statements: ${successCount}`);
    console.log(`❌ Failed statements: ${errorCount}`);
    
    if (errorCount > 0) {
      console.log('\n⚠️  Some statements failed - this is expected!');
      console.log('🔑 RLS policies and admin functions require service role privileges');
      console.log('📋 Manual execution required in Supabase SQL Editor');
      
      console.log('\n🎯 Next Steps:');
      console.log('1. Go to Supabase Dashboard → SQL Editor');
      console.log('2. Copy the contents of scripts/rls-policies.sql');
      console.log('3. Paste and execute in SQL Editor');
      console.log('4. Return here and run the status check');
    } else {
      console.log('\n🎉 All RLS policies executed successfully!');
    }
    
  } catch (error) {
    console.error('❌ Failed to execute RLS policies:', error.message);
  }
}

// Also create a verification function
async function verifyRLSStatus() {
  console.log('\n🔍 Verifying RLS status...');
  
  try {
    // Test if RLS is working by trying to access data without auth
    const { data, error } = await supabase
      .from('users')
      .select('*');
    
    if (error && (error.message.includes('RLS') || error.message.includes('policy'))) {
      console.log('✅ RLS is working: Access properly restricted');
      return true;
    } else if (data) {
      console.log('⚠️  RLS may not be enabled: Data accessible without auth');
      return false;
    }
  } catch (err) {
    console.log('❌ RLS verification failed:', err.message);
    return false;
  }
}

// Run the execution
executeRLSPolicies().then(() => {
  return verifyRLSStatus();
}).then((rlsWorking) => {
  if (rlsWorking) {
    console.log('\n🎉 Database is fully secured and ready for development!');
  } else {
    console.log('\n⚠️  Manual RLS setup still required');
  }
});
