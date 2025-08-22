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
use Illuminate\Support\Facades\Log;

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
        Log::debug('ExecuteSessionSwap: starting job', [
            'sessionId' => $this->sessionId,
            'lockKey' => $lockKey,
        ]);

        $lock = Cache::lock($lockKey, 30);

        if (! $lock->block(10)) {
            Log::warning('ExecuteSessionSwap: could not acquire lock', [
                'sessionId' => $this->sessionId,
                'lockKey' => $lockKey,
            ]);

            return;
        }

        try {
            $session = GameSession::find($this->sessionId);

            if ($session === null) {
                Log::info('ExecuteSessionSwap: session not found', [
                    'sessionId' => $this->sessionId,
                ]);

                return;
            }

            Log::debug('ExecuteSessionSwap: session loaded', [
                'sessionId' => $this->sessionId,
                'isRunning' => $session->isRunning,
                'currentRound' => $session->current_round,
                'swapTime' => $session->swapTime(),
                'stateAt' => $session->state_at,
            ]);

            if (! $session->isRunning) {
                Log::info('ExecuteSessionSwap: session not running, skipping', [
                    'sessionId' => $this->sessionId,
                ]);

                return;
            }

            $nextSwapAt = now()->addSeconds($session->swapTime());

            $session->refresh();

            $swapper->performSwap($session, $nextSwapAt);

            self::dispatch($session->id)->delay($nextSwapAt);
        } catch (\Throwable $e) {
            Log::error('ExecuteSessionSwap: exception occurred', [
                'sessionId' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // rethrow so Laravel marks job as failed
        } finally {
            $lock->release();
        }
    }
}
