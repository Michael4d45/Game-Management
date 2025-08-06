<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\SessionPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionController
{
    public function joinSession(Request $request, GameSession $gameSession): JsonResponse
    {
        $sessionId = $gameSession->id;
        $playerId = Str::uuid();
        $playerName = $request->input('name', 'Player');

        SessionPlayer::updateOrCreate(
            ['player_id' => $playerId],
            [
                'game_session_id' => $sessionId,
                'player_name' => $playerName,
                'is_connected' => true,
                'last_seen' => now(),
            ]
        );

        return response()->json([
            'player_id' => $playerId,
            'game_session_id' => $sessionId,
            'status' => 'joined',
        ]);
    }
}
