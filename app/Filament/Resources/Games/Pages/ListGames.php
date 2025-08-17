<?php

declare(strict_types=1);

namespace App\Filament\Resources\Games\Pages;

use Override;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Games\GameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
