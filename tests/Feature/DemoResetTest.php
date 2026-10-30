<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/*
 * The public demo lets anyone into the admin, so every hour it goes back to the seeded content
 * and drops what visitors uploaded. Other sites made from the starter never reset.
 */

function demoResetEvent(): Event
{
    $events = array_filter(
        app(Schedule::class)->events(),
        fn (Event $event): bool => str_contains((string) $event->command, 'site:reset-demo'),
    );

    return array_values($events)[0] ?? throw new RuntimeException('The demo reset is not scheduled.');
}

it('resets the demo at the start of every hour', function (): void {
    config(['site.demo_reset' => true]);

    expect(demoResetEvent()->expression)->toBe('0 * * * *')
        ->and(demoResetEvent()->filtersPass(app()))->toBeTrue();
});

it('never resets a site that is not the demo', function (): void {
    config(['site.demo_reset' => false]);

    expect(demoResetEvent()->filtersPass(app()))->toBeFalse();
});

it('refuses to run on a site that is not the demo', function (): void {
    config(['site.demo_reset' => false]);
    User::factory()->create();

    $this->artisan('site:reset-demo')->assertFailed();

    expect(User::query()->count())->toBe(3);
});

it('puts the seeded content back and drops uploads', function (): void {
    // A database and a storage folder of its own: the reset wipes both, and they mustn't be the developer's.
    $root = sys_get_temp_dir().'/demo-reset-'.bin2hex(random_bytes(4));
    $database = "{$root}/database.sqlite";

    foreach (['app/public/lynx/999', 'app/private/lynx-admin', 'app/private/forms/a-form', 'framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $folder) {
        File::ensureDirectoryExists("{$root}/{$folder}");
    }
    touch($database);

    $artisan = fn (string $command) => Process::path(base_path())->env([
        'APP_ENV' => 'testing',
        'LARAVEL_STORAGE_PATH' => $root,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $database,
        'CACHE_STORE' => 'array',
        'OWNER_PASSWORD' => 'password-for-tests',
        'SITE_ARTWORK' => 'false',
        'SITE_DEMO_RESET' => 'true',
    ])->timeout(180)->run("php artisan {$command}")->throw();

    try {
        $artisan('migrate --force');
        $artisan('site:seed-once');

        $pdo = new PDO("sqlite:{$database}");
        $pdo->exec("insert into users (name, email, password) values ('Visitor', 'visitor@example.com', 'x')");
        $uploads = ["{$root}/app/public/lynx/999/upload.png", "{$root}/app/private/lynx-admin/logo.png", "{$root}/app/private/forms/a-form/cv.pdf"];
        array_walk($uploads, fn (string $file) => file_put_contents($file, 'uploaded'));

        $artisan('site:reset-demo');

        $count = fn (string $sql): int => (int) (new PDO("sqlite:{$database}"))->query($sql)?->fetchColumn();

        expect($count('select count(*) from users'))->toBe(2)
            ->and($count("select count(*) from lynx_templates where name = 'home'"))->toBe(1)
            ->and(array_filter($uploads, 'file_exists'))->toBe([])
            ->and(file_exists("{$root}/framework/down"))->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});

it('warns admin users in the sidebar that the demo resets', function (bool $isDemo): void {
    config(['site.demo_reset' => $isDemo]);

    $response = $this->actingAs(User::query()->firstOrFail())->get(route('lynx.admin.pages'))->assertOk();

    // The sidebar is printed twice, docked and as the phone's drawer, so the notice is in both or neither.
    expect(dataValues($response, 'data-demo-reset') !== [])->toBe($isDemo);
})->with(['on the demo' => true, 'on other sites' => false]);
