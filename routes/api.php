<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\SessionPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/upload-save', function (Request $request) {
    $request->validate([
        'file' => 'required|file',
        'player_id' => 'required|string',
        'session_id' => 'required|string',
    ]);

    $sessionId = $request->string('session_id')->toString();
    $playerId = $request->string('player_id')->toString();

    $path = $request->file('file')->storeAs(
        "saves/$sessionId",
        "$playerId.state"
    );

    return response()->json([
        'status' => 'ok',
        'path' => url("storage/$path"),
    ]);
});

Route::post('/register-player', function (Request $request) {
    $request->validate([
        'player_id' => 'required|string|unique:session_players,player_id',
    ]);

    // Create player record
    $player = SessionPlayer::create([
        'player_id' => $request->player_id,
        'player_name' => $request->player_id,
        'game_session_id' => null,
    ]);

    // Create forever Sanctum token
    $token = $player->createToken('forever-token')->plainTextToken;

    $key = config()->string('reverb.apps.apps.0.key');

    return response()->json([
        'player_id' => $player->player_id,
        'bearer_token' => $token,
        'reverb_app_key' => $key,
        'reverb_auth_url' => url('/broadcasting/auth'),
    ]);
});

Route::get('/check-session/{name}', function ($name) {
    $exists = GameSession::where('name', $name)->exists();
    if (! $exists) {
        return response()->json(['exists' => false], 404);
    }

    return response()->json(['exists' => true]);
});

Route::get('/roms/{filename}', function ($filename) {
    $path = storage_path("app/roms/{$filename}");
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path);
});

Route::get('/scripts/latest', function () {
    $files = collect(Storage::files('scripts'))
        ->filter(fn ($f) => str_ends_with($f, '.lua'))
        ->sortDesc()
        ->values();
    if ($files->isEmpty()) {
        abort(404);
    }

    return response()->download(storage_path('app/' . $files->first()));
});
