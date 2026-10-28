<?php

declare(strict_types=1);

/*
 * The home page is the sections editors arrange: these tests change them the way the page editor
 * does (order, the on/off switch, the pages a section features) and check what visitors get.
 */

it('renders every section in the order editors set', function (): void {
    expect(dataValues($this->get('/')->assertOk(), 'data-section'))
        ->toBe(['hero', 'cards', 'feature-links', 'cards', 'cards', 'cards', 'cards', 'newsletter']);
});

it('leaves out a section that is switched off, and keeps it', function (): void {
    changeSections(function (array $sections): array {
        $sections[2]['enabled'] = false;

        return $sections;
    });

    expect(dataValues($this->get('/'), 'data-section'))->not->toContain('feature-links')
        ->and(collect(home()->getUnformatted('sections'))->pluck('type'))->toContain('feature-links');
});

it('follows a new order', function (): void {
    changeSections(fn (array $sections): array => [$sections[7], ...array_slice($sections, 0, 7)]);

    expect(dataValues($this->get('/'), 'data-section')[0])->toBe('newsletter');
});

it('shows the cards an editor picks, in their order', function (): void {
    $picked = [lynx()->pages->get('/case-studies/halcyon-health/')?->id, lynx()->pages->get('/products/keyring/')?->id];
    changeSections(function (array $sections) use ($picked): array {
        $sections[1]['values']['cards'] = $picked;

        return $sections;
    });

    $html = (string) $this->get('/')->getContent();
    $recommended = str($html)->after('data-section="cards"')->before('data-section="feature-links"');

    expect(dataValues($this->get('/'), 'data-card'))->toContain('case-study')
        ->and(substr_count((string) $recommended, 'data-card='))->toBe(2)
        ->and(strpos((string) $recommended, '/case-studies/halcyon-health'))->toBeLessThan(strpos((string) $recommended, '/products/keyring'));
});

it('leaves out a featured page that is unpublished', function (): void {
    lynx()->pages->setStatus('/products/pilot/', add: ['unpublished']);

    $products = collect(dataValues($this->get('/'), 'data-card'))->filter(fn (string $card): bool => $card === 'product');

    expect($products)->toHaveCount(5);
});

it('leaves out a featured page that is offline in the visitor\'s language', function (): void {
    $pilot = lynx()->pages->get('/products/pilot/');
    $pilot?->setLanguageName('pt', 'pilot', active: false)->save();

    $count = fn (string $url): int => collect(dataValues($this->get($url), 'data-card'))->filter(fn (string $card): bool => $card === 'product')->count();

    expect($count('/'))->toBe(6)->and($count('/pt'))->toBe(5);
});
