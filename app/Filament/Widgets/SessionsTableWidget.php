<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SessionsTableWidget extends BaseWidget
{
    protected static string|null $heading = 'Recent Sessions';

    protected array|int|string $columnSpan = 'full';

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->query(GameSession::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('players_count')
                    ->label('Players')
                    ->getStateUsing(fn ($record) => $record->players()->count()),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->paginated([5]);
    }
}
