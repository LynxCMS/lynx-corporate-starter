<?php

declare(strict_types=1);

namespace App\Stats;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Daily totals of what visitors do on pages: views, downloads and the site's own events, each with
 * an optional subject (a file's language, a button's label) and a source ("site" for what the site
 * counts itself, another name for counts brought from elsewhere). No row ever names a visitor.
 */
final readonly class PageStats
{
    public const string VIEW = 'view';

    public const string DOWNLOAD = 'download';

    public const string SITE = 'site';

    /** What a subject may hold: a label the site chose, never a visitor's text. */
    private const string SUBJECT = '/^[A-Za-z0-9._-]{1,64}$/';

    public static function connection(): Connection
    {
        $name = config('stats.connection');

        return DB::connection(is_string($name) && $name !== '' ? $name : null);
    }

    /** Adds to a page's count for an event on a day (today by default). */
    public function record(int $page, string $event, int $count = 1, string $subject = '', ?CarbonInterface $day = null, string $source = self::SITE): void
    {
        $key = [
            'page_id' => $page,
            'event' => $event,
            'subject' => self::subject($subject),
            'source' => $source,
            'day' => ($day ?? CarbonImmutable::today())->toDateString(),
        ];

        $this->table()->insertOrIgnore($key + ['count' => 0]);
        $this->table()->where($key)->increment('count', $count);
    }

    /**
     * Each page's total for an event over the last $days days, today included; 0 for all time.
     *
     * @param  list<int>  $ids
     * @return array<int, int> page id => total, for pages with any
     */
    public function totals(array $ids, string $event, int $days = 0): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->since($this->table()->where('event', $event)->whereIn('page_id', $ids), $days)
            ->groupBy('page_id')
            ->selectRaw('page_id, sum(count) as total')
            ->pluck('total', 'page_id');

        return $rows->map(fn (mixed $total): int => (int) $total)->all();
    }

    /**
     * Each page's count per day for an event over the last $days days.
     *
     * @param  list<int>  $ids
     * @return array<int, array<string, int>> page id => day (Y-m-d) => count
     */
    public function daily(array $ids, string $event, int $days): array
    {
        $series = [];

        if ($ids === []) {
            return $series;
        }

        $rows = $this->since($this->table()->where('event', $event)->whereIn('page_id', $ids), $days)
            ->groupBy('page_id', 'day')
            ->selectRaw('page_id, day, sum(count) as total')
            ->get();

        foreach ($rows as $row) {
            $series[(int) $row->page_id][substr((string) $row->day, 0, 10)] = (int) $row->total;
        }

        return $series;
    }

    /**
     * The pages (or a page's subjects, such as the files of downloads) with the highest totals.
     *
     * @return list<array{page_id: int, subject: string, total: int}>
     */
    public function top(string $event, int $days, int $limit, bool $bySubject = false): array
    {
        $groups = $bySubject ? ['page_id', 'subject'] : ['page_id'];
        $rows = $this->since($this->table()->where('event', $event), $days)
            ->groupBy(...$groups)
            ->selectRaw(implode(', ', $groups).', sum(count) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return array_values($rows->map(fn (object $row): array => [
            'page_id' => (int) $row->page_id,
            'subject' => $bySubject ? (string) $row->subject : '',
            'total' => (int) $row->total,
        ])->all());
    }

    /** Every page's total for an event over the last $days days, today included; 0 for all time. */
    public function sum(string $event, int $days = 0): int
    {
        return (int) $this->since($this->table()->where('event', $event), $days)->sum('count');
    }

    /** A subject the table may hold, or '' for anything else. */
    public static function subject(string $subject): string
    {
        return preg_match(self::SUBJECT, $subject) === 1 ? $subject : '';
    }

    private function since(Builder $query, int $days): Builder
    {
        return $days > 0 ? $query->where('day', '>', CarbonImmutable::today()->subDays($days)->toDateString()) : $query;
    }

    private function table(): Builder
    {
        return self::connection()->table('stat_counts');
    }
}
