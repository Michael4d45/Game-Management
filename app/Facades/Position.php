<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Position extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'position';
    }
}
