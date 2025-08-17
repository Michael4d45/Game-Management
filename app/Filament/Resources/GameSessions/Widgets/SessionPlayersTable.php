<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Widgets;

use App\Facades\Position;
use App\Models\GameSession;
use App\Models\SessionPlayer;
use App\Services\GamePlayerBroadcast;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

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
                Tables\Columns\TextColumn::make('player_name'),
                Tables\Columns\TextColumn::make('current_game'),
                Tables\Columns\TextColumn::make('ping'),
                Tables\Columns\IconColumn::make('is_ready')
                    ->boolean()
                    ->label('Ready')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_connected')
                    ->boolean()
                    ->label('Connected'),
                Tables\Columns\TextColumn::make('last_seen')->dateTime(
                    timezone: Position::timezone(),
                ),
            ])
            ->actions([
                Tables\Actions\Action::make('kick')
                    ->label('Kick')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SessionPlayer $player): void {
                        GamePlayerBroadcast::toPlayer($player)
                            ->kick('Removed by admin');

                        Notification::make()
                            ->title("Player {$player->player_name} kicked")
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('message')
                    ->label('Message')
                    ->form([
                        Forms\Components\TextInput::make('text')
                            ->label('Message')
                            ->required(),
                    ])
                    ->action(function (SessionPlayer $player, array $data): void {
                        GamePlayerBroadcast::toPlayer($player)
                            ->message($data['text']);

                        Notification::make()
                            ->title("Message sent to {$player->player_name}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('force_swap')
                    ->label('Force Swap')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('game')
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
                            ->title("Forced swap for {$player->player_name}")
                            ->warning()
                            ->send();
                    }),
            ])
            ->poll();
    }
}
