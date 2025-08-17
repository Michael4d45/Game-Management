<?php

declare(strict_types=1);

namespace App\Events;

use Override;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SessionEndedEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public string $sessionName) {}

    /**
     * @return array<mixed>
     */
    #[Override]
    public function broadcastOn(): array
    {
        return [new PrivateChannel("session.{$this->sessionName}")];
    }

    /**
     * @return array<mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'session_ended',
            'payload' => [],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
