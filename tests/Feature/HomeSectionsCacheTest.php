<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

/*
 * The home page's card sections and the newsletter's headlines are kept as fragments, one per
 * section and language, and the newsletter form loads after the page: a visit renders neither.
 */

beforeEach(function (): void {
    $this->cardRenders = 0;
    View::composer('components.card', function (): void {
        $this->cardRenders++;
    });
});

/** How many cards a visit to the path renders. */
function cardRendersFor(string $path): int
{
    test()->cardRenders = 0;
    test()->get($path)->assertOk();

    return test()->cardRenders;
}

it('serves the card sections from fragments after the first visit', function (): void {
    $first = cardRendersFor('/');

    expect($first)->toBeGreaterThan(0)
        ->and(cardRendersFor('/'))->toBeLessThan($first);
});

it('renders the sections again after an editor changes a page', function (): void {
    $first = cardRendersFor('/');
    $product = lynx()->pages->get('/products/keyring/');
    $product->title = 'Keyring 2';
    $product->save();

    expect(cardRendersFor('/'))->toBe($first);
});

it('keeps a copy of each section per language', function (): void {
    $first = cardRendersFor('/');

    expect(cardRendersFor('/pt'))->toBe($first);
});

it('serves the same cards from fragments as on the first visit', function (): void {
    $first = dataValues($this->get('/'), 'data-card');

    expect($first)->not->toBeEmpty()
        ->and(dataValues($this->get('/'), 'data-card'))->toBe($first);
});

it('loads the newsletter form after the page', function (): void {
    $html = (string) $this->get('/')->getContent();

    expect($html)->toContain('data-newsletter-form')
        ->not->toContain('lynx-form-body');
});

it('gives the lazy newsletter form the page\'s language', function (): void {
    preg_match("/__lazyLoad\\('([^']+)'/", html_entity_decode((string) $this->get('/pt')->getContent()), $match);
    $payload = json_decode(base64_decode($match[1] ?? '', true) ?: '', true);

    expect(data_get($payload, 'data.forMount.0.lang'))->toBe('pt');
});
