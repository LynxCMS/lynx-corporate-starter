<?php

declare(strict_types=1);

namespace App\Stats\Console;

use App\Stats\FakeHistory as History;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LynxCms\Core\Pages\Page;

/**
 * Fills the stats tables with made-up history for the pages a selector finds (App\Stats\FakeHistory),
 * for testing listings and the admin's lists. Never in production unless forced.
 */
final class FakeHistory extends Command
{
    protected $signature = 'stats:fake-history {selector : Lynx selector of the pages, such as "template=download"}
        {--days=90 : Days of history, up to today}
        {--date-field=published_on : The date field that says when a page came out; history starts there}
        {--subjects= : Comma-separated subjects downloads are shared among, the first getting most}
        {--seed= : Seed for a repeatable history}
        {--force : Run in production}';

    protected $description = 'Make up realistic daily views and downloads for testing';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('This makes up visitor counts. Run it in production only with --force.');

            return self::FAILURE;
        }

        $seed = $this->option('seed');
        $history = new History(max(1, (int) $this->option('days')), is_numeric($seed) ? (int) $seed : null);
        $subjects = array_values(array_filter(explode(',', (string) $this->option('subjects')))) ?: [''];
        $field = (string) $this->option('date-field');
        $pages = lynx()->pages->find((string) $this->argument('selector'));
        $written = 0;

        foreach ($pages as $page) {
            $written += History::store($history->rows((int) $page->id, $this->published($page, $field), $subjects));
        }

        $this->clearPageCache();
        $this->info("Wrote {$written} daily counts for {$pages->count()} pages.");

        return self::SUCCESS;
    }

    private function published(Page $page, string $field): ?CarbonInterface
    {
        $value = $page->templateField($field) === null ? null : $page->get($field);

        return $value instanceof CarbonInterface ? $value : null;
    }

    private function clearPageCache(): void
    {
        if (array_key_exists('lynx:page-cache', Artisan::all())) {
            Artisan::call('lynx:page-cache', ['--clear' => true]);
        }
    }
}
