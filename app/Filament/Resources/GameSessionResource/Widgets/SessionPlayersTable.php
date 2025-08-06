<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Widgets;

use App\Facades\Position;
use App\Models\GameSession;
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
        // dd(Position::timezone());
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
            ]);
    }
}
