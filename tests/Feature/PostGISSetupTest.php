<?php

use Illuminate\Support\Facades\DB;

test('postgis extension is enabled', function () {
    $result = DB::connection('pgsql')->select('SELECT PostGIS_Version()');

    expect($result)->not->toBeEmpty()
        ->and($result[0])->toHaveProperty('postgis_version');
})->skip(fn () => config('database.default') !== 'pgsql', 'PostGIS tests require PostgreSQL');

test('can create and query geography point', function () {
    // Create a test point (San Francisco coordinates)
    $lat = 37.7749;
    $lng = -122.4194;

    $result = DB::connection('pgsql')->select("SELECT ST_AsText(ST_GeographyFromText('POINT($lng $lat)')) as point");

    expect($result)->not->toBeEmpty()
        ->and($result[0]->point)->toBe("POINT($lng $lat)");
})->skip(fn () => config('database.default') !== 'pgsql', 'PostGIS tests require PostgreSQL');

test('can calculate distance between two points', function () {
    // San Francisco and Los Angeles coordinates
    $sf = 'POINT(-122.4194 37.7749)';
    $la = 'POINT(-118.2437 34.0522)';

    $result = DB::connection('pgsql')->select('
        SELECT ST_Distance(
            ST_GeographyFromText(?),
            ST_GeographyFromText(?)
        ) as distance
    ', [$sf, $la]);

    // Distance should be approximately 559 km (559000 meters)
    expect($result)->not->toBeEmpty()
        ->and($result[0]->distance)->toBeGreaterThan(550000)
        ->and($result[0]->distance)->toBeLessThan(570000);
})->skip(fn () => config('database.default') !== 'pgsql', 'PostGIS tests require PostgreSQL');
