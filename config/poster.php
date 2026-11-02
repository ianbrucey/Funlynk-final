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
    | Meta app (Facebook Page + Instagram) — Phase 2
    |--------------------------------------------------------------------------
    | The "Funlink Poster" developer app. The app id is a public identifier;
    | the app secret comes only from the environment (Laravel Cloud env
    | vars in production) and is never committed. The redirect URI must
    | match the URI registered on the Meta app.
    */
    'meta' => [
        'app_id' => env('POSTER_META_APP_ID', '1420149999776735'),
        'app_secret' => env('POSTER_META_APP_SECRET'),
        'page_id' => env('POSTER_META_PAGE_ID', '61563258179586'),
        'graph_version' => env('POSTER_META_GRAPH_VERSION', 'v21.0'),
        'redirect_uri' => env('POSTER_META_REDIRECT_URI', 'https://funlynk.com/poster/connect/meta/callback'),
        'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'instagram_basic', 'instagram_content_publish'],
    ],

    /*
    |--------------------------------------------------------------------------
    | TikTok app — Phase 2
    |--------------------------------------------------------------------------
    | The "Funlink Poster" TikTok developer app (scopes user.info.basic +
    | video.upload — upload to the account's drafts/inbox, not direct
    | post). The client key is a public identifier; the client secret is
    | environment-only and never committed.
    */
    'tiktok' => [
        'client_key' => env('POSTER_TIKTOK_CLIENT_KEY', 'aw1a16e8hrl8w5a7'),
        'client_secret' => env('POSTER_TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('POSTER_TIKTOK_REDIRECT_URI', 'https://funlynk.com/poster/connect/tiktok/callback'),
        'scopes' => ['user.info.basic', 'video.upload'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Poster admin allowlist
    |--------------------------------------------------------------------------
    | Who may use the /poster/connect/* OAuth routes. Funlynk has no
    | is_admin flag; the app's existing admin pattern is the email
    | allowlist gate used for Horizon
    | (App\Providers\HorizonServiceProvider::gate). The Poster module
    | reuses that exact mechanism — a Gate backed by this allowlist —
    | defaulting to the same addresses as the Horizon gate, overridable
    | via POSTER_ADMIN_EMAILS (comma-separated) in the environment.
    */
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('POSTER_ADMIN_EMAILS', 'ian@yopmail.com,brucey@yopmail.com'))
    ))),

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
