<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => [
    'auth:sanctum',
    'web',
]]);

Broadcast::channel('App.Models.{model}.{id}', function ($user, $model, $id) {
    return true;
});

Broadcast::channel('player.{playerName}', function ($user, $playerName) {
    return true;
});

Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    return true;
});
