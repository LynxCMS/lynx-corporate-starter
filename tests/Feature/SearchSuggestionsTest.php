<?php

declare(strict_types=1);

use App\Search\Popularity;
use App\Search\Suggestions;
use LynxCms\Core\Pages\Page;

/*
 * The search panel's list: the pages editors pick on the Search page first, then the pages most
 * opened from search results lately (each visit counts less as it ages), then the newest pages,
 * until the Search page's "How many" is reached.
 */

function searchPage(): Page
{
    return lynx()->pages->get('/search/') ?? throw new RuntimeException('No search page.');
}

/** @param  list<string>  $paths */
function pickSuggestions(array $paths, int $size): void
{
    $search = searchPage();
    $search->suggested = array_map(fn (string $path): int => (int) lynx()->pages->get($path)?->id, $paths);
    $search->list_size = $size;
    $search->save();
}

/** @return list<string> */
function suggestedPaths(): array
{
    return array_map(fn (Page $page): string => $page->path() ?? '', app(Suggestions::class)->for(searchPage()));
}

function openFromSearch(string $path, int $times = 1): void
{
    for ($i = 0; $i < $times; $i++) {
        app(Popularity::class)->record((int) lynx()->pages->get($path)?->id);
    }
}

it('lists the picked pages first, in their order, then fills up to the size', function (): void {
    pickSuggestions(['/products/keyring/', '/products/pilot/'], 4);

    expect(suggestedPaths())->toHaveCount(4)
        ->and(array_slice(suggestedPaths(), 0, 2))->toBe(['products/keyring', 'products/pilot']);
});

it('fills with the pages most opened from search, before the newest', function (): void {
    pickSuggestions(['/products/keyring/'], 3);
    openFromSearch('/case-studies/halcyon-health/', 3);
    openFromSearch('/products/ledger/', 1);

    expect(suggestedPaths())->toBe(['products/keyring', 'case-studies/halcyon-health', 'products/ledger']);
});

it('lets older visits count less than recent ones', function (): void {
    pickSuggestions([], 2);
    openFromSearch('/products/connect/', 4);
    $this->travel(30)->days();
    openFromSearch('/products/orchestrate/', 1);

    expect(suggestedPaths())->toBe(['products/orchestrate', 'products/connect']);
});

it('fills with the newest pages when nobody has searched yet', function (): void {
    pickSuggestions([], 2);
    $this->travel(1)->minutes();
    $news = lynx()->pages->new('news', '/news/');
    $news->name = 'brand-new';
    $news->title = 'Brand new';
    $news->published_on = now()->toDateString();
    $news->save();

    expect(suggestedPaths()[0])->toBe('news/brand-new');
});

it('never repeats a page, and leaves out unpublished ones', function (): void {
    pickSuggestions(['/products/pilot/'], 3);
    openFromSearch('/products/pilot/', 5);
    openFromSearch('/products/keyring/', 2);
    lynx()->pages->setStatus('/products/keyring/', add: ['unpublished']);

    $paths = suggestedPaths();

    expect($paths)->toHaveCount(3)
        ->and($paths)->not->toContain('products/keyring')
        ->and(array_unique($paths))->toBe($paths);
});
