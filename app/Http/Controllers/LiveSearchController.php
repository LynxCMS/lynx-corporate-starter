<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Search\SiteSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use LynxCms\Core\Support\Value;

/**
 * The search panel's results as the visitor types, for its JavaScript version
 * (resources/js/search.js): the first matches in their language, and a link to the Search page
 * when there are more. The Livewire version is the live-search single-file component.
 */
final class LiveSearchController
{
    public function __invoke(Request $request): View
    {
        $language = lynx()->languages->find(Value::string($request->query('lang')));

        if ($language !== null) {
            lynx()->languages->setCurrent($language);
        }

        $query = SiteSearch::query($request->query('q'));

        return view('search.live', [
            'query' => $query,
            'results' => SiteSearch::live($query),
            'shown' => SiteSearch::SHOWN,
            'search' => lynx()->pages->findOne('template=search'),
        ]);
    }
}
