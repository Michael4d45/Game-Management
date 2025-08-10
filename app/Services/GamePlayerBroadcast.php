<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DownloadLuaEvent;
use App\Events\DownloadROMEvent;
use App\Events\KickEvent;
use App\Events\PrepareSwapEvent;
use App\Events\ServerMessageEvent;
use App\Events\SwapEvent;
use App\Models\GameSwap;
use App\Models\SessionPlayer;
use Carbon\Carbon;

class GamePlayerBroadcast
{
    protected SessionPlayer|null $player = null;

    public static function toPlayer(SessionPlayer $player): self
    {
        $instance = new self;
        $instance->player = $player;

        return $instance;
    }

    public function swap(int $roundNumber, Carbon $swapAt, string $newGame, string|null $saveUrl = null): void
    {
        if (! $this->player) {
            return;
        }
        GameSwap::create([
            'game_session_id' => $this->player->game_session_id,
            'player_id' => $this->player->player_id,
            'game_name' => $newGame,
            'save_state_path' => $saveUrl,
            'initiated_by' => auth()->id() ?? 'system',
            'round_number' => $roundNumber,
            'swap_at' => $swapAt,
        ]);

        broadcast(new SwapEvent(
            playerId: $this->player->player_id,
            roundNumber: $roundNumber,
            swapAt: $swapAt,
            newGame: $newGame,
            saveUrl: $saveUrl
        ));
    }

    public function downloadROM(string $romName, string $romUrl): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new DownloadROMEvent(
            playerId: $this->player->player_id,
            romName: $romName,
            romUrl: $romUrl
        ));
    }

    public function downloadLua(string $luaVersion, string $luaUrl): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new DownloadLuaEvent(
            playerId: $this->player->player_id,
            luaVersion: $luaVersion,
            luaUrl: $luaUrl
        ));
    }

    public function message(string $text): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new ServerMessageEvent(
            playerId: $this->player->player_id,
            text: $text
        ));
    }

    public function kick(string $reason): void
    {
        if (! $this->player) {
            return;
        }
        $this->player->update([
            'game_session_id' => null,
        ]);
        broadcast(new KickEvent(
            playerId: $this->player->player_id,
            reason: $reason
        ));
    }

    public function prepareSwap(int $roundNumber, Carbon $uploadBy): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new PrepareSwapEvent(
            playerId: $this->player->player_id,
            roundNumber: $roundNumber,
            uploadBy: $uploadBy
        ));
    }
}
