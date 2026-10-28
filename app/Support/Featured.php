<?php

declare(strict_types=1);

namespace App\Support;

use LynxCms\Core\Pages\PageArray;

/**
 * Pages a section features, as the visitor's market should see them. Lynx lists a page that is
 * offline in the visitor's language and links it to its default-language URL (the fallback), so
 * the Portuguese home page would still feature a product taken offline in Portuguese. Sections
 * leave such pages out. Pages without a URL (promos) show in every language.
 */
final class Featured
{
    public static function inCurrentLanguage(PageArray $pages): PageArray
    {
        $language = lynx()->languages->current();

        return $pages->filter(fn ($page): bool => ! $page->template->hasUrl() || $page->isActiveIn($language));
    }
}
