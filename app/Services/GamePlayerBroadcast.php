<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DownloadLuaEvent;
use App\Events\DownloadROMEvent;
use App\Events\KickEvent;
use App\Events\PrepareSwapEvent;
use App\Events\ServerMessageEvent;
use App\Events\SwapEvent;
use App\Models\Game;
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

    public function swap(int $roundNumber, Carbon $swapAt, Game $newGame, string|null $saveUrl = null): void
    {
        if (! $this->player) {
            return;
        }
        GameSwap::create([
            'game_session_id' => $this->player->game_session_id,
            'session_player_name' => $this->player->name,
            'game_file' => $newGame->file,
            'save_state_path' => $saveUrl,
            'initiated_by' => auth()->id() ?? 'system',
            'round_number' => $roundNumber,
            'swap_at' => $swapAt,
        ]);

        broadcast(new SwapEvent(
            playerName: $this->player->name,
            roundNumber: $roundNumber,
            swapAt: $swapAt,
            newGame: $newGame->file,
            saveUrl: $saveUrl
        ));
    }

    public function downloadROM(string $romName, string $romUrl): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new DownloadROMEvent(
            playerName: $this->player->name,
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
            playerName: $this->player->name,
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
            playerName: $this->player->name,
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
            playerName: $this->player->name,
            reason: $reason
        ));
    }

    public function prepareSwap(int $roundNumber, Carbon $uploadBy): void
    {
        if (! $this->player) {
            return;
        }
        broadcast(new PrepareSwapEvent(
            playerName: $this->player->name,
            roundNumber: $roundNumber,
            uploadBy: $uploadBy
        ));
    }
}
