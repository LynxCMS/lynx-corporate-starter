<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Process;

/*
 * What the Docker image relies on: a first start seeds the demo, later starts leave the content
 * alone, and links follow the scheme the proxy in front of the container saw.
 */

it('seeds the demo content into an empty database, once', function (): void {
    // A database of its own: Lynx makes field tables outside migrations, so the test's own can't be emptied.
    $database = (string) tempnam(sys_get_temp_dir(), 'seed-once');
    $artisan = fn (string $command) => Process::path(base_path())->env([
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $database,
        'CACHE_STORE' => 'array',
        'OWNER_PASSWORD' => 'password-for-tests',
        'SITE_ARTWORK' => 'false',
    ])->timeout(120)->run("php artisan {$command}")->throw();

    try {
        $artisan('migrate --force');
        $artisan('site:seed-once');
        $artisan('site:seed-once');

        $count = fn (string $sql): int => (int) (new PDO("sqlite:{$database}"))->query($sql)?->fetchColumn();

        expect($count('select count(*) from users'))->toBe(1)
            ->and($count("select count(*) from lynx_templates where name = 'home'"))->toBe(1);
    } finally {
        unlink($database);
    }
});

it('leaves a seeded site alone', function (): void {
    $home = home();
    $home->title = 'Changed by an editor';
    $home->save();

    $this->artisan('site:seed-once')->assertSuccessful();

    expect(User::query()->count())->toBe(1)
        ->and((string) home()->title)->toBe('Changed by an editor');
});

it('builds https links behind a proxy that ends TLS', function (): void {
    $response = $this->withoutVite()->get('/', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.example', 'X-Forwarded-Port' => '443']);

    $scripts = dataValues($response, 'src');

    expect($scripts)->not->toBe([])
        ->and(array_filter($scripts, fn (string $src): bool => ! str_starts_with($src, 'https://demo.example/')))->toBe([]);
});
