<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ClearSavesEvent;
use App\Events\DownloadLuaEvent;
use App\Events\DownloadROMEvent;
use App\Events\KickEvent;
use App\Events\PrepareSwapEvent;
use App\Events\ServerMessageEvent;
use App\Events\SwapEvent;
use App\Models\Game;
use App\Models\SessionPlayer;
use Carbon\Carbon;

class GamePlayerBroadcast
{
    private string $channel;

    public function __construct(
        public SessionPlayer $player
    ) {
        $this->channel = "player.{$player->name}";
    }

    public function swap(int $roundNumber, Carbon $swapAt, Game $newGame, string|null $saveUrl = null): void
    {
        broadcast(new SwapEvent(
            channel: $this->channel,
            roundNumber: $roundNumber,
            swapAt: $swapAt,
            newGame: $newGame->file,
            saveUrl: $saveUrl
        ));
    }

    public function downloadROM(string $romName, string $romUrl): void
    {
        broadcast(new DownloadROMEvent(
            channel: $this->channel,
            romName: $romName,
            romUrl: $romUrl
        ));
    }

    public function downloadLua(string $luaVersion, string $luaUrl): void
    {
        broadcast(new DownloadLuaEvent(
            channel: $this->channel,
            luaVersion: $luaVersion,
            luaUrl: $luaUrl
        ));
    }

    public function message(string $text): void
    {
        broadcast(new ServerMessageEvent(
            channel: $this->channel,
            text: $text
        ));
    }

    public function kick(string $reason): void
    {
        $this->player->update([
            'game_session_id' => null,
        ]);
        broadcast(new KickEvent(
            channel: $this->channel,
            reason: $reason
        ));
    }

    public function prepareSwap(int $roundNumber, Carbon $uploadBy): void
    {
        broadcast(new PrepareSwapEvent(
            channel: $this->channel,
            roundNumber: $roundNumber,
            uploadBy: $uploadBy
        ));
    }

    public function clearSaves(): void
    {
        broadcast(new ClearSavesEvent(
            channel: $this->channel,
        ));
    }
}
