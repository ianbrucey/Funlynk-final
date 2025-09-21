// Authentication types for Funlynk
import { User as SupabaseUser, Session } from '@supabase/supabase-js';

export interface User {
  id: string;
  email: string;
  username?: string;
  display_name?: string;
  avatar_url?: string;
  email_verified: boolean;
  created_at: string;
  updated_at: string;
}

export interface AuthState {
  user: User | null;
  session: Session | null;
  loading: boolean;
  initialized: boolean;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterCredentials {
  email: string;
  password: string;
  username: string;
  display_name: string;
}

export interface AuthError {
  message: string;
  code?: string;
  details?: any;
}

export interface AuthContextType {
  // State
  user: User | null;
  session: Session | null;
  loading: boolean;
  initialized: boolean;
  
  // Actions
  signUp: (credentials: RegisterCredentials) => Promise<{ user: User | null; error: AuthError | null }>;
  signIn: (credentials: LoginCredentials) => Promise<{ user: User | null; error: AuthError | null }>;
  signOut: () => Promise<{ error: AuthError | null }>;
  resetPassword: (email: string) => Promise<{ error: AuthError | null }>;
  updatePassword: (password: string) => Promise<{ error: AuthError | null }>;
  refreshSession: () => Promise<{ session: Session | null; error: AuthError | null }>;
}

export interface SocialProvider {
  id: 'google' | 'apple' | 'facebook';
  name: string;
  icon: string;
  enabled: boolean;
}

export const SOCIAL_PROVIDERS: SocialProvider[] = [
  {
    id: 'google',
    name: 'Google',
    icon: 'google',
    enabled: true
  },
  {
    id: 'apple',
    name: 'Apple',
    icon: 'apple',
    enabled: true
  },
  {
    id: 'facebook',
    name: 'Facebook',
    icon: 'facebook',
    enabled: false // Will enable after setup
  }
];

export interface AuthValidation {
  email: {
    isValid: boolean;
    message?: string;
  };
  password: {
    isValid: boolean;
    message?: string;
    strength?: 'weak' | 'fair' | 'good' | 'strong';
  };
  username: {
    isValid: boolean;
    message?: string;
    available?: boolean;
    checking?: boolean;
  };
  displayName: {
    isValid: boolean;
    message?: string;
  };
}

export interface UsernameAvailability {
  available: boolean;
  message: string;
  error: string | null;
}

export interface PasswordPolicy {
  minLength: number;
  maxLength: number;
  requireUppercase: boolean;
  requireLowercase: boolean;
  requireNumbers: boolean;
  requireSpecialChars: boolean;
  forbidCommonPasswords: boolean;
}

export const DEFAULT_PASSWORD_POLICY: PasswordPolicy = {
  minLength: 8,
  maxLength: 128,
  requireUppercase: true,
  requireLowercase: true,
  requireNumbers: true,
  requireSpecialChars: true,
  forbidCommonPasswords: true
};
