<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Links to this site from its root, for HTML that goes into a JSON file a static export writes:
 * the export rewrites links in pages, not in other files, and a file with full addresses would
 * only work on the host it was exported for.
 */
final class SiteLinks
{
    /** https://site/pt/x becomes /pt/x; other sites' addresses stay as they are. */
    public static function fromRoot(string $html): string
    {
        return str_replace(rtrim(url('/'), '/').'/', '/', $html);
    }
}
