<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Support\Str;
use LynxCms\Core\Pages\PageArray;

/**
 * Search in Lynx's index, in the visitor's language, for the Search page and the panel's live
 * results alike. Lynx leaves out pages without a URL, such as hero slides and promos. The
 * visitor's text is trimmed and cut, and quotes, which would end the selector's value, are
 * dropped: words are what the index matches anyway.
 */
final class SiteSearch
{
    private const int MAX_QUERY = 100;

    /** Templates with a URL that are not worth finding: a search, a list in the browser, a 404. */
    private const array NOT_FOUND = ['search', 'bookmarks', 'not-found'];

    /** What the visitor typed, as text: a query sent as a list (?q[]=x) reads as nothing. */
    public static function query(mixed $input): string
    {
        return is_string($input) ? trim(Str::limit($input, self::MAX_QUERY, '')) : '';
    }

    public static function find(string $query, int $limit): PageArray
    {
        $words = trim(preg_replace('/["\\\\]+/', ' ', $query) ?? '');

        return $words === '' ? new PageArray([]) : lynx()->pages->find('template!='.implode('|', self::NOT_FOUND).', search*="'.$words.'", limit='.$limit);
    }
}
