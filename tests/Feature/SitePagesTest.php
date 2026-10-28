<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\LatestPages;

/*
 * The rest of the site: the pages cards link to, the menus and the lists that fill themselves.
 */

it('answers every page with a URL, in both languages', function (): void {
    $pages = lynx()->pages->find('template=home|products|product|case-studies|case-study|insights|resource|newsroom|news|training|training-program|topics|topic');

    foreach ($pages as $page) {
        foreach (['en', 'pt'] as $language) {
            $this->get((string) $page->url($language))->assertOk();
        }
    }

    expect($pages->count())->toBe(37);
});

it('serves the home page in Portuguese under /pt', function (): void {
    $this->get('/pt')->assertOk()->assertSee('<html lang="pt-PT">', escape: false);
});

it('opens a mega-menu panel per section, and a footer column per label', function (): void {
    $response = $this->get('/');

    expect(dataValues($response, 'data-menu'))->toContain('top', 'top-mobile', 'footer')
        ->and(substr_count((string) $response->getContent(), 'name="mega"'))->toBe(4)
        ->and(substr_count((string) $response->getContent(), 'data-menu-panel'))->toBe(4);
});

it('lists the newest news first, as many as asked, on a topic', function (): void {
    $security = lynx()->pages->get('/topics/security/');

    expect(LatestPages::find('news', size: 3)->implode(', ', 'name'))->toBe('q3-results, velaris-renewal, agent-identity')
        ->and(LatestPages::find('news', $security, 2)->implode(', ', 'name'))->toBe('agent-identity, emea-incident-study')
        ->and(LatestPages::find('resource', size: 1)->implode(', ', 'name'))->toBe('agents-in-production');
});

it('signs the owner in to the admin', function (): void {
    $owner = User::query()->where('email', 'owner@arcturus.example')->firstOrFail();

    $this->actingAs($owner)->followingRedirects()->get('/lynx')->assertOk();
});
