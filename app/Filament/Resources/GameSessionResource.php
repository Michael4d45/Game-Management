<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GameSessionResource\Pages;
use App\Models\GameSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;

class GameSessionResource extends Resource
{
    protected static string|null $model = GameSession::class;

    protected static string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('mode')
                    ->options(['sync_list' => 'Sync List', 'save_swap' => 'Save Swap'])
                    ->default('sync_list')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(false),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_game')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mode')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('players_count')
                    ->label('Players')
                    ->getStateUsing(fn ($record) => $record->players()->count()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modal(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [

        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameSessions::route('/'),
            'create' => Pages\CreateGameSession::route('/create'),
            'edit' => Pages\EditGameSession::route('/{record}/edit'),
        ];
    }
}
