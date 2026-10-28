<?php

declare(strict_types=1);

/*
 * The search panel shows results under its field as the visitor types: /search/results answers
 * with the first matches, in the visitor's language, each counting when opened.
 */

it('answers the first matches for the words typed, each counting when opened', function (): void {
    $response = $this->get('/search/results?q=agents')->assertOk();

    expect(dataValues($response, 'data-search-hit'))->not->toBeEmpty()
        ->and(dataValues($response, 'data-live-results'))->toBe(['agents']);
});

it('shows at most eight, and links to every result when there are more', function (): void {
    $response = $this->get('/search/results?q=arcturus');

    expect(count(dataValues($response, 'data-search-hit')))->toBeLessThanOrEqual(8)
        ->and((string) $response->getContent())->toContain('data-search-all');
});

it('answers nothing for fewer than two characters, or a query that is not text', function (string $query): void {
    expect(dataValues($this->get('/search/results?'.$query), 'data-search-hit'))->toBe([]);
})->with(['q=a', 'q=', 'q[]=agents', '']);

it('searches in the language asked for', function (): void {
    $response = $this->get('/search/results?lang=pt&q=agentes');

    expect(dataValues($response, 'data-search-hit'))->not->toBeEmpty()
        ->and((string) $response->getContent())->toContain('/pt/');
});

it('says so when nothing matches', function (): void {
    $response = $this->get('/search/results?q=zzqqxx');

    expect(dataValues($response, 'data-search-hit'))->toBe([])
        ->and(dataValues($response, 'data-live-results'))->toBe(['zzqqxx']);
});

it('never finds pages without a URL, such as hero slides', function (): void {
    $slide = (int) lynx()->pages->findOne('template=hero-slide, name=orchestrate-launch')?->id;

    expect(dataValues($this->get('/search/results?q=agents'), 'data-search-hit'))->not->toContain((string) $slide)
        ->and(dataValues($this->get('/search?q=agents'), 'data-search-hit'))->not->toContain((string) $slide);
});
