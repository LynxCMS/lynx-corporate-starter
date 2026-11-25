<?php

declare(strict_types=1);

namespace App\Stats;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Made-up daily views and downloads that look like a real site's, for testing listings and the
 * admin's lists: nothing before a page is published, a launch bump that fades over two to three
 * weeks, a long-tailed appeal per page with a shape (steady, rising, fading, popular long ago or a
 * recent spike), quieter weekends and holidays, a few newsletter days that lift many pages at once,
 * and each day's count drawn around its expected value (Poisson). The same seed gives the same history.
 */
final class FakeHistory
{
    private const array SHAPES = ['steady', 'steady', 'rising', 'fading', 'old-popular', 'spike'];

    private const float WEEKEND = 0.4;

    private const float HOLIDAY = 0.3;

    private const float NEWSLETTER = 2.5;

    private const int NEWSLETTER_DAYS = 4;

    private const int CHUNK = 500;

    private const float FIRST_SUBJECT_SHARE = 0.7;

    private Randomizer $random;

    /** @var array<string, true> days of the newsletter, Y-m-d */
    private array $newsletters = [];

    private CarbonImmutable $today;

    public function __construct(private readonly int $days = 90, ?int $seed = null)
    {
        $this->random = new Randomizer(new Mt19937($seed ?? random_int(0, PHP_INT_MAX)));
        $this->today = CarbonImmutable::today();

        for ($i = 0; $i < self::NEWSLETTER_DAYS; $i++) {
            $this->newsletters[$this->today->subDays($this->random->getInt(1, $this->days))->toDateString()] = true;
        }
    }

    /**
     * Rows for one page, ready for the stat_counts table.
     *
     * @param  list<string>  $subjects  downloads are shared among these (the first gets most), or ''
     * @return list<array{page_id: int, event: string, subject: string, source: string, day: string, count: int}>
     */
    public function rows(int $page, ?CarbonInterface $published, array $subjects = ['']): array
    {
        $first = $this->today->subDays($this->days - 1);
        $start = $published === null ? $first : CarbonImmutable::parse($published)->startOfDay()->max($first);
        $appeal = exp($this->normal(log(2.0), 1.1));
        $shape = self::SHAPES[$this->random->getInt(0, count(self::SHAPES) - 1)];
        $viewsPerDownload = $this->random->getInt(8, 20);
        $readsNewsletter = $this->random->nextFloat() < 0.4;
        $rows = [];

        for ($day = $start; $day->lte($this->today); $day = $day->addDay()) {
            $expected = $appeal * $this->rhythm($day, $readsNewsletter) * $this->launch($day, $published) * $this->shape($shape, $day, $start);
            $date = $day->toDateString();
            $rows[] = $this->row($page, PageStats::VIEW, '', $date, $this->poisson($expected * $viewsPerDownload));

            foreach ($this->shares($subjects) as $subject => $share) {
                $rows[] = $this->row($page, PageStats::DOWNLOAD, $subject, $date, $this->poisson($expected * $share));
            }
        }

        return array_values(array_filter($rows, fn (array $row): bool => $row['count'] > 0));
    }

    /**
     * Writes rows to the stats tables, replacing any count already there for the same day.
     *
     * @param  list<array{page_id: int, event: string, subject: string, source: string, day: string, count: int}>  $rows
     */
    public static function store(array $rows): int
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            PageStats::connection()->table('stat_counts')->upsert($chunk, ['page_id', 'event', 'subject', 'source', 'day'], ['count']);
        }

        return count($rows);
    }

    /** Weekends, the Christmas holidays and newsletter days. */
    private function rhythm(CarbonImmutable $day, bool $readsNewsletter): float
    {
        $factor = $day->isWeekend() ? self::WEEKEND : 1.0;
        $isHoliday = ($day->month === 12 && $day->day >= 24) || ($day->month === 1 && $day->day === 1);

        return $factor
            * ($isHoliday ? self::HOLIDAY : 1.0)
            * ($readsNewsletter && isset($this->newsletters[$day->toDateString()]) ? self::NEWSLETTER : 1.0);
    }

    /** A new page draws visitors in its first days; the bump fades over two to three weeks. */
    private function launch(CarbonImmutable $day, ?CarbonInterface $published): float
    {
        if ($published === null) {
            return 1.0;
        }

        $age = max(0, (int) CarbonImmutable::parse($published)->startOfDay()->diffInDays($day));

        return 1.0 + 4.0 * exp(-$age / 7);
    }

    private function shape(string $shape, CarbonImmutable $day, CarbonImmutable $start): float
    {
        $span = max(1, (int) $start->diffInDays($this->today));
        $progress = (int) $start->diffInDays($day) / $span;
        $daysAgo = (int) $day->diffInDays($this->today);

        return match ($shape) {
            'rising' => 0.3 + 1.4 * $progress,
            'fading' => 1.6 - 1.4 * $progress,
            'old-popular' => $daysAgo >= 60 && $daysAgo <= 90 ? 4.0 : 0.3,
            'spike' => $daysAgo < 7 ? 6.0 : 0.6,
            default => 1.0,
        };
    }

    /**
     * @param  list<string>  $subjects
     * @return array<string, float>
     */
    private function shares(array $subjects): array
    {
        if (count($subjects) < 2) {
            return [$subjects[0] ?? '' => 1.0];
        }

        $rest = (1 - self::FIRST_SUBJECT_SHARE) / (count($subjects) - 1);

        return array_combine($subjects, [self::FIRST_SUBJECT_SHARE, ...array_fill(0, count($subjects) - 1, $rest)]);
    }

    /** @return array{page_id: int, event: string, subject: string, source: string, day: string, count: int} */
    private function row(int $page, string $event, string $subject, string $day, int $count): array
    {
        return ['page_id' => $page, 'event' => $event, 'subject' => $subject, 'source' => PageStats::SITE, 'day' => $day, 'count' => $count];
    }

    /** A Poisson draw: Knuth's method for small means, a rounded normal for large ones. */
    private function poisson(float $mean): int
    {
        if ($mean <= 0) {
            return 0;
        }

        if ($mean > 30) {
            return max(0, (int) round($this->normal($mean, sqrt($mean))));
        }

        $limit = exp(-$mean);
        $count = 0;

        for ($product = $this->random->nextFloat(); $product > $limit; $product *= $this->random->nextFloat()) {
            $count++;
        }

        return $count;
    }

    private function normal(float $mean, float $deviation): float
    {
        $u = max(PHP_FLOAT_MIN, $this->random->nextFloat());

        return $mean + $deviation * sqrt(-2 * log($u)) * cos(2 * M_PI * $this->random->nextFloat());
    }
}
