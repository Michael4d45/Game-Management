<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\GameSwap;
use App\Models\SessionPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Endpoints
|--------------------------------------------------------------------------
*/

// Upload save state (System 2)
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

// Register a new player and return Sanctum token + Reverb info
Route::post('/register-player', function (Request $request) {
    $request->validate([
        'player_id' => 'required|string|unique:session_players,player_id',
    ]);

    $player = SessionPlayer::create([
        'player_id' => $request->player_id,
        'player_name' => $request->player_id,
    ]);

    $token = $player->createToken('forever-token')->plainTextToken;

    $key = config('reverb.apps.apps.0.key');

    return response()->json([
        'player_id' => $player->player_id,
        'bearer_token' => $token,
        'reverb_app_key' => $key,
        'reverb_auth_url' => url('/broadcasting/auth'),
    ]);
});

// Check if a session exists
Route::get('/check-session/{name}', function ($name) {
    $exists = GameSession::where('name', $name)->exists();
    if (! $exists) {
        return response()->json(['exists' => false], 404);
    }

    return response()->json(['exists' => true]);
});

// ROM download
Route::get('/roms/{filename}', function ($filename) {
    $path = storage_path("app/roms/{$filename}");
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path);
});

// Latest Lua script download
Route::get('/scripts/latest', function () {
    $filename = config('game.latest_lua_script');

    // @phpstan-ignore encapsedStringPart.nonString
    return response()->download(storage_path("app/scripts/$filename"));
});

/*
|--------------------------------------------------------------------------
| Authenticated Endpoints (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function (): void {

    // Check if token is still valid
    Route::post('/check-token', function () {
        return response()->json(['exists' => true]);
    });

    // Join a session
    Route::post('/join-session/{name}', function ($name) {
        /** @var GameSession|null $session */
        $session = GameSession::firstWhere('name', $name);
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        /** @var SessionPlayer $player */
        $player = auth()->user();
        if ($player->game_session_id !== $session->id) {
            $player->update([
                'game_session_id' => $session->id,
            ]);
        }
        $session->load([
            'games',
        ]);

        return response()->json($session->toArray());
    });

    // Heartbeat (Phase 3)
    Route::post('/heartbeat', function (Request $request) {
        $request->validate([
            'ping' => 'required|integer',
            'current_game' => 'nullable|string',
        ]);

        /** @var SessionPlayer $player */
        $player = auth()->user();
        $player->update([
            'ping' => $request->integer('ping'),
            'current_game' => $request->string('current_game')->toString(),
            'last_seen' => now(),
            'is_connected' => true,
        ]);

        return response()->json(['status' => 'ok']);
    });

    // Ready state (Phase 1)
    Route::post('/ready', function () {
        /** @var SessionPlayer $player */
        $player = auth()->user();
        $player->update(['is_ready' => true]);

        return response()->json(['status' => 'ok']);
    });

    // Swap complete (Phase 4)
    Route::post('/swap-complete', function (Request $request) {
        $request->validate([
            'round_number' => 'required|integer',
        ]);

        /** @var SessionPlayer $player */
        $player = auth()->user();

        GameSwap::where('game_session_id', $player->game_session_id)
            ->where('player_id', $player->player_id)
            ->where('round_number', $request->integer('round_number'))
            ->update(['executed_at' => now()]);

        return response()->json(['status' => 'ok']);
    });
});
