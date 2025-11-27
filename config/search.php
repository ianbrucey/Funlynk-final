<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default search driver that will be used for
    | searching posts and events. You may set this to "database" to use
    | PostgreSQL full-text search, or "meilisearch" to use Meilisearch.
    |
    | Supported: "database", "meilisearch"
    |
    */

    'driver' => env('SEARCH_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Search Limits
    |--------------------------------------------------------------------------
    |
    | These options control the maximum number of results returned for each
    | content type when searching.
    |
    */

    'limits' => [
        'posts' => 50,
        'events' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Radius
    |--------------------------------------------------------------------------
    |
    | The default search radius in kilometers when geo-filtering is enabled
    | but no specific radius is provided.
    |
    */

    'default_radius' => 25,

];

