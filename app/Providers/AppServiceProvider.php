<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PositionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('position', function () {
            $service = new PositionService;
            $service->detect(); // auto-detect on first resolve

            return $service;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
