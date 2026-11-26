<?php

declare(strict_types=1);

/*
 * What a static export of this site needs besides its pages: the files the browser reads where
 * the PHP site would answer a query (the resources list, My bookmarks), in every language.
 */

it('exports the cards files in every language of the site', function (string $file): void {
    foreach (lynx()->languages->all() as $language) {
        expect(config('lynx-static.paths'))->toContain("/{$file}/{$language->code}.json");
    }
})->with(['downloads/cards', 'bookmarks/cards']);
