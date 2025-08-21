<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameSessionMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $start_at
 * @property int $current_round
 * @property string|null $status
 * @property int $swap_interval
 * @property GameSessionMode $mode
 * @property int $user_id
 * @property string $name
 * @property int $id
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SessionPlayer> $players
 * @property-read int|null $players_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameSwap> $swaps
 * @property-read int|null $swaps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Game> $games
 * @property-read int|null $games_count
 * @property-read Collection<string,string> $game_files
 */
class GameSession extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'start_at',
        'mode',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'mode' => GameSessionMode::class,
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

    /**
     * @return BelongsToMany<Game,$this>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'session_games');
    }

    /**
     * @return Collection<array-key,string>
     */
    public function getGameFilesAttribute(): Collection
    {
        // @phpstan-ignore return.type
        return $this->games->pluck('file', 'file');
    }

    public function chooseStartGameFor(SessionPlayer $player): Game|null
    {
        logger('Choosing start game for player', [
            'player' => $player->name,
            'mode' => $this->mode,
        ]);

        switch ($this->mode) {
            case GameSessionMode::SyncList:
                /** @var Collection<array-key,string> $games */
                $games = $this->players()->pluck('game_file')->unique()->filter();

                logger('SyncList mode: collected games', [
                    'games' => $games->values()->all(),
                ]);

                if ($games->isNotEmpty()) {
                    $firstGame = $games->first();
                    logger('SyncList mode: choosing first game', [
                        'chosen_game' => $firstGame,
                    ]);

                    return Game::fromFile($firstGame);
                } else {
                    $game = $this->games->random();
                    logger('SyncList mode: no games found, choosing random: ' . $game->file);

                    return $game;
                }

            case GameSessionMode::SaveSwap:
                $excludedGames = $this->players()
                    ->whereNot('name', $player->name)
                    ->pluck('game_file')
                    ->unique()
                    ->filter();

                logger('SaveSwap mode: excluding games', [
                    'excluded' => $excludedGames->values()->all(),
                ]);

                $games = $this->games()
                    ->whereNotIn('file', $excludedGames)
                    ->get();

                logger('SaveSwap mode: available games', [
                    'games' => $games->pluck('file')->all(),
                ]);

                if ($games->isNotEmpty()) {
                    $chosen = $games->random();
                    logger('SaveSwap mode: randomly chosen game', [
                        'chosen_game' => $chosen->file,
                    ]);

                    return $chosen;
                }

                logger('SaveSwap mode: no available games');
                break;
        }

        logger('No game chosen, returning null', [
            'player' => $player->name,
            'mode' => $this->mode,
        ]);

        return null;
    }
}
