<?php

declare(strict_types=1);

namespace App\Events;

class ChangeGameStateEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public string $state,
        public int $stateAt,
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'state' => $this->state,
            'state_at' => $this->stateAt,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'change_game_state';
    }
}
