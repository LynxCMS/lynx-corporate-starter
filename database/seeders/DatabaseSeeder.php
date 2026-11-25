<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\DownloadLibrary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use LynxCms\Core\Actions\Roles\AssignRoles;
use LynxCms\Core\Support\Value;
use RuntimeException;

/**
 * Arcturus Systems: an owner and an editor, Lynx installed with the owner as superuser, the schema, the
 * pages the home page features, the home page's sections, and the menus, SEO and page cache.
 * php artisan migrate:fresh --seed starts over.
 */
final class DatabaseSeeder extends Seeder
{
    private const int DEMO_DOWNLOADS = 60;

    private const int DEMO_HISTORY_DAYS = 90;

    public function run(): void
    {
        $password = Value::string(config('site.owner_password'));

        if ($password === '') {
            throw new RuntimeException('Set OWNER_PASSWORD in .env before seeding.');
        }

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@arcturus.example'],
            ['name' => 'Olivia Owner', 'password' => Hash::make($password)],
        );

        Artisan::call('lynx:install', ['--user' => (string) $owner->id]);

        $editor = User::query()->firstOrCreate(
            ['email' => 'editor@arcturus.example'],
            ['name' => 'Eddie Editor', 'password' => Hash::make($password)],
        );

        lynx()->run(AssignRoles::class, ['user' => (string) $editor->id, 'roles' => ['editor']]);

        $this->call([SiteSchemaSeeder::class, SectionsSchemaSeeder::class, DownloadsSchemaSeeder::class, SiteContentSeeder::class, DownloadsContentSeeder::class, LegalSeeder::class, HomeSectionsSeeder::class, SiteFeaturesSeeder::class, SearchAndBookmarksSeeder::class]);

        // The public demo resets every hour: a small library with made-up history keeps its
        // resources list and the admin's lists from looking empty (site:seed-downloads makes more).
        if (config('site.demo_reset') === true) {
            (new DownloadLibrary)->seed(self::DEMO_DOWNLOADS, self::DEMO_HISTORY_DAYS);
        }
    }
}
