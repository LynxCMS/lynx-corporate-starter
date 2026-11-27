<?php

declare(strict_types=1);

/*
 * Visitor counts (app/Stats): page views, downloads and the site's own events, as daily totals with
 * no personal data. See app/Stats/README.md.
 */

return [
    // The database connection of the stats tables; the app's default when empty. Pointing it at a
    // second SQLite file keeps stats writes apart from the content database.
    'connection' => env('STATS_DB_CONNECTION'),

    // Popular sorts weigh each day's count by half every this many days.
    'half_life_days' => (int) env('STATS_HALF_LIFE_DAYS', 7),

    // The site's own events besides "view" and "download", counted by the beacon from elements with
    // data-stats-event="name". Names left out of this list are ignored.
    'events' => ['hero-cta'],

    // Requests carrying this cookie come from signed-in editors and aren't counted (Lynx's page
    // cache sets it on sign in).
    'skip_cookie' => 'lynx_bypass',

    // Hits recorded per minute from one address; past it, the beacon still answers but records nothing.
    'hits_per_minute' => 120,
];
