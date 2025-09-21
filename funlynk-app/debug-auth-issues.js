// Debug authentication issues
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

console.log('🔍 Debugging Authentication Issues...\n');

const supabase = createClient(supabaseUrl, supabaseKey);

async function debugAuthIssues() {
  console.log('1️⃣ Testing login with non-existent account...\n');
  
  try {
    const { data, error } = await supabase.auth.signInWithPassword({
      email: 'nonexistent@example.com',
      password: 'wrongpassword'
    });
    
    console.log('Login attempt result:');
    console.log('Data:', data);
    console.log('Error:', error);
    
    if (error) {
      console.log('✅ Error properly returned:', error.message);
    } else {
      console.log('❌ No error returned - this is unexpected');
    }
    
  } catch (err) {
    console.log('❌ Exception during login:', err.message);
  }
  
  console.log('\n2️⃣ Testing registration flow...\n');
  
  try {
    const testEmail = `test${Date.now()}@example.com`;
    const { data, error } = await supabase.auth.signUp({
      email: testEmail,
      password: 'TestPassword123!',
      options: {
        data: {
          username: `testuser${Date.now()}`,
          display_name: 'Test User'
        }
      }
    });
    
    console.log('Registration attempt result:');
    console.log('Data:', data);
    console.log('Error:', error);
    
    if (data.user) {
      console.log('User created:', {
        id: data.user.id,
        email: data.user.email,
        email_confirmed_at: data.user.email_confirmed_at,
        confirmed_at: data.user.confirmed_at
      });
      
      if (data.session) {
        console.log('✅ Session created immediately - user is signed in');
      } else {
        console.log('⚠️  No session - email verification required');
      }
    }
    
  } catch (err) {
    console.log('❌ Exception during registration:', err.message);
  }
  
  console.log('\n3️⃣ Checking Supabase Auth configuration...\n');
  
  try {
    // Check current session
    const { data: sessionData, error: sessionError } = await supabase.auth.getSession();
    console.log('Current session:', sessionData.session ? 'Active' : 'None');
    
    if (sessionError) {
      console.log('Session error:', sessionError.message);
    }
    
  } catch (err) {
    console.log('❌ Session check failed:', err.message);
  }
  
  console.log('\n4️⃣ Testing auth state listener...\n');
  
  // Test auth state change listener
  const { data: { subscription } } = supabase.auth.onAuthStateChange((event, session) => {
    console.log(`Auth event: ${event}`);
    if (session) {
      console.log(`Session user: ${session.user.email}`);
    }
  });
  
  // Clean up listener after a moment
  setTimeout(() => {
    subscription.unsubscribe();
    console.log('Auth listener cleaned up');
  }, 1000);
  
  console.log('\n🎯 Diagnosis Summary:');
  console.log('═══════════════════════');
  
  console.log('\n📋 Likely Issues:');
  console.log('1. Login Error Display: Check if Alert.alert works in web environment');
  console.log('2. Registration Flow: Email verification may be required');
  console.log('3. Auth State: Session might not be created until email verified');
  console.log('4. Navigation: App expects immediate sign-in after registration');
  
  console.log('\n🔧 Fixes Needed:');
  console.log('1. Improve error display for login failures');
  console.log('2. Handle email verification requirement in registration');
  console.log('3. Show proper loading/verification states');
  console.log('4. Add better error handling for auth flows');
  
  return true;
}

debugAuthIssues().then(() => {
  console.log('\n✅ Authentication debugging complete!');
  process.exit(0);
});
