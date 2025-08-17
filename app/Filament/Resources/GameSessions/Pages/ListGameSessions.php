<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListGameSessions extends ListRecords
{
    protected static string $resource = GameSessionResource::class;
}
