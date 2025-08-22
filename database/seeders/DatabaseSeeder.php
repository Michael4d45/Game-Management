<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $filePath = config()->string('game.files_path');
        if (! is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }
        $existingFiles = array_diff(scandir($filePath), ['.', '..']);
        $fileCount = count($existingFiles);

        if ($fileCount < 10) {
            foreach (range(1, 10 - $fileCount) as $_) {
                $filename = fake()->word . '.txt';
                file_put_contents($filePath . '/' . $filename, '');
            }
        }
        foreach ($existingFiles as $filename) {
            // Special handling for .bin files: skip them
            if (str_ends_with($filename, '.bin')) {
                continue;
            }

            // Special handling for .cue files: attach matching .bin if it exists
            if (str_ends_with($filename, '.cue')) {
                $binFile = preg_replace('/\.cue$/i', '.bin', $filename);

                Game::firstOrCreate(
                    ['file' => $filename],
                    ['extra_file' => in_array($binFile, $existingFiles) ? $binFile : null]
                );

                continue;
            }

            // Default case: just create the record with no extra_file
            Game::firstOrCreate(
                ['file' => $filename],
                ['extra_file' => null]
            );
        }
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $session = GameSession::create([
            'user_id' => $user->id,
            'name' => 'test',
            'mode' => 'sync_list',
            'state' => 'stopped',
            'state_at' => now(),
        ]);

        $session->games()->attach(
            fake()->randomElements(Game::all(), 5)
        );
    }
}
