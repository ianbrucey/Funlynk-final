<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External event listings ("Around town") — spec 019
    |--------------------------------------------------------------------------
    |
    | Kill switch: when disabled, listings vanish from the feed, map,
    | search and detail pages without a deploy.
    |
    */

    'enabled' => (bool) env('LISTINGS_ENABLED', true),

    /*
    | Maximum listings per 10 items of a feed page (spec 019 [O-01]
    | default: 2 per 10). Owner-tunable.
    */
    'feed_cap' => (int) env('LISTINGS_FEED_CAP', 2),

    /*
    | Who may approve, unpublish and edit listings. Same mechanism as
    | the Horizon and Poster admin gates: an email allowlist.
    */
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('LISTINGS_ADMIN_EMAILS', implode(',', (array) config('admin.emails', []))))
    ))),

];
