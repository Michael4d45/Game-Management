<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Widgets;

use App\Facades\Position;
use App\Models\GameSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\TableWidget as BaseWidget;

class SessionSwapsTable extends BaseWidget
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
                $this->record->swaps()->latest()->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('round_number')->label('Round'),
                Tables\Columns\TextColumn::make('player_id')->label('Player'),
                Tables\Columns\TextColumn::make('game_name')->label('Game'),
                Tables\Columns\TextColumn::make('swap_at')->dateTime(
                    timezone: Position::timezone(),
                ),
                Tables\Columns\TextColumn::make('executed_at')->dateTime(
                    timezone: Position::timezone(),
                ),
            ])
            ->poll();
    }
}
