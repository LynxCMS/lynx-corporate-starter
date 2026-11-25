<?php

declare(strict_types=1);

/*
 * app/Stats is meant to move to another site, or into a Lynx module: it uses nothing from the rest
 * of this app.
 */

arch('the stats code stands alone')
    ->expect('App\Stats')
    ->not->toUse(['App\Downloads', 'App\Livewire', 'App\Search', 'App\Support', 'App\Listeners', 'App\Mail', 'App\Models', 'Database\Seeders']);
