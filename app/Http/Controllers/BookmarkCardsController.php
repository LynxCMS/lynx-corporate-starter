<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * The cards of the pages a visitor bookmarked, for the My bookmarks page to show: the list lives in
 * their browser, so the page asks for the cards by id. Only pages they may see in their language
 * come back, in the order asked, at most site.bookmarks_max of them.
 */
final class BookmarkCardsController
{
    public function __invoke(Request $request): View
    {
        $language = lynx()->languages->find(Value::string($request->query('lang')));

        if ($language !== null) {
            lynx()->languages->setCurrent($language);
        }

        $ids = $this->ids(Value::string($request->query('ids')));
        $found = $ids === [] ? [] : lynx()->pages->find('id='.implode('|', $ids).', limit='.count($ids))->publishedIn()->all();
        $byId = array_column(array_map(fn (Page $page): array => [(int) $page->id, $page], $found), 1, 0);

        return view('bookmarks.cards', ['pages' => array_values(array_filter(array_map(fn (int $id): ?Page => $byId[$id] ?? null, $ids)))]);
    }

    /** @return list<int> distinct positive ids, in order, capped */
    private function ids(string $list): array
    {
        $ids = array_filter(array_map(fn (string $id): int|false => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]), explode(',', $list)), is_int(...));

        return array_slice(array_values(array_unique($ids)), 0, Value::int(config('site.bookmarks_max'), 50));
    }
}
