// Authentication service for Funlynk
import { supabase } from '../lib/supabase';
import { 
  User, 
  LoginCredentials, 
  RegisterCredentials, 
  AuthError,
  AuthValidation,
  DEFAULT_PASSWORD_POLICY 
} from '../types/auth';

export class AuthService {
  
  /**
   * Register a new user with email and password
   */
  static async signUp(credentials: RegisterCredentials) {
    try {
      // Validate input
      const validation = this.validateRegistration(credentials);
      if (!this.isValidationPassed(validation)) {
        return {
          user: null,
          error: { message: 'Please fix validation errors', details: validation }
        };
      }

      // Sign up with Supabase Auth
      const { data, error } = await supabase.auth.signUp({
        email: credentials.email,
        password: credentials.password,
        options: {
          data: {
            username: credentials.username,
            display_name: credentials.display_name
          }
        }
      });

      if (error) {
        return {
          user: null,
          error: { message: error.message, code: error.message }
        };
      }

      // The user profile will be auto-created by database trigger
      // Return the user data
      const user = data.user ? this.mapSupabaseUser(data.user) : null;
      
      return {
        user,
        error: null
      };

    } catch (error) {
      return {
        user: null,
        error: { 
          message: error instanceof Error ? error.message : 'Registration failed',
          details: error 
        }
      };
    }
  }

  /**
   * Sign in with email and password
   */
  static async signIn(credentials: LoginCredentials) {
    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email: credentials.email,
        password: credentials.password
      });

      if (error) {
        return {
          user: null,
          error: { message: error.message, code: error.message }
        };
      }

      const user = data.user ? this.mapSupabaseUser(data.user) : null;
      
      return {
        user,
        error: null
      };

    } catch (error) {
      return {
        user: null,
        error: { 
          message: error instanceof Error ? error.message : 'Login failed',
          details: error 
        }
      };
    }
  }

  /**
   * Sign out current user
   */
  static async signOut() {
    try {
      const { error } = await supabase.auth.signOut();
      
      if (error) {
        return { error: { message: error.message, code: error.message } };
      }

      return { error: null };

    } catch (error) {
      return {
        error: { 
          message: error instanceof Error ? error.message : 'Sign out failed',
          details: error 
        }
      };
    }
  }

  /**
   * Reset password via email
   */
  static async resetPassword(email: string) {
    try {
      const { error } = await supabase.auth.resetPasswordForEmail(email, {
        redirectTo: `${process.env.EXPO_PUBLIC_APP_URL || 'http://localhost:5001'}/auth/reset-password`
      });

      if (error) {
        return { error: { message: error.message, code: error.message } };
      }

      return { error: null };

    } catch (error) {
      return {
        error: { 
          message: error instanceof Error ? error.message : 'Password reset failed',
          details: error 
        }
      };
    }
  }

  /**
   * Update user password
   */
  static async updatePassword(password: string) {
    try {
      // Validate password strength
      const validation = this.validatePassword(password);
      if (!validation.isValid) {
        return {
          error: { message: validation.message || 'Password does not meet requirements' }
        };
      }

      const { error } = await supabase.auth.updateUser({
        password: password
      });

      if (error) {
        return { error: { message: error.message, code: error.message } };
      }

      return { error: null };

    } catch (error) {
      return {
        error: { 
          message: error instanceof Error ? error.message : 'Password update failed',
          details: error 
        }
      };
    }
  }

  /**
   * Get current session
   */
  static async getSession() {
    try {
      const { data, error } = await supabase.auth.getSession();
      
      if (error) {
        return { session: null, error: { message: error.message } };
      }

      return { session: data.session, error: null };

    } catch (error) {
      return {
        session: null,
        error: { 
          message: error instanceof Error ? error.message : 'Failed to get session',
          details: error 
        }
      };
    }
  }

  /**
   * Refresh current session
   */
  static async refreshSession() {
    try {
      const { data, error } = await supabase.auth.refreshSession();
      
      if (error) {
        return { session: null, error: { message: error.message } };
      }

      return { session: data.session, error: null };

    } catch (error) {
      return {
        session: null,
        error: { 
          message: error instanceof Error ? error.message : 'Failed to refresh session',
          details: error 
        }
      };
    }
  }

  /**
   * Map Supabase user to our User type
   */
  private static mapSupabaseUser(supabaseUser: any): User {
    return {
      id: supabaseUser.id,
      email: supabaseUser.email || '',
      username: supabaseUser.user_metadata?.username || '',
      display_name: supabaseUser.user_metadata?.display_name || '',
      avatar_url: supabaseUser.user_metadata?.avatar_url || '',
      email_verified: supabaseUser.email_confirmed_at !== null,
      created_at: supabaseUser.created_at,
      updated_at: supabaseUser.updated_at
    };
  }

  /**
   * Validate registration data
   */
  static validateRegistration(credentials: RegisterCredentials): AuthValidation {
    return {
      email: this.validateEmail(credentials.email),
      password: this.validatePassword(credentials.password),
      username: this.validateUsername(credentials.username),
      displayName: this.validateDisplayName(credentials.display_name)
    };
  }

  /**
   * Validate email format
   */
  static validateEmail(email: string) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(email);
    
    return {
      isValid,
      message: isValid ? undefined : 'Please enter a valid email address'
    };
  }

  /**
   * Validate password strength
   */
  static validatePassword(password: string) {
    const policy = DEFAULT_PASSWORD_POLICY;
    const issues: string[] = [];

    if (password.length < policy.minLength) {
      issues.push(`Password must be at least ${policy.minLength} characters`);
    }

    if (password.length > policy.maxLength) {
      issues.push(`Password must be no more than ${policy.maxLength} characters`);
    }

    if (policy.requireUppercase && !/[A-Z]/.test(password)) {
      issues.push('Password must contain at least one uppercase letter');
    }

    if (policy.requireLowercase && !/[a-z]/.test(password)) {
      issues.push('Password must contain at least one lowercase letter');
    }

    if (policy.requireNumbers && !/\d/.test(password)) {
      issues.push('Password must contain at least one number');
    }

    if (policy.requireSpecialChars && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      issues.push('Password must contain at least one special character');
    }

    const isValid = issues.length === 0;
    const strength = this.calculatePasswordStrength(password);

    return {
      isValid,
      message: issues.length > 0 ? issues.join('. ') : undefined,
      strength
    };
  }

  /**
   * Calculate password strength
   */
  private static calculatePasswordStrength(password: string): 'weak' | 'fair' | 'good' | 'strong' {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
    if (password.length >= 16) score++;

    if (score <= 2) return 'weak';
    if (score <= 4) return 'fair';
    if (score <= 6) return 'good';
    return 'strong';
  }

  /**
   * Check if username is available in database
   */
  static async checkUsernameAvailability(username: string) {
    try {
      // First validate format
      const formatValidation = this.validateUsername(username);
      if (!formatValidation.isValid) {
        return {
          available: false,
          message: formatValidation.message,
          error: null
        };
      }

      // Check database for existing username
      const { data, error } = await supabase
        .from('users')
        .select('username')
        .eq('username', username.toLowerCase())
        .limit(1);

      if (error) {
        return {
          available: false,
          message: 'Unable to check username availability',
          error: error.message
        };
      }

      const isAvailable = !data || data.length === 0;

      return {
        available: isAvailable,
        message: isAvailable ? 'Username is available' : 'Username is already taken',
        error: null
      };

    } catch (error) {
      return {
        available: false,
        message: 'Unable to check username availability',
        error: error instanceof Error ? error.message : 'Unknown error'
      };
    }
  }

  /**
   * Validate username format only
   */
  static validateUsername(username: string) {
    const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
    const isValid = usernameRegex.test(username);

    return {
      isValid,
      message: isValid ? undefined : 'Username must be 3-20 characters, letters, numbers, and underscores only'
    };
  }

  /**
   * Validate display name
   */
  static validateDisplayName(displayName: string) {
    const isValid = displayName.length >= 2 && displayName.length <= 50;
    
    return {
      isValid,
      message: isValid ? undefined : 'Display name must be 2-50 characters'
    };
  }

  /**
   * Check if validation passed
   */
  private static isValidationPassed(validation: AuthValidation): boolean {
    return validation.email.isValid && 
           validation.password.isValid && 
           validation.username.isValid && 
           validation.displayName.isValid;
  }
}
