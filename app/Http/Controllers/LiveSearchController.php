<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Search\SiteSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use LynxCms\Core\Support\Value;

/**
 * The search panel's results as the visitor types (resources/js/search.js): the first matches in
 * their language, and a link to the Search page when there are more. Fewer than two characters
 * answer nothing, to spare the index single letters.
 */
final class LiveSearchController
{
    public const int SHOWN = 8;

    private const int MIN_LENGTH = 2;

    public function __invoke(Request $request): View
    {
        $language = lynx()->languages->find(Value::string($request->query('lang')));

        if ($language !== null) {
            lynx()->languages->setCurrent($language);
        }

        $query = SiteSearch::query($request->query('q'));
        $isLongEnough = mb_strlen($query) >= self::MIN_LENGTH;

        return view('search.live', [
            'query' => $query,
            // One more than shown says whether there are more.
            'results' => $isLongEnough ? SiteSearch::find($query, self::SHOWN + 1) : null,
            'shown' => self::SHOWN,
            'search' => lynx()->pages->findOne('template=search'),
        ]);
    }
}
