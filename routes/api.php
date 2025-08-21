<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\GameSwap;
use App\Models\SessionPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage; // Added for file storage

/*
|--------------------------------------------------------------------------
| Public Endpoints
|--------------------------------------------------------------------------
*/

// Upload save state (System 2)
Route::post('/upload-save', function (Request $request) {
    $request->validate([
        'file' => 'required|file',
        'name' => 'required|string',
        'session_id' => 'required|string',
    ]);

    $sessionId = $request->string('session_id')->toString();
    $name = $request->string('name')->toString();

    $path = $request->file('file')->storeAs(
        "saves/$sessionId",
        "$name.state"
    );

    return response()->json([
        'status' => 'ok',
        'path' => url("storage/$path"),
    ]);
});

// Register a new player and return Sanctum token + Reverb info
Route::post('/register-player', function (Request $request) {
    $request->validate([
        'name' => 'required|string|unique:session_players,name',
    ]);

    $player = SessionPlayer::create([
        'name' => $request->name,
    ]);

    $token = $player->createToken('forever-token')->plainTextToken;

    $key = config('reverb.apps.apps.0.key');

    return response()->json([
        'bearer_token' => $token,
        'reverb_app_key' => $key,
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
    $path = config()->string('game.files_path') . '/' . $filename;
    logger('attempting to download: ' . $filename);
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

// New endpoint for BizhawkFiles.zip
Route::get('/BizhawkFiles.zip', function () {
    $zipPath = storage_path('app/BizhawkFiles.zip');

    // Create the zip file on the fly if it doesn't exist
    // In a production environment, you would likely pre-generate this.
    if (! file_exists($zipPath)) {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add Firmware directory contents
            $firmwareDir = storage_path('app/Firmware');
            if (is_dir($firmwareDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($firmwareDir),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    // Skip directories (they would be added automatically) and non-files
                    if (! $file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($firmwareDir) + 1);
                        $zip->addFile($filePath, 'Firmware/' . $relativePath);
                    }
                }
            } else {
                // Optionally create the Firmware directory if it's missing for demonstration
                // In a real scenario, this content would be managed by deployment
                mkdir($firmwareDir, 0755, true);
                file_put_contents($firmwareDir . '/placeholder.txt', 'This is a placeholder firmware file.');
                $zip->addFile($firmwareDir . '/placeholder.txt', 'Firmware/placeholder.txt');
            }

            // Add config.ini
            $configPath = storage_path('app/config.ini');
            if (! file_exists($configPath)) {
                // Create a dummy config.ini if it doesn't exist for demonstration
                file_put_contents($configPath, "[EmuHawk]\nExampleSetting=true\n");
            }
            $zip->addFile($configPath, 'config.ini');

            $zip->close();
        } else {
            abort(500, 'Could not create BizhawkFiles.zip');
        }
    }

    return response()->download($zipPath, 'BizhawkFiles.zip', [
        'Content-Type' => 'application/zip',
    ]);
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

        logger($player->name . ' joined session ' . $session->name, ['session' => $session->toArray()]);

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
            'game_file' => $request->string('current_game')->toString(),
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

        $currentGame = $player->gameSession?->chooseStartGameFor($player)?->file;
        $player->update([
            'game_file' => $currentGame,
        ]);

        return response()->json([
            'game_file' => $currentGame,
            'start_at' => $player->gameSession?->start_at?->getTimestamp(),
        ]);
    });

    // Swap complete (Phase 4)
    Route::post('/swap-complete', function (Request $request) {
        $request->validate([
            'round_number' => 'required|integer',
        ]);

        /** @var SessionPlayer $player */
        $player = auth()->user();

        GameSwap::where('game_session_id', $player->game_session_id)
            ->where('session_player_name', $player->name)
            ->where('round_number', $request->integer('round_number'))
            ->update(['executed_at' => now()]);

        return response()->json(['status' => 'ok']);
    });
});
