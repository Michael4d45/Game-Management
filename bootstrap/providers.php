<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\FilamentAssetProvider;
use Laravel\Reverb\ReverbServiceProvider;

return [
    AppServiceProvider::class,
    FilamentAssetProvider::class,
    AdminPanelProvider::class,
    ReverbServiceProvider::class,
];
