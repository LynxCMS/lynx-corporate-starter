<?php

declare(strict_types=1);

return [
    // Password of the owner account the seeders make; set it in .env.
    'owner_password' => env('OWNER_PASSWORD'),

    // Draw a picture for each demo page while seeding. Tests turn it off: it is most of the seed's time.
    'artwork' => (bool) env('SITE_ARTWORK', true),
];
