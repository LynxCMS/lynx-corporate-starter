<?php

declare(strict_types=1);

namespace App\Stats;

use Carbon\CarbonImmutable;

/**
 * Orders pages by how often an event happened lately: each day's count weighs half as much every
 * half-life (config stats.half_life_days), counted over the last 8 half-lives, so recent activity
 * outranks more activity long ago. Computed from the daily totals, so changing the half-life, or
 * correcting a past day, needs no migration.
 */
final readonly class Ranking
{
    /** Older days weigh under 0.4% and are left out. */
    private const int HALF_LIVES_COUNTED = 8;

    public function __construct(private PageStats $stats) {}

    /**
     * The ids, the ones with activity first by weighted total, the rest after them in the order given.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public function ids(array $ids, string $event): array
    {
        $scores = $this->scores($ids, $event);
        $position = array_flip($ids);

        usort($ids, function (int $a, int $b) use ($scores, $position): int {
            $byScore = ($scores[$b] ?? 0.0) <=> ($scores[$a] ?? 0.0);

            return $byScore !== 0 ? $byScore : $position[$a] <=> $position[$b];
        });

        return $ids;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, float> page id => weighted total
     */
    public function scores(array $ids, string $event): array
    {
        $halfLife = max(1, (int) config('stats.half_life_days', 7));
        $today = CarbonImmutable::today();
        $scores = [];

        foreach ($this->stats->daily($ids, $event, $halfLife * self::HALF_LIVES_COUNTED) as $id => $days) {
            foreach ($days as $day => $count) {
                $age = (int) CarbonImmutable::parse($day)->diffInDays($today);
                $scores[$id] = ($scores[$id] ?? 0.0) + $count * 0.5 ** ($age / $halfLife);
            }
        }

        return $scores;
    }
}
