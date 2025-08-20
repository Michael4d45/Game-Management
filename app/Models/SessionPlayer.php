<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 * @property mixed $swap_offset
 * @property string|null $timezone
 * @property Carbon|null $last_seen
 * @property int|null $last_game_id
 * @property string|null $game_file
 * @property bool $is_connected
 * @property bool $is_ready
 * @property int|null $ping
 * @property string|null $client_version
 * @property int|null $game_session_id
 * @property string $name
 * @property-read GameSession|null $gameSession
 */
class SessionPlayer extends Authenticatable
{
    use HasApiTokens;

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'game_session_id',
        'name',
        'ping',
        'is_ready',
        'is_connected',
        'game_file',
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
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
