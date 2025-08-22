<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GameSession;
use App\Services\SessionSwapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ExecuteSessionSwap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sessionId;

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(SessionSwapper $swapper): void
    {
        $lockKey = "session-swap:{$this->sessionId}";
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->block(10)) {
            return;
        }

        try {
            $session = GameSession::find($this->sessionId);
            if ($session === null || ! $session->isRunning) {
                return;
            }

            $nextSwapAt = $session->status_at->copy()
                ->addSeconds(($session->current_round + 1) * $session->swapTime());

            $session->refresh();

            $swapper->performSwap($session, $nextSwapAt);

            self::dispatch($session->id)
                ->delay(now()->addSeconds($session->swapTime()));
        } finally {
            $lock->release();
        }
    }
}
