<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Facades\Position;
use App\Models\GameSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                TextColumn::make('round_number')->label('Round'),
                TextColumn::make('player_id')->label('Player'),
                TextColumn::make('game_name')->label('Game'),
                TextColumn::make('swap_at')->dateTime(
                    timezone: Position::timezone(),
                ),
                TextColumn::make('executed_at')->dateTime(
                    timezone: Position::timezone(),
                ),
            ])
            ->poll();
    }
}
