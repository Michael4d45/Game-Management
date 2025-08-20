<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SessionPlayer;
use Illuminate\Console\Command;

class CheckPlayerConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:check-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks player connections and marks them as disconnected if last_seen is older than 15 seconds.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get players who are currently marked as connected
        // and whose last_seen timestamp is older than 15 seconds.
        $disconnectedPlayers = SessionPlayer::where('is_connected', true)
            ->where('last_seen', '<', now()->subSeconds(15))
            ->get();

        if ($disconnectedPlayers->isEmpty()) {
            $this->info('No players to disconnect.');

            return;
        }

        $this->info(
            sprintf(
                'Found %d players to disconnect.',
                $disconnectedPlayers->count(),
            ),
        );

        foreach ($disconnectedPlayers as $player) {
            $player->update(['is_connected' => false]);
            $this->comment(
                sprintf('Disconnected player: %s', $player->name),
            );
        }

        $this->info('Player connection check complete.');
    }
}
