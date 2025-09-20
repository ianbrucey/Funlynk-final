import { StatusBar } from 'expo-status-bar';
import { StyleSheet, Text, View, Button, Alert, ScrollView } from 'react-native';
import { useEffect, useState } from 'react';
import { supabase, checkDatabaseHealth, setupDatabaseSchema } from './src/lib/supabase';

export default function App() {
  const [dbStatus, setDbStatus] = useState('checking');
  const [schemaStatus, setSchemaStatus] = useState('unknown');

  useEffect(() => {
    testConnection();
    checkSchema();
  }, []);

  const testConnection = async () => {
    try {
      const health = await checkDatabaseHealth();
      setDbStatus(health.status);
      console.log('Database health:', health);
    } catch (error) {
      console.error('Database connection error:', error);
      setDbStatus('error');
    }
  };

  const checkSchema = async () => {
    try {
      const result = await setupDatabaseSchema();
      setSchemaStatus(result.success ? 'ready' : 'needs_setup');
      console.log('Schema status:', result);
    } catch (error) {
      console.error('Schema check error:', error);
      setSchemaStatus('error');
    }
  };

  const showSchemaInstructions = () => {
    Alert.alert(
      'Database Schema Setup Required',
      'The database tables need to be created. Please:\n\n1. Go to your Supabase dashboard\n2. Navigate to SQL Editor\n3. Run the schema.sql file from funlynk-app/scripts/schema.sql\n4. Come back and tap "Refresh Schema Status"',
      [{ text: 'OK' }]
    );
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>Funlynk - Social Activity Platform</Text>
      <Text style={styles.subtitle}>Mobile-First Social Network for Activity Discovery</Text>
      
      <View style={styles.statusContainer}>
        <Text style={styles.statusLabel}>Database Connection:</Text>
        <Text style={[styles.status, { color: dbStatus === 'connected' ? 'green' : 'red' }]}>
          {dbStatus}
        </Text>
      </View>
      
      <View style={styles.statusContainer}>
        <Text style={styles.statusLabel}>Schema Status:</Text>
        <Text style={[styles.status, { color: schemaStatus === 'ready' ? 'green' : 'orange' }]}>
          {schemaStatus}
        </Text>
      </View>

      <View style={styles.buttonContainer}>
        <Button title="Refresh Connection" onPress={testConnection} />
        <Button title="Check Schema" onPress={checkSchema} />
        {schemaStatus === 'needs_setup' && (
          <Button title="Setup Instructions" onPress={showSchemaInstructions} />
        )}
      </View>

      {schemaStatus === 'ready' && (
        <Text style={styles.successText}>
          ✅ Database is ready! Ready to start building features.
        </Text>
      )}
      
      <StatusBar style="auto" />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flexGrow: 1,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 30,
    textAlign: 'center',
  },
  statusContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 5,
  },
  statusLabel: {
    fontSize: 16,
    marginRight: 10,
  },
  status: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  buttonContainer: {
    marginTop: 20,
    gap: 10,
    width: '100%',
  },
  successText: {
    marginTop: 20,
    fontSize: 18,
    color: 'green',
    textAlign: 'center',
    fontWeight: 'bold',
  },
});
