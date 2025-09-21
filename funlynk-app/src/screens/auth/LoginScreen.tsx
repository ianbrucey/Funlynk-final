// Login screen for Funlynk
import React, { useState } from 'react';
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

export default function LoginScreen({ navigation }: any) {
  const { signIn, loading } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [formErrors, setFormErrors] = useState<{[key: string]: string}>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [loginError, setLoginError] = useState<string>('');

  /**
   * Handle form input changes
   */
  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));

    // Clear errors when user starts typing
    if (formErrors[field]) {
      setFormErrors(prev => ({ ...prev, [field]: '' }));
    }
    if (loginError) {
      setLoginError('');
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
    if (!formData.password) {
      errors.password = 'Password is required';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  /**
   * Handle login submission
   */
  const handleLogin = async () => {
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);

    try {
      const result = await signIn({
        email: formData.email.trim().toLowerCase(),
        password: formData.password
      });

      if (result.error) {
        setLoginError(result.error.message);
      } else {
        // Navigation will be handled by auth state change
        console.log('Login successful');
      }
    } catch (error) {
      setLoginError('An unexpected error occurred. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * Navigate to registration
   */
  const navigateToRegister = () => {
    navigation.navigate('Register');
  };

  /**
   * Navigate to forgot password
   */
  const navigateToForgotPassword = () => {
    navigation.navigate('ForgotPassword');
  };

  const isLoading = loading || isSubmitting;

  return (
    <KeyboardAvoidingView 
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.title}>Welcome to Funlynk</Text>
          <Text style={styles.subtitle}>Sign in to discover activities</Text>
        </View>

        <View style={styles.form}>
          {/* Login Error Display */}
          {loginError && (
            <View style={styles.errorContainer}>
              <Text style={styles.errorText}>{loginError}</Text>
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

          {/* Password Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Password</Text>
            <TextInput
              style={[styles.input, formErrors.password && styles.inputError]}
              value={formData.password}
              onChangeText={(value) => handleInputChange('password', value)}
              placeholder="Enter your password"
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
              editable={!isLoading}
            />
            {formErrors.password && (
              <Text style={styles.errorText}>{formErrors.password}</Text>
            )}
          </View>

          {/* Forgot Password Link */}
          <TouchableOpacity 
            style={styles.forgotPasswordLink}
            onPress={navigateToForgotPassword}
            disabled={isLoading}
          >
            <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
          </TouchableOpacity>

          {/* Login Button */}
          <TouchableOpacity
            style={[styles.loginButton, isLoading && styles.buttonDisabled]}
            onPress={handleLogin}
            disabled={isLoading}
          >
            {isLoading ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.loginButtonText}>Sign In</Text>
            )}
          </TouchableOpacity>

          {/* Register Link */}
          <View style={styles.registerSection}>
            <Text style={styles.registerText}>Don't have an account? </Text>
            <TouchableOpacity 
              onPress={navigateToRegister}
              disabled={isLoading}
            >
              <Text style={styles.registerLink}>Sign Up</Text>
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
  input: {
    borderWidth: 1,
    borderColor: '#E0E0E0',
    borderRadius: 12,
    padding: 16,
    fontSize: 16,
    backgroundColor: '#FAFAFA'
  },
  inputError: {
    borderColor: '#FF4444'
  },
  errorText: {
    color: '#FF4444',
    fontSize: 14,
    marginTop: 4
  },
  forgotPasswordLink: {
    alignSelf: 'flex-end',
    marginBottom: 24
  },
  forgotPasswordText: {
    color: '#007AFF',
    fontSize: 14,
    fontWeight: '500'
  },
  loginButton: {
    backgroundColor: '#007AFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 24
  },
  buttonDisabled: {
    opacity: 0.6
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600'
  },
  registerSection: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center'
  },
  registerText: {
    color: '#666666',
    fontSize: 14
  },
  registerLink: {
    color: '#007AFF',
    fontSize: 14,
    fontWeight: '500'
  }
});
