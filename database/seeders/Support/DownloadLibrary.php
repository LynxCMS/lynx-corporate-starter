<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Downloads\Document;
use App\Stats\FakeHistory;
use Carbon\CarbonImmutable;
use Database\Seeders\DownloadsSchemaSeeder;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Many download pages with made-up history, for testing the resources list and the admin's lists
 * (site:seed-downloads, and a small set on the public demo). Titles are varied, languages are en,
 * pt or both, and publication dates spread over two years: more in recent months, clustered in a
 * few release weeks, with a dozen in the last week. The same seed gives the same library.
 */
final class DownloadLibrary
{
    private const int YEARS_BACK = 2;

    private const int RELEASE_WEEKS = 8;

    private const int RECENT_ITEMS = 12;

    private const array SUBJECTS = ['cloud security', 'zero trust', 'data platforms', 'AI governance', 'hybrid cloud', 'observability', 'identity', 'resilience', 'edge computing', 'automation', 'sovereign data', 'platform engineering'];

    private const array FORMATS = ['The state of %s', 'A field guide to %s', '%s in practice', 'Benchmark: %s', 'What leaders get wrong about %s', '%s, step by step', 'The %s playbook', 'Ten questions about %s'];

    private const array LANGUAGES = [['en'], ['en'], ['en', 'pt'], ['en', 'pt'], ['pt']];

    private Randomizer $random;

    public function __construct(private readonly ?int $seed = null)
    {
        $this->random = new Randomizer(new Mt19937($seed ?? random_int(0, PHP_INT_MAX)));
    }

    /** Makes $count downloads with $days of history; returns the daily counts written. */
    public function seed(int $count, int $days): int
    {
        $history = new FakeHistory($days, $this->seed);
        $releaseWeeks = array_map(fn (): CarbonImmutable => $this->pastDay(), range(1, self::RELEASE_WEEKS));
        $written = 0;

        for ($index = 1; $index <= $count; $index++) {
            $published = $this->publishedOn($index, $count, $releaseWeeks);
            $languages = self::LANGUAGES[$this->random->getInt(0, count(self::LANGUAGES) - 1)];
            $page = Downloads::create($this->title($index), $this->kind(), $languages, 'A made-up download for testing the resources list.');
            $page->published_on = $published->toDateString();
            $page->save();

            $written += FakeHistory::store($history->rows((int) $page->id, $published, (new Document($page))->languages()));
        }

        return $written;
    }

    /**
     * A dozen this week, half in release weeks, the rest spread with more in recent months.
     *
     * @param  list<CarbonImmutable>  $releaseWeeks
     */
    private function publishedOn(int $index, int $count, array $releaseWeeks): CarbonImmutable
    {
        $today = CarbonImmutable::today();

        if ($index > $count - self::RECENT_ITEMS) {
            return $today->subDays($this->random->getInt(0, 6));
        }

        if ($this->random->nextFloat() < 0.5) {
            return $releaseWeeks[$this->random->getInt(0, count($releaseWeeks) - 1)]->addDays($this->random->getInt(0, 4))->min($today);
        }

        return $this->pastDay();
    }

    /** A day in the last two years, weighted toward recent months. */
    private function pastDay(): CarbonImmutable
    {
        $span = self::YEARS_BACK * 365;

        return CarbonImmutable::today()->subDays((int) floor($span * $this->random->nextFloat() ** 2));
    }

    private function title(int $index): string
    {
        $subject = self::SUBJECTS[$this->random->getInt(0, count(self::SUBJECTS) - 1)];
        $format = self::FORMATS[$this->random->getInt(0, count(self::FORMATS) - 1)];

        return ucfirst(sprintf($format, $subject)).' #'.$index;
    }

    private function kind(): string
    {
        $kinds = array_keys(DownloadsSchemaSeeder::KINDS);

        return $kinds[$this->random->getInt(0, count($kinds) - 1)];
    }
}
