<?php

declare(strict_types=1);

return [
    // Password of the owner account the seeders make; set it in .env.
    'owner_password' => env('OWNER_PASSWORD'),

    // Draw a picture for each demo page while seeding. Tests turn it off: it is most of the seed's time.
    'artwork' => (bool) env('SITE_ARTWORK', true),

    // Search results visitors open count towards the search panel's suggestions; a visit loses
    // half its weight every half-life (App\Search\Popularity). Scores are relative to the epoch.
    'popularity' => [
        'half_life_days' => (int) env('SITE_POPULARITY_HALF_LIFE_DAYS', 7),
        'epoch' => '2026-01-01',
    ],

    // How the search panel shows results as visitors type: "javascript" (resources/js/search.js) or
    // "livewire" (the live-search single-file component, in a Lynx island so pages stay in the page cache).
    'search_panel' => env('SITE_SEARCH_PANEL', 'javascript'),

    // For the public demo only, where anyone may sign in to the admin: every hour the site goes back
    // to the seeded content and drops uploads (site:reset-demo), and the admin's sidebar says so.
    'demo_reset' => (bool) env('SITE_DEMO_RESET', false),

    // Most pages a visitor's bookmark list holds.
    'bookmarks_max' => 50,
];
