<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\Support\DownloadLibrary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Many download pages with made-up history, to test the resources list (sorting, pages, counts)
 * and the admin's lists. It changes the site's content, so never in production unless forced.
 */
final class SeedDownloads extends Command
{
    protected $signature = 'site:seed-downloads {--count=500 : Download pages to make} {--days=90 : Days of history} {--seed= : Seed for a repeatable library} {--force : Run in production}';

    protected $description = 'Make many download pages with realistic made-up downloads and views';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('This adds made-up pages and counts. Run it in production only with --force.');

            return self::FAILURE;
        }

        $seed = $this->option('seed');
        $count = max(1, (int) $this->option('count'));
        $written = (new DownloadLibrary(is_numeric($seed) ? (int) $seed : null))->seed($count, max(1, (int) $this->option('days')));

        if (array_key_exists('lynx:page-cache', Artisan::all())) {
            Artisan::call('lynx:page-cache', ['--clear' => true]);
        }

        $this->info("Made {$count} download pages with {$written} daily counts.");

        return self::SUCCESS;
    }
}
