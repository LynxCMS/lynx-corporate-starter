<?php

declare(strict_types=1);

namespace App\Search;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LynxCms\Core\Support\Value;

/**
 * Pages ranked by how often visitors open them from search results, recent visits counting most.
 *
 * Each visit adds 2^(age of the epoch / half-life) to the page's score: a visit a half-life ago
 * weighs half as much as one now, without rewriting old scores. Sorting by the stored score is
 * therefore the decayed ranking at any moment. A double holds 1,024 half-lives, nearly 20 years
 * with a week's half-life, before the epoch in config/site.php needs moving.
 */
final class Popularity
{
    private const string TABLE = 'page_popularity';

    public function record(int $pageId): void
    {
        $weight = $this->weight();
        $updated = DB::table(self::TABLE)->where('page_id', $pageId)->increment('score', $weight, ['updated_at' => now()]);

        if ($updated === 0) {
            DB::table(self::TABLE)->insert(['page_id' => $pageId, 'score' => $weight, 'updated_at' => now()]);
        }
    }

    /**
     * Page ids, most popular first.
     *
     * @return list<int>
     */
    public function top(int $limit): array
    {
        return Value::ints(DB::table(self::TABLE)->orderByDesc('score')->limit($limit)->pluck('page_id')->all());
    }

    private function weight(): float
    {
        $epoch = CarbonImmutable::parse(Value::string(config('site.popularity.epoch'), '2026-01-01'));
        $halfLife = max(1, Value::int(config('site.popularity.half_life_days'), 7)) * 86400;

        return 2 ** ((now()->getTimestamp() - $epoch->getTimestamp()) / $halfLife);
    }
}
