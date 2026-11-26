<?php

declare(strict_types=1);

// Only what this site changes from lynxcms/static-site's config; the package fills in the rest.
return [
    // Extra addresses to export besides the pages. The resources list needs its cards file in
    // every language of the site (App\Http\Controllers\DownloadCardsController); a test checks
    // that each language has one.
    'paths' => [
        '/sitemap.xml',
        '/robots.txt',
        '/downloads/cards/en.json',
        '/downloads/cards/pt.json',
        '/downloads/cards/es.json',
    ],
];
