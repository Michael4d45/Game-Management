<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Game extends Model
{
    protected $primaryKey = 'file';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['file', 'extra_file'];

    /**
     * @return BelongsToMany<GameSession,$this>
     */
    public function gameSessions(): BelongsToMany
    {
        return $this->belongsToMany(GameSession::class, 'session_games');
    }

    public static function fromFile(string $file): self
    {
        return self::where('file', $file)->firstOrFail();
    }
}
