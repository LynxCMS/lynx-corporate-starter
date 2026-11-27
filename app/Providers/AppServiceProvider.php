<?php

namespace App\Providers;

use App\Livewire\DownloadForm;
use App\Support\DemoSite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    private const int PASSWORD_MIN_LENGTH = 12;

    private const int LIST_VARIANTS = 120;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('download-form', DownloadForm::class);
        $this->passwordRules();
        // The resources list keeps a cached copy per sort and page (4 sorts × about 25 pages).
        config(['lynx-cache.pages.max_variants' => max(self::LIST_VARIANTS, (int) config('lynx-cache.pages.max_variants'))]);
        DemoSite::configure();
    }

    /**
     * Every password form (the admin's included) asks for at least 12 characters with upper and
     * lower case letters, a number and a symbol. Live sites also refuse passwords found in known
     * data breaches, which asks an outside service.
     */
    private function passwordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(self::PASSWORD_MIN_LENGTH)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
