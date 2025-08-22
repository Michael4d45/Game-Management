<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameSessionMode;
use App\Jobs\RetryUnackedSwaps;
use App\Models\GameSession;
use App\Models\GameSwap;
use Carbon\Carbon;

class SessionSwapper
{
    /**
     * Perform a swap for the session. Keep UI logic out of this service.
     */
    public function performSwap(GameSession $session, Carbon $swapAt): void
    {
        $roundNumber = $session->current_round + 1;

        $session->update([
            'current_round' => $roundNumber,
        ]);

        switch ($session->mode) {
            case GameSessionMode::SyncList:
                $newGame = $session->games->random();
                foreach ($session->players as $player) {
                    $swap = GameSwap::create([
                        'game_session_id' => $session->id,
                        'initiated_by' => auth()->id() ?? 'system',
                        'round_number' => $roundNumber,
                        'swap_at' => $swapAt,
                        'session_player_name' => $player->name,
                        'game_file' => $newGame->file,
                    ]);
                    if ($player->is_connected) {
                        RetryUnackedSwaps::dispatch($swap->id)->delay(3);
                    }
                }
                (new GameSessionBroadcast($session))
                    ->swap(
                        roundNumber: $roundNumber,
                        swapAt: $swapAt,
                        newGame: $newGame,
                        saveUrl: null
                    );
                break;

            case GameSessionMode::SaveSwap:
                foreach ($session->players as $player) {
                    $newGame = $session->chooseStartGameFor($player)
                               ?? $session->games->random();
                    $saveUrl = url("/saves/{$session->id}/{$player->name}.state");
                    $swap = GameSwap::create([
                        'game_session_id' => $session->id,
                        'initiated_by' => auth()->id() ?? 'system',
                        'round_number' => $roundNumber,
                        'swap_at' => $swapAt,
                        'session_player_name' => $player->name,
                        'game_file' => $newGame->file,
                        'save_state_path' => $saveUrl,
                    ]);
                    if ($player->is_connected) {
                        RetryUnackedSwaps::dispatch($swap->id)->delay(3);

                        (new GamePlayerBroadcast($player))
                            ->swap(
                                roundNumber: $roundNumber,
                                swapAt: $swapAt,
                                newGame: $newGame,
                                saveUrl: $saveUrl
                            );
                    }
                }
                break;
        }
    }
}
