<?php

declare(strict_types=1);

use App\Downloads\Document;
use Database\Seeders\Support\Downloads;

/*
 * The downloads section: a list of reports and ebooks and a page for each, whose files exist in
 * some of the site's languages. Every page is listed in every language, marked with the languages
 * its document has.
 */

beforeEach(function (): void {
    spanish();
    $this->everywhere = Downloads::create('Cloud outlook', 'report', ['en', 'pt', 'es']);
    $this->english = Downloads::create('Zero trust guide', 'ebook', ['en']);
});

it('knows the languages a document exists in, in the site\'s order', function (): void {
    $mixed = Downloads::create('Mixed', 'report', ['es', 'en']);

    expect((new Document($this->everywhere))->languages())->toBe(['en', 'pt', 'es'])
        ->and((new Document($mixed))->languages())->toBe(['en', 'es'])
        ->and((new Document($this->english))->languages())->toBe(['en']);
});

it('has no languages when no file is there', function (): void {
    expect((new Document(Downloads::create('Empty', 'report', [])))->languages())->toBe([]);
});

it('starts in the visitor\'s language when the document has it, else in the site\'s first', function (): void {
    expect((new Document($this->everywhere))->defaultLanguage('pt'))->toBe('pt')
        ->and((new Document($this->english))->defaultLanguage('pt'))->toBe('en')
        ->and((new Document(Downloads::create('Spanish', 'report', ['es'])))->defaultLanguage('pt'))->toBe('es');
});

it('lists every download with its kind and languages', function (): void {
    $response = $this->get('/resources/')->assertOk()->assertSee('Cloud outlook')->assertSee('Zero trust guide');

    expect(dataValues($response, 'data-card'))->toContain('download')
        ->and(dataValues($response, 'data-languages'))->toContain('en pt es', 'en');
    $response->assertSee('English only');
});

it('lists English only documents on the other languages\' sites too', function (): void {
    $this->get('/es/recursos/')->assertOk()->assertSee('Zero trust guide')->assertSee('Solo en inglés');
    $this->get('/pt/recursos/')->assertOk()->assertSee('Só em inglês');
});

it('shows a download\'s page with the form in an island', function (): void {
    $this->get($this->everywhere->url())->assertOk()->assertSee('Cloud outlook')
        ->assertSee('Download report')->assertSeeHtml('data-download-form');
});

it('links the privacy notice the list page names, and hides it when none is chosen', function (): void {
    $list = lynx()->pages->get('/resources/');
    $list->privacy_page = null;
    $list->save();
    $this->get($this->everywhere->url())->assertDontSeeHtml('data-privacy-notice');

    $list->privacy_page = lynx()->pages->get('/insights/')->id;
    $list->save();

    $this->get($this->everywhere->url())->assertSeeHtml('data-privacy-notice')->assertSee(lynx()->pages->get('/insights/')->url());
});

it('stores a download page in the page cache, with its form left to load for each visitor', function (): void {
    config(['lynx-cache.pages.enabled' => true, 'lynx-cache.pages.driver' => 'store', 'lynx-cache.pages.debug' => true]);

    $this->get($this->everywhere->url())->assertHeader('X-Lynx-Cache', 'MISS');
    $cached = $this->get($this->everywhere->url())->assertHeader('X-Lynx-Cache', 'HIT');

    $cached->assertSeeHtml('data-download-form')->assertDontSee('First name');
});
