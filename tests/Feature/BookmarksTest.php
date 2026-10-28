<?php

declare(strict_types=1);

/*
 * Bookmarks live in the visitor's browser (localStorage); the site renders the cards of the pages
 * a list names, in the visitor's language, and only pages they may see.
 */

/** @param  list<string>  $paths */
function bookmarkIds(array $paths): string
{
    return implode(',', array_map(fn (string $path): int => (int) lynx()->pages->get($path)?->id, $paths));
}

it('renders the cards of the bookmarked pages, in the order given', function (): void {
    $response = $this->get('/bookmarks/cards?ids='.bookmarkIds(['/products/keyring/', '/news/q3-results/']))->assertOk();

    expect(dataValues($response, 'data-card'))->toBe(['product', 'news'])
        ->and(dataValues($response, 'data-bookmark-card'))->toBe(explode(',', bookmarkIds(['/products/keyring/', '/news/q3-results/'])));
});

it('leaves out pages a visitor may not see, unknown ids and junk', function (): void {
    lynx()->pages->setStatus('/products/keyring/', add: ['unpublished']);

    $response = $this->get('/bookmarks/cards?ids='.bookmarkIds(['/products/keyring/', '/products/pilot/']).',999999,abc,-3')->assertOk();

    expect(dataValues($response, 'data-card'))->toBe(['product']);
});

it('renders the cards in the language asked for, and leaves out pages offline in it', function (): void {
    lynx()->pages->get('/products/pilot/')?->setLanguageName('pt', 'pilot', active: false)->save();

    $response = $this->get('/bookmarks/cards?lang=pt&ids='.bookmarkIds(['/products/pilot/', '/products/connect/']));

    expect(dataValues($response, 'data-card'))->toHaveCount(1)
        ->and((string) $response->getContent())->toContain('/pt/');
});

it('takes at most 50 pages', function (): void {
    $ids = implode(',', array_fill(0, 60, (int) lynx()->pages->get('/products/pilot/')?->id));

    expect(dataValues($this->get('/bookmarks/cards?ids='.$ids), 'data-card'))->toHaveCount(1);
});

it('has a bookmarks page and offers bookmarking and search in the header', function (): void {
    $this->get('/my-bookmarks')->assertOk()->assertSee('data-bookmarks-list', escape: false);

    $response = $this->get('/products/pilot');

    expect(dataValues($response, 'data-bookmark-toggle'))->toBe([(string) lynx()->pages->get('/products/pilot/')?->id])
        ->and(dataValues($response, 'data-menu'))->toContain('search');
});
