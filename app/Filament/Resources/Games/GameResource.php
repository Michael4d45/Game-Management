<?php

declare(strict_types=1);

namespace App\Filament\Resources\Games;

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Models\Game;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GameResource extends Resource
{
    protected static string|null $model = Game::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-puzzle-piece';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        $availableFiles = static::getAvailableGameFiles();

        return $schema
            ->components([
                Select::make('file')
                    ->label('Game File')
                    ->options($availableFiles)
                    ->unique()
                    ->hiddenOn('edit')
                    ->searchable()
                    ->required()
                    ->preload(),
                Select::make('extra_file')
                    ->label('Extra File')
                    ->options($availableFiles)
                    ->searchable()
                    ->preload(),
            ]);
    }

    /**
     * Get available game files.
     *
     * @return array<array-key,string>
     */
    protected static function getAvailableGameFiles(): array
    {
        $path = config()->string('game.files_path');

        $allFiles = collect(File::files($path))
            ->map(fn ($file) => $file->getFilename());

        /** @var Collection<array-key, string> $usedFiles */
        $usedFiles = Game::pluck('file');

        /** @var Collection<array-key, string> $usedExtraFiles */
        $usedExtraFiles = Game::pluck('extra_file');

        $availableFiles = $allFiles
            ->diff($usedFiles)
            ->diff($usedExtraFiles);

        // @phpstan-ignore return.type
        return $availableFiles->mapWithKeys(fn ($f) => [$f => $f])->toArray();
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('file')->searchable(),
                TextColumn::make('extra_file')->searchable(),
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

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }
}
