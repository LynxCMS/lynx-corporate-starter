<?php

declare(strict_types=1);

use App\Downloads\DownloadListing;
use App\Stats\PageStats;
use Database\Seeders\Support\Downloads;

/*
 * The resources list on a static export: a static host ignores ?sort= and ?page=, so the page
 * fetches /downloads/cards/{language}.json (every sort's order and every card) and shows the one
 * asked for. The PHP site renders each sort and page itself and never fetches the file.
 */

it('gives every sort\'s order, as the server lists it, and a card per download', function (): void {
    foreach (range(1, 30) as $number) {
        Downloads::create(sprintf('Report %02d', $number), 'report', ['en']);
    }
    $ids = array_map('intval', lynx()->pages->find('template=download')->ids());
    app(PageStats::class)->record($ids[5], PageStats::DOWNLOAD, 40);

    $data = $this->getJson('/downloads/cards/en.json')->assertOk()->json();

    expect(array_keys($data['orders']))->toBe(DownloadListing::SORTS)
        ->and($data['per_page'])->toBe(DownloadListing::PER_PAGE)
        ->and($data['orders']['downloads'][0])->toBe($ids[5])
        ->and(array_map('intval', array_keys($data['cards'])))->toEqualCanonicalizing($ids);

    $secondTitlePage = $this->get('/resources/?sort=title&page=2')->assertOk();

    expect(array_map('strval', array_slice($data['orders']['title'], 24)))->toBe(dataValues($secondTitlePage, 'data-card-id'));
});

it('renders the cards in the language asked for, with counts and links from the site root', function (): void {
    $id = (int) lynx()->pages->find('template=download')->ids()[0];
    app(PageStats::class)->record($id, PageStats::DOWNLOAD, 1234);

    $card = $this->getJson('/downloads/cards/pt.json')->assertOk()->json('cards.'.$id);

    expect($card)->toContain('data-card="download"')
        ->toContain('data-downloads="1234"')
        ->toContain('href="/pt/')
        ->not->toContain('://');
});

it('answers 404 for a language the site does not have', function (): void {
    $this->get('/downloads/cards/xx.json')->assertNotFound();
});

it('tells the script which sort and page it shows, and where the cards are', function (): void {
    $response = $this->get('/resources/')->assertOk();

    expect(dataValues($response, 'data-shown-sort'))->toBe([DownloadListing::DEFAULT_SORT])
        ->and(dataValues($response, 'data-shown-page'))->toBe(['1'])
        ->and(dataValues($response, 'data-cards-url'))->toBe(['/downloads/cards/en.json']);
});
