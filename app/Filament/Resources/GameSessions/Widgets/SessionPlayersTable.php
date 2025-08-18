<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Facades\Position;
use App\Models\GameSession;
use App\Models\SessionPlayer;
use App\Services\GamePlayerBroadcast;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Override;

class SessionPlayersTable extends BaseWidget
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
                $this->record->players()->getQuery()
            )
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('current_game'),
                TextColumn::make('ping'),
                IconColumn::make('is_ready')
                    ->boolean()
                    ->label('Ready')
                    ->trueColor('success')
                    ->falseColor('danger'),
                IconColumn::make('is_connected')
                    ->boolean()
                    ->label('Connected'),
                TextColumn::make('last_seen')->dateTime(
                    timezone: Position::timezone(),
                ),
            ])
            ->recordActions([
                Action::make('kick')
                    ->label('Kick')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SessionPlayer $player): void {
                        GamePlayerBroadcast::toPlayer($player)
                            ->kick('Removed by admin');

                        Notification::make()
                            ->title("Player {$player->name} kicked")
                            ->danger()
                            ->send();
                    }),

                Action::make('message')
                    ->label('Message')
                    ->schema([
                        TextInput::make('text')
                            ->label('Message')
                            ->required(),
                    ])
                    ->action(function (SessionPlayer $player, array $data): void {
                        GamePlayerBroadcast::toPlayer($player)
                            ->message($data['text']);

                        Notification::make()
                            ->title("Message sent to {$player->name}")
                            ->success()
                            ->send();
                    }),

                Action::make('force_swap')
                    ->label('Force Swap')
                    ->color('warning')
                    ->schema([
                        TextInput::make('game')
                            ->label('Game Name')
                            ->required(),
                    ])
                    ->action(function (SessionPlayer $player, array $data): void {
                        GamePlayerBroadcast::toPlayer($player)
                            ->swap(
                                roundNumber: 999, // admin override
                                swapAt: now()->addSeconds(3),
                                newGame: $data['game']
                            );

                        Notification::make()
                            ->title("Forced swap for {$player->name}")
                            ->warning()
                            ->send();
                    }),
            ])
            ->poll();
    }
}
