<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class StartGameEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public string $sessionName) {}

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function broadcastOn(): array
    {
        return [new PrivateChannel("session.{$this->sessionName}")];
    }

    /**
     * @return array<mixed>
     */
    public function broadcastWith(): array
    {
        $startAt = GameSession::query()->firstWhere('name', $this->sessionName)?->start_at;
        if (! $startAt) {
            throw new \Exception('Expected start at for session: ' . $this->sessionName);
        }

        return [
            'type' => 'start_game',
            'payload' => [
                'start_time' => $startAt->getTimestamp(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
