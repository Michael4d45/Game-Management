<?php

declare(strict_types=1);

namespace App\Filament\Resources\Games;

use Override;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GameResource extends Resource
{
    protected static string|null $model = Game::class;

    // protected static string|null $navigationIcon = 'heroicon-o-collection';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('file')
                    ->label('Game File')
                    ->options(function () {
                        $path = config()->string('game.files_path');

                        // Get all files in the folder
                        $allFiles = collect(File::files($path))
                            ->map(fn ($file) => $file->getFilename());

                        // Get files already used in DB
                        /** @var Collection<array-key,string> $usedFiles */
                        $usedFiles = Game::pluck('file');

                        // Filter out used files
                        $availableFiles = $allFiles->diff($usedFiles);

                        // Return as key-value array for dropdown
                        return $availableFiles->mapWithKeys(fn ($f) => [$f => $f])->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('file')->searchable(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }
}
