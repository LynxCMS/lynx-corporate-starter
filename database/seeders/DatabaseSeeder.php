<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
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

        $this->call([SiteSchemaSeeder::class, SectionsSchemaSeeder::class, SiteContentSeeder::class, HomeSectionsSeeder::class, SiteFeaturesSeeder::class, SearchAndBookmarksSeeder::class]);
    }
}
