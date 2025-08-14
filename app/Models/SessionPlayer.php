<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SessionPlayer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'game_session_id',
        'player_name',
        'player_id',
        'ping',
        'is_ready',
        'is_connected',
        'current_game',
        'last_seen',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'is_connected' => 'boolean',
        'last_seen' => 'datetime',
    ];

    /**
     * @return BelongsTo<GameSession,$this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
