<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'is_active',
        'start_at',
        'mode',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SessionPlayer,$this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(SessionPlayer::class);
    }

    /**
     * @return HasMany<GameSwap,$this>
     */
    public function swaps(): HasMany
    {
        return $this->hasMany(GameSwap::class);
    }
}
