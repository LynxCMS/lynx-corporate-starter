<?php

declare(strict_types=1);

use Livewire\Livewire;

/*
 * The search panel's live results come in two versions: plain JavaScript (the default) or the
 * live-search Livewire component, chosen with SITE_SEARCH_PANEL. Both show the same results, count opened
 * results the same way, and fall back to the Search page without JavaScript.
 */

it('shows the first matches as the visitor types, each counting when opened', function (): void {
    Livewire::test('live-search')
        ->assertDontSeeHtml('data-search-hit')
        ->set('query', 'agents')
        ->assertSeeHtml('data-search-hit')
        ->assertSeeHtml('data-live-results="agents"');
});

it('waits for two characters, and ignores a query that is not text', function (): void {
    Livewire::test('live-search')->set('query', 'a')->assertDontSeeHtml('data-live-results');
});

it('shows at most eight, with a link to every result when there are more', function (): void {
    $component = Livewire::test('live-search')->set('query', 'arcturus');

    expect(substr_count($component->html(), 'data-search-hit='))->toBe(8)
        ->and($component->html())->toContain('data-search-all');
});

it('searches in the language of the page it sits on', function (): void {
    $component = Livewire::test('live-search', ['lang' => 'pt'])->set('query', 'agentes');

    expect($component->html())->toContain('data-search-hit')->toContain('/pt/');
});

it('keeps a form that opens the Search page, for visitors without JavaScript', function (): void {
    Livewire::test('live-search')->assertSeeHtml('name="q"')->assertSeeHtml('action="'.lynx()->pages->findOne('template=search')?->url().'"');
});

it('puts the version SITE_SEARCH_PANEL names in the header', function (): void {
    config(['site.search_panel' => 'javascript']);

    $this->get('/')->assertDontSeeLivewire('live-search')->assertSee('data-live-target', escape: false);

    config(['site.search_panel' => 'livewire']);

    $this->get('/')->assertSeeLivewire('live-search')->assertDontSee('data-live-target', escape: false);
});
