<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/*
 * Opening a search result counts for that page: once per visitor (address) and page a day, never
 * for bots, and only for published pages with a URL.
 */

function hits(string $path): float
{
    return (float) DB::table('page_popularity')->where('page_id', lynx()->pages->get($path)?->id)->value('score');
}

function hit(string $path, string $agent = 'Mozilla/5.0'): TestResponse
{
    return test()->withHeader('User-Agent', $agent)->post('/search/hit', ['page' => (int) lynx()->pages->get($path)?->id]);
}

it('counts a result a visitor opens, once a day', function (): void {
    hit('/products/pilot/')->assertNoContent();
    hit('/products/pilot/')->assertNoContent();

    expect(hits('/products/pilot/'))->toBeGreaterThan(0.0)
        ->and(DB::table('page_popularity')->count())->toBe(1);

    $first = hits('/products/pilot/');
    $this->travel(25)->hours();
    hit('/products/pilot/');

    expect(hits('/products/pilot/'))->toBeGreaterThan($first);
});

it('ignores bots, unknown pages, unpublished pages and pages without a URL', function (): void {
    hit('/products/pilot/', 'Googlebot/2.1')->assertNoContent();
    $this->post('/search/hit', ['page' => 999999])->assertNoContent();
    lynx()->pages->setStatus('/products/keyring/', add: ['unpublished']);
    hit('/products/keyring/');
    $this->post('/search/hit', ['page' => (int) lynx()->pages->findOne('template=promo')?->id])->assertNoContent();

    expect(DB::table('page_popularity')->count())->toBe(0);
});

it('refuses a page that is not a number', function (): void {
    $this->post('/search/hit', ['page' => 'drop table'])->assertNoContent();

    expect(DB::table('page_popularity')->count())->toBe(0);
});
