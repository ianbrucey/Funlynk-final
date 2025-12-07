# E04 Discovery Engine - Integration Points

## Overview

This document defines how Discovery Engine services integrate with other epics and external systems. It establishes the data flow patterns, real-time updates, and integration requirements that enable intelligent discovery across the entire platform.

## Integration Architecture

### Core Data Dependencies

**Multi-Epic Data Integration**:
- **E01 Core Infrastructure**: Database access, geolocation services, real-time notifications
- **E02 User & Profile Management**: User profiles, interests, social graph, privacy settings
- **E03 Activity Management**: Activity data, tags, RSVP information, host details

```typescript
// Discovery Engine aggregates data from multiple epics
class DiscoveryDataAggregator {
  constructor(
    private activityService: ActivityService,
    private profileService: ProfileService,
    private socialGraphService: SocialGraphService,
    private geolocationService: GeolocationService
  ) {}
  
  async buildUserDiscoveryContext(userId: string): Promise<UserDiscoveryContext> {
    const [userProfile, following, userInteractions, locationPreferences] = await Promise.all([
      this.profileService.getProfile(userId),
      this.socialGraphService.getFollowing(userId),
      this.getUserInteractionHistory(userId),
      this.getUserLocationPreferences(userId)
    ]);
    
    return {
      profile: userProfile,
      interests: userProfile.interests,
      social_connections: following,
      location_preferences: locationPreferences,
      interaction_patterns: this.analyzeInteractionPatterns(userInteractions),
      discovery_preferences: await this.getDiscoveryPreferences(userId)
    };
  }
  
  async buildActivityDiscoveryData(activityId: string): Promise<ActivityDiscoveryData> {
    const [activity, host, participants, similarActivities] = await Promise.all([
      this.activityService.getActivity(activityId),
      this.profileService.getProfile(activity.host_id),
      this.activityService.getActivityParticipants(activityId),
      this.findSimilarActivities(activityId)
    ]);
    
    return {
      activity,
      host_profile: host,
      participant_profiles: participants,
      social_signals: this.calculateSocialSignals(participants),
      popularity_metrics: this.calculatePopularityMetrics(activity),
      discovery_metadata: {
        tags: activity.tags,
        category: this.categorizeActivity(activity),
        location_data: await this.enrichLocationData(activity.location_coordinates),
        time_factors: this.analyzeTimeFactors(activity.start_time)
      }
    };
  }
}
```

## Epic Integration Details

### E05 Social Interaction Integration

**Discovery Drives Social Engagement**:
- Search and recommendations surface content for social interaction
- Social signals (likes, comments, shares) influence discovery algorithms
- Discovery feeds include social context and interaction opportunities

**Integration Points**:
```typescript
// Social signals enhance discovery algorithms
class SocialEnhancedDiscovery {
  async enhanceSearchWithSocialSignals(
    searchResults: Activity[], 
    userId: string
  ): Promise<EnhancedSearchResult[]> {
    return await Promise.all(
      searchResults.map(async (activity) => {
        const [socialContext, interactionHistory] = await Promise.all([
          this.getSocialContext(activity.id, userId),
          this.getInteractionHistory(activity.id, userId)
        ]);
        
        return {
          ...activity,
          social_context: {
            friends_attending: socialContext.friends_rsvped,
            friends_interested: socialContext.friends_viewed,
            social_proof_score: this.calculateSocialProofScore(socialContext),
            interaction_opportunities: this.identifyInteractionOpportunities(activity, userId)
          },
          engagement_indicators: {
            comment_count: socialContext.comment_count,
            share_count: socialContext.share_count,
            recent_activity: socialContext.recent_engagement
          }
        };
      })
    );
  }
  
  async generateSociallyAwareRecommendations(userId: string): Promise<Recommendation[]> {
    const [baseRecommendations, socialGraph, socialActivity] = await Promise.all([
      this.recommendationEngine.getBaseRecommendations(userId),
      this.socialGraphService.getFollowing(userId),
      this.getSocialActivityData(userId)
    ]);
    
    // Boost recommendations based on social signals
    return baseRecommendations.map(rec => ({
      ...rec,
      social_boost: this.calculateSocialBoost(rec.activity_id, socialGraph),
      social_context: this.buildSocialContext(rec.activity_id, socialGraph),
      conversation_starters: this.generateConversationStarters(rec.activity, socialActivity)
    }));
  }
}

// Discovery content provides context for social interactions
class DiscoveryToSocialBridge {
  async enrichActivityForSocialInteraction(
    activityId: string, 
    userId: string
  ): Promise<SociallyEnrichedActivity> {
    const [activity, discoveryContext, socialContext] = await Promise.all([
      this.activityService.getActivity(activityId),
      this.getDiscoveryContext(activityId, userId),
      this.getSocialInteractionContext(activityId, userId)
    ]);
    
    return {
      ...activity,
      discovery_source: discoveryContext.source, // search, recommendation, feed
      discovery_reason: discoveryContext.reasoning,
      social_interaction_prompts: this.generateInteractionPrompts(activity, socialContext),
      related_conversations: await this.getRelatedConversations(activityId),
      discussion_topics: this.suggestDiscussionTopics(activity)
    };
  }
}
```

### E06 Payments & Monetization Integration

**Discovery Supports Monetization**:
- Paid activities receive appropriate visibility in search and recommendations
- Payment conversion data improves recommendation quality
- Premium discovery features for hosts and users

**Integration Points**:
```typescript
// Payment data enhances discovery algorithms
class MonetizationAwareDiscovery {
  async adjustDiscoveryForPayments(
    activities: Activity[], 
    userId: string
  ): Promise<PaymentAwareActivity[]> {
    const userPaymentProfile = await this.paymentService.getUserPaymentProfile(userId);
    
    return activities.map(activity => {
      const paymentFactors = this.calculatePaymentFactors(activity, userPaymentProfile);
      
      return {
        ...activity,
        payment_context: {
          price_appeal_score: paymentFactors.priceAppealScore,
          payment_friction_score: paymentFactors.frictionScore,
          value_perception_score: paymentFactors.valueScore,
          payment_history_relevance: paymentFactors.historyRelevance
        },
        monetization_boost: this.calculateMonetizationBoost(activity, userPaymentProfile),
        payment_recommendations: this.generatePaymentRecommendations(activity, userPaymentProfile)
      };
    });
  }
  
  async optimizeDiscoveryForRevenue(
    baseRecommendations: Recommendation[], 
    userId: string
  ): Promise<RevenueOptimizedRecommendation[]> {
    const userSpendingProfile = await this.analyzeUserSpendingPatterns(userId);
    
    return baseRecommendations.map(rec => {
      const revenueScore = this.calculateRevenueScore(rec.activity, userSpendingProfile);
      
      return {
        ...rec,
        revenue_optimization: {
          revenue_potential: revenueScore,
          conversion_probability: this.predictPaymentConversion(rec.activity, userId),
          pricing_appeal: this.assessPricingAppeal(rec.activity, userSpendingProfile)
        },
        adjusted_score: this.balanceRelevanceAndRevenue(rec.score, revenueScore)
      };
    });
  }
}

// Discovery drives payment conversions
class DiscoveryToPaymentFunnel {
  async trackDiscoveryToPaymentConversion(
    discoveryEvent: DiscoveryEvent, 
    paymentEvent: PaymentEvent
  ): Promise<void> {
    // Track conversion funnel: discovery → view → RSVP → payment
    await this.analyticsService.recordConversionEvent({
      user_id: discoveryEvent.user_id,
      activity_id: discoveryEvent.activity_id,
      discovery_source: discoveryEvent.source, // search, recommendation, feed
      discovery_position: discoveryEvent.position,
      time_to_payment: paymentEvent.timestamp - discoveryEvent.timestamp,
      conversion_path: this.buildConversionPath(discoveryEvent, paymentEvent)
    });
    
    // Update recommendation models with conversion data
    await this.recommendationEngine.updateConversionFeedback({
      recommendation_id: discoveryEvent.recommendation_id,
      converted: true,
      conversion_value: paymentEvent.amount,
      conversion_time: paymentEvent.timestamp
    });
  }
}
```

### E07 Administration Integration

**Discovery Analytics for Platform Management**:
- Search and recommendation analytics inform product decisions
- Content moderation affects discovery visibility
- A/B testing framework for discovery algorithms

**Integration Points**:
```typescript
// Discovery provides rich analytics for administration
class DiscoveryAnalyticsService {
  async generateDiscoveryInsights(timeframe: string): Promise<DiscoveryInsights> {
    const [searchAnalytics, recommendationMetrics, feedPerformance] = await Promise.all([
      this.analyzeSearchPatterns(timeframe),
      this.analyzeRecommendationPerformance(timeframe),
      this.analyzeFeedEngagement(timeframe)
    ]);
    
    return {
      search_insights: {
        popular_queries: searchAnalytics.topQueries,
        search_success_rate: searchAnalytics.successRate,
        zero_result_queries: searchAnalytics.zeroResults,
        search_to_rsvp_conversion: searchAnalytics.conversionRate,
        geographic_search_patterns: searchAnalytics.geoPatterns
      },
      recommendation_insights: {
        algorithm_performance: recommendationMetrics.algorithmComparison,
        personalization_effectiveness: recommendationMetrics.personalizationImpact,
        recommendation_diversity: recommendationMetrics.diversityMetrics,
        cold_start_performance: recommendationMetrics.coldStartSuccess
      },
      feed_insights: {
        engagement_by_content_type: feedPerformance.contentTypeEngagement,
        optimal_feed_composition: feedPerformance.optimalMix,
        real_time_update_impact: feedPerformance.realTimeImpact,
        feed_refresh_patterns: feedPerformance.refreshPatterns
      },
      platform_health: {
        discovery_coverage: this.calculateDiscoveryCoverage(),
        content_freshness: this.analyzeContentFreshness(),
        user_discovery_satisfaction: this.measureDiscoverySatisfaction()
      }
    };
  }
  
  async moderateDiscoveryContent(moderationAction: ModerationAction): Promise<void> {
    switch (moderationAction.type) {
      case 'hide_activity':
        await this.removeFromDiscovery(moderationAction.activity_id);
        break;
      case 'boost_activity':
        await this.boostInDiscovery(moderationAction.activity_id, moderationAction.boost_factor);
        break;
      case 'flag_inappropriate':
        await this.flagInappropriateContent(moderationAction.activity_id);
        break;
      case 'adjust_algorithm':
        await this.adjustAlgorithmWeights(moderationAction.algorithm_adjustments);
        break;
    }
    
    // Update discovery indexes and caches
    await this.refreshDiscoveryIndexes();
  }
}

// A/B testing framework for discovery optimization
class DiscoveryExperimentationService {
  async runDiscoveryExperiment(experiment: DiscoveryExperiment): Promise<ExperimentResult> {
    const [controlGroup, testGroup] = await this.assignExperimentGroups(experiment);
    
    // Run experiment for specified duration
    const results = await this.executeExperiment({
      control_algorithm: experiment.control_algorithm,
      test_algorithm: experiment.test_algorithm,
      control_users: controlGroup,
      test_users: testGroup,
      metrics: experiment.success_metrics,
      duration: experiment.duration
    });
    
    // Analyze results
    const analysis = await this.analyzeExperimentResults(results);
    
    // Make recommendation for rollout
    const recommendation = this.generateRolloutRecommendation(analysis);
    
    return {
      experiment_id: experiment.id,
      results: analysis,
      recommendation: recommendation,
      statistical_significance: analysis.significance,
      business_impact: analysis.businessImpact
    };
  }
}
```

## Real-time Data Synchronization

### Discovery Index Updates
```typescript
// Real-time updates to discovery systems
class DiscoveryIndexManager {
  async handleActivityUpdate(activityUpdate: ActivityUpdateEvent): Promise<void> {
    // Update search indexes
    await this.searchService.updateActivityIndex(activityUpdate.activity_id, activityUpdate.changes);
    
    // Refresh recommendations for affected users
    const affectedUsers = await this.getAffectedUsers(activityUpdate);
    await Promise.all(
      affectedUsers.map(userId => 
        this.recommendationEngine.refreshUserRecommendations(userId)
      )
    );
    
    // Update trending calculations
    if (this.affectsTrending(activityUpdate)) {
      await this.trendingService.recalculateTrending();
    }
    
    // Invalidate relevant feed caches
    await this.feedService.invalidateAffectedFeeds(activityUpdate);
  }
  
  async handleUserProfileUpdate(profileUpdate: ProfileUpdateEvent): Promise<void> {
    // Update user search index
    await this.searchService.updateUserIndex(profileUpdate.user_id, profileUpdate.changes);
    
    // Refresh user's personalized recommendations
    await this.recommendationEngine.refreshUserRecommendations(profileUpdate.user_id);
    
    // Update collaborative filtering models if interests changed
    if (profileUpdate.changes.interests) {
      await this.collaborativeFilteringService.updateUserProfile(profileUpdate.user_id);
    }
    
    // Refresh user's feeds
    await this.feedService.refreshUserFeeds(profileUpdate.user_id);
  }
  
  async handleSocialGraphUpdate(socialUpdate: SocialGraphUpdateEvent): Promise<void> {
    // Update social recommendations for both users
    await Promise.all([
      this.recommendationEngine.refreshSocialRecommendations(socialUpdate.follower_id),
      this.recommendationEngine.refreshSocialRecommendations(socialUpdate.following_id)
    ]);
    
    // Update social feeds
    await this.feedService.refreshSocialFeed(socialUpdate.follower_id);
    
    // Update collaborative filtering models
    await this.collaborativeFilteringService.updateSocialConnections(socialUpdate);
  }
}
```

### Performance Optimization Across Epics
```typescript
// Cross-epic performance optimization
class DiscoveryPerformanceOptimizer {
  async optimizeDiscoveryQueries(): Promise<void> {
    // Optimize database queries across epics
    await this.optimizeActivityQueries();
    await this.optimizeUserQueries();
    await this.optimizeSocialQueries();
    
    // Update materialized views for common discovery patterns
    await this.refreshMaterializedViews();
    
    // Optimize search indexes
    await this.optimizeSearchIndexes();
  }
  
  async precomputeDiscoveryData(): Promise<void> {
    // Precompute popular recommendations
    await this.precomputePopularRecommendations();
    
    // Precompute trending activities
    await this.precomputeTrendingActivities();
    
    // Precompute user similarity scores
    await this.precomputeUserSimilarities();
    
    // Precompute activity similarity scores
    await this.precomputeActivitySimilarities();
  }
}
```

## Data Privacy and Security

### Privacy-Aware Discovery
```typescript
// Privacy controls in discovery
class PrivacyAwareDiscoveryService {
  async filterDiscoveryByPrivacy(
    content: DiscoveryContent[], 
    viewerId: string
  ): Promise<DiscoveryContent[]> {
    return await Promise.all(
      content.map(async (item) => {
        const privacyCheck = await this.checkPrivacyPermissions(item, viewerId);
        
        if (!privacyCheck.canView) {
          return null;
        }
        
        // Filter sensitive data based on privacy settings
        return this.filterSensitiveData(item, privacyCheck.permissions);
      })
    ).then(results => results.filter(Boolean));
  }
  
  async respectLocationPrivacy(
    activities: Activity[], 
    viewerId: string
  ): Promise<Activity[]> {
    return activities.map(activity => {
      const locationPrivacy = this.getLocationPrivacyLevel(activity.host_id);
      
      if (locationPrivacy === 'hidden') {
        return { ...activity, location_coordinates: null };
      } else if (locationPrivacy === 'city_only') {
        return { 
          ...activity, 
          location_coordinates: this.generalizeLocation(activity.location_coordinates)
        };
      }
      
      return activity;
    });
  }
}
```

---

**Integration Points Status**: ✅ Complete
**E04 Discovery Engine Epic Status**: ✅ Complete - Ready for dependent epics
