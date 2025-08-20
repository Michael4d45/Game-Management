<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Models\Game;
use App\Models\GameSession; // Make sure to import the Game model
use App\Services\GamePlayerBroadcast;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Override;

class SessionGamesTable extends BaseWidget
{
    public GameSession|null $record = null;

    protected array|int|string $columnSpan = 'full';

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // @phpstan-ignore argument.type
                // @phpstan-ignore method.nonObject
                $this->record->games()->getQuery()
            )
            ->columns([
                TextColumn::make('file')
                    ->label('Game File'),
                // You can add more columns if your Game model has other relevant attributes
            ])
            ->recordActions([
                Action::make('swapAllPlayers')
                    ->label('Swap All Players')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Game $game): void {
                        // @phpstan-ignore property.nonObject
                        foreach ($this->record->players as $player) {
                            GamePlayerBroadcast::toPlayer($player)
                                ->swap(
                                    roundNumber: 999, // admin override
                                    swapAt: now()->addSeconds(3),
                                    newGame: $game
                                );
                        }

                        Notification::make()
                            ->title("All players swapped to {$game->file}")
                            ->success()
                            ->send();
                    }),
            ])
            ->poll();
    }
}
