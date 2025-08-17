<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GameResource extends Resource
{
    protected static string|null $model = Game::class;

    // protected static string|null $navigationIcon = 'heroicon-o-collection';

    #[\Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('file')
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

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('file')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
