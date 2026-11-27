<?php

declare(strict_types=1);

namespace App\Support;

use LynxCms\Core\Models\Template;
use LynxCms\Core\Pages\Page;

/**
 * Which pages a visitor can bookmark: those with an address, other than the search, My bookmarks
 * and not found pages. The header's button and the static export's cards file both ask here.
 */
final class Bookmarkable
{
    public const array EXCLUDED_TEMPLATES = ['search', 'bookmarks', 'not-found'];

    public static function allows(Page $page): bool
    {
        return $page->template()->hasUrl() && ! in_array($page->template->name, self::EXCLUDED_TEMPLATES, true);
    }

    /** A selector for every page that can be bookmarked; null when no template qualifies. */
    public static function selector(): ?string
    {
        $names = lynx()->templates->all()
            ->filter(fn (Template $template): bool => $template->hasUrl() && ! in_array($template->name, self::EXCLUDED_TEMPLATES, true))
            ->pluck('name')
            ->all();

        return $names === [] ? null : 'template='.implode('|', $names);
    }
}
