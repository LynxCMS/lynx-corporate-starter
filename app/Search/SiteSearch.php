<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Support\Str;

/**
 * Search in Lynx's index, in the visitor's language, for the Search page and the panel's live
 * results alike. Lynx leaves out pages without a URL, such as hero slides; a promo's words find the
 * promo, linking to a page that shows it (ShownInside). The
 * visitor's text is trimmed and cut, and quotes, which would end the selector's value, are
 * dropped: words are what the index matches anyway.
 */
final class SiteSearch
{
    private const int MAX_QUERY = 100;

    /** Live results the search panel shows, in either version. */
    public const int SHOWN = 8;

    /** Fewer characters get no live results, to spare the index single letters. */
    private const int MIN_LENGTH = 2;

    /** Templates with a URL that are not worth finding: a search, a list in the browser, a 404. */
    private const array NOT_FOUND = ['search', 'bookmarks', 'not-found'];

    /** What the visitor typed, as text: a query sent as a list (?q[]=x) reads as nothing. */
    public static function query(mixed $input): string
    {
        return is_string($input) ? trim(Str::limit($input, self::MAX_QUERY, '')) : '';
    }

    /**
     * The search panel's live results for what the visitor typed: one more than it shows, which says
     * whether there are more, or null below MIN_LENGTH characters.
     *
     * @return list<SearchHit>|null
     */
    public static function live(string $query): ?array
    {
        return mb_strlen($query) >= self::MIN_LENGTH ? self::find($query, self::SHOWN + 1) : null;
    }

    /** @return list<SearchHit> */
    public static function find(string $query, int $limit): array
    {
        $words = trim(preg_replace('/["\\\\]+/', ' ', $query) ?? '');

        if ($words === '') {
            return [];
        }

        $search = 'search*="'.$words.'"';

        return ShownInside::add(lynx()->pages->find('template!='.implode('|', self::NOT_FOUND).', '.$search.', limit='.$limit), $search, $limit);
    }
}
