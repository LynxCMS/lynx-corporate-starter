<?php

namespace App\Providers;

use App\Livewire\DownloadForm;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    private const int PASSWORD_MIN_LENGTH = 12;

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
    }

    /**
     * Every password form (the admin's included) asks for at least 12 characters. Live sites also
     * refuse passwords found in known data breaches, which asks an outside service.
     */
    private function passwordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(self::PASSWORD_MIN_LENGTH);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
