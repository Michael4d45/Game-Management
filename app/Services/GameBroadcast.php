<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DownloadLuaEvent;
use App\Events\DownloadROMEvent;
use App\Events\KickEvent;
use App\Events\ServerMessageEvent;
use App\Events\SwapEvent;
use App\Events\WelcomeEvent;

class GameBroadcast
{
    protected string $playerId;

    public static function toPlayer(string $playerId): self
    {
        $instance = new self;
        $instance->playerId = $playerId;

        return $instance;
    }

    public function welcome(string $sessionId, string $mode, int $swapInterval): void
    {
        broadcast(new WelcomeEvent(
            playerId: $this->playerId,
            sessionId: $sessionId,
            mode: $mode,
            swapInterval: $swapInterval
        ));
    }

    public function swap(int $roundNumber, string $swapAt, string $newGame, string|null $saveUrl = null): void
    {
        broadcast(new SwapEvent(
            playerId: $this->playerId,
            roundNumber: $roundNumber,
            swapAt: $swapAt,
            newGame: $newGame,
            saveUrl: $saveUrl
        ));
    }

    public function downloadROM(string $romName, string $romUrl): void
    {
        broadcast(new DownloadROMEvent(
            playerId: $this->playerId,
            romName: $romName,
            romUrl: $romUrl
        ));
    }

    public function downloadLua(string $luaVersion, string $luaUrl): void
    {
        broadcast(new DownloadLuaEvent(
            playerId: $this->playerId,
            luaVersion: $luaVersion,
            luaUrl: $luaUrl
        ));
    }

    public function message(string $text): void
    {
        broadcast(new ServerMessageEvent(
            playerId: $this->playerId,
            text: $text
        ));
    }

    public function kick(string $reason): void
    {
        broadcast(new KickEvent(
            playerId: $this->playerId,
            reason: $reason
        ));
    }
}
