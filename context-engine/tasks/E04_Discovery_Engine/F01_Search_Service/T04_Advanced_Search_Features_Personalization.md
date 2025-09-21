# T04 Advanced Search Features & Personalization

## Problem Definition

### Task Overview
Implement advanced search capabilities including personalized result ranking, saved searches, search within results, and intelligent search suggestions based on user behavior and preferences. This enhances the basic search functionality with sophisticated features that improve user experience and search relevance.

### Problem Statement
Users need advanced search capabilities that:
- **Provide personalized results**: Rank search results based on user interests and behavior
- **Enable search refinement**: Allow users to search within results and refine queries
- **Support complex queries**: Handle advanced search syntax and boolean operations
- **Remember preferences**: Save searches and provide personalized suggestions
- **Learn from behavior**: Improve search relevance based on user interactions

### Scope
**In Scope:**
- Personalized search result ranking based on user profile and behavior
- Saved searches with notification preferences
- Search within results functionality
- Advanced search syntax and boolean operations
- Intelligent search suggestions based on user history
- Search preference management and customization
- A/B testing framework for search improvements

**Out of Scope:**
- Basic search functionality (covered in T02 and T03)
- Search analytics dashboards (covered in T05)
- Social search features (handled by E05)
- Machine learning model training (basic scoring algorithms only)

### Success Criteria
- [ ] Personalized search improves click-through rate by 25%
- [ ] Saved searches increase user engagement by 20%
- [ ] Search within results reduces search abandonment by 15%
- [ ] Advanced search features are used by 10%+ of power users
- [ ] Search suggestions improve query completion by 30%
- [ ] Personalization maintains sub-300ms response times

### Dependencies
- **Requires**: T02 Search infrastructure for advanced query processing
- **Requires**: T03 Frontend components for advanced feature integration
- **Requires**: E02 User profiles for personalization data
- **Requires**: E03 Activity data for behavioral analysis
- **Blocks**: T05 Search analytics (needs advanced features for comprehensive tracking)
- **Informs**: F02 Recommendation engine (shared personalization logic)

### Acceptance Criteria

#### Personalized Search Ranking
- [ ] Search results ranked based on user interests and past behavior
- [ ] Location-based personalization for local activity preference
- [ ] Time-based personalization for preferred activity times
- [ ] Social signals integration (friends' activities ranked higher)
- [ ] Learning from user interactions (clicks, RSVPs, saves)

#### Saved Searches & Notifications
- [ ] Save search queries with custom names and descriptions
- [ ] Notification preferences for saved search updates
- [ ] Saved search management (edit, delete, share)
- [ ] Automatic notifications when new matching activities appear
- [ ] Saved search analytics and performance tracking

#### Advanced Search Features
- [ ] Search within results functionality
- [ ] Boolean search operators (AND, OR, NOT)
- [ ] Exact phrase search with quotes
- [ ] Wildcard and fuzzy search capabilities
- [ ] Advanced date and time range queries

#### Intelligent Suggestions
- [ ] Search suggestions based on user history
- [ ] Trending search terms and popular queries
- [ ] Contextual suggestions based on current location and time
- [ ] Auto-complete with personalized ranking
- [ ] Search query expansion and synonym suggestions

#### Search Customization
- [ ] User search preferences and default filters
- [ ] Search result layout preferences (card vs list)
- [ ] Notification settings for search-related updates
- [ ] Search history management and privacy controls
- [ ] Export and import of search preferences

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Personalization Engine** (90 minutes)
   - Build user behavior tracking and analysis
   - Implement personalized ranking algorithms
   - Create user preference learning system
   - Add social signal integration

2. **Advanced Search Features** (90 minutes)
   - Implement saved searches with notifications
   - Build search within results functionality
   - Add advanced search syntax support
   - Create intelligent suggestion system

3. **Integration & Optimization** (60 minutes)
   - Integrate advanced features with existing search
   - Add A/B testing framework for search improvements
   - Optimize performance for personalized queries
   - Create comprehensive testing and validation

### Deliverables
- [ ] Personalized search ranking system
- [ ] Saved searches with notification capabilities
- [ ] Search within results functionality
- [ ] Advanced search syntax and boolean operations
- [ ] Intelligent search suggestions based on user behavior
- [ ] Search preference management interface
- [ ] A/B testing framework for search improvements
- [ ] Performance optimization for advanced features
- [ ] User behavior tracking and analysis system

### Technical Specifications

#### Personalization Engine
```typescript
interface UserSearchProfile {
  userId: string;
  interests: string[];
  preferredCategories: string[];
  preferredLocations: GeoPoint[];
  preferredTimes: TimePreference[];
  searchHistory: SearchHistoryItem[];
  clickHistory: ClickHistoryItem[];
  rsvpHistory: RSVPHistoryItem[];
  socialConnections: string[];
  lastUpdated: Date;
}

class SearchPersonalizationEngine {
  async personalizeSearchResults(
    results: SearchResult[],
    userId: string,
    query: string
  ): Promise<SearchResult[]> {
    const userProfile = await this.getUserSearchProfile(userId);
    
    // Calculate personalization scores for each result
    const scoredResults = results.map(result => ({
      ...result,
      personalizedScore: this.calculatePersonalizationScore(result, userProfile, query),
    }));
    
    // Re-rank results based on combined relevance and personalization scores
    return scoredResults.sort((a, b) => {
      const scoreA = (a.relevanceScore * 0.7) + (a.personalizedScore * 0.3);
      const scoreB = (b.relevanceScore * 0.7) + (b.personalizedScore * 0.3);
      return scoreB - scoreA;
    });
  }
  
  private calculatePersonalizationScore(
    result: SearchResult,
    profile: UserSearchProfile,
    query: string
  ): number {
    let score = 0;
    
    // Interest matching
    const interestMatch = this.calculateInterestMatch(result.tags, profile.interests);
    score += interestMatch * 0.3;
    
    // Category preference
    const categoryMatch = profile.preferredCategories.includes(result.categoryId) ? 1 : 0;
    score += categoryMatch * 0.2;
    
    // Location preference
    const locationScore = this.calculateLocationPreference(result.location, profile.preferredLocations);
    score += locationScore * 0.2;
    
    // Time preference
    const timeScore = this.calculateTimePreference(result.startTime, profile.preferredTimes);
    score += timeScore * 0.1;
    
    // Social signals
    const socialScore = this.calculateSocialScore(result, profile.socialConnections);
    score += socialScore * 0.1;
    
    // Historical behavior
    const behaviorScore = this.calculateBehaviorScore(result, profile);
    score += behaviorScore * 0.1;
    
    return Math.min(score, 1); // Normalize to 0-1
  }
  
  private calculateInterestMatch(tags: string[], interests: string[]): number {
    if (interests.length === 0) return 0;
    
    const matchingTags = tags.filter(tag => 
      interests.some(interest => 
        interest.toLowerCase().includes(tag.toLowerCase()) ||
        tag.toLowerCase().includes(interest.toLowerCase())
      )
    );
    
    return matchingTags.length / Math.max(tags.length, interests.length);
  }
  
  async updateUserProfile(userId: string, interaction: UserInteraction): Promise<void> {
    const profile = await this.getUserSearchProfile(userId);
    
    switch (interaction.type) {
      case 'search':
        profile.searchHistory.push({
          query: interaction.query,
          timestamp: new Date(),
          filters: interaction.filters,
        });
        break;
        
      case 'click':
        profile.clickHistory.push({
          activityId: interaction.activityId,
          query: interaction.query,
          position: interaction.position,
          timestamp: new Date(),
        });
        break;
        
      case 'rsvp':
        profile.rsvpHistory.push({
          activityId: interaction.activityId,
          timestamp: new Date(),
        });
        
        // Update interests based on RSVP activity
        await this.updateInterestsFromActivity(profile, interaction.activityId);
        break;
    }
    
    // Keep history limited to recent items
    profile.searchHistory = profile.searchHistory.slice(-100);
    profile.clickHistory = profile.clickHistory.slice(-500);
    profile.rsvpHistory = profile.rsvpHistory.slice(-200);
    
    profile.lastUpdated = new Date();
    await this.saveUserSearchProfile(profile);
  }
}
```

#### Saved Searches Implementation
```typescript
interface SavedSearch {
  id: string;
  userId: string;
  name: string;
  description?: string;
  query: string;
  filters: SearchFilters;
  notificationEnabled: boolean;
  notificationFrequency: 'immediate' | 'daily' | 'weekly';
  lastNotified?: Date;
  createdAt: Date;
  updatedAt: Date;
}

class SavedSearchService {
  async createSavedSearch(
    userId: string,
    searchData: CreateSavedSearchRequest
  ): Promise<SavedSearch> {
    const savedSearch: SavedSearch = {
      id: generateId(),
      userId,
      name: searchData.name,
      description: searchData.description,
      query: searchData.query,
      filters: searchData.filters,
      notificationEnabled: searchData.notificationEnabled || false,
      notificationFrequency: searchData.notificationFrequency || 'daily',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    
    await this.saveSavedSearch(savedSearch);
    
    // Set up notification schedule if enabled
    if (savedSearch.notificationEnabled) {
      await this.scheduleSearchNotifications(savedSearch);
    }
    
    return savedSearch;
  }
  
  async checkForNewResults(savedSearch: SavedSearch): Promise<SearchResult[]> {
    const lastCheck = savedSearch.lastNotified || savedSearch.createdAt;
    
    // Search for activities created/updated since last check
    const results = await searchService.searchActivities({
      query: savedSearch.query,
      filters: {
        ...savedSearch.filters,
        updatedSince: lastCheck,
      },
    });
    
    return results.items;
  }
  
  async sendSavedSearchNotifications(): Promise<void> {
    const savedSearches = await this.getActiveNotificationSearches();
    
    for (const savedSearch of savedSearches) {
      const newResults = await this.checkForNewResults(savedSearch);
      
      if (newResults.length > 0) {
        await notificationService.sendSavedSearchNotification(
          savedSearch.userId,
          savedSearch.name,
          newResults.length
        );
        
        // Update last notified timestamp
        await this.updateLastNotified(savedSearch.id, new Date());
      }
    }
  }
}
```

#### Advanced Search Syntax
```typescript
class AdvancedSearchParser {
  parseAdvancedQuery(query: string): ParsedQuery {
    const tokens = this.tokenizeQuery(query);
    return this.buildQueryAST(tokens);
  }
  
  private tokenizeQuery(query: string): QueryToken[] {
    const tokens: QueryToken[] = [];
    const regex = /("([^"]*)")|(\w+:(\w+|\([^)]*\)))|(\w+)|([()&|!])/g;
    let match;
    
    while ((match = regex.exec(query)) !== null) {
      if (match[2]) {
        // Quoted phrase
        tokens.push({ type: 'phrase', value: match[2] });
      } else if (match[3]) {
        // Field search (e.g., category:sports)
        const [field, value] = match[3].split(':');
        tokens.push({ type: 'field', field, value: value.replace(/[()]/g, '') });
      } else if (match[5]) {
        // Regular word
        tokens.push({ type: 'word', value: match[5] });
      } else if (match[6]) {
        // Operator
        tokens.push({ type: 'operator', value: match[6] });
      }
    }
    
    return tokens;
  }
  
  buildElasticsearchQuery(parsedQuery: ParsedQuery): any {
    return this.convertASTToElasticsearch(parsedQuery.ast);
  }
  
  private convertASTToElasticsearch(node: QueryNode): any {
    switch (node.type) {
      case 'and':
        return {
          bool: {
            must: node.children.map(child => this.convertASTToElasticsearch(child)),
          },
        };
        
      case 'or':
        return {
          bool: {
            should: node.children.map(child => this.convertASTToElasticsearch(child)),
            minimum_should_match: 1,
          },
        };
        
      case 'not':
        return {
          bool: {
            must_not: this.convertASTToElasticsearch(node.children[0]),
          },
        };
        
      case 'phrase':
        return {
          match_phrase: {
            _all: node.value,
          },
        };
        
      case 'field':
        return {
          match: {
            [node.field]: node.value,
          },
        };
        
      case 'word':
        return {
          multi_match: {
            query: node.value,
            fields: ['title^3', 'description^2', 'tags^2'],
          },
        };
        
      default:
        return { match_all: {} };
    }
  }
}
```

#### Search Within Results
```typescript
interface SearchWithinResultsProps {
  originalResults: SearchResult[];
  onResultsFiltered: (filteredResults: SearchResult[]) => void;
}

const SearchWithinResults: React.FC<SearchWithinResultsProps> = ({
  originalResults,
  onResultsFiltered,
}) => {
  const [withinQuery, setWithinQuery] = useState('');
  const [filteredResults, setFilteredResults] = useState(originalResults);
  
  const searchWithinResults = useMemo(
    () => debounce((query: string) => {
      if (!query.trim()) {
        setFilteredResults(originalResults);
        onResultsFiltered(originalResults);
        return;
      }
      
      const filtered = originalResults.filter(result => {
        const searchText = `${result.title} ${result.description} ${result.tags.join(' ')}`.toLowerCase();
        return searchText.includes(query.toLowerCase());
      });
      
      setFilteredResults(filtered);
      onResultsFiltered(filtered);
    }, 300),
    [originalResults, onResultsFiltered]
  );
  
  useEffect(() => {
    searchWithinResults(withinQuery);
  }, [withinQuery, searchWithinResults]);
  
  return (
    <View style={styles.searchWithinContainer}>
      <TextInput
        style={styles.searchWithinInput}
        placeholder="Search within results..."
        value={withinQuery}
        onChangeText={setWithinQuery}
      />
      <Text style={styles.resultCount}>
        {filteredResults.length} of {originalResults.length} results
      </Text>
    </View>
  );
};
```

### Quality Checklist
- [ ] Personalization improves search relevance without performance impact
- [ ] Saved searches work reliably with accurate notifications
- [ ] Advanced search syntax is intuitive and well-documented
- [ ] Search within results provides instant feedback
- [ ] User behavior tracking respects privacy preferences
- [ ] Performance optimized for complex personalized queries
- [ ] A/B testing framework enables continuous improvement
- [ ] Advanced features are discoverable but not overwhelming

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E04 Discovery Engine  
**Feature**: F01 Search Service  
**Dependencies**: T02 Search Infrastructure, T03 Frontend Components, User Profiles (E02), Activity Data (E03)  
**Blocks**: T05 Search Analytics
