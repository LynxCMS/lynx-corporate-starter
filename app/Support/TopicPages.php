<?php

declare(strict_types=1);

namespace App\Support;

use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;

/**
 * The pages tagged with a topic (their `topics` field), grouped by type for the topic's page. Each
 * group carries its list page, whose title heads it, and only the pages published in the visitor's
 * language. Types without pages on the topic are left out.
 */
final class TopicPages
{
    /** Templates with a `topics` field: their list page and how the group is sorted. */
    public const array TYPES = [
        'product' => ['/products/', 'sort=sort'],
        'resource' => ['/insights/', 'sort=-date'],
        'news' => ['/news/', 'sort=-published_on'],
    ];

    /** @return list<array{type: string, list: Page|null, pages: PageArray}> */
    public static function for(Page $topic): array
    {
        $groups = [];

        foreach (self::TYPES as $template => [$list, $sort]) {
            $pages = lynx()->pages->find("template={$template}, topics={$topic->id}, {$sort}")->publishedIn();

            if ($pages->count() > 0) {
                $groups[] = ['type' => $template, 'list' => lynx()->pages->get($list), 'pages' => $pages];
            }
        }

        return $groups;
    }
}
