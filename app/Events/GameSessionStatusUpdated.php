<?php

declare(strict_types=1);

namespace App\Events;

use Override;
use App\Models\GameSession;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GameSessionStatusUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public GameSession $gameSession;

    public string $status;

    public function __construct(GameSession $gameSession, string $status)
    {
        $this->gameSession = $gameSession;
        $this->status = $status;
    }

    #[Override]
    public function broadcastOn(): PrivateChannel
    {
        // NOTE: PrivateChannel name *should not* include the "private-" prefix;
        // Echo.private('session.{id}') will map to the server's PrivateChannel('session.{id}')
        return new PrivateChannel("session.{$this->gameSession->id}");
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return ['status' => $this->status];
    }

    public function broadcastAs(): string
    {
        return 'session_status';
    }
}
