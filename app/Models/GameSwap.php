<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $executed_at
 * @property Carbon $swap_at
 * @property int $round_number
 * @property string $initiated_by
 * @property string|null $save_state_path
 * @property string $game_file
 * @property string $session_player_name
 * @property int $game_session_id
 * @property int $id
 * @property-read GameSession $session
 */
class GameSwap extends Model
{
    protected $fillable = [
        'game_session_id',
        'session_player_name',
        'game_file',
        'save_state_path',
        'initiated_by',
        'round_number',
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
