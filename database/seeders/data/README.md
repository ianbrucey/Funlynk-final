# Location Data Seeding

This directory contains the CSV data file used to seed the `locations` table with US city data.

## Overview

The FunLynk application uses a local database of US cities and their coordinates instead of relying solely on external APIs like Google Places. This provides:

- **Reliability**: No dependency on external API availability
- **Performance**: Faster lookups without API calls
- **Cost Savings**: No per-request API charges
- **Offline Capability**: Works without internet connection

## Files

- `locations.csv` - Contains 41,755+ US cities with coordinates (generated from SQL files)

## Data Structure

Each location record contains:

| Column       | Type   | Description                          |
|-------------|--------|--------------------------------------|
| id          | int    | Unique identifier                    |
| city        | string | City name                            |
| state_code  | string | 2-letter state code (e.g., "CA")    |
| state       | string | Full state name (e.g., "California")|
| zip         | string | ZIP code                             |
| latitude    | float  | Latitude coordinate                  |
| longitude   | float  | Longitude coordinate                 |
| county      | string | County name                          |
| timezone    | string | Timezone (optional)                  |

## Usage

### Initial Setup

1. **Convert SQL to CSV** (if CSV doesn't exist):
   ```bash
   python3 scripts/convert_sql_to_csv.py
   ```

2. **Run the seeder**:
   ```bash
   php artisan db:seed --class=LocationSeeder
   ```

### Deployment

The location seeder is automatically run during deployment via `deploy.sh`:

```bash
./deploy.sh
```

## Source Data

The original data came from a prototype application and was stored in MySQL SQL files:

- `public/sql/locations.sql` (6,116 records)
- `public/sql/locations1.sql` (5,892 records)
- `public/sql/locations2.sql` (6,270 records)
- `public/sql/locations3.sql` (6,944 records)
- `public/sql/locations4.sql` (6,253 records)
- `public/sql/locations5.sql` (6,404 records)
- `public/sql/locations6.sql` (3,876 records)

**Total: 41,755 locations**

## Performance

The `LocationSeeder` uses batch inserts (1,000 records per batch) for optimal performance:

- **Seeding time**: ~2-5 seconds for 41,755 records
- **Database size**: ~5-10 MB

## Regenerating CSV

If you need to regenerate the CSV from the SQL files:

```bash
python3 scripts/convert_sql_to_csv.py
```

This will:
1. Read all `public/sql/locations*.sql` files
2. Extract INSERT statements
3. Convert to CSV format
4. Save to `database/seeders/data/locations.csv`

## Model Usage

The `Location` model provides helper methods:

```php
// Find location by ZIP code or city/state
$location = Location::findLocation([
    'zip' => '90210',
    'city' => 'Beverly Hills',
    'state' => 'CA'
]);

// Get default location (ID: 30301)
$defaultId = Location::defaultLocationId();
```

## Search Integration

Locations are indexed in Meilisearch with geospatial data:

```php
// Locations are searchable with geo coordinates
$results = Location::search('Los Angeles')->get();
```

## Notes

- The seeder checks if locations are already seeded (>41,000 records) and skips if so
- PostgreSQL sequence is automatically updated after seeding
- The CSV approach is database-agnostic (works with MySQL, PostgreSQL, SQLite)
- Original SQL files are preserved in `public/sql/` for reference

