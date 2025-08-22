<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GameSwap;
use App\Services\GamePlayerBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryUnackedSwaps implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $swapId,
    ) {}

    public function handle(): void
    {
        $swap = GameSwap::with([
            'game',
            'sessionPlayer',
        ])->find($this->swapId);
        if (! $swap || $swap->executed_at || !$swap->sessionPlayer->is_connected) {
            return; // already acked/not available
        }

        (new GamePlayerBroadcast($swap->sessionPlayer))
            ->swap(
                roundNumber: $swap->round_number,
                swapAt: $swap->swap_at,
                newGame: $swap->game,
                saveUrl: $swap->save_state_path
            );

        // Re-dispatch this job with delay (retry loop)
        self::dispatch($this->swapId)->delay(3);
    }
}
