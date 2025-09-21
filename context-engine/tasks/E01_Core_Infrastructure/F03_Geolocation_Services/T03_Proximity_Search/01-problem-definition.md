# T03: Proximity Search and Spatial Queries - Problem Definition

## Problem Statement

We need to implement efficient proximity search and spatial query capabilities that enable users to discover activities near their location, search within specific geographic areas, and perform location-based filtering on the Funlynk platform. This includes optimized spatial queries, distance calculations, and geographic search algorithms.

## Context

### Current State
- PostGIS spatial database is configured (T01 completed)
- Geocoding services convert addresses to coordinates (T02 completed)
- Spatial data types and indexes are available
- No proximity search functionality exists
- Users cannot find activities "near me"
- No distance-based filtering or sorting capabilities

### Desired State
- Users can search for activities within specified distances
- "Near me" functionality works accurately and quickly
- Distance-based sorting shows closest activities first
- Geographic bounding box searches are efficient
- Complex spatial queries support advanced filtering
- Search performance scales with growing activity data

## Business Impact

### Why This Matters
- **Core Value Proposition**: Location-based discovery is fundamental to Funlynk
- **User Experience**: Users expect fast, accurate "near me" functionality
- **Activity Discovery**: Proximity is the primary factor in activity recommendations
- **Mobile Usage**: Essential for mobile users discovering activities on-the-go
- **Engagement**: Location-relevant results increase user engagement
- **Competitive Advantage**: Superior location search differentiates the platform

### Success Metrics
- Proximity search response time <500ms for 10km radius
- Search accuracy >95% for activities within specified radius
- "Near me" searches account for >60% of activity discovery
- User engagement with proximity results >40% click-through rate
- Search performance scales linearly with data growth

## Technical Requirements

### Functional Requirements
- **Proximity Search**: Find activities within specified distance from a point
- **Distance Calculation**: Accurate distance measurements between locations
- **Geographic Filtering**: Filter activities by geographic boundaries
- **Sorting by Distance**: Order results by proximity to user location
- **Bounding Box Search**: Efficient searches within rectangular areas
- **Multi-Point Search**: Search near multiple locations simultaneously
- **Performance Optimization**: Sub-second response times for typical queries

### Non-Functional Requirements
- **Performance**: Search queries execute within 500ms for 10km radius
- **Scalability**: Support 100k+ activities with consistent performance
- **Accuracy**: Distance calculations accurate within 1% for local searches
- **Reliability**: 99.9% search service availability
- **Efficiency**: Optimal use of spatial indexes and query planning
- **Flexibility**: Support various search patterns and use cases

## Proximity Search Implementation

### Core Search Functions
```sql
-- Basic proximity search function
CREATE OR REPLACE FUNCTION find_activities_nearby(
    user_lat DOUBLE PRECISION,
    user_lng DOUBLE PRECISION,
    radius_meters INTEGER DEFAULT 5000,
    activity_limit INTEGER DEFAULT 20
)
RETURNS TABLE (
    id UUID,
    title TEXT,
    description TEXT,
    location GEOMETRY,
    distance_meters DOUBLE PRECISION,
    start_date TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.id,
        a.title,
        a.description,
        a.location,
        ST_Distance(a.location, ST_MakePoint(user_lng, user_lat)::geography) as distance_meters,
        a.start_date
    FROM activities a
    WHERE a.location IS NOT NULL
      AND a.status = 'active'
      AND ST_DWithin(
          a.location::geography,
          ST_MakePoint(user_lng, user_lat)::geography,
          radius_meters
      )
    ORDER BY a.location <-> ST_MakePoint(user_lng, user_lat)
    LIMIT activity_limit;
END;
$$ LANGUAGE plpgsql;
```

### Advanced Spatial Queries
```sql
-- Bounding box search with filters
CREATE OR REPLACE FUNCTION search_activities_in_bounds(
    north_lat DOUBLE PRECISION,
    south_lat DOUBLE PRECISION,
    east_lng DOUBLE PRECISION,
    west_lng DOUBLE PRECISION,
    category_filter TEXT[] DEFAULT NULL,
    date_filter TIMESTAMPTZ DEFAULT NULL
)
RETURNS TABLE (
    id UUID,
    title TEXT,
    category TEXT,
    location GEOMETRY,
    start_date TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.id,
        a.title,
        a.category,
        a.location,
        a.start_date
    FROM activities a
    WHERE a.location IS NOT NULL
      AND a.status = 'active'
      AND ST_Within(
          a.location,
          ST_MakeEnvelope(west_lng, south_lat, east_lng, north_lat, 4326)
      )
      AND (category_filter IS NULL OR a.category = ANY(category_filter))
      AND (date_filter IS NULL OR a.start_date >= date_filter)
    ORDER BY a.start_date;
END;
$$ LANGUAGE plpgsql;
```

## Distance Calculation Strategies

### Distance Calculation Methods
```typescript
interface DistanceCalculation {
  method: 'haversine' | 'vincenty' | 'postgis_geography' | 'postgis_geometry';
  accuracy: 'high' | 'medium' | 'low';
  performance: 'fast' | 'medium' | 'slow';
  useCase: string;
}

const distanceMethods: DistanceCalculation[] = [
  {
    method: 'postgis_geography',
    accuracy: 'high',
    performance: 'medium',
    useCase: 'Precise distance calculations for proximity search'
  },
  {
    method: 'postgis_geometry',
    accuracy: 'medium',
    performance: 'fast',
    useCase: 'Fast approximate distances for initial filtering'
  },
  {
    method: 'haversine',
    accuracy: 'medium',
    performance: 'fast',
    useCase: 'Application-level distance calculations'
  }
];
```

### Optimized Distance Queries
```sql
-- Two-stage distance calculation for performance
-- Stage 1: Fast bounding box filter
-- Stage 2: Precise distance calculation
WITH nearby_candidates AS (
    SELECT id, title, location
    FROM activities
    WHERE location && ST_Expand(ST_MakePoint(user_lng, user_lat), 0.1) -- ~11km box
      AND status = 'active'
),
precise_distances AS (
    SELECT 
        id,
        title,
        location,
        ST_Distance(location::geography, ST_MakePoint(user_lng, user_lat)::geography) as distance
    FROM nearby_candidates
    WHERE ST_DWithin(
        location::geography,
        ST_MakePoint(user_lng, user_lat)::geography,
        radius_meters
    )
)
SELECT * FROM precise_distances
ORDER BY distance
LIMIT 20;
```

## Search Query Optimization

### Index Optimization Strategy
```sql
-- Optimized spatial indexes for different query patterns
CREATE INDEX idx_activities_location_gist ON activities USING GIST (location);
CREATE INDEX idx_activities_location_status ON activities USING GIST (location) 
    WHERE status = 'active';

-- Composite indexes for filtered searches
CREATE INDEX idx_activities_location_category ON activities (category, start_date) 
    WHERE location IS NOT NULL AND status = 'active';

-- KNN (K-Nearest Neighbor) index for distance ordering
CREATE INDEX idx_activities_location_knn ON activities USING GIST (location gist_geometry_ops_2d);
```

### Query Performance Patterns
```typescript
interface SearchQuery {
  type: 'proximity' | 'bounding_box' | 'route_based' | 'multi_point';
  optimizationStrategy: string;
  expectedPerformance: string;
}

const queryOptimizations = {
  proximity: {
    strategy: 'Use ST_DWithin with geography for accuracy, geometry for speed',
    pattern: 'Bounding box pre-filter + precise distance calculation',
    indexUsage: 'GIST spatial index with KNN ordering'
  },
  
  bounding_box: {
    strategy: 'Use ST_Within with envelope for rectangular areas',
    pattern: 'Single spatial index lookup with additional filters',
    indexUsage: 'GIST spatial index with composite filters'
  },
  
  route_based: {
    strategy: 'Buffer around line geometry for route proximity',
    pattern: 'ST_Buffer + ST_Within for activities along routes',
    indexUsage: 'GIST index with buffered geometry'
  }
};
```

## Advanced Search Features

### Multi-Criteria Spatial Search
```typescript
interface SpatialSearchCriteria {
  location: {
    lat: number;
    lng: number;
  };
  radius?: number;
  bounds?: {
    north: number;
    south: number;
    east: number;
    west: number;
  };
  filters: {
    categories?: string[];
    dateRange?: {
      start: Date;
      end: Date;
    };
    priceRange?: {
      min: number;
      max: number;
    };
    availability?: 'available' | 'full' | 'any';
  };
  sorting: {
    primary: 'distance' | 'date' | 'popularity' | 'price';
    secondary?: 'distance' | 'date' | 'popularity' | 'price';
  };
  pagination: {
    limit: number;
    offset: number;
  };
}

class SpatialSearchService {
  async searchActivities(criteria: SpatialSearchCriteria): Promise<SearchResult> {
    // Build dynamic spatial query based on criteria
    const query = this.buildSpatialQuery(criteria);
    
    // Execute with performance monitoring
    const startTime = Date.now();
    const results = await this.executeQuery(query);
    const executionTime = Date.now() - startTime;
    
    // Log performance metrics
    this.logSearchMetrics(criteria, results.length, executionTime);
    
    return {
      activities: results,
      totalCount: await this.getSearchCount(criteria),
      executionTime,
      searchCriteria: criteria
    };
  }
}
```

### Geographic Clustering
```sql
-- Activity clustering for map display
CREATE OR REPLACE FUNCTION cluster_activities_for_map(
    bounds_north DOUBLE PRECISION,
    bounds_south DOUBLE PRECISION,
    bounds_east DOUBLE PRECISION,
    bounds_west DOUBLE PRECISION,
    zoom_level INTEGER
)
RETURNS TABLE (
    cluster_id INTEGER,
    center_lat DOUBLE PRECISION,
    center_lng DOUBLE PRECISION,
    activity_count INTEGER,
    activities UUID[]
) AS $$
DECLARE
    cluster_distance DOUBLE PRECISION;
BEGIN
    -- Adjust clustering distance based on zoom level
    cluster_distance := CASE 
        WHEN zoom_level >= 15 THEN 100   -- 100m at high zoom
        WHEN zoom_level >= 12 THEN 500   -- 500m at medium zoom
        WHEN zoom_level >= 9 THEN 2000   -- 2km at low zoom
        ELSE 10000                       -- 10km at very low zoom
    END;
    
    RETURN QUERY
    WITH clustered AS (
        SELECT 
            ST_ClusterDBSCAN(location, cluster_distance, 1) OVER() as cluster_id,
            id,
            location
        FROM activities
        WHERE location IS NOT NULL
          AND status = 'active'
          AND ST_Within(
              location,
              ST_MakeEnvelope(bounds_west, bounds_south, bounds_east, bounds_north, 4326)
          )
    )
    SELECT 
        c.cluster_id,
        ST_Y(ST_Centroid(ST_Collect(c.location))) as center_lat,
        ST_X(ST_Centroid(ST_Collect(c.location))) as center_lng,
        COUNT(*)::INTEGER as activity_count,
        array_agg(c.id) as activities
    FROM clustered c
    WHERE c.cluster_id IS NOT NULL
    GROUP BY c.cluster_id;
END;
$$ LANGUAGE plpgsql;
```

## Performance Monitoring and Optimization

### Query Performance Tracking
```typescript
interface SearchMetrics {
  queryType: string;
  executionTime: number;
  resultCount: number;
  indexUsage: string[];
  cacheHit: boolean;
  userLocation: { lat: number; lng: number };
  searchRadius: number;
  timestamp: Date;
}

class SearchPerformanceMonitor {
  async trackSearchQuery(
    query: SpatialSearchCriteria,
    results: SearchResult,
    executionTime: number
  ): Promise<void> {
    const metrics: SearchMetrics = {
      queryType: this.classifyQuery(query),
      executionTime,
      resultCount: results.activities.length,
      indexUsage: await this.analyzeIndexUsage(query),
      cacheHit: results.fromCache || false,
      userLocation: query.location,
      searchRadius: query.radius || 5000,
      timestamp: new Date()
    };
    
    // Store metrics for analysis
    await this.storeMetrics(metrics);
    
    // Alert on performance issues
    if (executionTime > 1000) {
      await this.alertSlowQuery(metrics);
    }
  }
}
```

### Caching Strategy for Spatial Queries
```typescript
interface SpatialQueryCache {
  key: string;
  results: SearchResult;
  location: { lat: number; lng: number };
  radius: number;
  filters: any;
  timestamp: Date;
  ttl: number;
}

class SpatialSearchCache {
  private generateCacheKey(criteria: SpatialSearchCriteria): string {
    // Round location to reduce cache fragmentation
    const lat = Math.round(criteria.location.lat * 1000) / 1000;
    const lng = Math.round(criteria.location.lng * 1000) / 1000;
    const radius = criteria.radius || 5000;
    
    // Include relevant filters in cache key
    const filterHash = this.hashFilters(criteria.filters);
    
    return `spatial:${lat}:${lng}:${radius}:${filterHash}`;
  }
  
  async getCachedResults(criteria: SpatialSearchCriteria): Promise<SearchResult | null> {
    const key = this.generateCacheKey(criteria);
    const cached = await this.redis.get(key);
    
    if (cached) {
      const result = JSON.parse(cached);
      // Check if cache is still valid based on data freshness
      if (this.isCacheValid(result, criteria)) {
        return result;
      }
    }
    
    return null;
  }
}
```

## API Design and Integration

### REST API Endpoints
```typescript
// Proximity search endpoint
GET /api/activities/nearby
Query Parameters:
- lat: number (required)
- lng: number (required)
- radius: number (default: 5000, max: 50000)
- category: string[]
- limit: number (default: 20, max: 100)
- offset: number (default: 0)

// Bounding box search endpoint
GET /api/activities/search
Query Parameters:
- bounds: string (north,south,east,west)
- category: string[]
- dateFrom: ISO date string
- dateTo: ISO date string
- limit: number
- offset: number

// Distance calculation endpoint
POST /api/activities/distances
Body: {
  origin: { lat: number, lng: number },
  destinations: { lat: number, lng: number }[]
}
```

### GraphQL Integration
```graphql
type Query {
  activitiesNearby(
    location: LocationInput!
    radius: Int = 5000
    filters: ActivityFilters
    pagination: PaginationInput
  ): ActivitySearchResult!
  
  activitiesInBounds(
    bounds: BoundsInput!
    filters: ActivityFilters
    pagination: PaginationInput
  ): ActivitySearchResult!
}

type ActivitySearchResult {
  activities: [Activity!]!
  totalCount: Int!
  executionTime: Int!
  hasMore: Boolean!
}

input LocationInput {
  lat: Float!
  lng: Float!
}

input BoundsInput {
  north: Float!
  south: Float!
  east: Float!
  west: Float!
}
```

## Constraints and Assumptions

### Constraints
- Must work within PostGIS spatial database capabilities
- Must maintain sub-second response times for typical searches
- Must scale to support 100k+ activities efficiently
- Must handle concurrent search requests from multiple users
- Must integrate with existing activity data structure

### Assumptions
- User locations are reasonably accurate (within 100m)
- Most searches will be within 10km radius
- Activity locations are properly geocoded and validated
- Spatial indexes are properly maintained and optimized
- Network latency to database is minimal

## Acceptance Criteria

### Must Have
- [ ] Proximity search finds activities within specified radius accurately
- [ ] Distance calculations are accurate within 1% for local searches
- [ ] Search queries execute within 500ms for 10km radius
- [ ] Bounding box searches work efficiently for map views
- [ ] Results are sorted by distance correctly
- [ ] Search performance scales with data growth
- [ ] Spatial indexes are optimized for query patterns

### Should Have
- [ ] Advanced filtering combines location with other criteria
- [ ] Geographic clustering for map display
- [ ] Search result caching improves performance
- [ ] Performance monitoring tracks query efficiency
- [ ] Multi-point search supports complex use cases
- [ ] Search analytics provide usage insights

### Could Have
- [ ] Machine learning-based search result ranking
- [ ] Predictive caching based on user patterns
- [ ] Advanced geographic algorithms (route-based search)
- [ ] Integration with external location services
- [ ] Real-time search result updates

## Risk Assessment

### High Risk
- **Performance Degradation**: Spatial queries could become slow with large datasets
- **Index Maintenance**: Spatial indexes may require ongoing optimization
- **Accuracy Issues**: Distance calculations may be inaccurate in edge cases

### Medium Risk
- **Scalability Limits**: Query performance may not scale linearly
- **Cache Invalidation**: Stale cached results could show outdated activities
- **Complex Query Performance**: Advanced filters may slow down searches

### Low Risk
- **Edge Case Handling**: Unusual geographic scenarios may cause issues
- **API Rate Limiting**: High search volume may require rate limiting

### Mitigation Strategies
- Comprehensive performance testing with large datasets
- Regular spatial index maintenance and optimization
- Monitoring and alerting for query performance issues
- Caching strategies to reduce database load
- Fallback mechanisms for complex query failures

## Dependencies

### Prerequisites
- T01: PostGIS Spatial Database Setup (completed)
- T02: Geocoding and Address Resolution (completed)
- Activity data with properly geocoded locations
- Spatial indexes and database optimization

### Blocks
- T04: Interactive Map Integration (needs search results)
- Activity discovery and recommendation features
- Location-based notification features
- Mobile app "near me" functionality

## Definition of Done

### Technical Completion
- [ ] Proximity search functions are implemented and optimized
- [ ] Distance calculations work accurately for all use cases
- [ ] Bounding box searches support map-based discovery
- [ ] Spatial queries meet performance requirements
- [ ] Search result caching improves response times
- [ ] API endpoints expose search functionality
- [ ] Performance monitoring tracks search metrics

### Integration Completion
- [ ] Search integrates with activity discovery workflows
- [ ] Frontend applications can consume search results
- [ ] Mobile apps support "near me" functionality
- [ ] Map components display search results correctly
- [ ] Search analytics track user behavior

### Quality Completion
- [ ] Search accuracy meets specified requirements
- [ ] Performance benchmarks are consistently achieved
- [ ] Scalability testing validates large dataset performance
- [ ] Error handling covers edge cases and failures
- [ ] User testing confirms search experience quality
- [ ] Documentation covers search API usage

---

**Task**: T03 Proximity Search and Spatial Queries
**Feature**: F03 Geolocation Services
**Epic**: E01 Core Infrastructure
**Estimated Effort**: 3-4 hours
**Priority**: P0 (Critical Path)
**Dependencies**: T01 PostGIS Setup, T02 Geocoding Services
**Status**: Ready for Research Phase
