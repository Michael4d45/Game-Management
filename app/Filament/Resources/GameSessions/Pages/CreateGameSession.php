<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Pages;

use Override;
use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
