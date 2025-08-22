<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Jobs\ExecuteSessionSwap;
use App\Models\GameSession;
use App\Services\GameSessionBroadcast;
use App\Services\SessionSwapper;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class SessionControlsWidget extends Widget
{
    protected string $view =
        'filament.resources.game-session-resource.widgets.session-controls';

    public GameSession|null $record = null;

    public int $startDelay = 15;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    public function start(): void
    {
        if (! $this->record) {
            return;
        }

        $this->record->update([
            'status' => 'running',
            'status_at' => $this->getDelayed(),
        ]);

        (new GameSessionBroadcast($this->record))->start();

        // schedule first automatic swap at status_at + swap_interval
        $firstSwapAt = $this->record->status_at->addSeconds($this->record->swapTime());

        ExecuteSessionSwap::dispatch($this->record->id)
            ->delay($firstSwapAt);

        Notification::make()
            ->title('Game start scheduled')
            ->success()
            ->send();
    }

    public function pause(): void
    {
        if (! $this->record) {
            return;
        }

        $this->record->update([
            'status' => 'paused',
            'status_at' => $this->getDelayed(),
        ]);

        (new GameSessionBroadcast($this->record))->pause();

        Notification::make()
            ->title('Game paused')
            ->warning()
            ->send();
    }

    public function clearSaves(): void
    {
        if (! $this->record) {
            return;
        }

        (new GameSessionBroadcast($this->record))->clearSaves();

        Notification::make()
            ->title('Saves cleared')
            ->warning()
            ->send();
    }

    public function triggerSwap(): void
    {
        if (! $this->record) {
            return;
        }

        app(SessionSwapper::class)->performSwap($this->record, $this->getDelayed());

        Notification::make()
            ->title('Swap triggered for all players')
            ->success()
            ->send();
    }

    private function getDelayed(): Carbon
    {
        return now()->addSeconds($this->startDelay);
    }
}
