<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

abstract class CommandEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(
        public string $channel,
    ) {
    }

    #[\Override]
    public function broadcastOn(): PrivateChannel
    {
        logger(sprintf(
            'Event [%s] broadcasting to channel [%s]',
            static::class,
            $this->channel
        ));
        return new PrivateChannel($this->channel);
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->getType(),
            'payload' => $this->getPayload(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }

    abstract public function getType(): string;

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return [];
    }
}
