# E04 Discovery Engine - Service Architecture

## Architecture Overview

The Discovery Engine epic provides three main services that transform raw data into intelligent discovery experiences: Search Service, Recommendation Engine, and Feed Generation Service. These services work together to help users discover relevant activities through multiple pathways.

## Service Design Principles

### 1. Personalization-First
All discovery services prioritize personalized experiences based on user context and behavior.

### 2. Real-time Responsiveness
Services provide real-time results while maintaining high performance under load.

### 3. Algorithmic Transparency
Recommendation and ranking algorithms provide explainable results to build user trust.

### 4. Continuous Learning
Services continuously learn from user interactions to improve discovery quality.

## Core Services

### 4.1 Search Service

**Purpose**: Provides comprehensive search capabilities with intelligent ranking and filtering

**Responsibilities**:
- Full-text search across activities and users
- Advanced filtering and faceted search
- Search result ranking and personalization
- Search suggestions and autocomplete
- Search analytics and optimization
- Query performance monitoring

**Service Interface**:
```typescript
interface SearchService {
  // Activity Search
  searchActivities(query: SearchQuery, userId?: string): Promise<SearchResult<Activity>>
  getSearchSuggestions(prefix: string, context?: SearchContext): Promise<SearchSuggestion[]>
  getPopularSearches(timeframe?: string): Promise<PopularSearch[]>
  
  // User Search
  searchUsers(query: UserSearchQuery, searcherId?: string): Promise<SearchResult<User>>
  
  // Advanced Search
  searchWithFilters(filters: SearchFilters, userId?: string): Promise<SearchResult<Activity>>
  getFacetCounts(query: string, filters: SearchFilters): Promise<SearchFacets>
  
  // Search Analytics
  recordSearchInteraction(searchId: string, interaction: SearchInteraction): Promise<void>
  getSearchAnalytics(timeframe: string): Promise<SearchAnalytics>
}
```

**Search Architecture**:
```typescript
class IntelligentSearchEngine {
  constructor(
    private database: DatabaseService,
    private searchIndex: ElasticsearchService,
    private geolocationService: GeolocationService,
    private userProfileService: ProfileService
  ) {}
  
  async searchActivities(query: SearchQuery, userId?: string): Promise<SearchResult<Activity>> {
    // Get user context for personalization
    const userContext = userId ? await this.getUserSearchContext(userId) : null;
    
    // Build search query with multiple strategies
    const searchStrategies = [
      this.buildTextSearch(query.text),
      this.buildLocationSearch(query.location, query.radius),
      this.buildCategorySearch(query.categories),
      this.buildTimeSearch(query.timeRange),
      this.buildPriceSearch(query.priceRange)
    ].filter(Boolean);
    
    // Execute search with ranking
    const rawResults = await this.executeMultiStrategySearch(searchStrategies);
    
    // Apply personalized ranking
    const rankedResults = await this.applyPersonalizedRanking(rawResults, userContext);
    
    // Record search for analytics
    await this.recordSearch(query, rankedResults.length, userId);
    
    return {
      results: rankedResults,
      total_count: rawResults.total,
      facets: await this.calculateFacets(query, rawResults),
      suggestions: await this.getQuerySuggestions(query.text),
      search_id: this.generateSearchId()
    };
  }
  
  private async applyPersonalizedRanking(
    results: Activity[], 
    userContext: UserSearchContext | null
  ): Promise<Activity[]> {
    if (!userContext) return results;
    
    return results.map(activity => ({
      ...activity,
      relevance_score: this.calculatePersonalizedScore(activity, userContext),
      personalization_factors: this.explainPersonalization(activity, userContext)
    })).sort((a, b) => b.relevance_score - a.relevance_score);
  }
  
  private calculatePersonalizedScore(activity: Activity, context: UserSearchContext): number {
    let score = activity.base_relevance_score || 0;
    
    // Interest matching boost
    const interestMatch = this.calculateInterestMatch(activity.tags, context.interests);
    score += interestMatch * 0.3;
    
    // Location preference boost
    const locationScore = this.calculateLocationScore(activity.location, context.location);
    score += locationScore * 0.2;
    
    // Social signals boost
    const socialScore = this.calculateSocialScore(activity, context.following);
    score += socialScore * 0.25;
    
    // Behavioral pattern boost
    const behaviorScore = this.calculateBehaviorScore(activity, context.pastInteractions);
    score += behaviorScore * 0.15;
    
    // Time preference boost
    const timeScore = this.calculateTimeScore(activity.start_time, context.timePreferences);
    score += timeScore * 0.1;
    
    return Math.min(1.0, Math.max(0.0, score));
  }
}
```

### 4.2 Recommendation Engine

**Purpose**: Generates personalized activity recommendations using multiple algorithms

**Responsibilities**:
- Interest-based recommendations
- Collaborative filtering recommendations
- Social graph recommendations
- Location-based recommendations
- Trending activity recommendations
- Recommendation explanation and feedback

**Service Interface**:
```typescript
interface RecommendationEngine {
  // Personal Recommendations
  getPersonalizedRecommendations(userId: string, options?: RecommendationOptions): Promise<Recommendation[]>
  getRecommendationsByType(userId: string, type: RecommendationType): Promise<Recommendation[]>
  
  // Contextual Recommendations
  getLocationBasedRecommendations(location: Coordinates, userId?: string): Promise<Recommendation[]>
  getSocialRecommendations(userId: string): Promise<Recommendation[]>
  getTrendingRecommendations(userId?: string): Promise<Recommendation[]>
  
  // Recommendation Management
  recordRecommendationFeedback(userId: string, recommendationId: string, feedback: FeedbackType): Promise<void>
  refreshUserRecommendations(userId: string): Promise<void>
  
  // Similar Content
  getSimilarActivities(activityId: string, userId?: string): Promise<Activity[]>
  getSimilarUsers(userId: string, targetUserId: string): Promise<User[]>
}
```

**Multi-Algorithm Recommendation System**:
```typescript
class HybridRecommendationEngine {
  private algorithms: RecommendationAlgorithm[] = [
    new InterestBasedRecommendation(),
    new CollaborativeFilteringRecommendation(),
    new SocialGraphRecommendation(),
    new LocationBasedRecommendation(),
    new ContentBasedRecommendation(),
    new TrendingRecommendation()
  ];
  
  async generateRecommendations(userId: string, options: RecommendationOptions): Promise<Recommendation[]> {
    // Get user profile and context
    const userProfile = await this.profileService.getProfile(userId);
    const userContext = await this.buildUserContext(userId);
    
    // Generate recommendations from each algorithm
    const algorithmResults = await Promise.all(
      this.algorithms.map(async (algorithm) => {
        try {
          const recommendations = await algorithm.generateRecommendations(userProfile, userContext, options);
          return {
            algorithm: algorithm.name,
            recommendations,
            weight: algorithm.getWeight(userProfile, userContext)
          };
        } catch (error) {
          this.logger.warn(`Algorithm ${algorithm.name} failed`, { error, userId });
          return { algorithm: algorithm.name, recommendations: [], weight: 0 };
        }
      })
    );
    
    // Combine and rank recommendations
    const combinedRecommendations = this.combineAlgorithmResults(algorithmResults);
    
    // Apply diversity and freshness filters
    const diversifiedRecommendations = this.applyDiversityFilters(combinedRecommendations, options);
    
    // Cache recommendations
    await this.cacheRecommendations(userId, diversifiedRecommendations);
    
    return diversifiedRecommendations;
  }
  
  private combineAlgorithmResults(results: AlgorithmResult[]): Recommendation[] {
    const recommendationMap = new Map<string, CombinedRecommendation>();
    
    // Combine scores from different algorithms
    results.forEach(({ algorithm, recommendations, weight }) => {
      recommendations.forEach(rec => {
        const key = rec.activity_id;
        const existing = recommendationMap.get(key);
        
        if (existing) {
          existing.combined_score += rec.score * weight;
          existing.algorithm_scores[algorithm] = rec.score;
          existing.reasoning.push(...rec.reasoning);
        } else {
          recommendationMap.set(key, {
            ...rec,
            combined_score: rec.score * weight,
            algorithm_scores: { [algorithm]: rec.score },
            reasoning: [...rec.reasoning]
          });
        }
      });
    });
    
    // Sort by combined score and return top recommendations
    return Array.from(recommendationMap.values())
      .sort((a, b) => b.combined_score - a.combined_score)
      .map(rec => ({
        activity_id: rec.activity_id,
        score: rec.combined_score,
        reasoning: this.consolidateReasoning(rec.reasoning),
        algorithm_breakdown: rec.algorithm_scores,
        confidence: this.calculateConfidence(rec)
      }));
  }
}
```

### 4.3 Feed Generation Service

**Purpose**: Creates personalized activity feeds combining multiple content sources

**Responsibilities**:
- Personalized home feed generation
- Social feed from followed users
- Category-based feeds
- Trending activities feed
- Feed ranking and optimization
- Real-time feed updates

**Service Interface**:
```typescript
interface FeedGenerationService {
  // Feed Generation
  generateHomeFeed(userId: string, options?: FeedOptions): Promise<FeedItem[]>
  generateSocialFeed(userId: string, options?: FeedOptions): Promise<FeedItem[]>
  generateCategoryFeed(category: string, userId?: string, options?: FeedOptions): Promise<FeedItem[]>
  generateTrendingFeed(userId?: string, options?: FeedOptions): Promise<FeedItem[]>
  
  // Feed Management
  refreshUserFeed(userId: string, feedType: FeedType): Promise<void>
  getFeedUpdates(userId: string, feedType: FeedType, since: Date): Promise<FeedUpdate[]>
  
  // Feed Analytics
  recordFeedInteraction(userId: string, feedItemId: string, interaction: FeedInteraction): Promise<void>
  getFeedPerformanceMetrics(feedType: FeedType, timeframe: string): Promise<FeedMetrics>
}
```

**Intelligent Feed Composition**:
```typescript
class PersonalizedFeedGenerator {
  async generateHomeFeed(userId: string, options: FeedOptions): Promise<FeedItem[]> {
    const userProfile = await this.profileService.getProfile(userId);
    const feedContext = await this.buildFeedContext(userId);
    
    // Get content from multiple sources
    const contentSources = await Promise.all([
      this.getSocialContent(userId, feedContext),      // 40% weight
      this.getRecommendedContent(userId, feedContext), // 35% weight
      this.getTrendingContent(userId, feedContext),    // 15% weight
      this.getLocationContent(userId, feedContext),    // 10% weight
    ]);
    
    // Combine content with intelligent mixing
    const mixedContent = this.intelligentContentMixing(contentSources, feedContext);
    
    // Apply engagement-based ranking
    const rankedContent = await this.applyEngagementRanking(mixedContent, userProfile);
    
    // Ensure content diversity
    const diversifiedContent = this.ensureContentDiversity(rankedContent, options);
    
    // Add real-time updates
    const finalFeed = await this.addRealTimeUpdates(diversifiedContent, userId);
    
    // Cache feed for performance
    await this.cacheFeed(userId, 'home', finalFeed);
    
    return finalFeed;
  }
  
  private intelligentContentMixing(
    sources: ContentSource[], 
    context: FeedContext
  ): FeedItem[] {
    const mixedFeed: FeedItem[] = [];
    const sourceIterators = sources.map(source => ({ 
      content: source.content, 
      index: 0, 
      weight: source.weight 
    }));
    
    // Intelligent interleaving based on engagement patterns
    while (mixedFeed.length < context.targetFeedSize && this.hasMoreContent(sourceIterators)) {
      // Choose next source based on weights and user engagement patterns
      const nextSource = this.selectNextSource(sourceIterators, context);
      
      if (nextSource && nextSource.index < nextSource.content.length) {
        const item = nextSource.content[nextSource.index];
        mixedFeed.push({
          ...item,
          feed_position: mixedFeed.length,
          source_type: nextSource.type,
          insertion_reason: this.explainInsertion(item, context)
        });
        nextSource.index++;
      }
    }
    
    return mixedFeed;
  }
  
  private async applyEngagementRanking(
    content: FeedItem[], 
    userProfile: UserProfile
  ): Promise<FeedItem[]> {
    // Calculate engagement probability for each item
    const scoredContent = await Promise.all(
      content.map(async (item) => {
        const engagementScore = await this.predictEngagementProbability(item, userProfile);
        return {
          ...item,
          engagement_score: engagementScore,
          ranking_factors: this.explainRanking(item, userProfile)
        };
      })
    );
    
    // Sort by engagement score while maintaining some source diversity
    return this.diversityAwareSort(scoredContent);
  }
}
```

## Service Communication Patterns

### Cross-Service Integration
```typescript
// Search Service uses Recommendation Engine for query suggestions
class SearchService {
  async getSearchSuggestions(prefix: string, userId?: string): Promise<SearchSuggestion[]> {
    const [
      textSuggestions,
      personalizedSuggestions,
      trendingSuggestions
    ] = await Promise.all([
      this.getTextBasedSuggestions(prefix),
      userId ? this.recommendationEngine.getSearchRecommendations(userId, prefix) : [],
      this.getTrendingSuggestions(prefix)
    ]);
    
    return this.combineAndRankSuggestions([
      ...textSuggestions,
      ...personalizedSuggestions,
      ...trendingSuggestions
    ]);
  }
}

// Feed Service uses both Search and Recommendation engines
class FeedGenerationService {
  async generateHomeFeed(userId: string): Promise<FeedItem[]> {
    // Get personalized recommendations
    const recommendations = await this.recommendationEngine.getPersonalizedRecommendations(userId);
    
    // Get trending content
    const trending = await this.searchService.searchActivities({
      sort: 'trending',
      timeframe: '24h'
    });
    
    // Combine into feed
    return this.composeFeed(recommendations, trending, userId);
  }
}
```

## Performance Optimizations

### Caching Strategy
```typescript
class DiscoveryCache {
  private readonly SEARCH_CACHE_TTL = 5 * 60; // 5 minutes
  private readonly RECOMMENDATION_CACHE_TTL = 30 * 60; // 30 minutes
  private readonly FEED_CACHE_TTL = 15 * 60; // 15 minutes
  
  async getCachedSearchResults(query: SearchQuery): Promise<SearchResult | null> {
    const cacheKey = this.generateSearchCacheKey(query);
    return await this.redis.get(cacheKey);
  }
  
  async cacheSearchResults(query: SearchQuery, results: SearchResult): Promise<void> {
    const cacheKey = this.generateSearchCacheKey(query);
    await this.redis.setex(cacheKey, this.SEARCH_CACHE_TTL, JSON.stringify(results));
  }
  
  async invalidateUserCache(userId: string): Promise<void> {
    const patterns = [
      `recommendations:${userId}:*`,
      `feed:${userId}:*`,
      `search:user:${userId}:*`
    ];
    
    await Promise.all(
      patterns.map(pattern => this.redis.deleteByPattern(pattern))
    );
  }
}
```

### Background Processing
```typescript
class DiscoveryBackgroundJobs {
  // Update recommendation models
  @Cron('0 */6 * * *') // Every 6 hours
  async updateRecommendationModels(): Promise<void> {
    await this.recommendationEngine.retrainModels();
  }
  
  // Refresh trending content
  @Cron('*/15 * * * *') // Every 15 minutes
  async updateTrendingContent(): Promise<void> {
    await this.feedService.refreshTrendingFeeds();
  }
  
  // Clean up expired cache entries
  @Cron('0 2 * * *') // Daily at 2 AM
  async cleanupExpiredCache(): Promise<void> {
    await this.cacheService.cleanupExpiredEntries();
  }
}
```

## Error Handling and Resilience

### Graceful Degradation
```typescript
class DiscoveryServiceResilience {
  async searchWithFallback(query: SearchQuery, userId?: string): Promise<SearchResult> {
    try {
      // Try advanced search with personalization
      return await this.searchService.searchActivities(query, userId);
    } catch (error) {
      this.logger.warn('Advanced search failed, falling back to basic search', { error });
      
      try {
        // Fallback to basic database search
        return await this.basicSearchService.searchActivities(query);
      } catch (fallbackError) {
        this.logger.error('All search methods failed', { error, fallbackError });
        
        // Return empty results with error indication
        return {
          results: [],
          total_count: 0,
          error: 'Search temporarily unavailable',
          fallback_used: true
        };
      }
    }
  }
}
```

---

**Service Architecture Status**: ✅ Complete
**Next Steps**: Define API contracts for search, recommendations, and feed operations
