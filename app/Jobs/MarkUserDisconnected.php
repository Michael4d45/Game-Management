<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SessionPlayer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class MarkUserDisconnected implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $playerName,
    ) {}

    public function handle(): void
    {
        // Only disconnect if no newer heartbeat has been received
        $lastHeartbeat = Cache::get("heartbeat:{$this->playerName}");

        // @phpstan-ignore argument.type
        if ($lastHeartbeat && now()->diffInSeconds($lastHeartbeat, true) < 12) {
            // Still active, skip disconnect
            return;
        }

        $player = SessionPlayer::find($this->playerName);
        if ($player) {
            $player->update(['is_connected' => false]);
        }
    }
}
