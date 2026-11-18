<?php

declare(strict_types=1);

// Gated downloads: how long the links to a document's file work.
return [
    // The link the form hands back, which starts the download at once.
    'page_link_hours' => (int) env('DOWNLOADS_PAGE_LINK_HOURS', 24),

    // The link sent by email, for visitors who come back to it later.
    'email_link_days' => (int) env('DOWNLOADS_EMAIL_LINK_DAYS', 7),
];
