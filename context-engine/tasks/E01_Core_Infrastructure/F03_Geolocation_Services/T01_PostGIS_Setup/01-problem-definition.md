# T01: PostGIS Spatial Database Setup - Problem Definition

## Problem Statement

We need to configure and optimize PostGIS spatial database capabilities within our Supabase PostgreSQL instance to enable location-based features for the Funlynk platform. This includes setting up spatial data types, indexes, and query optimization for efficient geographic operations.

## Context

### Current State
- Supabase PostgreSQL database is configured (F01 completed)
- Standard relational database schema is implemented
- No spatial database capabilities are available
- Location data cannot be stored or queried efficiently
- No geographic indexing or spatial query optimization

### Desired State
- PostGIS extension is installed and configured in Supabase
- Spatial data types are available for storing geographic coordinates
- Spatial indexes are optimized for location-based queries
- Geographic coordinate systems are properly configured
- Spatial query performance is optimized for the platform's needs

## Business Impact

### Why This Matters
- **Core Platform Feature**: Location is fundamental to Funlynk's value proposition
- **User Experience**: Fast location-based activity discovery depends on spatial queries
- **Scalability**: Efficient spatial indexing supports platform growth
- **Performance**: Optimized spatial queries enable real-time location features
- **Foundation**: Required for all location-based features across the platform

### Success Metrics
- Spatial queries execute in <100ms for 10km radius searches
- Database supports 100k+ activities with location data
- Spatial index performance scales linearly with data growth
- Zero spatial query errors or data corruption
- PostGIS configuration supports all planned location features

## Technical Requirements

### Functional Requirements
- **PostGIS Extension**: Install and configure PostGIS in Supabase PostgreSQL
- **Spatial Data Types**: Configure GEOMETRY and GEOGRAPHY data types
- **Coordinate Systems**: Set up WGS84 (EPSG:4326) coordinate reference system
- **Spatial Indexes**: Create and optimize GIST spatial indexes
- **Query Functions**: Enable spatial query functions and operators
- **Performance Tuning**: Optimize spatial query performance and memory usage

### Non-Functional Requirements
- **Performance**: Spatial queries must execute within 100ms for typical use cases
- **Scalability**: Support for millions of spatial records with consistent performance
- **Reliability**: 99.9% uptime for spatial database operations
- **Accuracy**: Maintain coordinate precision to within 1 meter
- **Compatibility**: Work seamlessly with Supabase infrastructure
- **Security**: Spatial data access follows existing RLS policies

## PostGIS Configuration Requirements

### Extension Installation
```sql
-- Enable PostGIS extensions
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;
CREATE EXTENSION IF NOT EXISTS postgis_sfcgal;
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder;

-- Verify installation
SELECT PostGIS_Version();
SELECT PostGIS_Full_Version();
```

### Spatial Data Types Setup
```sql
-- Configure spatial columns for activities
ALTER TABLE activities ADD COLUMN location GEOMETRY(POINT, 4326);
ALTER TABLE activities ADD COLUMN service_area GEOMETRY(POLYGON, 4326);

-- Configure spatial columns for users
ALTER TABLE users ADD COLUMN last_known_location GEOMETRY(POINT, 4326);
ALTER TABLE user_profiles ADD COLUMN preferred_location GEOMETRY(POINT, 4326);

-- Configure spatial columns for venues
ALTER TABLE venues ADD COLUMN location GEOMETRY(POINT, 4326);
ALTER TABLE venues ADD COLUMN boundary GEOMETRY(POLYGON, 4326);
```

### Spatial Indexing Strategy
```sql
-- Create spatial indexes for optimal query performance
CREATE INDEX idx_activities_location ON activities USING GIST (location);
CREATE INDEX idx_activities_service_area ON activities USING GIST (service_area);
CREATE INDEX idx_users_last_location ON users USING GIST (last_known_location);
CREATE INDEX idx_user_profiles_preferred_location ON user_profiles USING GIST (preferred_location);
CREATE INDEX idx_venues_location ON venues USING GIST (location);
CREATE INDEX idx_venues_boundary ON venues USING GIST (boundary);

-- Create composite indexes for common query patterns
CREATE INDEX idx_activities_location_status ON activities USING GIST (location) WHERE status = 'active';
CREATE INDEX idx_activities_location_date ON activities (start_date) WHERE location IS NOT NULL;
```

## Coordinate Reference System

### WGS84 Configuration
```sql
-- Verify WGS84 (EPSG:4326) is available
SELECT * FROM spatial_ref_sys WHERE srid = 4326;

-- Set default SRID for geometry columns
ALTER TABLE activities ALTER COLUMN location TYPE GEOMETRY(POINT, 4326);
ALTER TABLE users ALTER COLUMN last_known_location TYPE GEOMETRY(POINT, 4326);

-- Create constraint to enforce SRID
ALTER TABLE activities ADD CONSTRAINT enforce_srid_location CHECK (ST_SRID(location) = 4326);
ALTER TABLE users ADD CONSTRAINT enforce_srid_location CHECK (ST_SRID(last_known_location) = 4326);
```

### Spatial Query Functions
```sql
-- Common spatial query functions to enable
-- Distance calculations
SELECT ST_Distance(location1, location2) FROM activities;

-- Proximity searches
SELECT * FROM activities WHERE ST_DWithin(location, ST_MakePoint(-122.4194, 37.7749), 1000);

-- Bounding box searches
SELECT * FROM activities WHERE location && ST_MakeEnvelope(-122.5, 37.7, -122.3, 37.8, 4326);

-- Area calculations
SELECT ST_Area(service_area) FROM activities WHERE service_area IS NOT NULL;
```

## Performance Optimization

### Query Performance Tuning
```sql
-- Configure PostGIS for optimal performance
SET work_mem = '256MB';
SET shared_buffers = '1GB';
SET effective_cache_size = '4GB';

-- Enable parallel query execution for spatial operations
SET max_parallel_workers_per_gather = 4;
SET parallel_tuple_cost = 0.1;
SET parallel_setup_cost = 1000.0;

-- Optimize GIST index parameters
CREATE INDEX idx_activities_location_optimized ON activities 
USING GIST (location) WITH (fillfactor = 90, buffering = on);
```

### Memory and Storage Configuration
```sql
-- Configure spatial memory settings
SET postgis.gdal_enabled_drivers = 'ENABLE_ALL';
SET postgis.enable_outdb_rasters = true;

-- Set up spatial statistics for query planning
ANALYZE activities;
ANALYZE users;
ANALYZE venues;

-- Update spatial statistics
SELECT UpdateGeometrySRID('activities', 'location', 4326);
SELECT UpdateGeometrySRID('users', 'last_known_location', 4326);
```

## Data Validation and Constraints

### Spatial Data Validation
```sql
-- Validate geometry data integrity
CREATE OR REPLACE FUNCTION validate_geometry_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Ensure geometry is valid
    IF NEW.location IS NOT NULL AND NOT ST_IsValid(NEW.location) THEN
        RAISE EXCEPTION 'Invalid geometry data';
    END IF;
    
    -- Ensure coordinates are within reasonable bounds
    IF NEW.location IS NOT NULL THEN
        IF ST_X(NEW.location) < -180 OR ST_X(NEW.location) > 180 THEN
            RAISE EXCEPTION 'Longitude out of bounds';
        END IF;
        IF ST_Y(NEW.location) < -90 OR ST_Y(NEW.location) > 90 THEN
            RAISE EXCEPTION 'Latitude out of bounds';
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply validation triggers
CREATE TRIGGER validate_activity_location
    BEFORE INSERT OR UPDATE ON activities
    FOR EACH ROW EXECUTE FUNCTION validate_geometry_data();

CREATE TRIGGER validate_user_location
    BEFORE INSERT OR UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION validate_geometry_data();
```

## Supabase Integration

### Supabase PostGIS Configuration
```sql
-- Enable PostGIS in Supabase (via SQL Editor or CLI)
-- Note: Some extensions may require Supabase Pro plan

-- Verify Supabase PostGIS support
SELECT name, default_version, installed_version 
FROM pg_available_extensions 
WHERE name LIKE 'postgis%';

-- Configure Supabase RLS for spatial data
CREATE POLICY "Users can read public activity locations" ON activities
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        visibility = 'public' AND 
        location IS NOT NULL
    );

CREATE POLICY "Users can update their own location" ON users
    FOR UPDATE USING (auth.uid() = id);
```

### API Integration Preparation
```typescript
// TypeScript types for spatial data
interface Location {
  type: 'Point';
  coordinates: [number, number]; // [longitude, latitude]
}

interface Activity {
  id: string;
  title: string;
  location: Location;
  // ... other fields
}

// Supabase client spatial query examples
const nearbyActivities = await supabase
  .from('activities')
  .select('*')
  .filter('location', 'not.is', null)
  .rpc('activities_within_radius', {
    lat: userLat,
    lng: userLng,
    radius_meters: 5000
  });
```

## Constraints and Assumptions

### Constraints
- Must work within Supabase PostgreSQL environment
- Must maintain compatibility with existing database schema
- Must support RLS policies for spatial data
- Must handle coordinate precision requirements
- Must scale to support planned user and activity volumes

### Assumptions
- Supabase supports PostGIS extensions (may require Pro plan)
- WGS84 coordinate system is sufficient for global usage
- GIST indexes provide adequate performance for spatial queries
- PostgreSQL version supports all required PostGIS features
- Network latency to Supabase is acceptable for spatial queries

## Acceptance Criteria

### Must Have
- [ ] PostGIS extension is installed and functional in Supabase
- [ ] Spatial data types are configured for all location-related tables
- [ ] Spatial indexes are created and optimized for query performance
- [ ] WGS84 coordinate reference system is properly configured
- [ ] Basic spatial query functions work correctly
- [ ] Data validation ensures spatial data integrity
- [ ] RLS policies work with spatial data

### Should Have
- [ ] Query performance meets specified benchmarks (<100ms)
- [ ] Spatial statistics are configured for query optimization
- [ ] Memory and storage settings are optimized
- [ ] Error handling for invalid spatial data
- [ ] Documentation for spatial query patterns
- [ ] Monitoring for spatial query performance

### Could Have
- [ ] Advanced PostGIS extensions for specialized use cases
- [ ] Automated spatial data quality checks
- [ ] Performance monitoring dashboard for spatial queries
- [ ] Backup and recovery procedures for spatial data
- [ ] Integration with external GIS tools

## Risk Assessment

### High Risk
- **Supabase Limitations**: PostGIS may not be fully supported or may require plan upgrade
- **Performance Issues**: Spatial queries could be slower than expected
- **Data Corruption**: Invalid spatial data could cause query failures

### Medium Risk
- **Index Optimization**: Spatial indexes may need tuning for optimal performance
- **Memory Usage**: Spatial operations could consume excessive memory
- **Compatibility Issues**: PostGIS version compatibility with Supabase

### Low Risk
- **Configuration Complexity**: PostGIS setup may be more complex than anticipated
- **Documentation Gaps**: Limited documentation for Supabase PostGIS integration

### Mitigation Strategies
- Verify PostGIS support with Supabase before implementation
- Implement comprehensive testing of spatial query performance
- Create robust data validation to prevent spatial data corruption
- Plan for alternative spatial solutions if Supabase limitations are discovered
- Monitor spatial query performance and optimize indexes as needed

## Dependencies

### Prerequisites
- F01: Database Foundation (Supabase PostgreSQL setup)
- Supabase account with PostGIS extension support
- Database migration system for spatial schema changes
- Understanding of geographic coordinate systems

### Blocks
- T02: Geocoding and Address Resolution (requires spatial data types)
- T03: Proximity Search and Spatial Queries (requires spatial indexes)
- All location-based features across the platform

## Definition of Done

### Technical Completion
- [ ] PostGIS extension is installed and configured in Supabase
- [ ] All spatial data types are properly configured
- [ ] Spatial indexes are created and optimized
- [ ] Coordinate reference system is set up correctly
- [ ] Data validation functions are implemented and active
- [ ] Basic spatial queries execute successfully
- [ ] Performance benchmarks are met

### Integration Completion
- [ ] Spatial data integrates with existing RLS policies
- [ ] API endpoints can handle spatial data types
- [ ] Frontend applications can consume spatial data
- [ ] Migration scripts handle spatial schema changes
- [ ] Backup and recovery includes spatial data

### Quality Completion
- [ ] Spatial query performance meets requirements
- [ ] Data integrity validation works correctly
- [ ] Error handling covers spatial operation failures
- [ ] Documentation covers spatial database usage
- [ ] Testing validates all spatial functionality
- [ ] Monitoring tracks spatial query performance

---

**Task**: T01 PostGIS Spatial Database Setup
**Feature**: F03 Geolocation Services
**Epic**: E01 Core Infrastructure
**Estimated Effort**: 2-3 hours
**Priority**: P0 (Critical Path)
**Dependencies**: F01 Database Foundation
**Status**: Ready for Research Phase
