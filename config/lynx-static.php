<?php

declare(strict_types=1);

// Only what this site changes from lynxcms/static-site's config; the package fills in the rest.
return [
    // Extra addresses to export besides the pages. The resources list and My bookmarks need their
    // cards files in every language of the site (DownloadCardsController, BookmarkCardsFileController);
    // a test checks that each language has them.
    'paths' => [
        '/sitemap.xml',
        '/robots.txt',
        '/downloads/cards/en.json',
        '/downloads/cards/pt.json',
        '/downloads/cards/es.json',
        '/bookmarks/cards/en.json',
        '/bookmarks/cards/pt.json',
        '/bookmarks/cards/es.json',
    ],
];
