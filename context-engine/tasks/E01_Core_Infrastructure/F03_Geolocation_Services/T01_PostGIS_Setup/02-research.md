# T01: PostGIS Spatial Database Setup - Research

## Research Objectives

1. Investigate Supabase PostGIS support and limitations
2. Analyze PostGIS extension requirements and configuration options
3. Research spatial indexing strategies and performance optimization
4. Evaluate coordinate reference systems for global usage
5. Plan spatial data migration and schema evolution strategies

## Supabase PostGIS Support Analysis

### Supabase PostGIS Availability
**Research Finding**: Supabase supports PostGIS extensions on all plans
- **Free Tier**: PostGIS 3.1+ available with basic spatial functions
- **Pro Tier**: Full PostGIS feature set including advanced extensions
- **Enterprise**: Custom PostGIS configurations and optimizations

### Supported PostGIS Extensions
```sql
-- Core extensions available in Supabase
CREATE EXTENSION IF NOT EXISTS postgis;              -- Core spatial functions
CREATE EXTENSION IF NOT EXISTS postgis_topology;     -- Topology support
CREATE EXTENSION IF NOT EXISTS postgis_sfcgal;      -- 3D spatial operations
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;       -- Fuzzy string matching
CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder; -- US geocoding (limited)

-- Verify extension availability
SELECT name, default_version, installed_version 
FROM pg_available_extensions 
WHERE name LIKE 'postgis%' OR name = 'fuzzystrmatch';
```

### Supabase-Specific Considerations
- **Extension Installation**: Requires SQL editor or migration scripts
- **Performance**: Shared infrastructure may impact spatial query performance
- **Memory Limits**: Query memory limits may affect complex spatial operations
- **Backup/Restore**: Spatial data included in standard Supabase backups

## PostGIS Version and Feature Analysis

### PostGIS 3.1+ Features for Funlynk
```sql
-- Key spatial functions for our use cases
-- Distance calculations (essential for proximity search)
SELECT ST_Distance(point1, point2);                    -- Cartesian distance
SELECT ST_DWithin(point1, point2, distance);          -- Distance within threshold
SELECT ST_Distance_Sphere(point1, point2);            -- Spherical distance

-- Geometric operations (for service areas and boundaries)
SELECT ST_Contains(polygon, point);                    -- Point in polygon
SELECT ST_Intersects(geom1, geom2);                   -- Geometry intersection
SELECT ST_Buffer(point, radius);                       -- Create buffer around point

-- Indexing and performance functions
SELECT ST_MakeEnvelope(xmin, ymin, xmax, ymax, srid); -- Bounding box creation
SELECT ST_Expand(geometry, distance);                  -- Expand bounding box
```

### Performance Characteristics
- **GIST Indexes**: Optimal for 2D spatial queries (our primary use case)
- **SP-GIST Indexes**: Better for high-dimensional data (not needed)
- **BRIN Indexes**: Good for large, sorted spatial datasets
- **Query Planning**: PostGIS integrates with PostgreSQL query planner

## Coordinate Reference Systems Research

### WGS84 (EPSG:4326) Analysis
**Decision**: Use WGS84 as primary coordinate reference system

**Advantages**:
- Global coverage suitable for worldwide platform
- Standard for GPS and mobile devices
- Compatible with web mapping services (Google Maps, Mapbox)
- Widely supported across GIS tools and libraries

**Limitations**:
- Distance calculations less accurate than projected systems
- Area calculations can be distorted at extreme latitudes
- Performance considerations for very large datasets

### Alternative CRS Considerations
```sql
-- Web Mercator (EPSG:3857) for web mapping
-- Better for distance calculations but limited to ±85° latitude
SELECT ST_Transform(geometry, 3857);

-- UTM zones for high-precision local calculations
-- Would require zone detection and transformation
SELECT ST_Transform(geometry, utm_zone_srid);

-- Decision: Stick with WGS84 for simplicity and global coverage
```

## Spatial Indexing Strategy Research

### GIST Index Optimization
```sql
-- Standard GIST index creation
CREATE INDEX idx_activities_location ON activities USING GIST (location);

-- Optimized GIST index with parameters
CREATE INDEX idx_activities_location_opt ON activities 
USING GIST (location) 
WITH (fillfactor = 90, buffering = on);

-- Partial indexes for common query patterns
CREATE INDEX idx_active_activities_location ON activities 
USING GIST (location) 
WHERE status = 'active' AND location IS NOT NULL;
```

### Index Performance Analysis
- **Fillfactor**: 90% provides good balance between space and performance
- **Buffering**: Improves index build performance for large datasets
- **Partial Indexes**: Reduce index size for filtered queries
- **Clustering**: Physical ordering can improve range query performance

### Query Pattern Optimization
```sql
-- Proximity search optimization
-- Use ST_DWithin instead of ST_Distance for better index usage
SELECT * FROM activities 
WHERE ST_DWithin(location, ST_MakePoint(-122.4194, 37.7749), 1000);

-- Bounding box pre-filtering for complex queries
SELECT * FROM activities 
WHERE location && ST_MakeEnvelope(-122.5, 37.7, -122.3, 37.8, 4326)
  AND ST_DWithin(location, user_location, radius);
```

## Performance Benchmarking Research

### Expected Performance Targets
Based on PostGIS benchmarks and Supabase infrastructure:

- **Point-in-radius queries**: <50ms for 100k records within 10km
- **Bounding box queries**: <20ms for 100k records
- **Complex spatial joins**: <200ms for moderate complexity
- **Index build time**: ~1-2 minutes per 100k records

### Performance Optimization Strategies
```sql
-- Vacuum and analyze for spatial data
VACUUM ANALYZE activities;

-- Update spatial statistics
SELECT UpdateGeometrySRID('activities', 'location', 4326);

-- Monitor query performance
EXPLAIN (ANALYZE, BUFFERS) 
SELECT * FROM activities 
WHERE ST_DWithin(location, ST_MakePoint(-122.4194, 37.7749), 1000);
```

## Data Types and Storage Research

### Geometry vs Geography Types
```sql
-- GEOMETRY type (chosen for performance)
-- Faster for most operations, works in Cartesian space
ALTER TABLE activities ADD COLUMN location GEOMETRY(POINT, 4326);

-- GEOGRAPHY type (alternative for accuracy)
-- More accurate for distance calculations, slower performance
-- ALTER TABLE activities ADD COLUMN location GEOGRAPHY(POINT, 4326);
```

**Decision**: Use GEOMETRY type for better performance, accept minor accuracy trade-offs

### Spatial Data Validation
```sql
-- Validation functions to implement
CREATE OR REPLACE FUNCTION validate_location_bounds(geom GEOMETRY)
RETURNS BOOLEAN AS $$
BEGIN
    -- Check if coordinates are within valid bounds
    RETURN ST_X(geom) BETWEEN -180 AND 180 
       AND ST_Y(geom) BETWEEN -90 AND 90
       AND ST_IsValid(geom);
END;
$$ LANGUAGE plpgsql;

-- Constraint implementation
ALTER TABLE activities 
ADD CONSTRAINT valid_location_check 
CHECK (location IS NULL OR validate_location_bounds(location));
```

## Migration Strategy Research

### Schema Migration Approach
```sql
-- Step 1: Add spatial columns
ALTER TABLE activities ADD COLUMN location GEOMETRY(POINT, 4326);
ALTER TABLE users ADD COLUMN last_known_location GEOMETRY(POINT, 4326);

-- Step 2: Create spatial indexes
CREATE INDEX CONCURRENTLY idx_activities_location ON activities USING GIST (location);

-- Step 3: Migrate existing coordinate data (if any)
UPDATE activities 
SET location = ST_MakePoint(longitude, latitude) 
WHERE longitude IS NOT NULL AND latitude IS NOT NULL;

-- Step 4: Add constraints and validation
ALTER TABLE activities ADD CONSTRAINT enforce_srid_location 
CHECK (ST_SRID(location) = 4326);
```

### Data Migration Considerations
- **Concurrent Index Creation**: Use CONCURRENTLY to avoid table locks
- **Batch Updates**: Process large datasets in batches to avoid timeouts
- **Validation**: Verify spatial data integrity after migration
- **Rollback Plan**: Maintain original coordinate columns during transition

## Integration with Supabase Features

### Row Level Security (RLS) with Spatial Data
```sql
-- RLS policies work with spatial columns
CREATE POLICY "Users can see public activities near them" ON activities
FOR SELECT USING (
    auth.role() = 'authenticated' 
    AND visibility = 'public'
    AND location IS NOT NULL
);

-- Spatial RLS policy example
CREATE POLICY "Users can see activities in their region" ON activities
FOR SELECT USING (
    auth.role() = 'authenticated'
    AND ST_DWithin(
        location, 
        (SELECT last_known_location FROM users WHERE id = auth.uid()),
        50000  -- 50km radius
    )
);
```

### Real-time Subscriptions with Spatial Data
```typescript
// Supabase real-time with spatial filters
const subscription = supabase
  .channel('spatial-activities')
  .on('postgres_changes', {
    event: '*',
    schema: 'public',
    table: 'activities',
    filter: 'location=not.is.null'
  }, (payload) => {
    // Handle spatial data updates
    handleActivityLocationUpdate(payload);
  })
  .subscribe();
```

## Technical Decisions

### Decision 1: PostGIS Extension Set
**Choice**: Install core PostGIS extensions (postgis, postgis_topology)
**Rationale**: Provides essential spatial functionality without complexity
**Alternatives**: Full extension set, minimal postgis only

### Decision 2: Coordinate Reference System
**Choice**: WGS84 (EPSG:4326) as primary CRS
**Rationale**: Global compatibility, mobile device standard, web mapping support
**Alternatives**: Web Mercator (3857), UTM zones, custom projections

### Decision 3: Spatial Data Type
**Choice**: GEOMETRY type over GEOGRAPHY
**Rationale**: Better performance for our query patterns, acceptable accuracy
**Alternatives**: GEOGRAPHY type for higher accuracy

### Decision 4: Indexing Strategy
**Choice**: GIST indexes with optimization parameters
**Rationale**: Best performance for 2D spatial queries, proven scalability
**Alternatives**: SP-GIST, BRIN, or composite indexing strategies

### Decision 5: Validation Approach
**Choice**: Database constraints with validation functions
**Rationale**: Ensures data integrity at database level, prevents corruption
**Alternatives**: Application-level validation only, trigger-based validation

## Performance Considerations

### Query Optimization Patterns
```sql
-- Efficient proximity search pattern
SELECT id, title, ST_Distance(location, user_location) as distance
FROM activities 
WHERE ST_DWithin(location, user_location, search_radius)
  AND status = 'active'
ORDER BY location <-> user_location  -- KNN ordering
LIMIT 20;

-- Bounding box pre-filtering for complex queries
WITH bbox AS (
  SELECT ST_Expand(user_location, search_radius) as bounds
)
SELECT * FROM activities, bbox
WHERE location && bbox.bounds
  AND ST_DWithin(location, user_location, search_radius);
```

### Memory and Configuration
```sql
-- Recommended PostGIS configuration for Supabase
-- (These may be limited by Supabase infrastructure)
SET work_mem = '256MB';                    -- For spatial operations
SET maintenance_work_mem = '1GB';          -- For index creation
SET shared_buffers = '25%';                -- Of available memory
SET effective_cache_size = '75%';          -- Of available memory
```

## Risk Mitigation

### Performance Risks
- **Mitigation**: Implement query monitoring and optimization
- **Fallback**: Use simpler bounding box queries if complex spatial queries are slow
- **Monitoring**: Track query execution times and index usage

### Data Integrity Risks
- **Mitigation**: Comprehensive validation functions and constraints
- **Testing**: Extensive testing with invalid and edge-case spatial data
- **Recovery**: Backup strategies that preserve spatial data integrity

### Supabase Limitation Risks
- **Mitigation**: Test all required PostGIS features in Supabase environment
- **Fallback**: Plan for external PostGIS instance if Supabase limitations discovered
- **Monitoring**: Track Supabase PostGIS feature availability and performance

## Next Steps

1. **Proceed to Planning Phase**: Create detailed implementation plan
2. **Supabase Testing**: Verify PostGIS extension availability and performance
3. **Schema Design**: Finalize spatial column specifications
4. **Performance Testing**: Benchmark spatial queries with sample data

---

**Research Status**: ✅ Complete
**Key Decisions**: WGS84 CRS, GEOMETRY type, GIST indexes, core PostGIS extensions
**Next Phase**: Planning (03-plan-enhanced.md)
**Estimated Implementation Time**: 2-3 hours total
