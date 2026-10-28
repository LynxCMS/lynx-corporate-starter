<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use LynxCms\Core\Pages\Page;
use Tests\TestCase;

/*
 * Every feature test starts from the seeded site, without the pictures (drawing them is most of
 * the seed's time) and with uploads on a fake disk.
 */
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->beforeEach(function (): void {
    config(['site.owner_password' => 'password-for-tests', 'site.artwork' => false]);
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
})->in('Feature');

function home(): Page
{
    return lynx()->pages->get('/') ?? throw new RuntimeException('The home page is missing.');
}

/**
 * The values of one data attribute in a response, in document order: data-section="cards" → cards.
 *
 * @return list<string>
 */
function dataValues(TestResponse $response, string $attribute): array
{
    preg_match_all('/\b'.preg_quote($attribute, '/').'="([^"]*)"/', (string) $response->getContent(), $matches);

    return $matches[1];
}

/**
 * Changes the home page's sections as an editor would in the page editor: each item keeps its
 * key, type, switch and values.
 *
 * @param  Closure(list<array<string, mixed>>): list<array<string, mixed>>  $change
 */
function changeSections(Closure $change): void
{
    $home = home();
    $home->sections = $change(array_values((array) $home->getUnformatted('sections')));
    $home->save();
}
