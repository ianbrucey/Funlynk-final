import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';
import LoginScreen from './src/screens/auth/LoginScreen';
import RegisterScreen from './src/screens/auth/RegisterScreen';
import ForgotPasswordScreen from './src/screens/auth/ForgotPasswordScreen';
import HomeScreen from './src/screens/HomeScreen';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';

const Stack = createStackNavigator();

// Loading screen component
function LoadingScreen() {
  return (
    <View style={styles.loadingContainer}>
      <ActivityIndicator size="large" color="#007AFF" />
      <Text style={styles.loadingText}>Loading Funlynk...</Text>
    </View>
  );
}

// Auth stack for unauthenticated users
function AuthStack() {
  return (
    <Stack.Navigator
      initialRouteName="Login"
      screenOptions={{
        headerShown: false
      }}
    >
      <Stack.Screen name="Login" component={LoginScreen} />
      <Stack.Screen name="Register" component={RegisterScreen} />
      <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
    </Stack.Navigator>
  );
}

// Main stack for authenticated users
function MainStack() {
  return (
    <Stack.Navigator
      initialRouteName="Home"
      screenOptions={{
        headerShown: false
      }}
    >
      <Stack.Screen name="Home" component={HomeScreen} />
    </Stack.Navigator>
  );
}

// App navigation component
function AppNavigation() {
  const { user, loading, initialized } = useAuth();

  // Show loading screen while initializing
  if (!initialized || loading) {
    return <LoadingScreen />;
  }

  // Show auth stack if user is not authenticated
  if (!user) {
    return <AuthStack />;
  }

  // Show main app if user is authenticated
  return <MainStack />;
}

// Main app component
export default function App() {
  return (
    <AuthProvider>
      <NavigationContainer>
        <AppNavigation />
        <StatusBar style="auto" />
      </NavigationContainer>
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFFFFF'
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666666'
  }
});
