// Authentication context for Funlynk
import React, { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { Session } from '@supabase/supabase-js';
import { supabase } from '../lib/supabase';
import { AuthService } from '../services/auth';
import { 
  User, 
  AuthState, 
  AuthContextType, 
  LoginCredentials, 
  RegisterCredentials 
} from '../types/auth';

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [authState, setAuthState] = useState<AuthState>({
    user: null,
    session: null,
    loading: true,
    initialized: false
  });

  // Initialize auth state on mount
  useEffect(() => {
    initializeAuth();
    
    // Listen for auth state changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      async (event, session) => {
        console.log('Auth state changed:', event, session?.user?.email);
        await handleAuthStateChange(event, session);
      }
    );

    return () => {
      subscription.unsubscribe();
    };
  }, []);

  /**
   * Initialize authentication state
   */
  const initializeAuth = async () => {
    try {
      const { session } = await AuthService.getSession();
      
      if (session?.user) {
        const user = mapSessionToUser(session);
        setAuthState({
          user,
          session,
          loading: false,
          initialized: true
        });
      } else {
        setAuthState({
          user: null,
          session: null,
          loading: false,
          initialized: true
        });
      }
    } catch (error) {
      console.error('Failed to initialize auth:', error);
      setAuthState({
        user: null,
        session: null,
        loading: false,
        initialized: true
      });
    }
  };

  /**
   * Handle auth state changes from Supabase
   */
  const handleAuthStateChange = async (event: string, session: Session | null) => {
    if (event === 'SIGNED_IN' && session?.user) {
      const user = mapSessionToUser(session);
      setAuthState(prev => ({
        ...prev,
        user,
        session,
        loading: false
      }));
    } else if (event === 'SIGNED_OUT') {
      setAuthState(prev => ({
        ...prev,
        user: null,
        session: null,
        loading: false
      }));
    } else if (event === 'TOKEN_REFRESHED' && session) {
      const user = mapSessionToUser(session);
      setAuthState(prev => ({
        ...prev,
        user,
        session,
        loading: false
      }));
    }
  };

  /**
   * Map session to user object
   */
  const mapSessionToUser = (session: Session): User => {
    const supabaseUser = session.user;
    return {
      id: supabaseUser.id,
      email: supabaseUser.email || '',
      username: supabaseUser.user_metadata?.username || '',
      display_name: supabaseUser.user_metadata?.display_name || '',
      avatar_url: supabaseUser.user_metadata?.avatar_url || '',
      email_verified: supabaseUser.email_confirmed_at !== null,
      created_at: supabaseUser.created_at || '',
      updated_at: supabaseUser.updated_at || ''
    };
  };

  /**
   * Sign up new user
   */
  const signUp = async (credentials: RegisterCredentials) => {
    setAuthState(prev => ({ ...prev, loading: true }));
    
    try {
      const result = await AuthService.signUp(credentials);
      
      if (result.error) {
        setAuthState(prev => ({ ...prev, loading: false }));
        return result;
      }

      // Auth state will be updated by onAuthStateChange
      return result;
      
    } catch (error) {
      setAuthState(prev => ({ ...prev, loading: false }));
      return {
        user: null,
        error: { 
          message: error instanceof Error ? error.message : 'Registration failed' 
        }
      };
    }
  };

  /**
   * Sign in user
   */
  const signIn = async (credentials: LoginCredentials) => {
    setAuthState(prev => ({ ...prev, loading: true }));
    
    try {
      const result = await AuthService.signIn(credentials);
      
      if (result.error) {
        setAuthState(prev => ({ ...prev, loading: false }));
        return result;
      }

      // Auth state will be updated by onAuthStateChange
      return result;
      
    } catch (error) {
      setAuthState(prev => ({ ...prev, loading: false }));
      return {
        user: null,
        error: { 
          message: error instanceof Error ? error.message : 'Login failed' 
        }
      };
    }
  };

  /**
   * Sign out user
   */
  const signOut = async () => {
    setAuthState(prev => ({ ...prev, loading: true }));
    
    try {
      const result = await AuthService.signOut();
      
      // Auth state will be updated by onAuthStateChange
      setAuthState(prev => ({ ...prev, loading: false }));
      return result;
      
    } catch (error) {
      setAuthState(prev => ({ ...prev, loading: false }));
      return {
        error: { 
          message: error instanceof Error ? error.message : 'Sign out failed' 
        }
      };
    }
  };

  /**
   * Reset password
   */
  const resetPassword = async (email: string) => {
    return await AuthService.resetPassword(email);
  };

  /**
   * Update password
   */
  const updatePassword = async (password: string) => {
    return await AuthService.updatePassword(password);
  };

  /**
   * Refresh session
   */
  const refreshSession = async () => {
    try {
      const result = await AuthService.refreshSession();
      
      if (result.session) {
        const user = mapSessionToUser(result.session);
        setAuthState(prev => ({
          ...prev,
          user,
          session: result.session
        }));
      }
      
      return result;
      
    } catch (error) {
      return {
        session: null,
        error: { 
          message: error instanceof Error ? error.message : 'Session refresh failed' 
        }
      };
    }
  };

  const contextValue: AuthContextType = {
    // State
    user: authState.user,
    session: authState.session,
    loading: authState.loading,
    initialized: authState.initialized,
    
    // Actions
    signUp,
    signIn,
    signOut,
    resetPassword,
    updatePassword,
    refreshSession
  };

  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
}

/**
 * Hook to use authentication context
 */
export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);
  
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  
  return context;
}

/**
 * Hook to require authentication
 */
export function useRequireAuth(): AuthContextType {
  const auth = useAuth();
  
  if (!auth.user && auth.initialized && !auth.loading) {
    throw new Error('Authentication required');
  }
  
  return auth;
}
