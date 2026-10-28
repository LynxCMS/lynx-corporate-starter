<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use LynxCms\Core\Pages\Page;
use RuntimeException;

/** Looking up the pages the seeders made: a page that is missing means a seeder ran out of order. */
final class Pages
{
    public static function get(int|string $path): Page
    {
        return lynx()->pages->get($path) ?? throw new RuntimeException("The seeders need [{$path}]: check the order in DatabaseSeeder.");
    }

    public static function findOne(string $selector): Page
    {
        return lynx()->pages->findOne($selector) ?? throw new RuntimeException("The seeders found no page for [{$selector}].");
    }

    /** The id of a page that has been saved, as every page a seeder looks up has. */
    public static function id(Page $page): int
    {
        return $page->id ?? throw new RuntimeException("Page [{$page->name}] has not been saved yet.");
    }
}
