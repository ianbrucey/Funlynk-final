// Home screen for authenticated users
import React from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  SafeAreaView,
  ScrollView
} from 'react-native';
import { useAuth } from '../contexts/AuthContext';

export default function HomeScreen() {
  const { user, signOut } = useAuth();

  /**
   * Handle sign out
   */
  const handleSignOut = async () => {
    Alert.alert(
      'Sign Out',
      'Are you sure you want to sign out?',
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Sign Out', 
          style: 'destructive',
          onPress: async () => {
            const result = await signOut();
            if (result.error) {
              Alert.alert('Error', result.error.message);
            }
          }
        }
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.content}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.title}>Welcome to Funlynk!</Text>
          <Text style={styles.subtitle}>
            Hello {user?.display_name || user?.username || 'User'}
          </Text>
        </View>

        {/* User Info Card */}
        <View style={styles.userCard}>
          <Text style={styles.cardTitle}>Your Profile</Text>
          <View style={styles.userInfo}>
            <Text style={styles.infoLabel}>Email:</Text>
            <Text style={styles.infoValue}>{user?.email}</Text>
          </View>
          <View style={styles.userInfo}>
            <Text style={styles.infoLabel}>Username:</Text>
            <Text style={styles.infoValue}>{user?.username || 'Not set'}</Text>
          </View>
          <View style={styles.userInfo}>
            <Text style={styles.infoLabel}>Display Name:</Text>
            <Text style={styles.infoValue}>{user?.display_name || 'Not set'}</Text>
          </View>
          <View style={styles.userInfo}>
            <Text style={styles.infoLabel}>Email Verified:</Text>
            <Text style={[
              styles.infoValue, 
              user?.email_verified ? styles.verified : styles.unverified
            ]}>
              {user?.email_verified ? 'Yes' : 'No'}
            </Text>
          </View>
        </View>

        {/* Status Card */}
        <View style={styles.statusCard}>
          <Text style={styles.cardTitle}>Authentication Status</Text>
          <Text style={styles.statusText}>✅ Successfully authenticated</Text>
          <Text style={styles.statusText}>✅ Database connection working</Text>
          <Text style={styles.statusText}>✅ User profile loaded</Text>
          <Text style={styles.statusText}>✅ Session management active</Text>
        </View>

        {/* Coming Soon Card */}
        <View style={styles.comingSoonCard}>
          <Text style={styles.cardTitle}>Coming Soon</Text>
          <Text style={styles.featureText}>🎯 Activity Discovery</Text>
          <Text style={styles.featureText}>👥 Profile Management</Text>
          <Text style={styles.featureText}>📍 Location Services</Text>
          <Text style={styles.featureText}>💬 Social Features</Text>
          <Text style={styles.featureText}>💳 Payment Integration</Text>
        </View>

        {/* Sign Out Button */}
        <TouchableOpacity
          style={styles.signOutButton}
          onPress={handleSignOut}
        >
          <Text style={styles.signOutButtonText}>Sign Out</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5'
  },
  content: {
    padding: 20
  },
  header: {
    alignItems: 'center',
    marginBottom: 30
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1A1A1A',
    marginBottom: 8
  },
  subtitle: {
    fontSize: 18,
    color: '#666666'
  },
  userCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3
  },
  statusCard: {
    backgroundColor: '#E8F5E8',
    borderRadius: 12,
    padding: 20,
    marginBottom: 20
  },
  comingSoonCard: {
    backgroundColor: '#FFF8E1',
    borderRadius: 12,
    padding: 20,
    marginBottom: 30
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1A1A1A',
    marginBottom: 15
  },
  userInfo: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10
  },
  infoLabel: {
    fontSize: 16,
    color: '#666666',
    fontWeight: '500'
  },
  infoValue: {
    fontSize: 16,
    color: '#1A1A1A',
    flex: 1,
    textAlign: 'right'
  },
  verified: {
    color: '#4CAF50'
  },
  unverified: {
    color: '#FF9800'
  },
  statusText: {
    fontSize: 16,
    color: '#2E7D32',
    marginBottom: 8
  },
  featureText: {
    fontSize: 16,
    color: '#F57C00',
    marginBottom: 8
  },
  signOutButton: {
    backgroundColor: '#FF4444',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center'
  },
  signOutButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600'
  }
});
