<?php

declare(strict_types=1);

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('sessions')
    ->name('sessions.')
    ->group(function (): void {
        Route::post('{gameSession:name}/join', [SessionController::class, 'joinSession'])->name('join');
    });
