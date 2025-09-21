# T01: Core Profile Data Structure - Problem Definition

## Problem Statement

We need to implement a comprehensive core profile data structure that serves as the foundation for user identity on the Funlynk platform. This includes designing the profile data model, implementing CRUD operations, establishing data validation and sanitization, and creating profile completion tracking to encourage users to build rich, complete profiles.

## Context

### Current State
- Database foundation with user authentication is established (E01.F01, E01.F02 completed)
- User accounts exist but have minimal profile information
- No structured profile data model beyond basic authentication fields
- No profile completion tracking or user guidance
- No comprehensive profile management interface

### Desired State
- Comprehensive user profile data model supporting rich user information
- Robust CRUD operations for profile management
- Data validation ensuring profile data quality and security
- Profile completion tracking with user guidance and incentives
- Scalable profile data architecture supporting future feature expansion

## Business Impact

### Why This Matters
- **User Identity**: Rich profiles establish credible user identity on the platform
- **Trust Building**: Complete profiles increase trust between users and activity participation
- **Social Connection**: Detailed profiles enable meaningful connections and community building
- **Personalization**: Profile data drives personalized recommendations and experiences
- **Platform Engagement**: Investment in profile creation increases user retention and engagement
- **Quality Control**: Proper validation ensures high-quality user data across the platform

### Success Metrics
- Profile completion rate >80% within first week of user registration
- Average profile completeness score >75% for active users
- Profile update frequency >2 updates per month per active user
- Data validation error rate <1% of profile operations
- Profile load performance <500ms for complete profiles
- User satisfaction with profile creation process >4.3/5

## Technical Requirements

### Functional Requirements
- **Comprehensive Data Model**: Support all essential profile information types
- **CRUD Operations**: Create, read, update, and delete profile data efficiently
- **Data Validation**: Ensure data quality, format compliance, and security
- **Profile Completion**: Track and encourage profile completion with scoring
- **Data Relationships**: Handle connections to other platform entities
- **Audit Trail**: Track profile changes for security and compliance
- **Bulk Operations**: Support efficient bulk profile operations

### Non-Functional Requirements
- **Performance**: Profile operations complete within 500ms
- **Scalability**: Support 100k+ user profiles with efficient queries
- **Data Integrity**: Maintain consistent and accurate profile data
- **Security**: Protect sensitive profile information with proper access controls
- **Compliance**: Meet data protection regulations (GDPR, CCPA)
- **Extensibility**: Easy addition of new profile fields and features

## Core Profile Data Model

### Primary Profile Structure
```typescript
interface UserProfile {
  // Primary identifiers
  id: string;
  userId: string; // Foreign key to auth.users
  
  // Basic personal information
  displayName: string;
  firstName: string;
  lastName: string;
  bio: string;
  tagline: string;
  dateOfBirth?: Date;
  gender?: 'male' | 'female' | 'non-binary' | 'prefer-not-to-say' | 'other';
  
  // Contact information
  email: string; // Synced from auth
  phone?: string;
  website?: string;
  socialLinks: SocialLink[];
  
  // Location information
  location: UserLocation;
  hometown?: string;
  timezone: string;
  
  // Interests and preferences
  interests: Interest[];
  activityPreferences: ActivityPreference[];
  skillsAndExpertise: Skill[];
  languages: Language[];
  
  // Profile media
  profileImageUrl?: string;
  coverImageUrl?: string;
  
  // Social metrics (computed fields)
  followerCount: number;
  followingCount: number;
  activityCount: number;
  reviewCount: number;
  averageRating: number;
  
  // Profile status and verification
  verified: boolean;
  verificationLevel: VerificationLevel;
  badges: ProfileBadge[];
  accountStatus: AccountStatus;
  
  // Profile completion and engagement
  profileCompleteness: number; // 0-100
  lastProfileUpdate: Date;
  lastActive: Date;
  joinedDate: Date;
  
  // Privacy and settings
  privacyLevel: 'public' | 'friends' | 'private';
  searchable: boolean;
  showOnlineStatus: boolean;
  
  // Metadata
  createdAt: Date;
  updatedAt: Date;
  version: number; // For optimistic concurrency control
}

interface SocialLink {
  id: string;
  platform: SocialPlatform;
  url: string;
  username?: string;
  verified: boolean;
  displayOrder: number;
}

enum SocialPlatform {
  INSTAGRAM = 'instagram',
  TWITTER = 'twitter',
  FACEBOOK = 'facebook',
  LINKEDIN = 'linkedin',
  TIKTOK = 'tiktok',
  YOUTUBE = 'youtube',
  CUSTOM = 'custom'
}

interface UserLocation {
  address?: string;
  city: string;
  state?: string;
  country: string;
  postalCode?: string;
  coordinates?: {
    latitude: number;
    longitude: number;
  };
  displayPublicly: boolean;
  precision: 'exact' | 'city' | 'region' | 'country';
}

interface Interest {
  id: string;
  category: string;
  name: string;
  level: 'beginner' | 'intermediate' | 'advanced' | 'expert';
  addedAt: Date;
}

interface ActivityPreference {
  category: string;
  subcategories: string[];
  preferredTimes: TimePreference[];
  preferredLocations: LocationPreference[];
  groupSizePreference: 'small' | 'medium' | 'large' | 'any';
  difficultyPreference: 'easy' | 'moderate' | 'challenging' | 'any';
}

interface Skill {
  id: string;
  name: string;
  category: string;
  level: 'beginner' | 'intermediate' | 'advanced' | 'expert';
  yearsOfExperience?: number;
  certifications: string[];
  endorsements: number;
}

enum VerificationLevel {
  UNVERIFIED = 'unverified',
  EMAIL_VERIFIED = 'email_verified',
  PHONE_VERIFIED = 'phone_verified',
  ID_VERIFIED = 'id_verified',
  BACKGROUND_CHECKED = 'background_checked'
}

interface ProfileBadge {
  id: string;
  type: BadgeType;
  name: string;
  description: string;
  iconUrl: string;
  earnedAt: Date;
  displayOrder: number;
}

enum BadgeType {
  ACHIEVEMENT = 'achievement',
  VERIFICATION = 'verification',
  COMMUNITY = 'community',
  ACTIVITY = 'activity',
  SPECIAL = 'special'
}
```

### Profile Completion Scoring
```typescript
interface ProfileCompletenessConfig {
  fields: ProfileFieldWeight[];
  bonusPoints: BonusPoint[];
  minimumForComplete: number; // e.g., 80
}

interface ProfileFieldWeight {
  fieldName: string;
  weight: number; // Points awarded for completing this field
  required: boolean;
  validationRules: ValidationRule[];
}

interface BonusPoint {
  condition: string; // e.g., "has_profile_image_and_cover"
  points: number;
  description: string;
}

const profileCompletenessConfig: ProfileCompletenessConfig = {
  fields: [
    { fieldName: 'displayName', weight: 10, required: true, validationRules: ['minLength:2', 'maxLength:50'] },
    { fieldName: 'firstName', weight: 5, required: true, validationRules: ['minLength:1', 'maxLength:30'] },
    { fieldName: 'lastName', weight: 5, required: true, validationRules: ['minLength:1', 'maxLength:30'] },
    { fieldName: 'bio', weight: 15, required: false, validationRules: ['maxLength:500'] },
    { fieldName: 'profileImageUrl', weight: 15, required: false, validationRules: ['validUrl', 'imageFormat'] },
    { fieldName: 'location.city', weight: 10, required: false, validationRules: ['minLength:2'] },
    { fieldName: 'interests', weight: 20, required: false, validationRules: ['minItems:3', 'maxItems:20'] },
    { fieldName: 'socialLinks', weight: 10, required: false, validationRules: ['maxItems:5'] },
    { fieldName: 'skillsAndExpertise', weight: 10, required: false, validationRules: ['maxItems:10'] }
  ],
  bonusPoints: [
    { condition: 'has_both_profile_and_cover_image', points: 5, description: 'Complete visual profile' },
    { condition: 'verified_email_and_phone', points: 10, description: 'Verified contact information' },
    { condition: 'has_activity_preferences', points: 5, description: 'Activity preferences set' }
  ],
  minimumForComplete: 80
};

class ProfileCompletenessCalculator {
  calculateCompleteness(profile: UserProfile): ProfileCompletenessResult {
    let totalPoints = 0;
    let earnedPoints = 0;
    const missingFields: string[] = [];
    const completedFields: string[] = [];
    
    // Calculate field-based points
    for (const fieldConfig of profileCompletenessConfig.fields) {
      totalPoints += fieldConfig.weight;
      
      const fieldValue = this.getFieldValue(profile, fieldConfig.fieldName);
      const isComplete = this.validateField(fieldValue, fieldConfig.validationRules);
      
      if (isComplete) {
        earnedPoints += fieldConfig.weight;
        completedFields.push(fieldConfig.fieldName);
      } else {
        missingFields.push(fieldConfig.fieldName);
      }
    }
    
    // Calculate bonus points
    for (const bonus of profileCompletenessConfig.bonusPoints) {
      if (this.evaluateBonusCondition(profile, bonus.condition)) {
        earnedPoints += bonus.points;
        totalPoints += bonus.points;
      }
    }
    
    const completenessPercentage = Math.round((earnedPoints / totalPoints) * 100);
    
    return {
      percentage: completenessPercentage,
      earnedPoints,
      totalPoints,
      isComplete: completenessPercentage >= profileCompletenessConfig.minimumForComplete,
      missingFields,
      completedFields,
      nextSteps: this.generateNextSteps(missingFields),
      lastCalculated: new Date()
    };
  }
  
  private generateNextSteps(missingFields: string[]): ProfileCompletionStep[] {
    const stepPriority = {
      'profileImageUrl': { priority: 1, description: 'Add a profile photo to help others recognize you' },
      'bio': { priority: 2, description: 'Write a bio to tell others about yourself' },
      'interests': { priority: 3, description: 'Add interests to find relevant activities' },
      'location.city': { priority: 4, description: 'Add your location to find nearby activities' },
      'socialLinks': { priority: 5, description: 'Connect your social media accounts' }
    };
    
    return missingFields
      .filter(field => stepPriority[field])
      .sort((a, b) => stepPriority[a].priority - stepPriority[b].priority)
      .slice(0, 3) // Top 3 next steps
      .map(field => ({
        field,
        description: stepPriority[field].description,
        priority: stepPriority[field].priority,
        estimatedPoints: this.getFieldWeight(field)
      }));
  }
}
```

## CRUD Operations Implementation

### Profile Service Architecture
```typescript
interface ProfileService {
  createProfile(userId: string, profileData: CreateProfileRequest): Promise<UserProfile>;
  getProfile(userId: string, viewerId?: string): Promise<UserProfile | null>;
  updateProfile(userId: string, updates: UpdateProfileRequest): Promise<UserProfile>;
  deleteProfile(userId: string): Promise<void>;
  searchProfiles(criteria: ProfileSearchCriteria): Promise<ProfileSearchResult>;
  bulkUpdateProfiles(updates: BulkProfileUpdate[]): Promise<BulkUpdateResult>;
}

interface CreateProfileRequest {
  displayName: string;
  firstName: string;
  lastName: string;
  bio?: string;
  location?: Partial<UserLocation>;
  interests?: string[];
  privacyLevel?: 'public' | 'friends' | 'private';
}

interface UpdateProfileRequest {
  displayName?: string;
  firstName?: string;
  lastName?: string;
  bio?: string;
  tagline?: string;
  location?: Partial<UserLocation>;
  interests?: Interest[];
  socialLinks?: SocialLink[];
  activityPreferences?: ActivityPreference[];
  skillsAndExpertise?: Skill[];
  privacyLevel?: 'public' | 'friends' | 'private';
  profileImageUrl?: string;
  coverImageUrl?: string;
}

class ProfileServiceImpl implements ProfileService {
  constructor(
    private db: Database,
    private validator: ProfileValidator,
    private completenessCalculator: ProfileCompletenessCalculator,
    private auditLogger: AuditLogger,
    private cacheManager: CacheManager
  ) {}
  
  async createProfile(userId: string, profileData: CreateProfileRequest): Promise<UserProfile> {
    // Validate input data
    const validationResult = await this.validator.validateCreateRequest(profileData);
    if (!validationResult.isValid) {
      throw new ValidationError(validationResult.errors);
    }
    
    // Check if profile already exists
    const existingProfile = await this.getProfile(userId);
    if (existingProfile) {
      throw new ConflictError('Profile already exists for this user');
    }
    
    // Create profile with defaults
    const profile: UserProfile = {
      id: generateUUID(),
      userId,
      ...profileData,
      followerCount: 0,
      followingCount: 0,
      activityCount: 0,
      reviewCount: 0,
      averageRating: 0,
      verified: false,
      verificationLevel: VerificationLevel.UNVERIFIED,
      badges: [],
      accountStatus: AccountStatus.ACTIVE,
      profileCompleteness: 0,
      lastProfileUpdate: new Date(),
      lastActive: new Date(),
      joinedDate: new Date(),
      searchable: true,
      showOnlineStatus: true,
      createdAt: new Date(),
      updatedAt: new Date(),
      version: 1
    };
    
    // Calculate initial completeness
    const completeness = this.completenessCalculator.calculateCompleteness(profile);
    profile.profileCompleteness = completeness.percentage;
    
    // Save to database
    const savedProfile = await this.db.profiles.create(profile);
    
    // Log audit event
    await this.auditLogger.logProfileEvent({
      userId,
      action: 'profile_created',
      profileId: profile.id,
      timestamp: new Date()
    });
    
    // Cache the profile
    await this.cacheManager.setProfile(userId, savedProfile);
    
    return savedProfile;
  }
  
  async getProfile(userId: string, viewerId?: string): Promise<UserProfile | null> {
    // Check cache first
    const cached = await this.cacheManager.getProfile(userId);
    if (cached) {
      return this.applyPrivacyFilters(cached, viewerId);
    }
    
    // Fetch from database
    const profile = await this.db.profiles.findByUserId(userId);
    if (!profile) {
      return null;
    }
    
    // Cache the result
    await this.cacheManager.setProfile(userId, profile);
    
    // Apply privacy filters based on viewer
    return this.applyPrivacyFilters(profile, viewerId);
  }
  
  async updateProfile(userId: string, updates: UpdateProfileRequest): Promise<UserProfile> {
    // Get current profile
    const currentProfile = await this.getProfile(userId);
    if (!currentProfile) {
      throw new NotFoundError('Profile not found');
    }
    
    // Validate updates
    const validationResult = await this.validator.validateUpdateRequest(updates, currentProfile);
    if (!validationResult.isValid) {
      throw new ValidationError(validationResult.errors);
    }
    
    // Apply updates with optimistic concurrency control
    const updatedProfile = {
      ...currentProfile,
      ...updates,
      lastProfileUpdate: new Date(),
      updatedAt: new Date(),
      version: currentProfile.version + 1
    };
    
    // Recalculate completeness
    const completeness = this.completenessCalculator.calculateCompleteness(updatedProfile);
    updatedProfile.profileCompleteness = completeness.percentage;
    
    // Save to database
    const savedProfile = await this.db.profiles.update(userId, updatedProfile, currentProfile.version);
    
    // Log audit event
    await this.auditLogger.logProfileEvent({
      userId,
      action: 'profile_updated',
      profileId: savedProfile.id,
      changes: this.calculateChanges(currentProfile, savedProfile),
      timestamp: new Date()
    });
    
    // Update cache
    await this.cacheManager.setProfile(userId, savedProfile);
    
    // Invalidate related caches
    await this.cacheManager.invalidateProfileRelatedCaches(userId);
    
    return savedProfile;
  }
  
  private applyPrivacyFilters(profile: UserProfile, viewerId?: string): UserProfile {
    if (!viewerId || viewerId === profile.userId) {
      return profile; // Owner sees everything
    }
    
    // Apply privacy filters based on profile privacy level and relationship
    const filteredProfile = { ...profile };
    
    if (profile.privacyLevel === 'private') {
      // Very limited information for private profiles
      return {
        ...filteredProfile,
        email: '',
        phone: '',
        location: { ...profile.location, address: '', coordinates: undefined },
        socialLinks: [],
        dateOfBirth: undefined
      };
    }
    
    if (profile.privacyLevel === 'friends') {
      // Check if viewer is a friend/follower
      const isFriend = this.checkFriendshipStatus(profile.userId, viewerId);
      if (!isFriend) {
        return this.applyPrivateFilters(filteredProfile);
      }
    }
    
    // Public or friend viewing - show most information but hide sensitive data
    return {
      ...filteredProfile,
      email: '',
      phone: ''
    };
  }
}
```

## Data Validation and Sanitization

### Comprehensive Validation System
```typescript
interface ValidationRule {
  type: ValidationType;
  parameters?: Record<string, any>;
  message?: string;
}

enum ValidationType {
  REQUIRED = 'required',
  MIN_LENGTH = 'minLength',
  MAX_LENGTH = 'maxLength',
  PATTERN = 'pattern',
  EMAIL = 'email',
  URL = 'url',
  PHONE = 'phone',
  DATE = 'date',
  ENUM = 'enum',
  CUSTOM = 'custom'
}

class ProfileValidator {
  private validationRules: Map<string, ValidationRule[]> = new Map();
  
  constructor() {
    this.initializeValidationRules();
  }
  
  private initializeValidationRules(): void {
    this.validationRules.set('displayName', [
      { type: ValidationType.REQUIRED, message: 'Display name is required' },
      { type: ValidationType.MIN_LENGTH, parameters: { length: 2 }, message: 'Display name must be at least 2 characters' },
      { type: ValidationType.MAX_LENGTH, parameters: { length: 50 }, message: 'Display name must be less than 50 characters' },
      { type: ValidationType.PATTERN, parameters: { pattern: /^[a-zA-Z0-9\s\-_.]+$/ }, message: 'Display name contains invalid characters' }
    ]);
    
    this.validationRules.set('bio', [
      { type: ValidationType.MAX_LENGTH, parameters: { length: 500 }, message: 'Bio must be less than 500 characters' },
      { type: ValidationType.CUSTOM, parameters: { validator: 'profanityFilter' }, message: 'Bio contains inappropriate content' }
    ]);
    
    this.validationRules.set('email', [
      { type: ValidationType.REQUIRED, message: 'Email is required' },
      { type: ValidationType.EMAIL, message: 'Invalid email format' }
    ]);
    
    this.validationRules.set('website', [
      { type: ValidationType.URL, message: 'Invalid website URL' }
    ]);
    
    this.validationRules.set('phone', [
      { type: ValidationType.PHONE, message: 'Invalid phone number format' }
    ]);
  }
  
  async validateCreateRequest(data: CreateProfileRequest): Promise<ValidationResult> {
    const errors: ValidationError[] = [];
    
    // Validate each field
    for (const [field, value] of Object.entries(data)) {
      const fieldErrors = await this.validateField(field, value);
      errors.push(...fieldErrors);
    }
    
    // Cross-field validation
    const crossFieldErrors = await this.validateCrossFields(data);
    errors.push(...crossFieldErrors);
    
    return {
      isValid: errors.length === 0,
      errors
    };
  }
  
  private async validateField(fieldName: string, value: any): Promise<ValidationError[]> {
    const rules = this.validationRules.get(fieldName);
    if (!rules) return [];
    
    const errors: ValidationError[] = [];
    
    for (const rule of rules) {
      const isValid = await this.applyValidationRule(value, rule);
      if (!isValid) {
        errors.push({
          field: fieldName,
          message: rule.message || `Validation failed for ${fieldName}`,
          rule: rule.type
        });
      }
    }
    
    return errors;
  }
  
  private async applyValidationRule(value: any, rule: ValidationRule): Promise<boolean> {
    switch (rule.type) {
      case ValidationType.REQUIRED:
        return value !== null && value !== undefined && value !== '';
        
      case ValidationType.MIN_LENGTH:
        return typeof value === 'string' && value.length >= rule.parameters!.length;
        
      case ValidationType.MAX_LENGTH:
        return typeof value === 'string' && value.length <= rule.parameters!.length;
        
      case ValidationType.PATTERN:
        return typeof value === 'string' && rule.parameters!.pattern.test(value);
        
      case ValidationType.EMAIL:
        return this.isValidEmail(value);
        
      case ValidationType.URL:
        return this.isValidUrl(value);
        
      case ValidationType.PHONE:
        return this.isValidPhone(value);
        
      case ValidationType.CUSTOM:
        return await this.applyCustomValidation(value, rule.parameters!.validator);
        
      default:
        return true;
    }
  }
  
  private async applyCustomValidation(value: any, validatorName: string): Promise<boolean> {
    switch (validatorName) {
      case 'profanityFilter':
        return await this.checkProfanity(value);
      case 'uniqueDisplayName':
        return await this.checkDisplayNameUniqueness(value);
      default:
        return true;
    }
  }
}
```

## Constraints and Assumptions

### Constraints
- Must integrate with existing authentication system and user management
- Must support efficient querying and caching for performance
- Must comply with data protection regulations (GDPR, CCPA)
- Must handle concurrent profile updates without data corruption
- Must provide extensible architecture for future profile features

### Assumptions
- Users will want to create rich, detailed profiles to enhance their platform experience
- Profile completion incentives will motivate users to provide comprehensive information
- Most profile data will be relatively static with occasional updates
- Profile viewing will be much more frequent than profile editing
- Users understand and value privacy controls for their profile information

## Acceptance Criteria

### Must Have
- [ ] Comprehensive profile data model supports all essential user information
- [ ] CRUD operations work reliably with proper validation and error handling
- [ ] Profile completion tracking encourages users to build complete profiles
- [ ] Data validation ensures profile data quality and security
- [ ] Performance meets requirements for profile operations
- [ ] Privacy controls protect sensitive profile information
- [ ] Audit logging tracks profile changes for security and compliance

### Should Have
- [ ] Profile caching improves performance for frequent operations
- [ ] Bulk operations support efficient administrative tasks
- [ ] Advanced validation includes profanity filtering and uniqueness checks
- [ ] Profile versioning supports optimistic concurrency control
- [ ] Analytics track profile completion and engagement patterns
- [ ] Migration tools support profile data structure updates

### Could Have
- [ ] Machine learning insights for profile completion optimization
- [ ] Advanced profile search and filtering capabilities
- [ ] Profile templates for quick setup
- [ ] Integration with external profile data sources
- [ ] Advanced audit and compliance reporting

## Risk Assessment

### High Risk
- **Data Privacy**: Profile data must be protected and comply with regulations
- **Performance**: Rich profiles could impact system performance
- **Data Integrity**: Concurrent updates could cause data corruption

### Medium Risk
- **User Adoption**: Users might not complete profiles, reducing platform value
- **Validation Complexity**: Complex validation rules could be difficult to maintain
- **Storage Growth**: Rich profile data could increase storage requirements

### Low Risk
- **Feature Complexity**: Advanced profile features might be complex to implement
- **Migration Challenges**: Profile schema changes could be difficult to deploy

### Mitigation Strategies
- Privacy-by-design approach with comprehensive data protection
- Performance optimization with caching and efficient queries
- Robust concurrency control and data validation
- User incentives and guidance for profile completion
- Modular architecture for easy feature additions and updates

## Dependencies

### Prerequisites
- E01.F01: Database Foundation (profile data storage)
- E01.F02: Authentication System (user identity and session management)
- Data validation and sanitization libraries
- Caching infrastructure (Redis or similar)
- Audit logging system

### Blocks
- Profile media management (T02)
- Profile customization features (T03)
- Social profile features (T04)
- Profile privacy controls (T05)
- Profile analytics and insights (T06)

## Definition of Done

### Technical Completion
- [ ] Profile data model is implemented with comprehensive field support
- [ ] CRUD operations work reliably with proper error handling
- [ ] Data validation ensures profile data quality and security
- [ ] Profile completion tracking calculates scores accurately
- [ ] Performance meets requirements for profile operations
- [ ] Caching improves performance for frequent profile access
- [ ] Audit logging tracks all profile changes

### Integration Completion
- [ ] Profile system integrates with authentication and user management
- [ ] Database schema supports efficient profile queries
- [ ] API endpoints expose profile management functionality
- [ ] Profile completion guidance appears in user interface
- [ ] Privacy controls protect sensitive profile information
- [ ] Error handling provides appropriate user feedback

### Quality Completion
- [ ] Profile operations meet performance requirements
- [ ] Data validation prevents invalid or malicious profile data
- [ ] Profile completion tracking motivates users effectively
- [ ] Concurrent profile updates work without data corruption
- [ ] Privacy compliance meets regulatory requirements
- [ ] User testing confirms profile creation and management usability
- [ ] Security testing validates profile data protection

---

**Task**: T01 Core Profile Data Structure
**Feature**: F01 Profile Creation & Management
**Epic**: E02 User Profile Management
**Estimated Effort**: 3-4 hours
**Priority**: P0 (Critical Path)
**Dependencies**: E01.F01 Database Foundation, E01.F02 Authentication System
**Status**: Ready for Research Phase
