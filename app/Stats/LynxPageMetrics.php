<?php

declare(strict_types=1);

namespace App\Stats;

use DateTimeInterface;
use LynxCms\Contracts\Metrics\PageMetrics;

/**
 * The site's counts as Lynx's page metrics (ADR-0156), for Insights: views per page from
 * PageStats, in the shape the contract asks for. PageStats keeps its own shape.
 */
final readonly class LynxPageMetrics implements PageMetrics
{
    public function __construct(private PageStats $stats) {}

    public function totals(array $pageIds, string $event, int $days): array
    {
        return $this->stats->totals($pageIds, $event, $days);
    }

    public function top(string $event, int $days, int $limit): array
    {
        $top = [];

        foreach ($this->stats->top($event, $days, $limit) as $row) {
            $top[$row['page_id']] = $row['total'];
        }

        return $top;
    }

    public function since(): ?DateTimeInterface
    {
        return $this->stats->firstDay();
    }
}
