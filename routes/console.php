<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The public demo goes back to its seeded content every hour (config site.demo_reset).
Schedule::command('site:reset-demo')->hourly()->when(fn (): bool => config('site.demo_reset') === true);
