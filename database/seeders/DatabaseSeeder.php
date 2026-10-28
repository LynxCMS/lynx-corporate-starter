<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use LynxCms\Core\Support\Value;
use RuntimeException;

/**
 * Arcturus Systems: an owner account, Lynx installed with that owner as superuser, the schema, the
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

        $this->call([SiteSchemaSeeder::class, SectionsSchemaSeeder::class]);
    }
}
