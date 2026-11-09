<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin accounts (email allowlist)
    |--------------------------------------------------------------------------
    |
    | FunLynk has no is_admin flag. Every admin surface checks this one
    | allowlist: the Filament panel, Horizon, the Poster connect routes
    | and listings approval. Override with APP_ADMIN_EMAILS
    | (comma-separated) in the environment. The per-feature env values
    | POSTER_ADMIN_EMAILS and LISTINGS_ADMIN_EMAILS still win for their
    | own feature when set.
    |
    */

    'emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_ADMIN_EMAILS', 'funlynk_admin@funlynk.com,ian.b@justicequest.pro'))
    ))),

];
