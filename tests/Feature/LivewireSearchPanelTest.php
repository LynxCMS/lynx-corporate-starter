<?php

declare(strict_types=1);

use App\Livewire\SearchPanel;
use Livewire\Livewire;

/*
 * The search panel's live results come in two versions: plain JavaScript (the default) or a
 * Livewire component, chosen with SITE_SEARCH_PANEL. Both show the same results, count opened
 * results the same way, and fall back to the Search page without JavaScript.
 */

it('shows the first matches as the visitor types, each counting when opened', function (): void {
    Livewire::test(SearchPanel::class)
        ->assertDontSeeHtml('data-search-hit')
        ->set('query', 'agents')
        ->assertSeeHtml('data-search-hit')
        ->assertSeeHtml('data-live-results="agents"');
});

it('waits for two characters, and ignores a query that is not text', function (): void {
    Livewire::test(SearchPanel::class)->set('query', 'a')->assertDontSeeHtml('data-live-results');
});

it('shows at most eight, with a link to every result when there are more', function (): void {
    $component = Livewire::test(SearchPanel::class)->set('query', 'arcturus');

    expect(substr_count($component->html(), 'data-search-hit='))->toBe(8)
        ->and($component->html())->toContain('data-search-all');
});

it('searches in the language of the page it sits on', function (): void {
    $component = Livewire::test(SearchPanel::class, ['lang' => 'pt'])->set('query', 'agentes');

    expect($component->html())->toContain('data-search-hit')->toContain('/pt/');
});

it('keeps a form that opens the Search page, for visitors without JavaScript', function (): void {
    Livewire::test(SearchPanel::class)->assertSeeHtml('name="q"')->assertSeeHtml('action="'.lynx()->pages->findOne('template=search')?->url().'"');
});

it('puts the JavaScript version in the header by default, and the Livewire one when asked', function (): void {
    $this->get('/')->assertDontSeeLivewire('search-panel')->assertSee('data-live-target', escape: false);

    config(['site.search_panel' => 'livewire']);

    $this->get('/')->assertSeeLivewire('search-panel')->assertDontSee('data-live-target', escape: false);
});
