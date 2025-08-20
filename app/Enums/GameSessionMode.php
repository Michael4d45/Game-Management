<?php

declare(strict_types=1);

namespace App\Enums;

enum GameSessionMode: string
{
    case SyncList = 'sync_list';
    case SaveSwap = 'save_swap';

    public function label(): string
    {
        return match ($this) {
            self::SyncList => 'Sync List',
            self::SaveSwap => 'Save Swap',
        };
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return [
            self::SyncList->value => self::SyncList->label(),
            self::SaveSwap->value => self::SaveSwap->label(),
        ];
    }
}
