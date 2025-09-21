// Forgot password screen for Funlynk
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

export default function ForgotPasswordScreen({ navigation }: any) {
  const { resetPassword } = useAuth();
  const [email, setEmail] = useState('');
  const [emailError, setEmailError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [emailSent, setEmailSent] = useState(false);

  /**
   * Handle email input change
   */
  const handleEmailChange = (value: string) => {
    setEmail(value);
    
    // Clear error when user starts typing
    if (emailError) {
      setEmailError('');
    }
  };

  /**
   * Validate email
   */
  const validateEmail = () => {
    const emailValidation = AuthService.validateEmail(email);
    if (!emailValidation.isValid) {
      setEmailError(emailValidation.message || 'Invalid email');
      return false;
    }
    return true;
  };

  /**
   * Handle password reset submission
   */
  const handleResetPassword = async () => {
    if (!validateEmail()) {
      return;
    }

    setIsSubmitting(true);

    try {
      const result = await resetPassword(email.trim().toLowerCase());

      if (result.error) {
        Alert.alert(
          'Reset Failed',
          result.error.message,
          [{ text: 'OK' }]
        );
      } else {
        setEmailSent(true);
        Alert.alert(
          'Reset Email Sent',
          'Please check your email for password reset instructions.',
          [{ text: 'OK' }]
        );
      }
    } catch (error) {
      Alert.alert(
        'Reset Error',
        'An unexpected error occurred. Please try again.',
        [{ text: 'OK' }]
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * Navigate back to login
   */
  const navigateToLogin = () => {
    navigation.navigate('Login');
  };

  if (emailSent) {
    return (
      <View style={styles.container}>
        <View style={styles.successContainer}>
          <Text style={styles.successTitle}>Email Sent!</Text>
          <Text style={styles.successMessage}>
            We've sent password reset instructions to {email}
          </Text>
          <Text style={styles.successSubtext}>
            Check your email and follow the link to reset your password.
          </Text>
          
          <TouchableOpacity
            style={styles.backButton}
            onPress={navigateToLogin}
          >
            <Text style={styles.backButtonText}>Back to Login</Text>
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
          <Text style={styles.title}>Reset Password</Text>
          <Text style={styles.subtitle}>
            Enter your email address and we'll send you instructions to reset your password.
          </Text>
        </View>

        <View style={styles.form}>
          {/* Email Input */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Email</Text>
            <TextInput
              style={[styles.input, emailError && styles.inputError]}
              value={email}
              onChangeText={handleEmailChange}
              placeholder="Enter your email"
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              editable={!isSubmitting}
            />
            {emailError && (
              <Text style={styles.errorText}>{emailError}</Text>
            )}
          </View>

          {/* Reset Button */}
          <TouchableOpacity
            style={[styles.resetButton, isSubmitting && styles.buttonDisabled]}
            onPress={handleResetPassword}
            disabled={isSubmitting}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.resetButtonText}>Send Reset Email</Text>
            )}
          </TouchableOpacity>

          {/* Back to Login Link */}
          <TouchableOpacity 
            style={styles.backLink}
            onPress={navigateToLogin}
            disabled={isSubmitting}
          >
            <Text style={styles.backLinkText}>Back to Login</Text>
          </TouchableOpacity>
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
    marginBottom: 16
  },
  subtitle: {
    fontSize: 16,
    color: '#666666',
    textAlign: 'center',
    lineHeight: 24
  },
  form: {
    width: '100%'
  },
  inputGroup: {
    marginBottom: 24
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
  resetButton: {
    backgroundColor: '#007AFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 24
  },
  buttonDisabled: {
    opacity: 0.6
  },
  resetButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600'
  },
  backLink: {
    alignItems: 'center'
  },
  backLinkText: {
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
    marginBottom: 16
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
