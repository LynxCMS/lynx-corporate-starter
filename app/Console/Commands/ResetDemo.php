<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use LynxCms\Admin\Branding\Branding;
use LynxCms\Core\Support\Value;

/**
 * Puts the public demo back as the seeders made it: drops every table and what visitors uploaded,
 * seeds again and empties the caches. Runs only where config site.demo_reset is on, since it
 * deletes everything editors did.
 */
#[Signature('site:reset-demo')]
#[Description('Put the demo back to its seeded content, dropping every edit and upload')]
final class ResetDemo extends Command
{
    public function handle(): int
    {
        if (config('site.demo_reset') !== true) {
            $this->components->error('This site is not the demo: set SITE_DEMO_RESET=true to reset it.');

            return self::FAILURE;
        }

        // Visitors get "back soon" for the few seconds the tables are missing, not an error.
        $this->call('down', ['--retry' => 60]);

        try {
            $this->deleteUploads();
            $this->call('migrate:fresh', ['--force' => true]);
            $this->call('cache:clear');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            $this->call('lynx:page-cache', ['--clear' => true]);
        } finally {
            $this->call('up');
        }

        return self::SUCCESS;
    }

    /** Media files (public and private), the admin's logo and the files sent with forms. */
    private function deleteUploads(): void
    {
        $mediaRoot = Value::string(config('lynx-media.root'), 'lynx');

        $folders = [
            [Value::string(config('lynx-media.disk'), 'public'), $mediaRoot],
            [Value::string(config('lynx-media.private_disk'), 'local'), $mediaRoot],
            [Branding::DISK, Branding::FOLDER],
            [Value::string(config('lynx-forms.disk'), 'local'), 'forms'],
        ];

        foreach ($folders as [$disk, $folder]) {
            Storage::disk($disk)->deleteDirectory($folder);
        }
    }
}
