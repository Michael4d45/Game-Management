<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSwap extends Model
{
    protected $fillable = [
        'game_session_id',
        'player_id',
        'game_name',
        'swap_at',
        'executed_at',
    ];

    protected $casts = [
        'swap_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<GameSession,$this>
     */
    public function session()
    {
        return $this->belongsTo(GameSession::class);
    }
}
