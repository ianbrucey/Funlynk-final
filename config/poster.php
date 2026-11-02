<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Drive intake
    |--------------------------------------------------------------------------
    | The front door: Ian drops a flyer image and/or video plus a caption
    | text file into the "FunLink Posts" folder; `poster:intake` turns the
    | drop into a draft bundle. The CLI client is the workspace's
    | hatch_gws_cli (authenticated on the Lotus VM); DRIVE_CLI_BIN points
    | at it wherever intake runs.
    */
    'drive' => [
        'cli' => env('DRIVE_CLI_BIN', '/opt/hatch/bin/hatch_gws_cli'),
        'posts_folder_id' => env('DRIVE_POSTS_FOLDER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public asset base URL
    |--------------------------------------------------------------------------
    | Instagram, Threads, and TikTok fetch media from a public URL — there
    | is no binary upload for those feeds. Normalized assets must be served
    | from this base URL in Phase 2.
    */
    'asset_base_url' => env('POSTER_ASSET_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Platform credentials (app-level, from .env — never committed)
    |--------------------------------------------------------------------------
    | Per-account tokens do NOT live here; they are stored (encrypted) on
    | platform_accounts once an account is connected. These are the
    | developer-app credentials and fixed target ids adapters need.
    */
    'credentials' => [
        'facebook_page_id' => env('FACEBOOK_PAGE_ID'),
        'meta_app_id' => env('META_APP_ID'),
        'meta_app_secret' => env('META_APP_SECRET'),
        'instagram_user_id' => env('INSTAGRAM_USER_ID'),
        'threads_user_id' => env('THREADS_USER_ID'),
        'tiktok_client_key' => env('TIKTOK_CLIENT_KEY'),
        'tiktok_client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'x_client_id' => env('X_CLIENT_ID'),
        'x_client_secret' => env('X_CLIENT_SECRET'),
        'youtube_client_id' => env('YOUTUBE_CLIENT_ID'),
        'youtube_client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'youtube_channel_id' => env('YOUTUBE_CHANNEL_ID'),
    ],

];
