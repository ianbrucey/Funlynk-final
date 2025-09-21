// Registration screen for Funlynk
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator
} from 'react-native';
import { useAuth } from '../../contexts/AuthContext';
import { AuthService } from '../../services/auth';

export default function RegisterScreen({ navigation }: any) {
  const { signUp, loading } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    confirmPassword: '',
    username: '',
    displayName: ''
  });
  const [formErrors, setFormErrors] = useState<{[key: string]: string}>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [registrationError, setRegistrationError] = useState<string>('');
  const [registrationSuccess, setRegistrationSuccess] = useState<boolean>(false);
  const [usernameStatus, setUsernameStatus] = useState<{
    checking: boolean;
    available: boolean | null;
    message: string;
  }>({
    checking: false,
    available: null,
    message: ''
  });

  /**
   * Debounced username availability checking
   */
  useEffect(() => {
    if (formData.username.length >= 3) {
      setUsernameStatus(prev => ({ ...prev, checking: true }));

      const timer = setTimeout(async () => {
        try {
          const result = await AuthService.checkUsernameAvailability(formData.username);
          setUsernameStatus({
            checking: false,
            available: result.available,
            message: result.message
          });

          // Update form errors if username is taken
          if (!result.available) {
            setFormErrors(prev => ({ ...prev, username: result.message }));
          } else {
            // Clear username error if available
            setFormErrors(prev => {
              const { username, ...rest } = prev;
              return rest;
            });
          }
        } catch (error) {
          setUsernameStatus({
            checking: false,
            available: false,
            message: 'Unable to check username availability'
          });
        }
      }, 500); // 500ms debounce

      return () => clearTimeout(timer);
    } else {
      // Reset status for short usernames
      setUsernameStatus({
        checking: false,
        available: null,
        message: ''
      });
    }
  }, [formData.username]);

  /**
   * Handle form input changes
   */
  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));

    // Clear errors when user starts typing
    if (formErrors[field]) {
      setFormErrors(prev => ({ ...prev, [field]: '' }));
    }
    if (registrationError) {
      setRegistrationError('');
    }
  };

  /**
   * Validate form data
   */
  const validateForm = () => {
    const errors: {[key: string]: string} = {};

    // Email validation
    const emailValidation = AuthService.validateEmail(formData.email);
    if (!emailValidation.isValid) {
      errors.email = emailValidation.message || 'Invalid email';
    }

    // Password validation
    const passwordValidation = AuthService.validatePassword(formData.password);
    if (!passwordValidation.isValid) {
      errors.password = passwordValidation.message || 'Invalid password';
    }

    // Confirm password validation
    if (formData.password !== formData.confirmPassword) {
      errors.confirmPassword = 'Passwords do not match';
    }

    // Username validation
    const usernameValidation = AuthService.validateUsername(formData.username);
    if (!usernameValidation.isValid) {
      errors.username = usernameValidation.message || 'Invalid username';
    } else if (usernameStatus.available === false) {
      errors.username = usernameStatus.message || 'Username is not available';
    } else if (usernameStatus.checking) {
      errors.username = 'Checking username availability...';
    }

    // Display name validation
    const displayNameValidation = AuthService.validateDisplayName(formData.displayName);
    if (!displayNameValidation.isValid) {
      errors.displayName = displayNameValidation.message || 'Invalid display name';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  /**
   * Handle registration submission
   */
  const handleRegister = async () => {
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);

    try {
      const result = await signUp({
        email: formData.email.trim().toLowerCase(),
        password: formData.password,
        username: formData.username.trim().toLowerCase(),
        display_name: formData.displayName.trim()
      });

      if (result.error) {
        setRegistrationError(result.error.message);
      } else {
        setRegistrationSuccess(true);
        // Note: User won't be signed in until email is verified
        // This is expected behavior with Supabase email confirmation
      }
    } catch (error) {
      setRegistrationError('An unexpected error occurred. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * Navigate to login
   */
  const navigateToLogin = () => {
    navigation.navigate('Login');
  };

  const isLoading = loading || isSubmitting;

  // Show success screen after registration
  if (registrationSuccess) {
    return (
      <View style={styles.container}>
        <View style={styles.successContainer}>
          <Text style={styles.successTitle}>Registration Successful!</Text>
          <Text style={styles.successMessage}>
            We've sent a verification email to {formData.email}
          </Text>
          <Text style={styles.successSubtext}>
            Please check your email and click the verification link to activate your account.
            Once verified, you can sign in with your credentials.
          </Text>

          <TouchableOpacity
            style={styles.backButton}
            onPress={navigateToLogin}
          >
            <Text style={styles.backButtonText}>Go to Login</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView 
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.title}>Join Funlynk</Text>
          <Text style={styles.subtitle}>Create your account to get started</Text>
        </View>

        <View style={styles.form}>
          {/* Registration Error Display */}
          {registrationError && (
            <View style={styles.errorContainer}>
              <Text style={styles.errorText}>{registrationError}</Text>
            </View>
          )}

          {/* Email Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Email</Text>
            <TextInput
              style={[styles.input, formErrors.email && styles.inputError]}
              value={formData.email}
              onChangeText={(value) => handleInputChange('email', value)}
              placeholder="Enter your email"
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              editable={!isLoading}
            />
            {formErrors.email && (
              <Text style={styles.errorText}>{formErrors.email}</Text>
            )}
          </View>

          {/* Username Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Username</Text>
            <View style={styles.inputContainer}>
              <TextInput
                style={[
                  styles.input,
                  styles.inputWithIcon,
                  formErrors.username && styles.inputError,
                  usernameStatus.available === true && styles.inputSuccess
                ]}
                value={formData.username}
                onChangeText={(value) => handleInputChange('username', value)}
                placeholder="Choose a username"
                autoCapitalize="none"
                autoCorrect={false}
                editable={!isLoading}
              />
              <View style={styles.inputIcon}>
                {usernameStatus.checking ? (
                  <ActivityIndicator size="small" color="#007AFF" />
                ) : usernameStatus.available === true ? (
                  <Text style={styles.successIcon}>✓</Text>
                ) : usernameStatus.available === false ? (
                  <Text style={styles.errorIcon}>✗</Text>
                ) : null}
              </View>
            </View>
            {formErrors.username && (
              <Text style={styles.errorText}>{formErrors.username}</Text>
            )}
            {!formErrors.username && usernameStatus.available === true && (
              <Text style={styles.successText}>{usernameStatus.message}</Text>
            )}
          </View>

          {/* Display Name Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Display Name</Text>
            <TextInput
              style={[styles.input, formErrors.displayName && styles.inputError]}
              value={formData.displayName}
              onChangeText={(value) => handleInputChange('displayName', value)}
              placeholder="Your display name"
              autoCorrect={false}
              editable={!isLoading}
            />
            {formErrors.displayName && (
              <Text style={styles.errorText}>{formErrors.displayName}</Text>
            )}
          </View>

          {/* Password Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Password</Text>
            <TextInput
              style={[styles.input, formErrors.password && styles.inputError]}
              value={formData.password}
              onChangeText={(value) => handleInputChange('password', value)}
              placeholder="Create a password"
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
              editable={!isLoading}
            />
            {formErrors.password && (
              <Text style={styles.errorText}>{formErrors.password}</Text>
            )}
          </View>

          {/* Confirm Password Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Confirm Password</Text>
            <TextInput
              style={[styles.input, formErrors.confirmPassword && styles.inputError]}
              value={formData.confirmPassword}
              onChangeText={(value) => handleInputChange('confirmPassword', value)}
              placeholder="Confirm your password"
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
              editable={!isLoading}
            />
            {formErrors.confirmPassword && (
              <Text style={styles.errorText}>{formErrors.confirmPassword}</Text>
            )}
          </View>

          {/* Register Button */}
          <TouchableOpacity
            style={[styles.registerButton, isLoading && styles.buttonDisabled]}
            onPress={handleRegister}
            disabled={isLoading}
          >
            {isLoading ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.registerButtonText}>Create Account</Text>
            )}
          </TouchableOpacity>

          {/* Login Link */}
          <View style={styles.loginSection}>
            <Text style={styles.loginText}>Already have an account? </Text>
            <TouchableOpacity 
              onPress={navigateToLogin}
              disabled={isLoading}
            >
              <Text style={styles.loginLink}>Sign In</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF'
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24
  },
  header: {
    alignItems: 'center',
    marginBottom: 40
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1A1A1A',
    marginBottom: 8
  },
  subtitle: {
    fontSize: 16,
    color: '#666666',
    textAlign: 'center'
  },
  form: {
    width: '100%'
  },
  errorContainer: {
    backgroundColor: '#FFF5F5',
    borderColor: '#FF4444',
    borderWidth: 1,
    borderRadius: 8,
    padding: 12,
    marginBottom: 20
  },
  inputGroup: {
    marginBottom: 20
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1A1A1A',
    marginBottom: 8
  },
  inputContainer: {
    position: 'relative'
  },
  input: {
    borderWidth: 1,
    borderColor: '#E0E0E0',
    borderRadius: 12,
    padding: 16,
    fontSize: 16,
    backgroundColor: '#FAFAFA'
  },
  inputWithIcon: {
    paddingRight: 50
  },
  inputError: {
    borderColor: '#FF4444'
  },
  inputSuccess: {
    borderColor: '#4CAF50'
  },
  inputIcon: {
    position: 'absolute',
    right: 16,
    top: 16,
    width: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center'
  },
  successIcon: {
    color: '#4CAF50',
    fontSize: 18,
    fontWeight: 'bold'
  },
  errorIcon: {
    color: '#FF4444',
    fontSize: 18,
    fontWeight: 'bold'
  },
  errorText: {
    color: '#FF4444',
    fontSize: 14,
    marginTop: 4
  },
  successText: {
    color: '#4CAF50',
    fontSize: 14,
    marginTop: 4
  },
  registerButton: {
    backgroundColor: '#007AFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 24,
    marginTop: 8
  },
  buttonDisabled: {
    opacity: 0.6
  },
  registerButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600'
  },
  loginSection: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center'
  },
  loginText: {
    color: '#666666',
    fontSize: 14
  },
  loginLink: {
    color: '#007AFF',
    fontSize: 14,
    fontWeight: '500'
  },
  successContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24
  },
  successTitle: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1A1A1A',
    marginBottom: 16,
    textAlign: 'center'
  },
  successMessage: {
    fontSize: 18,
    color: '#333333',
    textAlign: 'center',
    marginBottom: 16
  },
  successSubtext: {
    fontSize: 16,
    color: '#666666',
    textAlign: 'center',
    lineHeight: 24,
    marginBottom: 40
  },
  backButton: {
    backgroundColor: '#007AFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    minWidth: 200
  },
  backButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600'
  }
});
