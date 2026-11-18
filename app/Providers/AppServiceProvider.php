<?php

namespace App\Providers;

use App\Flickr\FillFromFlickr;
use Illuminate\Support\ServiceProvider;
use LynxCms\Core\Actions\Pages\SavePage;

class AppServiceProvider extends ServiceProvider
{
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
        // Photo pages take their album's thumbnails, photo count and description from Flickr on every save, whoever saves them.
        lynx()->hook(SavePage::class)->before($this->app->make(FillFromFlickr::class)(...));
    }
}
