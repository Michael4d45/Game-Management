<?php

declare(strict_types=1);

namespace App\Services;

use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

class PositionService
{
    protected string|null $timezone = null;

    protected Position|null $position = null;

    public function detect(string|null $ip = null): void
    {
        $ip = $ip ?? request()->ip();

        $location = Location::get($ip);

        if ($location instanceof Position) {
            $this->position = $location;

            $this->timezone = $location->timezone;
        }
    }

    public function timezone(): string
    {
        return $this->timezone ?? config()->string('app.display_timezone');
    }

    public function position(): Position|null
    {
        return $this->position;
    }
}
