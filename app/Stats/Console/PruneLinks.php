<?php

declare(strict_types=1);

namespace App\Stats\Console;

use App\Stats\PageStats;
use Illuminate\Console\Command;

/** Forgets download links once they have expired (run daily by the scheduler). */
final class PruneLinks extends Command
{
    protected $signature = 'stats:prune';

    protected $description = 'Delete expired download links from the stats tables';

    public function handle(): int
    {
        $deleted = PageStats::connection()->table('stat_links')->where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired download links.");

        return self::SUCCESS;
    }
}
