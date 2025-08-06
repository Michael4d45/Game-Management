<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Pages;

use App\Filament\Resources\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
