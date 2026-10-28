<?php

declare(strict_types=1);

namespace App\Support;

use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;

/**
 * The newest news or insights, for sections that fill themselves: editors choose what to list and
 * how many from dropdowns, and never write a selector.
 */
final class LatestPages
{
    /** What each list holds: template => the date field it is sorted by. */
    public const array SOURCES = ['news' => 'published_on', 'resource' => 'date'];

    public const int DEFAULT_SIZE = 6;

    public static function find(string $source, ?Page $topic = null, ?int $size = null): PageArray
    {
        $dateField = self::SOURCES[$source] ?? self::SOURCES['news'];
        $template = array_key_exists($source, self::SOURCES) ? $source : 'news';
        $limit = $size !== null && $size > 0 ? $size : self::DEFAULT_SIZE;
        $topicFilter = $topic === null ? '' : ", topics={$topic->id}";

        return lynx()->pages->find("template={$template}{$topicFilter}, sort=-{$dateField}, limit={$limit}");
    }
}
