<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions;

use Override;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Filament\Resources\GameSessionResource\Pages;
use App\Models\GameSession;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameSessionResource extends Resource
{
    protected static string|null $model = GameSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('mode')
                    ->options(['sync_list' => 'Sync List', 'save_swap' => 'Save Swap'])
                    ->default('sync_list')
                    ->required(),
                Toggle::make('is_active')
                    ->default(false),
                TextInput::make('swap_interval')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Select::make('games')
                    ->label('Games')
                    ->multiple()
                    ->relationship('games', 'file') // 'file' is the column to display
                    ->preload() // loads all options immediately
                    ->searchable(), // allows searching
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('current_game')
                    ->searchable(),
                TextColumn::make('mode')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('players_count')
                    ->label('Players')
                    ->getStateUsing(fn ($record) => $record->players()->count()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modal(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [

        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListGameSessions::route('/'),
            'create' => CreateGameSession::route('/create'),
            'edit' => EditGameSession::route('/{record}/edit'),
        ];
    }
}
