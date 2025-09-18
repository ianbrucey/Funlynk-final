# E01 Core Infrastructure - Service Architecture

## Architecture Overview

The Core Infrastructure epic provides four foundational services that all other epics depend on. These services are designed to be modular, scalable, and maintainable while leveraging Supabase's Backend-as-a-Service capabilities.

## Service Design Principles

### 1. Single Responsibility
Each service has a clear, focused responsibility and well-defined boundaries.

### 2. Loose Coupling
Services communicate through well-defined interfaces and avoid direct dependencies.

### 3. High Cohesion
Related functionality is grouped together within each service.

### 4. Stateless Design
Services are designed to be stateless where possible, with state managed in the database.

## Core Infrastructure Services

### 1.1 Database Schema & Models Service

**Purpose**: Provides the foundational data layer for the entire application

**Responsibilities**:
- Database schema definition and management
- Data model validation and constraints
- Migration management
- Database performance optimization

**Implementation Approach**:
- **Technology**: PostgreSQL with PostGIS via Supabase
- **Schema Management**: Supabase migrations and version control
- **Access Pattern**: Direct database access via Supabase client
- **Performance**: Optimized indexes and query patterns

**Service Boundaries**:
- **Provides**: Database schema, data models, migration scripts
- **Consumes**: Nothing (foundational service)
- **Exposes**: Database access via Supabase client libraries

### 1.2 Authentication Service

**Purpose**: Manages user identity, security, and session management

**Responsibilities**:
- User registration and email verification
- Password authentication and security
- Social login integration (Google, Apple)
- JWT token generation and validation
- Session management and refresh
- Password reset workflows

**Implementation Approach**:
- **Technology**: Supabase Auth with custom policies
- **Social Logins**: Google OAuth, Apple Sign-In
- **Token Management**: JWT with automatic refresh
- **Security**: Row Level Security (RLS) policies

**Service Interface**:
```typescript
interface AuthService {
  // Registration & Login
  signUp(email: string, password: string, metadata?: UserMetadata): Promise<AuthResponse>
  signIn(email: string, password: string): Promise<AuthResponse>
  signInWithOAuth(provider: 'google' | 'apple'): Promise<AuthResponse>
  signOut(): Promise<void>
  
  // Session Management
  getSession(): Promise<Session | null>
  refreshSession(): Promise<AuthResponse>
  
  // Password Management
  resetPassword(email: string): Promise<void>
  updatePassword(newPassword: string): Promise<void>
  
  // User Management
  getCurrentUser(): Promise<User | null>
  updateUserMetadata(metadata: UserMetadata): Promise<User>
}
```

**Security Considerations**:
- Password hashing handled by Supabase Auth
- JWT tokens signed with secure keys
- Rate limiting on authentication endpoints
- Email verification required for new accounts

### 1.3 Geolocation Service

**Purpose**: Handles all location-based functionality and spatial queries

**Responsibilities**:
- Coordinate validation and processing
- Distance calculations between points
- Spatial queries (find activities within radius)
- Location-based search optimization
- Privacy and permission handling

**Implementation Approach**:
- **Technology**: PostGIS extension for PostgreSQL
- **Coordinate System**: WGS84 (EPSG:4326)
- **Distance Calculations**: Great circle distance for accuracy
- **Indexing**: GIST indexes for spatial performance

**Service Interface**:
```typescript
interface GeolocationService {
  // Distance Calculations
  calculateDistance(point1: Coordinates, point2: Coordinates): Promise<number>
  
  // Spatial Queries
  findActivitiesNearby(
    center: Coordinates, 
    radiusKm: number, 
    filters?: ActivityFilters
  ): Promise<Activity[]>
  
  findUsersNearby(
    center: Coordinates, 
    radiusKm: number, 
    limit?: number
  ): Promise<User[]>
  
  // Location Utilities
  validateCoordinates(lat: number, lng: number): boolean
  geocodeAddress(address: string): Promise<Coordinates>
  reverseGeocode(coordinates: Coordinates): Promise<string>
}
```

**Performance Optimizations**:
- Spatial indexes on location columns
- Query optimization for common radius searches
- Caching for geocoding results
- Efficient bounding box queries

### 1.4 Notification Service

**Purpose**: Centralized communication hub for all platform notifications

**Responsibilities**:
- Push notification delivery (iOS/Android)
- Email notification sending
- In-app notification management
- Notification preference handling
- Delivery tracking and retry logic

**Implementation Approach**:
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **Email**: Supabase built-in email or external provider
- **Templates**: Structured notification templates
- **Queuing**: Background job processing for delivery

**Service Interface**:
```typescript
interface NotificationService {
  // Push Notifications
  sendPushNotification(
    userId: string, 
    notification: PushNotificationData
  ): Promise<NotificationResult>
  
  // Email Notifications
  sendEmail(
    userId: string, 
    template: EmailTemplate, 
    data: EmailData
  ): Promise<NotificationResult>
  
  // In-App Notifications
  createInAppNotification(
    userId: string, 
    notification: InAppNotificationData
  ): Promise<Notification>
  
  // Notification Management
  markAsRead(notificationId: string): Promise<void>
  getUserNotifications(userId: string, limit?: number): Promise<Notification[]>
  updateNotificationPreferences(
    userId: string, 
    preferences: NotificationPreferences
  ): Promise<void>
}
```

**Notification Types**:
- **Social**: New follower, activity RSVP
- **Activity**: Activity updates, reminders, cancellations
- **Payment**: Payment confirmations, refunds
- **System**: Welcome messages, security alerts
- **Moderation**: Content reports, account warnings

## Service Communication Patterns

### Internal Communication
Services communicate through:
1. **Direct Database Access**: Shared database schema
2. **Function Calls**: Direct service method invocation
3. **Event Triggers**: Database triggers for automatic actions

### External Communication
Services expose interfaces through:
1. **Supabase Client**: Direct database and auth access
2. **API Endpoints**: RESTful APIs for complex operations
3. **Real-time Subscriptions**: Supabase real-time for live updates

## Scalability Considerations

### Database Scaling
- **Read Replicas**: For read-heavy workloads
- **Connection Pooling**: Efficient connection management
- **Query Optimization**: Proper indexing and query patterns

### Service Scaling
- **Stateless Design**: Services can be horizontally scaled
- **Caching**: Redis for frequently accessed data
- **Background Jobs**: Async processing for heavy operations

### Performance Monitoring
- **Database Metrics**: Query performance and connection usage
- **Service Metrics**: Response times and error rates
- **User Metrics**: Authentication success rates and notification delivery

## Error Handling Strategy

### Database Errors
- **Connection Failures**: Retry logic with exponential backoff
- **Constraint Violations**: Graceful error messages
- **Transaction Failures**: Proper rollback and cleanup

### Service Errors
- **Authentication Failures**: Clear error messages and retry guidance
- **Geolocation Errors**: Fallback to less precise location data
- **Notification Failures**: Retry logic and alternative delivery methods

### Monitoring and Alerting
- **Error Tracking**: Comprehensive error logging
- **Performance Alerts**: Threshold-based alerting
- **Health Checks**: Service availability monitoring

## Security Architecture

### Authentication Security
- **Password Security**: Supabase Auth handles hashing and validation
- **Token Security**: JWT tokens with proper expiration
- **Session Security**: Secure session management

### Database Security
- **Row Level Security**: User-based data access control
- **API Security**: Authenticated API access only
- **Data Encryption**: Encryption at rest and in transit

### Privacy Controls
- **Location Privacy**: User-controlled location sharing
- **Data Minimization**: Collect only necessary data
- **User Consent**: Clear consent for data usage

---

**Service Architecture Status**: ✅ Complete
**Next Steps**: Define API contracts and integration points
