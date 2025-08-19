<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PositionService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
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
        // TODO: if local env or something
        // DB::listen(function (QueryExecuted $query): void {
        //     logger('debug', [
        //         'SQL' => $query->toRawSql(),
        //         'execution_time' => $query->time . 'ms',
        //     ]);
        // });
    }
}
